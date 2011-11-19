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

	public function testUpdate() {
		$post = new MockPost(array('title' => 'Hello', 'body' => 'Hello World!', 'author' => 'James'));
		$post->save();

		// load post and update attribute
		$existingPost = MockPost::find($post->id);
		$existingPost->author = 'Carl';
		$existingPost->save();

		// reload post
		$existingPost = MockPost::find($post->id);

		$this->assertArrayHasKey('title', $existingPost->getData());
		$this->assertArrayHasKey('body', $existingPost->getData());
		$this->assertArrayHasKey('author', $existingPost->getData());
		$this->assertSame('Carl', $existingPost->author);
	}

	public function testDefaultValues() {
		$post = new MockPost(array('title' => 'Hello'));
		$this->assertNull($post->body);
	}

	public function testInvalidAttribute() {
		$post = MockPost::create(array('title' => 'Hello 1', 'body' => 'Hello World 1!', 'author' => 'James'));

		$this->setExpectedException('\mongo_model\Exception');

		// try to access an attribute that is not defined in the schema
		$revision = $post->revision;
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

	public function testPaginate() {
		MockPost::create(array('title' => 'Hello 1', 'body' => 'Hello World 1!', 'author' => 'James'));
		MockPost::create(array('title' => 'Hello 2', 'body' => 'Hello World 2!', 'author' => 'James'));
		MockPost::create(array('title' => 'Hello 3', 'body' => 'Hello World 3!', 'author' => 'James'));
		MockPost::create(array('title' => 'Hello 4', 'body' => 'Hello World 4!', 'author' => 'James'));

		$results = MockPost::paginate(array('page' => 1, 'per_page' => 2));

		$this->assertInstanceOf('\rox\active_record\PaginationResult', $results);
		$this->assertEquals(2, count($results));
		$this->assertSame('Hello 1', $results[0]->title);
		$this->assertSame('Hello 2', $results[1]->title);

		$results = MockPost::paginate(array('page' => 2, 'per_page' => 2));

		$this->assertEquals(2, count($results));
		$this->assertSame('Hello 3', $results[0]->title);
		$this->assertSame('Hello 4', $results[1]->title);
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
		$this->assertInstanceOf('\MockPost', $post);
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

	public function testEmbedded() {
		$this->assertFalse(MockPost::embedded());
		$this->assertTrue(MockComment::embedded());
	}

	public function testEmbeddedCollectionInstance() {
		$post = MockPost::create(array('title' => 'Hello', 'body' => 'Hello World!', 'author' => 'James'));
		$this->assertInstanceOf('\mongo_model\embedded\Many', $post->mock_comments);
	}

	public function testAppendingInvalidValueToEmbeddedCollection() {
		$post = MockPost::create(array('title' => 'Hello', 'body' => 'Hello World!', 'author' => 'James'));

		$this->setExpectedException('\mongo_model\embedded\Exception');
		$post->mock_comments[] = "Hello";

		$this->setExpectedException('\mongo_model\embedded\Exception');
		$post->mock_comments[] = new \stdClass();
	}

	public function testAppendingToEmbeddedCollection() {
		$post = MockPost::create(array('title' => 'Hello', 'body' => 'Hello World!', 'author' => 'James'));
		$this->assertEquals(0, count($post->mock_comments));
		$post->mock_comments[] = new MockComment(array('author' => 'Jane', 'body' => 'hello'));
		$post->mock_comments[] = new MockComment(array('author' => 'Carl', 'body' => 'hello'));
		$this->assertEquals(2, count($post->mock_comments));
	}

	public function testSavingAndRetrievingWithEmbeddedObjects() {
		$original = new MockPost(array('title' => 'Hello', 'body' => 'Hello World!', 'author' => 'James'));
		$original->mock_comments[] = new MockComment(array('author' => 'Jane', 'body' => 'hello'));
		$original->mock_comments[] = new MockComment(array('author' => 'Carl', 'body' => 'hello'));
		$this->assertTrue($original->save());

		$saved = MockPost::find($original->id);
		$this->assertEquals(2, count($saved->mock_comments));
	}
}
