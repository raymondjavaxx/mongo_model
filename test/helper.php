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

error_reporting(E_ALL | E_STRICT);

define('LIBRARIES_PATH', dirname(dirname(dirname(__DIR__))) . '/libraries/');
define('APP_LIBRARIES_PATH', dirname(__DIR__) . '/libraries/');

set_include_path(implode(PATH_SEPARATOR, array(
	LIBRARIES_PATH,
	APP_LIBRARIES_PATH,
	get_include_path()
)));

// Load and register the autoloader
require 'rox/Loader.php';
\rox\Loader::register();

// Set the default timezone
date_default_timezone_set('America/New_York');
