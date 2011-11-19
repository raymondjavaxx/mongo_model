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

class MockComment extends \mongo_model\EmbeddedModel {

	protected static $_embeddedIn = 'mock_post';

	protected static $_schema = array(
		'author' => 'string',
		'body' => 'string',
	);
	
	protected function _validate() {
		// validation code
	}
}
