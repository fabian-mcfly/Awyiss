<?php declare(strict_types=1);

return [
	/*
	 * Connection information used by the ORM to connect
	 * to your application's datastores.
	 *
	 * See awyiss/config/awyiss.php for more configuration options.
	 */
	'Datasources' => [
		'default' => [
			'database' => '',
			'flags' => [],
			'host' => 'localhost',
			'log' => true,
			//'init' => ['SET GLOBAL innodb_stats_on_metadata = 0'],
			'password' => '',
			'username' => '',
		],
	],

	'debug' => true,

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
