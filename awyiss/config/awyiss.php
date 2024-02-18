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
		'timestamp' => true, //Set to 'force' to always enable timestamping regardless of debug value.
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
		'base' => false,
		'cssBaseUrl' => 'css/',
		'defaultLocale' => env('APP_DEFAULT_LOCALE', ''),
		'defaultTimezone' => env('APP_DEFAULT_TIMEZONE', 'UTC'),
		'dir' => 'awyiss',
		'encoding' => env('APP_ENCODING', 'UTF-8'),
		'fullBaseUrl' => false,
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

	'AvailableCommands' => null,

	'debug' => false,

	'Cache' => [
		'default' => [
			'className' => FileEngine::class,
			'path' => CACHE,
			'url' => env('CACHE_DEFAULT_URL'),
		],

		'_cake_core_' => [
			'className' => FileEngine::class, //set to null to disable
			'duration' => '+1 years',
			'path' => CACHE . 'persistent' . DS,
			'prefix' => 'awyiss_core_',
			'serialize' => true,
			'url' => env('CACHE_CAKECORE_URL'),
		],

		'_cake_model_' => [
			'className' => FileEngine::class,
			'duration' => '+1 years',
			'path' => CACHE . 'models' . DS,
			'prefix' => 'awyiss_model_',
			'serialize' => true,
			'url' => env('CACHE_CAKEMODEL_URL'),
		],
	],

	'Datasources' => [
		'default' => [
			'cacheMetadata' => true,
			'className' => Connection::class,
			'driver' => Mysql::class,
			'flags' => [],
			'host' => 'localhost',
			'log' => false,
			'persistent' => false,
			'quoteIdentifiers' => false,
			'timezone' => 'UTC',
		],

		'test' => [
			'cacheMetadata' => true,
			'className' => Connection::class,
			'driver' => Mysql::class,
			'flags' => [],
			'log' => false,
			'persistent' => false,
			'quoteIdentifiers' => false,
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
			'emailPattern' => null,
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
			'client' => null,
			'host' => 'localhost',
			'password' => null,
			'port' => 25,
			'timeout' => 30,
			'tls' => false,
			'url' => env('EMAIL_TRANSPORT_DEFAULT_URL'),
			'username' => null,
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
		'ignoredDeprecationPaths' => [
			'vendor/cakephp/cakephp/src/Controller/ComponentRegistry.php',
		],
		'log' => true,
		'skipLog' => [],
		'trace' => true,
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
			'scopes' => null,
		],
		'error' => [
			'className' => FileLog::class,
			'file' => 'error',
			'levels' => ['warning', 'error', 'critical', 'alert', 'emergency'],
			'path' => LOGS,
			'size' => 2097152,
			'scopes' => null,
		],
		'queue' => [
			'className' => FileLog::class,
			'file' => 'queue',
			'levels' => ['error', 'info'],
			'scopes' => ['queue'],
			'type' => 'queue',
		],
		'queries' => [
			'className' => FileLog::class,
			'file' => 'queries',
			'path' => LOGS,
			'size' => 2097152,
			'scopes' => ['cake.database.queries'],
		],
	],


	'MimeTypes' => [
		'text/plain' => ['txt', 'csv'],
		'text/html' => ['html', 'htm'],
		'text/css' => ['css'],
		'text/javascript' => ['js'],
		'application/json' => ['json'],
		'application/xml' => ['xml'],
		'image/jpeg' => ['jpeg', 'jpg'],
		'image/png' => ['png'],
		'image/gif' => ['gif'],
		'image/webp' => ['webp'],
		'image/svg+xml' => ['svg'],
		'audio/mpeg' => ['mp3'],
		'audio/ogg' => ['oga', 'ogg'],
		'video/mp4' => ['mp4'],
		'video/x-msvideo' => ['avi'],
		'application/pdf' => ['pdf', 'ai'],
		'application/zip' => ['zip'],
		'application/x-rar-compressed' => ['rar'],
		'application/msword' => ['doc'],
		'application/vnd-ms-excel' => ['xls'],
		'application/vnd-ms-powerpoint' => ['ppt'],
		'application/vnd-openxmlformats-officedocument-wordprocessingml-document' => ['docx'],
		'application/vnd-openxmlformats-officedocument-spreadsheetml-sheet' => ['xlsx'],
		'application/vnd-openxmlformats-officedocument-presentationml-presentation' => ['pptx'],
		'application/mac-binhex40' => ['hqx'],
		'application/mac-binhex' => ['hqx'],
		'application/x-binhex40' => ['hqx'],
		'application/x-mac-binhex40' => ['hqx'],
		'application/mac-compactpro' => ['cpt'],
		'text/x-comma-separated-values' => ['csv'],
		'text/comma-separated-values' => ['csv'],
		'application/octet-stream' => ['csv', 'bin', 'dms', 'lha', 'lzh', 'exe', 'class', 'so', 'sea', 'dll'],
		'application/x-csv' => ['csv'],
		'text/x-csv' => ['csv'],
		'text/csv' => ['csv'],
		'application/csv' => ['csv'],
		'application/excel' => ['csv', 'xls'],
		'application/vnd-msexcel' => ['csv'],
		'application/macbinary' => ['bin'],
		'application/mac-binary' => ['bin'],
		'application/x-binary' => ['bin'],
		'application/x-macbinary' => ['bin'],
		'application/x-msdownload' => ['exe'],
		'application/x-photoshop' => ['psd'],
		'image/vnd-adobe-photoshop' => ['psd'],
		'application/oda' => ['oda'],
		'application/force-download' => ['pdf'],
		'application/x-download' => ['pdf'],
		'binary/octet-stream' => ['pdf'],
		'application/postscript' => ['ai', 'eps', 'ps'],
		'application/smil' => ['smi', 'smil'],
		'application/vnd-mif' => ['mif'],
		'application/msexcel' => ['xls'],
		'application/x-msexcel' => ['xls'],
		'application/x-ms-excel' => ['xls'],
		'application/x-excel' => ['xls'],
		'application/x-dos_ms_excel' => ['xls'],
		'application/xls' => ['xls'],
		'application/x-xls' => ['xls'],
		'application/download' => ['xls'],
		'application/vnd-ms-office' => ['xls', 'ppt'],
		'application/powerpoint' => ['ppt'],
	],


	'Queue' => [
		'defaultworkertimeout' => 120,
		'maxworkers' => 3,
		'workermaxruntime' => 600,
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
		'timeout' => 1440, //Time in minutes!
	],
];
