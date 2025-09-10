<?php declare(strict_types=1);


/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link      https://cakephp.org CakePHP(tm) Project
 * @since     3.0.0
 * @license   https://opensource.org/licenses/mit-license.php MIT License
 */


use Awyiss\Model\Table;
use Awyiss\ORM\Locator\TableLocator;
use Cake\Chronos\Chronos;
use Cake\Core\Configure;
use Cake\Datasource\ConnectionManager;
use Cake\Datasource\FactoryLocator;
use Cake\TestSuite\ConnectionHelper;
use Migrations\Migrations;
use Migrations\TestSuite\Migrator;

putenv('HTTP_HOST=localhost');

define('CONFIG_ENV', 'development');
define('CUSTOM_DIR', 'tests' . DS . 'customer');
define('CUSTOM_NAMESPACE', 'Customer');

$ls_dir = dirname(__DIR__);

require $ls_dir . '/awyiss/I18n/functions.php';

$lo_loader = require dirname(__DIR__) . '/vendor/autoload.php';

require $ls_dir . '/awyiss/config/bootstrap.php';

$lo_loader->addPsr4(CUSTOM_NAMESPACE . '\\', [ROOT . DS . CUSTOM_DIR], true);

if (empty($_SERVER['HTTP_HOST']) && !Configure::read('App.fullBaseUrl')) {
	Configure::write('App.fullBaseUrl', 'http://localhost');
}

// DebugKit skips settings these connection config if PHP SAPI is CLI / PHPDBG.
// But since PagesControllerTest is run with debug enabled and DebugKit is loaded
// in application, without setting up these config DebugKit errors out.
ConnectionManager::setConfig('test_debug_kit', [
	'className' => 'Cake\Database\Connection',
	'driver' => 'Cake\Database\Driver\Sqlite',
	'database' => TMP . 'debug_kit.sqlite',
	'encoding' => 'utf8',
	'cacheMetadata' => true,
	'quoteIdentifiers' => false,
]);

ConnectionManager::alias('test_debug_kit', 'debug_kit');

// Fixate now to avoid one-second-leap-issues
Chronos::setTestNow(Chronos::now());

// Fixate sessionid early on, as php7.2+
// does not allow the sessionid to be set after stdout
// has been written to.
session_id('cli');

// Connection aliasing needs to happen before migrations are run.
// Otherwise, table objects inside migrations would use the default datasource
ConnectionHelper::addTestAliases();

// Run the migrations
(new Migrator())->runMany([
	['source' => 'Migrations'],
	['source' => '..' . DS . '..' . DS . 'tests' . DS . 'customer' . DS . 'config' . DS . 'Migrations'],
	['plugin' => 'Queue']
]);

// Seed the database
(new Migrations(['connection' => 'test']))->seed();
(new Migrations(['connection' => 'test']))->seed(['source' => '..' . DS . '..' . DS . 'tests' . DS . 'customer' . DS . 'config' . DS . 'Seeds']);

FactoryLocator::add('Table', (new TableLocator())->allowFallbackClass(true)->setFallbackClassName(Table::class));

// Use a locale that won't ever be present as translations
// Here it's 'en_ZW' (English in Zimbabwe)
ini_set('intl.default_locale', 'en_AG');
\Cake\I18n\I18n::setLocale('en_AG');

\Cake\Utility\Text::setTransliteratorId('de-ASCII; Any-Latin; Latin-ASCII; [\u0080-\u7fff] remove');
