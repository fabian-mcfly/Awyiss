<?php declare(strict_types=1);

/*
 * Configure paths required to find CakePHP + general filepath constants
 */
require __DIR__ . '/paths.php';

/*
 * Bootstrap CakePHP.
 *
 * Does the various bits of setup that CakePHP needs to do.
 * This includes:
 *
 * - Registering the CakePHP autoloader.
 * - Setting the default application paths.
 */

require CORE_PATH . 'config' . DS . 'bootstrap.php';


use Awyiss\Core\Configure\Engine\PhpConfig;
use Cake\Cache\Cache;
use Cake\Core\Configure;
use Cake\Datasource\ConnectionManager;
use Cake\Error\ConsoleErrorHandler;
use Cake\Error\ErrorHandler;
use Cake\Log\Log;
use Cake\Mailer\Mailer;
use Cake\Mailer\TransportFactory;
use Cake\Routing\Router;
use Cake\Utility\Security;


/*
 * Read configuration file
 */
try {
	Configure::config('default', new PhpConfig());
	Configure::load('awyiss', 'default', FALSE);
}
catch (\Exception $ex) {
	exit($ex->getMessage() . "\n");
}


/*
 * When debug = true the metadata cache should only last
 * for a short time.
 */
if (Configure::read('debug')) {
	Configure::write('Cache._cake_model_.duration', '+60 seconds');
	Configure::write('Cache._cake_core_.duration', '+60 seconds');
	Configure::write('Cache._cake_routes_.duration', '+60 seconds');
}


/*
 * Set the default server timezone. Using UTC makes time calculations / conversions easier.
 * Check http://php.net/manual/en/timezones.php for list of valid timezone strings.
 */
date_default_timezone_set(Configure::read('App.defaultTimezone'));


/*
 * Configure the mbstring extension to use the correct encoding.
 */
mb_internal_encoding(Configure::read('App.encoding'));


/*
 * Set the default locale. This controls how dates, number and currency is
 * formatted and sets the default language to use for translations.
 */
ini_set('intl.default_locale', Configure::read('App.defaultLocale'));


/*
 * Register application error and exception handlers.
 */
$lb_isCli = PHP_SAPI === 'cli';
if ($lb_isCli) {
	(new ConsoleErrorHandler(Configure::read('Error')))->register();
}
else {
	(new ErrorHandler(Configure::read('Error')))->register();
}


/*
 * Include the CLI bootstrap overrides.
 */
if ($lb_isCli) {
	require __DIR__ . '/bootstrap_cli.php';
}


/*
 * Set the full base URL.
 * This URL is used as the base of all absolute links.
 */
$ls_fullBaseUrl = Configure::read('App.fullBaseUrl');
if ( ! $ls_fullBaseUrl) {
	$s = NULL;
	if (env('HTTPS')) {
		$s = 's';
	}

	$ls_httpHost = env('HTTP_HOST');
	if (isset($ls_httpHost)) {
		$ls_fullBaseUrl = 'http' . $s . '://' . $ls_httpHost;
	}
	unset($ls_httpHost, $s);
}
if ($ls_fullBaseUrl) {
	Router::fullBaseUrl($ls_fullBaseUrl);
}
unset($ls_fullBaseUrl);


Cache::setConfig(Configure::consume('Cache'));
ConnectionManager::setConfig(Configure::consume('Datasources'));
Mailer::setConfig(Configure::consume('Email'));
Log::setConfig(Configure::consume('Log'));
Security::setSalt(Configure::consume('Security.salt'));
TransportFactory::setConfig(Configure::consume('EmailTransport'));


/*
 * Custom Inflector rules, can be set to correctly pluralize or singularize
 * table, model, controller names or whatever other string is passed to the
 * inflection functions.
 */
//\Cake\Utility\Inflector::rules('plural', ['/^(inflect)or$/i' => '\1ables']);
//\Cake\Utility\Inflector::rules('irregular', ['red' => 'redlings']);
\Cake\Utility\Inflector::rules('uninflected', ['media']);