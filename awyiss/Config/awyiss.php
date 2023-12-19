<?php declare(strict_types=1);


use Cake\Cache\Engine\FileEngine;
use Cake\Database\Connection;
use Cake\Database\Driver\Mysql;
use Cake\Error\Renderer\WebExceptionRenderer;
use Cake\Log\Engine\FileLog;
use Cake\Mailer\Transport\MailTransport;
use Queue\Generator\Task\QueuedJobTask;


return [
	'Asset' => [
		'timestamp' => TRUE, //Set to 'force' to always enable timestamping regardless of debug value.
		'cacheTime' => '+1 year',
	],

	/*
	 * Configure basic information about the application.
	 *
	 * - imageBaseUrl - Web path to the public images directory under webroot.
	 * - cssBaseUrl - Web path to the public css directory under webroot.
	 * - jsBaseUrl - Web path to the public js directory under webroot.
	 */
	'App' => [
		'base' => FALSE,
		'cssBaseUrl' => 'css/',
		'defaultLocale' => env('APP_DEFAULT_LOCALE', 'de_DE'),
		'defaultTimezone' => env('APP_DEFAULT_TIMEZONE', 'UTC'),
		'dir' => 'awyiss',
		'encoding' => env('APP_ENCODING', 'UTF-8'),
		'fullBaseUrl' => FALSE,
		'imageBaseUrl' => 'img/',
		'jsBaseUrl' => 'js/',
		'namespace' => 'Awyiss',
		'paths' => [
			'plugins' => [
				'customer' => ROOT . DS . CUSTOM_DIR . DS . 'plugins' . DS,
				'awyiss' => ROOT . DS . APP_DIR . DS . 'plugins' . DS,
			],
			'templates' => [
				'customer' => ROOT . DS . CUSTOM_DIR . DS . 'templates' . DS,
				'awyiss' => ROOT . DS . APP_DIR . DS . 'templates' . DS,
			],
			'locales' => [
				'customer' => ROOT . DS . CUSTOM_DIR . DS . 'locales' . DS,
				'awyiss' => ROOT . DS . APP_DIR . DS . 'locales' . DS,
			],
		],
		'webroot' => 'webroot',
		'wwwRoot' => WWW_ROOT,
	],

	'debug' => filter_var(env('DEBUG', FALSE), FILTER_VALIDATE_BOOLEAN),

	'Cache' => [
		'default' => [
			'className' => FileEngine::class,
			'path' => CACHE,
			'url' => env('CACHE_DEFAULT_URL'),
		],

		'_cake_core_' => [
			'className' => FileEngine::class, //set to NULL to disable
			'duration' => '+1 years',
			'path' => CACHE . 'persistent' . DS,
			'prefix' => 'awyiss_core_',
			'serialize' => TRUE,
			'url' => env('CACHE_CAKECORE_URL'),
		],

		'_cake_model_' => [
			'className' => FileEngine::class,
			'duration' => '+1 years',
			'path' => CACHE . 'models' . DS,
			'prefix' => 'awyiss_model_',
			'serialize' => TRUE,
			'url' => env('CACHE_CAKEMODEL_URL'),
		],

		'_cake_routes_' => [
			'className' => FileEngine::class,
			'duration' => '+1 years',
			'path' => CACHE,
			'prefix' => 'awyiss_routes_',
			'serialize' => TRUE,
			'url' => env('CACHE_CAKEROUTES_URL'),
		],
	],

	'Datasources' => [
		'default' => [
			'cacheMetadata' => TRUE,
			'className' => Connection::class,
			'driver' => Mysql::class,
			'flags' => [],
			'host' => 'localhost',
			'log' => FALSE,
			'persistent' => FALSE,
			'quoteIdentifiers' => FALSE,
			'timezone' => 'UTC',
		],

		'test' => [
			'cacheMetadata' => TRUE,
			'className' => Connection::class,
			'driver' => Mysql::class,
			'flags' => [],
			'log' => FALSE,
			'persistent' => FALSE,
			'quoteIdentifiers' => FALSE,
			'timezone' => 'UTC',
		],
	],

	'Debugger' => [
		'editor' => 'phpstorm',
	],

	/*
	 * See `Cake\Mailer\Email` for more information.
	 */
	'Email' => [
		'default' => [
			'emailPattern' => NULL,
			'from' => 'awyiss@localhost',
			'transport' => 'default',
		],
	],

	/*
	 * The keys host, port, timeout, username, password, client and tls are used in SMTP transports
	 */
	'EmailTransport' => [
		'default' => [
			'className' => MailTransport::class,
			'client' => NULL,
			'host' => 'localhost',
			'password' => NULL,
			'port' => 25,
			'timeout' => 30,
			'tls' => FALSE,
			'url' => env('EMAIL_TRANSPORT_DEFAULT_URL'),
			'username' => NULL,
		],
	],

	/*
	 * - `skipLog` - List of exceptions to skip for logging. Exceptions that
	 *   extend one of the listed exceptions will also be skipped for logging.
	 *   E.g.: ['Cake\Http\Exception\NotFoundException', 'Cake\Http\Exception\UnauthorizedException']
	 */
	'Error' => [
		'errorLevel' => 0,
		'exceptionRenderer' => WebExceptionRenderer::class,
		//'extraFatalErrorMemory' => 4,
		'ignoredDeprecationPaths' => [],
		'log' => TRUE,
		'skipLog' => [],
		'trace' => TRUE,
	],


	'IdeHelper' => [
		'generatorTasks' => [
			QueuedJobTask::class,
		],
	],


	/*
	 * Configures logging options
	 */
	'Log' => [
		'debug' => [
			'className' => FileLog::class,
			'file' => 'debug',
			'levels' => ['notice', 'info', 'debug'],
			'path' => LOGS,
			'size' => 2097152,
			'scopes' => FALSE,
		],
		'error' => [
			'className' => FileLog::class,
			'file' => 'error',
			'levels' => ['warning', 'error', 'critical', 'alert', 'emergency'],
			'path' => LOGS,
			'size' => 2097152,
			'scopes' => FALSE,
		],
		'queries' => [
			'className' => FileLog::class,
			'file' => 'queries',
			'path' => LOGS,
			'size' => 2097152,
			'scopes' => ['queriesLog'],
		],
	],


	'Queue' => [
		'maxworkers' => 3,
		'workermaxruntime' => 900,
		'workertimeout' => 900,
	],


	'Security' => [
		'salt' => env('SECURITY_SALT'),
	],


	'Session' => [
		'cookie' => 'awyiss',
		//'cookiePath' => '',
		'defaults' => 'cake',
		//'handler' => ['engine' => ''],
		'ini' => [
			'session.cookie_lifetime' => 0,
			'session.gc_divisor' => 1000,
			'session.gc_maxlifetime' => 86400, //Time in seconds!
			'session.gc_probability' => 1,
		],
		'timeout' => 1440 //Time in minues!
	],
];
