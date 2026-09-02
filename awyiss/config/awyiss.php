<?php declare(strict_types=1);


use Awyiss\Awyiss;
use Cake\Cache\Engine\FileEngine;
use Cake\Database\Connection;
use Cake\Database\Driver\Mysql;
use Cake\Database\Driver\Sqlite;
use Cake\Error\Renderer\WebExceptionRenderer;
use Cake\Log\Engine\FileLog;
use Queue\Generator\Task\QueuedJobTask;


$assetPaths = [
	Awyiss::REALM_FRONTEND => [],
	Awyiss::REALM_BACKEND => [
		'awyiss' => APP . 'assets' . DS,
	],
];

// Add the customer assets paths and make sure they are sorted; customer first
if (defined('CUSTOM_DIR')) {
	$assetPaths[ Awyiss::REALM_FRONTEND ]['customer'] = ROOT . DS . CUSTOM_DIR . DS . 'assets' . DS;
	krsort($assetPaths[ Awyiss::REALM_FRONTEND ]);
	$assetPaths[ Awyiss::REALM_BACKEND ]['customer'] = ROOT . DS . CUSTOM_DIR . DS . 'assets' . DS . 'awyiss' . DS;
	krsort($assetPaths[ Awyiss::REALM_BACKEND ]);
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
			'assets' => $assetPaths,
			'locales' => [
				'customer' => defined('CUSTOM_DIR') ? ROOT . DS . CUSTOM_DIR . DS . 'locales' . DS : null,
				'awyiss' => APP . 'locales' . DS,
			],
			'plugins' => [
				'customer' => defined('CUSTOM_DIR') ? ROOT . DS . CUSTOM_DIR . DS . 'plugins' . DS : null,
				'awyiss' => APP . 'plugins' . DS,
			],
			'templates' => [
				'customer' => defined('CUSTOM_DIR') ? ROOT . DS . CUSTOM_DIR . DS . 'templates' . DS : null,
				'awyiss' => APP . 'templates' . DS,
			],
		],
		'webroot' => 'webroot',
		'wwwRoot' => WWW_ROOT,
	],

	'AvailableCommands' => null,

	'Cache' => [
		'default' => [
			'className' => FileEngine::class,
			'path' => CACHE,
		],

		'classes' => [
			'className' => FileEngine::class,
			'duration' => '+1 years',
			'path' => CACHE . 'classes' . DS,
			'prefix' => 'classes_',
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
			'port' => null,
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

	'debug' => false,

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
		'customerCenter' => [
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
			'vendor/cakephp/migrations/src/Command/BakeSimpleMigrationCommand.php',
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


	/**
	 * Periodic events configuration
	 * Each key represents a frequency (currently only 'hourly' and 'daily' are supported)
	 * and contains an array of Events to be triggered at that frequency.
	 *
	 * If no event but a callable is given, the callable will be executed directly,
	 * without any context or event object.
	 */
	'PeriodicEvents' => [
		'hourly' => [],
		'daily' => [
			'Customers.cleanupUnverifiedCustomers',
		],
	],


	'Queue' => [
		'defaultRequeueTimeout' => 120,
		'maxworkers' => 3,
		'workerLifetime' => 600,
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

		/**
		 * Customer Center route configuration
		 *
		 * Allows configuring the URL path and available actions for the customer center.
		 * Actions are mapped from action names to visible URL names.
		 * Per-language customization is supported.
		 *
		 * Example:
		 * 'CustomerCenter' => [
		 *     'path' => '_customer-center',
		 *     'actions' => [
		 *         'login' => 'login',
		 *         'logout' => 'logout',
		 *         'register' => 'register',
		 *         'dashboard' => 'dashboard',
		 *         'editProfile' => 'edit-profile',
		 *         'changePassword' => 'change-password',
		 *         'forgotPassword' => 'forgot-password',
		 *         'resetPassword' => 'reset-password',
		 *         'verifyAccount' => 'verify-account',
		 *     ],
		 *     'languages' => [
		 *         'de' => [
		 *             'path' => '_kundencenter',
		 *             'actions' => [
		 *                 'login' => 'anmelden',
		 *                 'logout' => 'abmelden',
		 *             ],
		 *         ],
		 *     ],
		 * ]
		 */
		'CustomerCenter' => [
			'path' => 'customer-center',
			'actions' => [
				'login' => 'login',
				'logout' => 'logout',
				'register' => 'register',
				'dashboard' => 'dashboard',
				'editProfile' => 'edit-profile',
				'changePassword' => 'change-password',
				'forgotPassword' => 'forgot-password',
				'resetPassword' => 'reset-password',
				'verifyAccount' => 'verify-account',
			],
			'languages' => [
				'de' => [
					'path' => 'konto',
					'actions' => [
						'login' => 'anmelden',
						'logout' => 'abmelden',
						'register' => 'registrieren',
						'dashboard' => 'uebersicht',
						'editProfile' => 'profil-bearbeiten',
						'changePassword' => 'passwort-aendern',
						'forgotPassword' => 'passwort-vergessen',
						'resetPassword' => 'passwort-zuruecksetzen',
						'verifyAccount' => 'konto-bestaetigen',
					],
				],
			],
		],
	],


	'Security' => [
		'prehashPassword' => true,
		'salt' => env('SECURITY_SALT', 'dummy-salt'),
	],


	'Seo' => [
		'stopWords' => [
			'de' => [
				'aber','alle','allem','allen','aller','alles','als','also','am','an','ander','andere','anderem','anderen',
				'anderer','anderes','anderm','andern','anderr','anders','auch','auf','aus','bei','bin','bis','bist',
				'da','dadurch','daher','darum','das','daß','dass','dein','deine','deinem','deinen','deiner','deines',
				'dem','den','der','des','dessen','deshalb','die','dies','diese','diesem','diesen','dieser','dieses',
				'doch','dort','du','durch','ein','eine','einem','einen','einer','eines','er','es','euer','eure','eurem',
				'euren','eurer','eures','für','hat','hatte','hatten','hattest','hattet','hier','hinter','ich','ihm',
				'ihn','ihnen','ihr','ihre','ihrem','ihren','ihrer','ihres','im','in','ist','ja','jede','jedem','jeden',
				'jeder','jedes','jener','jenes','jetzt','kann','kannst','können','könnt','machen','mein','meine',
				'meinem','meinen','meiner','meines','mit','muß','müssen','musst','müsst','nach','nachdem','nein','nicht',
				'nun','oder','seid','sein','seine','seinem','seinen','seiner','seines','selbst','sich','sie','sind',
				'so','solche','solchem','solchen','solcher','solches','soll','sollen','sollst','sollt','sonst','um',
				'und','uns','unse','unsem','unsen','unser','unses','unter','vom','von','vor','wann','warum','was','weiter',
				'weitere','wenn','wer','werde','werden','werdet','weshalb','wie','wieder','wir','wird','wirst','wo','wollen',
				'wollt','während','würde','würden','zu','zum','zur','über'
			],
			'en' => [
				'a','about','above','after','again','against','all','am','an','and','any','are','aren\'t','as','at',
				'be','because','been','before','being','below','between','both','but','by','can','can\'t','could',
				'couldn\'t','did','didn\'t','do','does','doesn\'t','doing','don\'t','down','during','each','few','for',
				'from','further','had','hadn\'t','has','hasn\'t','have','haven\'t','having','he','he\'d','he\'ll','he\'s',
				'her','here','here\'s','hers','herself','him','himself','his','how','how\'s','i','i\'d','i\'ll','i\'m',
				'i\'ve','if','in','into','is','isn\'t','it','it\'s','its','itself','let\'s','me','more','most','mustn\'t',
				'my','myself','no','nor','not','of','off','on','once','only','or','other','ought','our','ours','ourselves',
				'out','over','own','same','shan\'t','she','she\'d','she\'ll','she\'s','should','shouldn\'t','so','some',
				'such','than','that','that\'s','the','their','theirs','them','themselves','then','there','there\'s',
				'these','they','they\'d','they\'ll','they\'re','they\'ve','this','those','through','to','too','under',
				'until','up','very','was','wasn\'t','we','we\'d','we\'ll','we\'re','we\'ve','were','weren\'t','what',
				'what\'s','when','when\'s','where','where\'s','which','while','who','who\'s','whom','why','why\'s','with',
				'won\'t','would','wouldn\'t','you','you\'d','you\'ll','you\'re','you\'ve','your','yours','yourself','yourselves'
			],
			'it' => [
				'a','ad','al','allo','ai','agli','all','alla','alle','con','col','coi','da','dal','dallo','dai','dagli',
				'dall','dalla','dalle','di','del','dello','dei','degli','dell','della','delle','in','nel','nello','nei',
				'negli','nell','nella','nelle','su','sul','sullo','sui','sugli','sull','sulla','sulle','per','tra','fra',
				'il','lo','la','i','gli','le','un','uno','una','ma','ed','se','perché','anche','come','dov','dove','che',
				'chi','cui','non','più','quale','quanto','quanti','quanta','quante','quello','quelli','quella','quelle',
				'questo','questi','questa','queste','si','tutto','tutti','a','c','e','i','l','o','ho','hai','ha','abbiamo',
				'avete','hanno','abbia','abbiate','abbiano','avrò','avrai','avrà','avremo','avrete','avranno','sarei',
				'saresti','sarebbe','saremmo','sareste','sarebbero','sono','sei','è','siamo','siete','sarò','sarai','sarà',
				'saremo','sarete','saranno'
			],
			'es' => [
				'un','una','unas','unos','uno','sobre','todo','también','tras','otro','algún','alguno','alguna','algunos',
				'algunas','ser','es','soy','eres','somos','sois','estoy','esta','estamos','estan','como','en','para','atras',
				'porque','por','qué','estado','estaba','ante','antes','siendo','ambos','pero','por','poder','puede','puedo',
				'podemos','pueden','fui','fue','fuimos','fueron','hacer','hago','hace','hacemos','hacen','cada','fin','incluso',
				'primero','desde','conseguir','consigo','consigue','consigues','conseguimos','consiguen','ir','voy','va','vamos',
				'van','vaya','gueno','ha','tener','tengo','tiene','tenemos','tienen','el','la','lo','las','los','su','aqui',
				'mio','tuyo','ellos','ellas','nos','nosotros','vosotros','vosotras','si','dentro','solo','solamente','saber',
				'sabes','sabe','sabemos','saben','ultimo','largo','bastante','haces','muchos','aquellos','aquellas','sus',
				'entonces','tiempo','verdad','verdadero','verdadera','cierto','ciertos','cierta','ciertas','intentar','intento',
				'intenta','intentas','intentamos','intentan','dos','bajo','arriba','encima','usar','uso','usas','usa','usamos',
				'usan','emplear','empleo','empleas','emplea','empleamos','emplean','largo','ni','siquiera','tan','tal','tales'
			],
			'fr' => [
				'au','aux','avec','ce','ces','dans','de','des','du','elle','en','et','eux','il','je','la','le','leur','lui',
				'ma','mais','me','même','mes','moi','mon','ne','nos','notre','nous','on','ou','par','pas','pour','qu','que',
				'qui','sa','se','ses','son','sur','ta','te','tes','toi','ton','tu','un','une','vos','votre','vous','c','d',
				'j','l','à','m','n','s','t','y','été','étée','étées','étés','étant','suis','es','est','sommes','êtes','sont',
				'serai','seras','sera','serons','serez','seront','serais','serait','serions','seriez','seraient','étais',
				'était','étions','étiez','étaient','fus','fut','fûmes','fûtes','furent','sois','soit','soyons','soyez',
				'soient','fusse','fusses','fût','fussions','fussiez','fussent'
			]
		],
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
