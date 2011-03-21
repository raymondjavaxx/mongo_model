<?php
/**
 * MongoModel
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

	protected static $_collection;

	protected static $_schema = array();

	public function setData($attribute, $value = null) {
		if (is_array($attribute)) {
			foreach ($attribute as $k => $v) {
				if (!in_array($k, static::$_protectedAttributes)) {
					$this->setData($k, $v);
				}
			}
		} else {
			if (!array_key_exists($attribute, static::$_schema) && $attribute != 'id') {
				throw new Exception('unknown attribute ' . $attribute);
			}

			$type = isset(static::$_schema[$attribute])
				? static::$_schema[$attribute] : 'string';

			$this->_data[$attribute] = $value;
			settype($this->_data[$attribute], $type);
			$this->_flagAttributeAsModified($attribute);
		}
	}

	public static function _fromMongoData($data) {
		$class = get_called_class();
		$instance = new $class;
		$instance->setId($data['_id']);
		unset($data['_id']);
		$instance->setData($data);
		$instance->_resetModifiedAttributesFlags();
		$instance->_newRecord = false;
		return $instance;
	}

	public static function __callStatic($method, $args) {
		if (strpos($method, 'findBy') === 0) {
			$key = Inflector::underscore(substr($method, 6));
			return static::findFirst(array('conditions' => array($key => $args[0])));
		}

		if (strpos($method, 'findAllBy') === 0) {
			$key = Inflector::underscore(substr($method, 9));
			return static::findAll(array('conditions' => array($key => $args[0])));
		}

		throw new Exception('Undefined method ' . get_called_class() . '::' . $method . '()');
	}

	/**
	 * Returns the \MongoCollection for the model
	 *
	 * @return \MongoCollection
	 */
	public static function collection() {
		if (static::$_collection === null) {
			$collectionName = Inflector::tableize(get_called_class());
			$db = ConnectionManager::getDataSource(static::$_dataSourceName);
			static::$_collection = $db->selectCollection($collectionName);
		}

		return static::$_collection;
	}

	/**
	 * Returns the modified attributes
	 *
	 * @return array
	 */
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

	/**
	 * Saves the document
	 *
	 * @return boolean
	 */
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

	/**
	 * Finds a document by id
	 *
	 * @param string $id 
	 * @return \mongo_model\Model
	 */
	public static function find($id) {
		$result = static::collection()->findOne(array('_id' => new \MongoId($id)));
		if (empty($result)) {
			throw new Exception("Couldn't find record with ID = {$id}");
		}

		return static::_fromMongoData($result);;
	}

	/**
	 * Finds the first document that matches the conditions
	 *
	 * @param array $options 
	 * @return \mongo_model\Model
	 */
	public static function findFirst($options = array()) {
		$options += array('limit' => 1);
		$results = static::findAll($options);
		return reset($results);
	}

	/**
	 * Queries the collection
	 *
	 * @param array $options 
	 * @return array of \mongo_model\Model
	 */
	public static function findAll($options = array()) {
		$defaults = array(
			'conditions' => array(),
			'fields' => array(),
			'order' => array(),
			'limit' => false
		);

		$options += $defaults;

		$records = array();

		$cursor = static::collection()->find($options['conditions'], $options['fields']);
		if (!empty($options['order'])) {
			$cursor->sort($options['order']);
		}

		if ($options['limit'] !== false) {
			$cursor->limit($options['limit']);
		}

		foreach ($cursor as $result) {
			$records[] = static::_fromMongoData($result);;
		}

		return $records;
	}

	/**
	 * Deletes the document from collection
	 *
	 * @return void
	 * @throws \mongo_model\Exception
	 */
	public function delete() {
		if ($this->_newRecord) {
			throw new Exception("Can't delete new record");
		}

		$this->_beforeDelete();

		$criteria = array('_id' => $this->mongoId());
		static::collection()->remove($criteria, array('justOne' => true, 'safe' => true));

		$this->_afterDelete();
	}
}
