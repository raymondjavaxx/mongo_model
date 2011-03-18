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

use \Mongo;

class ConnectionManager {

	/**
	 * MongoDB instances
	 *
	 * @var array
	 */
	protected static $_databases = array();

	/**
	 * Configurations
	 *
	 * @var array
	 */
	protected static $_configs = array(
		'default' => array(
			'database' => 'db',
			'server' => 'mongodb://127.0.0.1:27017'
		)
	);

	public static function getDataSource($name = 'default') {
		if (!isset(self::$_databases[$name])) {
			self::_instantiateDataSource($name);
		}

		return self::$_databases[$name];
	}

	public static function setConfig($name, $config = array()) {
		$defaults = array('database' => 'db','server' => 'mongodb://127.0.0.1:27017');
		self::$_configs[$name] = ($config + $defaults);
	}

	protected static function _instantiateDataSource($name) {
		if (!isset(self::$_configs[$name])) {
			throw new Exception('Configuration entry not found for ' . $name);
		}

		$connection = new Mongo(self::$_configs[$name]['server']);
		self::$_databases[$name] = $connection->selectDB(self::$_configs[$name]['database']);
	}
}
