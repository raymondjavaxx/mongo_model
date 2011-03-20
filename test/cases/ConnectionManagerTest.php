<?php

namespace mongo_model\test\cases;

require_once dirname(__DIR__) . '/helper.php';

use \mongo_model\ConnectionManager;

class ConnectionManagerTest extends \PHPUnit_Framework_TestCase {

	protected function setUp() {
		ConnectionManager::setConfig('default', array('db' => 'mongo_model_test'));
	}

	public function testGetDatasource() {
		$datasource = ConnectionManager::getDataSource();
		$this->assertTrue(is_object($datasource));
		$this->assertTrue($datasource instanceof \MongoDB);
	}
}
