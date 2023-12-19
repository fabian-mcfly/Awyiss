<?php /** @noinspection PhpDefineCanBeReplacedWithConstInspection */

declare(strict_types=1);

/*
 * Use the DS to separate the directories in other defines
 */
if ( ! defined('DS')) {
	define('DS', DIRECTORY_SEPARATOR);
}

/*
 * The full path to the directory which holds "awyiss", WITHOUT a trailing DS.
 */
define('ROOT', dirname(realpath(__DIR__ . DS . '..' . DS)));

/*
 * The actual directory name for the application directory.
 */
define('APP_DIR', 'awyiss');

/*
 * Path to the application's directory.
 */
define('APP', ROOT . DS . APP_DIR . DS);

/*
 * Path to the config directories.
 */
define('CONFIG', ROOT . DS . APP_DIR . DS . 'config' . DS);

define('CUSTOM_CONFIG', ROOT . DS . CUSTOM_DIR . DS . 'config' . DS);

define('CUSTOM_NAMESPACE', \Cake\Utility\Inflector::camelize(str_replace('_', '-', CUSTOM_DIR), '-'));

define('ENV_CUSTOM_CONFIG', ROOT . DS . CUSTOM_DIR . DS . 'config' . DS . CONFIG_ENV . DS);

/*
 * File path to the webroot directory.
 *
 * To derive your webroot from your webserver change this to:
 *
 * `define('WWW_ROOT', rtrim($_SERVER['DOCUMENT_ROOT'], DS) . DS);`
 */
define('WWW_ROOT', ROOT . DS . 'webroot' . DS);

/*
 * Path to the tests directory.
 */
define('TESTS', ROOT . DS . 'tests' . DS);

/*
 * Path to the temporary files directory.
 */
define('TMP', ROOT . DS . 'tmp' . DS);

/*
 * Path to the logs directory.
 */
define('LOGS', ROOT . DS . 'logs' . DS);

/*
 * Path to the cache files directory. It can be shared between hosts in a multi-server setup.
 */
define('CACHE', TMP . 'cache' . DS);

/*
 * Path to the resources directory.
 */
define('RESOURCES', ROOT . DS . 'resources' . DS);

/*
 * The absolute path to the "cake" directory, WITHOUT a trailing DS.
 *
 * CakePHP should always be installed with composer, so look there.
 */
define('CAKE_CORE_INCLUDE_PATH', ROOT . DS . 'vendor' . DS . 'cakephp' . DS . 'cakephp');

/*
 * Path to the cake directory.
 */
define('CORE_PATH', CAKE_CORE_INCLUDE_PATH . DS);
define('CAKE', CORE_PATH . 'src' . DS);
