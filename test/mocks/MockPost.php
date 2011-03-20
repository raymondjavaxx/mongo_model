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

namespace mongo_model\test\mocks;

class MockPost extends \mongo_model\Model {

	protected static $_dataSourceName = 'mongo_model_test';

    protected static $_fieldMap = array(
        'title' => 'string',
        'body' => 'string',
        'author' => 'string'
    );

    protected function _validate() {
        // validation code
    }
}
