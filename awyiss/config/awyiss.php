<?php declare(strict_types=1);


use Awyiss\Awyiss;
use Cake\Cache\Engine\FileEngine;
use Cake\Database\Connection;
use Cake\Database\Driver\Mysql;
use Cake\Database\Driver\Sqlite;
use Cake\Error\Renderer\WebExceptionRenderer;
use Cake\Log\Engine\FileLog;
use Cake\Mailer\Transport\MailTransport;
use Queue\Generator\Task\QueuedJobTask;


$la_assetPaths = [
	Awyiss::REALM_FRONTEND => [],
	Awyiss::REALM_BACKEND => [
		'awyiss' => ROOT . DS . APP_DIR . DS . 'assets' . DS,
	],
];

if (defined('CUSTOM_DIR')) {
	$la_assetPaths[ Awyiss::REALM_FRONTEND ]['customer'] = ROOT . DS . CUSTOM_DIR . DS . 'assets' . DS;
	$la_assetPaths[ Awyiss::REALM_BACKEND ] = [
		'customer' => ROOT . DS . CUSTOM_DIR . DS . 'assets' . DS . 'awyiss' . DS
	] + $la_assetPaths[ Awyiss::REALM_BACKEND ];
}

return [
	'App' => [
		'base' => false,
		'defaultLocale' => env('APP_DEFAULT_LOCALE', ''),
		'defaultTimezone' => env('APP_DEFAULT_TIMEZONE', 'UTC'),
		'dir' => 'awyiss',
		'encoding' => env('APP_ENCODING', 'UTF-8'),
		'fullBaseUrl' => false,
		'namespace' => 'Awyiss',
		'paths' => [
			'assets' => $la_assetPaths,
			'locales' => [
				'customer' => defined('CUSTOM_DIR') ? ROOT . DS . CUSTOM_DIR . DS . 'locales' . DS : null,
				'awyiss' => ROOT . DS . APP_DIR . DS . 'locales' . DS,
			],
			'plugins' => [
				'customer' => defined('CUSTOM_DIR') ? ROOT . DS . CUSTOM_DIR . DS . 'plugins' . DS : null,
				'awyiss' => ROOT . DS . APP_DIR . DS . 'plugins' . DS,
			],
			'templates' => [
				'customer' => defined('CUSTOM_DIR') ? ROOT . DS . CUSTOM_DIR . DS . 'templates' . DS : null,
				'awyiss' => ROOT . DS . APP_DIR . DS . 'templates' . DS,
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
		],

		'instagram' => [
			'className' => FileEngine::class,
			'duration' => 43200,
			'path' => CACHE . 'instagram' . DS,
			'prefix' => 'instagram_',
		],

		'persistent' => [
			'className' => FileEngine::class,
			'duration' => '+1 years',
			'path' => CACHE . 'persistent' . DS,
			'prefix' => 'persistent_',
		],

		'_cake_translations_' => [
			'className' => FileEngine::class,
			'duration' => '+1 years',
			'path' => CACHE . 'core' . DS,
			'prefix' => 'awyiss_core_',
			'serialize' => true,
		],

		'_cake_model_' => [
			'className' => FileEngine::class,
			'duration' => '+1 years',
			'path' => CACHE . 'models' . DS,
			'prefix' => 'awyiss_model_',
			'serialize' => true,
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
			'database' => TMP . 'awyiss_test.sqlite',
			'driver' => Sqlite::class,
			'log' => false,
			'persistent' => false,
			'quoteIdentifiers' => false,
			'timezone' => 'UTC',
		],
	],

	'Debugger' => [
		'editor' => 'phpstorm',
	],

	'Design' => [
		/**
		 * If set to true, the SCSS files will be compiled in 'production' environments
		 * if the url contains `/compile-scss:true/`.
		 *
		 * Can also be set to a callable that returns a boolean, in case you want to
		 * check for an IP address or something else.
		 */
		'allowCompile' => true,
		/**
		 * If set to true, the preview mode and the live preview in the backend
		 * will load the selected font files using Google Fonts.
		 *
		 * If set to false, no fonts will be loaded except those defined
		 * in your webfonts.scss file.
		 *
		 * Disable this if you need the preview mode and/or the backend preview
		 * to be GDPR-compliant.
		 */
		'allowGoogleFonts' => true,
		/**
		 * If set to true, the SCSS files will be compiled automatically
		 * if the environment is neither 'production', 'prod' nor 'live'.
		 */
		'autoCompile' => true,
		/**
		 * Blocklisted variables that should not be shown in the designer.
		 * If a variable name contains a regex pattern but the exact variable name
		 * is set in the variableMapping, the variable will still be shown.
		 */
		'blocklistedVariables' => [],
		'fontStacks' => [],
		'units' => [],
		'variableMapping' => [],
	],

	/**
	 * Email profiles
	 *
	 * @see https://book.cakephp.org/5/en/core-libraries/email.html#configuration-profiles
	 */
	'Email' => [
		'default' => [
			'emailFormat' => 'both',
			'emailPattern' => null,
			'priority' => 3,
			'transport' => 'default',
		],
		'form' => [
			'emailFormat' => 'both',
			'emailPattern' => null,
			'priority' => 3,
			'transport' => 'default',
		],
	],

	/**
	 * Email transport profiles.
	 *
	 * @see https://book.cakephp.org/5/en/core-libraries/email.html#email-transport
	 */
	'EmailTransport' => [],

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
		'text/plain' => ['txt', 'csv', 'vtt'],
		'text/html' => ['html', 'htm'],
		'text/css' => ['css'],
		'text/javascript' => ['js'],
		'text/vtt' => ['vtt'],
		'application/json' => ['json'],
		'application/xml' => ['xml'],
		'image/avif' => ['avif'],
		'image/jpeg' => ['jpeg', 'jpg'],
		'image/png' => ['png'],
		'image/gif' => ['gif'],
		'image/webp' => ['webp'],
		'image/svg+xml' => ['svg'],
		'audio/mpeg' => ['mp3'],
		'audio/ogg' => ['oga', 'ogg'],
		'video/mp4' => ['mp4'],
		'video/x-msvideo' => ['avi'],
		'video/webm' => ['webm'],
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


	'Route' => [
		/**
		 * If set to true, the route will be generated with the language code,
		 * e.g. `/en/company/foo:bar`. Slugs are not unique across languages,
		 * so the language code is required to find the correct route.
		 *
		 * If set to false, the route will be generated without the language code,
		 * e.g. `/company/foo:bar`. Slugs are unique across languages, so the language code
		 * is not required to find the correct route.
		 *
		 * Caution: Changing this option might break existing links or make
		 * pages unreachable if the same slug exists in different languages.
		 */
		'includeLanguageShortcode' => true,
	],


	'Security' => [
		'salt' => env('SECURITY_SALT', 'dummy-salt'),
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
