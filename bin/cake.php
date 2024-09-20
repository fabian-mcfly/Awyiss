#!/usr/bin/php -q
<?php

require dirname(__DIR__) . '/awyiss/I18n/functions.php';

$lo_loader = require dirname(__DIR__) . '/vendor/autoload.php';

use Cake\Console\CommandRunner;

// Build the runner with an application and root executable name.
$lo_runner = new CommandRunner(
	new \Awyiss\Awyiss(dirname(__DIR__) . DS . 'awyiss' . DS . 'config', null, null, $lo_loader),
	'cake'
);
exit($lo_runner->run($argv));
