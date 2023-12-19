<?

/*
 * Local configuration file to provide any overrides to our app.php configuration
 * Note: It is not recommended to commit files with credentials into source code version control.
 */
return [
	'debug' => filter_var(env('DEBUG', FALSE), FILTER_VALIDATE_BOOLEAN),

	/*
	 * Security and encryption configuration
	 *
	 * - salt - A random string used in security hashing methods.
	 *   The salt value is also used as the encryption key.
	 *   You should treat it as extremely sensitive data.
	 */
	'Security' => [
		'salt' => env('SECURITY_SALT', ''),
	],

	/*
	 * Connection information used by the ORM to connect
	 * to your application's datastores.
	 *
	 * See awyiss/config/app.php for more configuration options.
	 */
	'Datasources' => [
		'default' => [
			'username' => '',
			'password' => '',
			'database' => 'awyiss',
			/*
			 * You can use a DSN string to set the entire configuration
			 */
			'url' => env('DATABASE_URL', NULL),
		],
	],

	/*
	 * Email configuration.
	 *
	 * Host and credential configuration in case you are using SmtpTransport
	 *
	 * See awyiss/config/app.php for more configuration options.
	 */
	'EmailTransport' => [
		'default' => [
			//'className' => \Cake\Mailer\Transport\DebugTransport::class, //To not send any mails
			'className' => \Cake\Mailer\Transport\MailTransport::class, //To use the default php mail()
			//'className' => \Cake\Mailer\Transport\SmtpTransport::class, //To use a smtp server
			'host' => 'localhost',
			'port' => 25,
			'username' => NULL,
			'password' => NULL,
			'client' => NULL,
			'url' => env('EMAIL_TRANSPORT_DEFAULT_URL', NULL),
		],
	],

	'Email' => [
		'default' => [
			'from' => NULL,
		]
	],

	'Session' => [
		'cookie' => 'foobarcustomer',
	]
];
