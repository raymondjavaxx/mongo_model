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

use \mongo_model\ConnectionManager;

class ConnectionManagerTest extends \PHPUnit_Framework_TestCase {

	protected function setUp() {
		ConnectionManager::setConfig('mongo_model_test', array('database' => 'mongo_model_test'));
	}

	public function testGetDatasource() {
		$datasource = ConnectionManager::getDataSource('mongo_model_test');
		$this->assertTrue(is_object($datasource));
		$this->assertTrue($datasource instanceof \MongoDB);
	}
}
