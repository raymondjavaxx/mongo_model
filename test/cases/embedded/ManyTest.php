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
			array('_id' => new \MongoId('4e74f5554cfb452b6e000000'), 'author' => 'James', 'body' => 'Hello'),
			array('_id' => new \MongoId('4e74f5554cfb452b6e000000'), 'author' => 'Armaan', 'body' => 'Hello')
		);

		$result = Many::fromMongoData(new MockPost, 'mock_comments', $data);

		$this->assertInstanceOf('\mongo_model\embedded\Many', $result);
		$this->assertEquals(2, count($result));
		$this->assertInstanceOf('\MockComment', $result[0]);
		$this->assertInstanceOf('\MockComment', $result[1]);
	}

	public function testSerializeForSaving() {
		$data = array(
			array('_id' => new \MongoId('4e74f5554cfb452b6e000000'), 'author' => 'James', 'body' => 'Hello'),
			array('_id' => new \MongoId('4e74f5554cfb452b6e000000'), 'author' => 'Armaan', 'body' => 'Hello')
		);

		$collection = Many::fromMongoData(new MockPost, 'mock_comments', $data);
		$result = $collection->serializeForSaving();

		$this->assertEquals($data, $result);
	}

	public function testSerializeForSavingWithoutIds() {
		$collection = new Many(new MockPost, array());
		$collection[] = new MockComment(array('author' => 'James', 'body' => 'Hello'));

		$result = $collection->serializeForSaving();
		$this->assertInstanceOf('\MongoId', $result[0]['_id']);
	}
}
