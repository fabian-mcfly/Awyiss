<?php

/**
 * The Front Controller for handling every request
 *
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

$ls_dir = dirname(__DIR__);

// Check platform requirements
if (file_exists($ls_file = $ls_dir . '/awyiss/config/requirements.php')) {
	require $ls_file;
}

require $ls_dir . '/awyiss/I18n/functions.php';

// For built-in server
if (PHP_SAPI === 'cli-server') {
	$_SERVER['PHP_SELF'] = '/' . basename(__FILE__);

	$la_url = parse_url(urldecode($_SERVER['REQUEST_URI']));
	$ls_file = __DIR__ . $la_url['path'];
	if (strpos($la_url['path'], '..') === FALSE && strpos($la_url['path'], '.') !== FALSE && is_file($ls_file)) {
		return FALSE;
	}
}

$lo_loader = require $ls_dir . '/vendor/autoload.php';


use Awyiss\Application;
use Cake\Http\Server;


// Bind your application to the server.
$lo_server = new Server(new Application($lo_loader));

// Run the request/response through the application and emit the response.
$lo_server->emit($lo_server->run());
