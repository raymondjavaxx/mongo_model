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
use \mongo_model\embedded\Many;

class ManyTest extends \PHPUnit_Framework_TestCase {

	public function testFromMongoData() {
		$data = array(
			array('_id' => new \MongoId('4e74f5554cfb452b6e000000'), 'author' => 'James', 'message' => 'Hello'),
			array('_id' => new \MongoId('4e74f5554cfb452b6e000000'), 'author' => 'Armaan', 'message' => 'Hello')
		);

		$result = Many::fromMongoData(new MockPost, 'mock_comments', $data);

		$this->assertInstanceOf('\mongo_model\embedded\Many', $result);
		$this->assertEquals(2, count($result));
		$this->assertInstanceOf('\MockComment', $result[0]);
		$this->assertInstanceOf('\MockComment', $result[1]);
	}
}
