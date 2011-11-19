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

/**
 * EmbeddedModel base class
 *
 * @package mongo_model
 */
abstract class EmbeddedModel extends Base {

	protected static $_embeddedIn;

	public static function embedded() {
		return true;
	}
}
