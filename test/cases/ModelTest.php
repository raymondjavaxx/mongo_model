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

namespace mongo_model\test\cases;

require_once dirname(__DIR__) . '/helper.php';
require_once dirname(__DIR__) . '/mocks/MockPost.php';

use \mongo_model\ConnectionManager;
use \mongo_model\test\mocks\MockPost;

class ModelTest extends \PHPUnit_Framework_TestCase {

	protected function setUp() {
		ConnectionManager::setConfig('mongo_model_test', array('database' => 'mongo_model_test'));
		MockPost::collection()->drop();
	}

	public function testSave() {
		$post = new MockPost(array(
			'title' => 'Hello',
			'body' => 'Hello World!',
			'author' => 'James'
		));
		
		$this->assertTrue($post->save());
	}

	public function testFind() {
		$post = MockPost::create(array(
			'title' => 'Hello 1',
			'body' => 'Hello World 1!',
			'author' => 'James'
		));

		$result = MockPost::find($post->id);
		$this->assertTrue(is_object($post));
		$this->assertSame($result->id, $post->id);
	}

	public function testFindFirst() {
		MockPost::create(array('title' => 'Hello 1', 'body' => 'Hello World 1!', 'author' => 'James'));
		MockPost::create(array('title' => 'Hello 2', 'body' => 'Hello World 2!', 'author' => 'James'));
		$result = MockPost::findFirst(array('conditions' => array('author' => 'James')));
		$this->assertTrue(is_object($result));
		$this->assertSame('Hello 1', $result->title);
	}

	public function testFindWithNonExistingId() {
		$this->setExpectedException('\mongo_model\Exception');
		$post = MockPost::find('4d85b2ec5300dac40e000000');
	}

	public function testFindBy() {
		MockPost::create(array(
			'title' => 'Hello',
			'body' => 'Hello World!',
			'author' => 'James'
		));

		$post = MockPost::findByAuthor('James');
		$this->assertTrue(is_object($post));
		$this->assertTrue($post instanceof \mongo_model\test\mocks\MockPost);
	}

	public function testFindAllBy() {
		MockPost::create(array('title' => 'Hello 1', 'body' => 'Hello World 1!', 'author' => 'James'));
		MockPost::create(array('title' => 'Hello 2', 'body' => 'Hello World 2!', 'author' => 'Robert'));
		MockPost::create(array('title' => 'Hello 3', 'body' => 'Hello World 3!', 'author' => 'Carl'));
		MockPost::create(array('title' => 'Hello 4', 'body' => 'Hello World 4!', 'author' => 'James'));

		$results = MockPost::findAllByAuthor('James');
		$this->assertTrue(is_array($results));
		$this->assertEquals(2, count($results));
	}
}
