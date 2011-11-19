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
use \rox\active_record\PaginationResult;
use \mongo_model\embedded\Many;

/**
 * Model base class
 *
 * @package mongo_model
 */
abstract class Model extends Base {

	/**
	 * Document schema. Must be re-declared by subclasses. Schema should be defined as
	 * pairs of key-value where the key is the name of attribute and the value is the type.
	 *
	 * {{{
	 *    class Post extends \mongo_model\Model {
	 *        protected static $_schema = array(
	 *            'title'      => 'string',
	 *            'body'       => 'string',
	 *            'published'  => 'boolean',
	 *            'created_at' => 'integer',
	 *            'updated_at' => 'integer'
	 *        );
	 *    }
	 * }}}
	 *
	 * @var array
	 */
	protected static $_schema = array();

	/**
	 * \MongoCollection of model
	 *
	 * @var \MongoCollection
	 */
	protected static $_collection;

	/**
	 * Handles Model::findBy* Model::findAll* magic
	 *
	 * @param string $method 
	 * @param string $args 
	 * @return mixed
	 * @throws \mongo_model\Exception
	 */
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
			$collection = $db->selectCollection($collectionName);
			static::$_collection = &$collection;
		}

		return static::$_collection;
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

		foreach ($this->_embedded as $name => $collection) {
			$attributes[$name] = $collection->serializeForSaving();
		}

		if ($this->_newRecord) {
			$this->collection()->insert($attributes);
			$this->setId((string)$attributes['_id']);
			$this->_resetModifiedAttributesFlags();
			$this->_afterSave(true);
			$this->_newRecord = false;
		} else {
			$criteria = array('_id' => $this->mongoId());
			$this->collection()->update($criteria, array('$set' => $attributes));
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

		return static::fromMongoData($result);;
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
	 * Counts the number of documents a query returns
	 *
	 * @param array $conditions 
	 * @return integer
	 */
	public static function findCount($conditions = array()) {
		return static::collection()->find($conditions)->count();
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
			'limit' => false,
			'skip' => false
		);

		$options += $defaults;

		$cursor = static::collection()->find($options['conditions'], $options['fields']);
		if (!empty($options['order'])) {
			$cursor->sort($options['order']);
		}
		
		if ($options['limit'] !== false) {
			$cursor->limit($options['limit']);
		}

		if ($options['skip'] !== false) {
			$cursor->skip($options['skip']);
		}

		$records = array();

		foreach ($cursor as $result) {
			$records[] = static::fromMongoData($result);;
		}

		return $records;
	}

	/**
	 * Paginates over documents
	 *
	 * @param array $options
	 * @return \rox\actite_record\PaginationResult
	 */
	public static function paginate($options = array()) {
		$defaultOptions = array(
			'per_page'   => 10,
			'page'       => 1,
			'conditions' => array(),
			'order'      => null,
			'attributes' => null,
			'group'      => null
		);

		$options = array_merge($defaultOptions, $options);

		$pages = 1;
		$currentPage = 1;
		$items = array();

		$total = static::findCount($options['conditions']);
		if ($total > 0) {
			$pages = (integer)ceil($total / $options['per_page']);
			$currentPage = min(max(intval($options['page']), 1), $pages);
			$skip = ($currentPage - 1) * $options['per_page'];

			$items = static::findAll(array(
				'conditions' => $options['conditions'],
				'attributes' => $options['attributes'],
				'order'      => $options['order'],
				'skip'       => $skip,
				'limit'      => $options['per_page'],
				'group'      => $options['group']
			));
		}

		$nextPage = min($pages, $currentPage + 1);
		$previousPage = max(1, $currentPage - 1);

		$result = new PaginationResult($items, $pages, $currentPage,
			$nextPage, $previousPage, $total);
		return $result;
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
