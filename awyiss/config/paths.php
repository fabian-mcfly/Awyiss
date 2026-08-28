<?php /** @noinspection PhpDefineCanBeReplacedWithConstInspection */

declare(strict_types=1);

/*
 * Use the DS to separate the directories in other defines
 */


use Composer\InstalledVersions;


if (!defined('DS')) {
	define('DS', DIRECTORY_SEPARATOR);
}

/*
 * The actual directory name for the application directory.
 */
define('APP_DIR', 'awyiss');

$root = rtrim(InstalledVersions::getRootPackage()['install_path'], DS);
define('ROOT', realpath($root));

/*
 * The full path to the project root and
 * path to the awyiss directory.
 */
try {
	$awyissPath = InstalledVersions::getInstallPath('awyiss/awyiss');
	// Replace subpath instead of using `realpath` because `realpath` will resolve symlinks, but we want to keep the original path.
	$awyissPath = rtrim(str_replace(DS . 'vendor' . DS . 'composer' . DS . '..' . DS . '..' . DS, DS, $awyissPath), DS);
	$awyissPath = rtrim(str_replace(DS . 'composer' . DS . '..' . DS, DS, $awyissPath), DS);
	define('APP', $awyissPath . DS . APP_DIR . DS);
}
catch (OutOfBoundsException $e) {
	define('APP', ROOT . DS . APP_DIR . DS);
}

/*
 * Path to the config directories.
 */
define('CONFIG', APP . 'config' . DS);

/*
 * File path to the webroot directory.
 *
 * To derive your webroot from your webserver change this to:
 *
 * `define('WWW_ROOT', rtrim($_SERVER['DOCUMENT_ROOT'], DS) . DS);`
 */
define('WWW_ROOT', ROOT . DS . 'webroot' . DS);

/*
 * Path to the tests' directory.
 */
define('TESTS', ROOT . DS . 'tests' . DS);

/*
 * Path to the temporary files' directory.
 */
define('TMP', ROOT . DS . 'tmp' . DS);

/*
 * Path to the logs' directory.
 */
define('LOGS', ROOT . DS . 'logs' . DS);

/*
 * Path to the cache files directory. It can be shared between hosts in a multiserver setup.
 */
define('CACHE', TMP . 'cache' . DS);

/*
 * Path to the resources' directory.
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
