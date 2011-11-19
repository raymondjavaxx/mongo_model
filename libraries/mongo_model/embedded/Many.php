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

namespace mongo_model\embedded;

use \mongo_model\Base;
use \mongo_model\EmbeddedModel;
use \rox\Inflector;

/**
 * Collection of embedded documents
 *
 * @package mongo_model
 */
class Many implements \ArrayAccess, \Countable {

	/**
	 * Collection owner
	 *
	 * @var \mongo_model\Base
	 */
	protected $_parent;

	/**
	 * Array of embedded documents
	 *
	 * @var array
	 */
	protected $_objects = array();

	/**
	 * Constructor
	 *
	 * @param Base $parent  document who owns the collection
	 * @param string $objects  embedded documents
	 */
	public function __construct(Base $parent, $objects = array()) {
		$this->_parent = $parent;
		$this->_objects = $objects;
	}

	/**
	 * Creates a new \mongo_model\embedded\Many collection from MogoDB document data
	 *
	 * @param Base $parent  document who owns the collection
	 * @param string $name  name/class of embedded documents
	 * @param array $data  MongoDB data
	 * @return Many
	 */
	public static function fromMongoData(Base $parent, $name, $data) {
		$class = Inflector::classify($name);

		$objects = array_map(function($objectData) use ($class) {
			return $class::fromMongoData($objectData);
		}, $data);

		return new Many($parent, $objects);
	}

	/**
	 * Converts collection into an associative array for saving into MongoDB. If any
	 * of the documents doesn't have an _id it would create one for it.
	 *
	 * @return array
	 */
	public function serializeForSaving() {
		$data = array();

		foreach ($this->_objects as $object) {
			$objectData = $object->getData();
			if (empty($objectData['_id'])) {
				$objectData['_id'] = new \MongoId();
			}

			$data[] = $objectData;
		}

		return $data;
	}

	/**
	 * Returns the number of documents in collection
	 *
	 * @return integer
	 * @see \Countable
	 */
	public function count() {
		return count($this->_objects);
	}

	public function offsetExists($offset) {
		return isset($this->_objects[$offset]);
	}

	public function offsetGet($offset) {
		return isset($this->_objects[$offset]) ? $this->_objects[$offset] : null;
	}

	public function offsetSet($offset, $value) {
		$validValue = is_object($value) && ($value instanceof EmbeddedModel);
		if (!$validValue) {
			throw new Exception("Value is not a \mongo_model\EmbeddedModel");
		}

		if ($offset == null) {
			$this->_objects[] = $value;
		} else {
			$this->_objects[$offset] = $value;
		}
	}

	public function offsetUnset($offset) {
		unset($this->_objects[$offset]);
	}
}
