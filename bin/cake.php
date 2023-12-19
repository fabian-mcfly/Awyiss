#!/usr/bin/php -q
<?php
// Check platform requirements
require dirname(__DIR__) . '/awyiss/config/requirements.php';
$lo_loader = require dirname(__DIR__) . '/vendor/autoload.php';

use Cake\Console\CommandRunner;

//define('CONFIG_ENV', env('CONFIG_ENV', 'production'));
define('CUSTOM_DIR', env('CUSTOM_DIR', 'foobar-customer'));

// Build the runner with an application and root executable name.
$lo_runner = new CommandRunner(new \Awyiss\Application($lo_loader), 'cake');
exit($lo_runner->run($argv));
