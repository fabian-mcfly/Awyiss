#!/usr/bin/php -q
<?php

require dirname(__DIR__) . '/awyiss/I18n/functions.php';

$loader = require dirname(__DIR__) . '/vendor/autoload.php';

use Cake\Console\CommandRunner;

// Build the runner with an application and root executable name.
$runner = new CommandRunner(
	new \Awyiss\Awyiss(dirname(__DIR__) . DS . 'awyiss' . DS . 'config', null, null, $loader),
	'cake'
);
exit($runner->run($argv));
