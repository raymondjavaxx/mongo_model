<?php
/**
 * RoxPHP
 *
 * Copyright (C) 2011 Ramon Torres
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright (C) 2011 Ramon Torres
 * @package mongo_model
 * @license The MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

namespace mongo_model;

use \rox\Inflector;

/**
 * Model base class
 *
 * @package mongo_model
 */
abstract class Model extends \rox\ActiveModel {

	protected $_collection;

	protected $_fieldMap = array();

	public function setData($attribute, $value = null) {
		if (is_array($attribute)) {
			foreach ($attribute as $k => $v) {
				if (!in_array($k, $this->_protectedAttributes)) {
					$this->setData($k, $v);
				}
			}
		} else {
			if (!array_key_exists($attribute, $this->_fieldMap)) {
				throw new Exception('unknown attribute ' . $attribute);
			}

			$this->_data[$attribute] = $value;
			settype($this->_data[$attribute], $this->_fieldMap[$attribute]);
			$this->_flagAttributeAsModified($attribute);
		}
	}

	public function _fromMongoData($data) {
		$this->setId($data['_id']);
		unset($data['_id']);
		$this->setData($data);
		$this->_resetModifiedAttributesFlags();
		$this->_newRecord = false;
	}

	public function collection() {
		if ($this->_collection === null) {
			$collectionName = Inflector::tableize(get_class($this));
			$db = ConnectionManager::getDataSource($this->_dataSourceName);
			$this->_collection = $db->selectCollection($collectionName);
		}

		return $this->_collection;
	}

	public function modifiedAttributes() {
		$data = array();
		foreach ($this->_modifiedAttributes as $attribute) {
			$data[$attribute] = $this->_data[$attribute];
		}

		return $data;
	}

	public function mongoId() {
		return new \MongoId($this->getId());
	}

	public function save() {
		if (!$this->valid()) {
			return false;
		}

		$this->_beforeSave();

		$attributes = $this->modifiedAttributes();
		if (empty($attributes)) {
			return false;
		}

		if ($this->_newRecord) {
			$this->collection()->insert($attributes);
			$this->setId((string)$attributes['_id']);
			$this->_resetModifiedAttributesFlags();
			$this->_afterSave(true);
			$this->_newRecord = false;
		} else {
			$criteria = array('_id' => $this->mongoId());
			$this->collection()->update($criteria, $attributes);
			$this->_resetModifiedAttributesFlags();
			$this->_afterSave(false);
		}

		return true;
	}

	public function find($id) {
		$data = $this->collection()->findOne(array('_id' => new \MongoId($id)));
		if (empty($data)) {
			throw new Exception("Couldn't find record with ID = {$id}");
		}

		$class = get_class($this);

		$instance = new $class;
		$instance->_fromMongoData($data);
		return $instance;
	}

	public function findAll($criteria = array()) {
		$records = array();
		$class = get_class($this);

		$results = $this->collection()->find($criteria);
		foreach ($results as $result) {
			$instance = new $class;
			$instance->_fromMongoData($result);
			$records[] = $instance;
		}

		return $records;
	}

	public function delete() {
		if ($this->_newRecord) {
			throw new Exception("You can't delete new records");
		}

		$this->_beforeDelete();

		$criteria = array('_id' => $this->mongoId());
		$this->collection()->remove($criteria, array('justOne' => true, 'safe' => true));

		$this->_afterDelete();
	}
}
