#!/usr/bin/php -q
<?php

if (!defined('DS')) {
	define('DS', DIRECTORY_SEPARATOR);
}

$dir = dirname(__DIR__) . DS;

$awyissPath = $dir;
require $awyissPath . 'awyiss' . DS . 'I18n' . DS . 'functions.php';
require $awyissPath . 'awyiss' . DS . 'functions.php';

$loader = require $dir . 'vendor' . DS . 'autoload.php';

use Cake\Console\CommandRunner;

/**
 * Build the runner with an application and root executable name.
 *
 * @noinspection PhpFullyQualifiedNameUsageInspection
 */
$runner = new CommandRunner(
	new \Awyiss\Awyiss($awyissPath . 'awyiss' . DS . 'config', null, null, $loader),
	'cake'
);
exit($runner->run($argv));
