<?php declare(strict_types=1);

return [
	/**
	 * Connection information used by the ORM to connect
	 * to your application's datastores.
	 *
	 * @see /awyiss/config/awyiss.php for more configuration options.
	 */
	'Datasources' => [
		'default' => [
			'database' => '',
			'flags' => [],
			'host' => 'localhost',
			'log' => true,
			//'init' => ['SET GLOBAL innodb_stats_on_metadata = 0'],
			'password' => '',
			'port' => null,
			'username' => '',
		],
	],

	'debug' => true,

	'Design' => [
		// Only use this in development or staging environments. In production, it is recommended to set this to false or provide
		// a callable that checks the request's IP address or other criteria to determine if SCSS compilation is allowed.
		'allowCompile' => true,
	],

	'DebugKit' => [
		'forceEnable' => false,
		'ignoreAuthentication' => true,
		'panels' => [
			'DebugKit.Mail' => false,
		],
	],

	'Error' => [
		'errorLevel' => E_ALL,
	],
];
