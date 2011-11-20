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
abstract class Base extends \rox\ActiveModel {

	protected static $_schema = array();

	protected static $_embedsMany = array();

	protected static $_references = array();

	protected $_embedded = array();

	protected $_referenced = array();

	public static function embedded() {
		return false;
	}

	public function setReference($name, Base $object = null) {
		if (!in_array($name, static::$_references)) {
			throw new Exception("Document doesn't reference {$name}");
		}

		$this->_referenced[$name] = $object;

		if ($object === null) {
			$this->_data[$name] = null;
		} else {
			$class = get_class($object);
			$this->_data[$name] = \MongoDBRef::create($class::collection()->getName(), $object->mongoId());
		}

		$this->_flagAttributeAsModified($name);
	}

	public function getReference($name) {
		if (array_key_exists($name, $this->_referenced)) {
			return $this->_referenced[$name];
		}

		if (empty($this->_data[$name])) {
			return null;
		}

		$class = Inflector::classify($name);
		$this->_referenced[$name] = $class::find($this->_data[$name]['$id']);
		return $this->_referenced[$name];
	}

	public function __call($method, $args) {
		if (strpos($method, 'set') === 0) {
			$key = Inflector::underscore(substr($method, 3));
			return $this->setReference($key, $args[0]);
		}

		$key = Inflector::underscore($method);
		if (in_array($key, static::$_references)) {			
			return $this->getReference($key);
		}

		throw new Exception('Undefined method ' . get_called_class() . '::' . $method . '()');
	}

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

			$type = isset(static::$_schema[$attribute]) ? static::$_schema[$attribute] : 'string';
			$this->_data[$attribute] = $value;
			settype($this->_data[$attribute], $type);
			$this->_flagAttributeAsModified($attribute);
		}
	}

	public function __get($attribute) {
		if (array_key_exists($attribute, $this->_data)) {
			return $this->_data[$attribute];
		}

		if (array_key_exists($attribute, static::$_schema)) {
			return null;
		}

		if (array_key_exists($attribute, $this->_embedded)) {
			return $this->_embedded[$attribute];
		}

		if (in_array($attribute, static::$_embedsMany)) {
			$this->_embedded[$attribute] = new Many($this);
			return $this->_embedded[$attribute];
		}

		throw new Exception("unknown attribute {$attribute}");
	}

	/**
	 * Intantiates model from MongoDB data
	 *
	 * @param array $data 
	 * @return object
	 */
	public static function fromMongoData($data) {
		$class = get_called_class();

		$instance = new $class;

		foreach (static::$_embedsMany as $embedded) {
			if (isset($data[$embedded])) {
				$instance->_embedded[$embedded] = Many::fromMongoData($instance, $embedded, $data[$embedded]);
				unset($data[$embedded]);
			}
		}

		$instance->_data = $data;
		$instance->_data['id'] = (string)$data['_id'];
		$instance->_newRecord = false;

		return $instance;
	}

	/**
	 * Returns the list of modified attributes
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
}
