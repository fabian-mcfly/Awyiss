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


use Awyiss\Awyiss;
use Awyiss\Core\Configure\Engine\PhpConfig;
use Awyiss\Database\Type\IntegerType;
use Awyiss\Database\Type\StringType;
use Awyiss\I18n\MessagesFileLoader;
use Awyiss\Routing\Router;
use Cake\Cache\Cache;
use Cake\Core\Configure;
use Cake\Database\TypeFactory;
use Cake\Datasource\ConnectionManager;
use Cake\Error\ErrorTrap;
use Cake\Error\ExceptionTrap;
use Cake\I18n\I18n;
use Cake\I18n\Package;
use Cake\Log\Log;
use Cake\Mailer\Mailer;
use Cake\Mailer\TransportFactory;
use Cake\Utility\Inflector;
use Cake\Utility\Security;
use josegonzalez\Dotenv\Loader;


/**
 * Load global functions.
 */
require CAKE . 'functions.php';


if (!env('CONFIG_ENV') && file_exists(ROOT . DS . '.env')) {
	$lo_dotenv = new Loader([ROOT . DS . '.env']);
	$lo_dotenv->parse()->putenv()->toEnv()->toServer();
}

if (!defined('CONFIG_ENV')) {
	/**
	 * The current environment
	 */
	$ls_configEnv = env('CONFIG_ENV');
	if ($ls_configEnv) {
		define('CONFIG_ENV', $ls_configEnv);
	}
}

if (!defined('CUSTOM_DIR')) {
	/**
	 * The directory for customer logic and frontend data
	 */
	$ls_customDir = env('CUSTOM_DIR');
	if ($ls_customDir) {
		define('CUSTOM_DIR', $ls_customDir);
	}
}

if (defined('CUSTOM_DIR')) {
	/**
	 * Custom config folder
	 */
	define('CUSTOM_CONFIG', ROOT . DS . CUSTOM_DIR . DS . 'config' . DS);

	/**
	 * Custom namespace
	 */
	define('CUSTOM_NAMESPACE', Inflector::camelize(str_replace('_', '-', CUSTOM_DIR), '-'));

	if (defined('CONFIG_ENV')) {
		/**
		 * Environment-specific custom config folder
		 */
		define('ENV_CUSTOM_CONFIG', CUSTOM_CONFIG . CONFIG_ENV . DS);
	}
}


/*
 * Read configuration file
 */
try {
	Configure::config('default', new PhpConfig());
	Configure::load('awyiss', 'default', false);
}
catch (Exception $ex) {
	exit($ex->getMessage() . "\n");
}


/*
 * When debug = true the metadata cache should only last
 * for a short time.
 */
if (Configure::read('debug')) {
	Configure::write('Cache._cake_model_.duration', '+60 seconds');
	Configure::write('Cache._cake_core_.duration', '+60 seconds');
}


/*
 * Set the default server timezone. Using UTC makes time calculations / conversions easier.
 * Check https://php.net/manual/en/timezones.php for list of valid timezone strings.
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
(new ErrorTrap(Configure::read('Error')))->register();
(new ExceptionTrap(Configure::read('Error')))->register();


/*
 * Include the CLI bootstrap overrides.
 */
if (PHP_SAPI === 'cli') {
	require __DIR__ . '/bootstrap_cli.php';
}


/*
 * Set the full base URL.
 * This URL is used as the base of all absolute links.
 */
$ls_fullBaseUrl = Configure::read('App.fullBaseUrl');
if (!$ls_fullBaseUrl) {
	$ls_https = null;
	if (env('HTTPS')) {
		$ls_https = 's';
	}

	$ls_httpHost = env('HTTP_HOST');
	if (isset($ls_httpHost)) {
		$ls_fullBaseUrl = 'http' . $ls_https . '://' . $ls_httpHost;
	}
	unset($ls_httpHost, $ls_https);
}
if ($ls_fullBaseUrl) {
	Router::fullBaseUrl($ls_fullBaseUrl);
}
unset($ls_fullBaseUrl);

Cache::setConfig(Configure::consume('Cache'));
ConnectionManager::setConfig(PHP_SAPI === 'cli' ? Configure::read('Datasources') : Configure::consume('Datasources'));
Mailer::setConfig(Configure::consume('Email'));
Log::setConfig(Configure::consume('Log'));
Security::setSalt(Configure::consume('Security.salt'));
TransportFactory::setConfig(Configure::consume('EmailTransport'));

/**
 * Set the default locale to german
 * The LocaleMiddleware will overwrite this with the user's language
 * If german isn't your desired default locale, change it in your custom bootstrap.php
 */
ini_set('intl.default_locale', 'de_DE');
I18n::setLocale('de_DE');

I18n::config('_fallback', function ($domain, $locale) {
	$ls_domain = $domain;
	if (!str_contains($ls_domain, '/')) {
		$ls_domain = Awyiss::getRealm() . '/' . $ls_domain;
	}

	$lo_fileLoader = new MessagesFileLoader($ls_domain, $locale, 'po');
	$lo_default = $lo_fileLoader();

	return new Package('default', null, $lo_default->getMessages());
});

/*
 * Custom Inflector rules, can be set to correctly pluralize or singularize
 * table, model, controller names or whatever other string is passed to the
 * inflection functions.
 */
Inflector::rules('plural', ['/^(menu)s$/i' => '\1s']);
//\Cake\Utility\Inflector::rules('irregular', ['red' => 'redlings']);
Inflector::rules('uninflected', ['.*configuration', '.*found', '.*history', 'media', 'system']);

TypeFactory::map('tinyinteger', IntegerType::class);
TypeFactory::map('smallinteger', IntegerType::class);
TypeFactory::map('integer', IntegerType::class);
TypeFactory::map('biginteger', IntegerType::class);

TypeFactory::map('char', StringType::class);
TypeFactory::map('string', StringType::class);
TypeFactory::map('text', StringType::class);
