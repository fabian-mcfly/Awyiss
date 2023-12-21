<?php declare(strict_types=1);

/*
 * Local configuration file to provide any overrides to our awyiss.php configuration
 * Note: It is not recommended committing files with credentials into source code version control.
 */


use Cake\Mailer\Transport\MailTransport;


return [
	'debug' => filter_var(env('DEBUG', FALSE), FILTER_VALIDATE_BOOLEAN),

	/*
	 * Connection information used by the ORM to connect
	 * to your application's datastores.
	 *
	 * See awyiss/config/app.php for more configuration options.
	 */
	'Datasources' => [
		'default' => [
			'database' => 'awyiss',
			/*
			 * If your MySQL server is configured with `skip-character-set-client-handshake`
			 * then you MUST use the `flags` config to set your charset encoding.
			 * For e.g. `'flags' => [\PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8mb4']`
			 */
			'flags' => [],
			/*
			 * During development, if using MySQL < 5.6, uncommenting the
			 * following line could boost the speed at which schema metadata is
			 * fetched from the database. It can also be set directly with the
			 * mysql configuration directive 'innodb_stats_on_metadata = 0'
			 * which is the recommended value in production environments
			 */
			//'init' => ['SET GLOBAL innodb_stats_on_metadata = 0'],
			'password' => '7!s6Z*e.Qrpw@dQN',
			//'url' => env('DATABASE_URL', NULL), //You can use a DSN string to set the entire configuration
			'username' => 'awyiss',

		],
	],

	'Email' => [
		'default' => [
			'from' => NULL,
		]
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
			'className' => MailTransport::class, //To use the default php mail()
			//'className' => \Cake\Mailer\Transport\SmtpTransport::class, //To use a smtp server
			'client' => NULL,
			'host' => 'localhost',
			'password' => NULL,
			'port' => 25,
			'url' => env('EMAIL_TRANSPORT_DEFAULT_URL'),
			'username' => NULL,
		],
	],

	/*'Log' => [
		'error' => [
			'className' => \Cake\Log\Engine\SyslogLog::class,
			'levels' => ['warning', 'error', 'critical', 'alert', 'emergency'],
			'scopes' => FALSE,
		],
	],*/

	/*
	 * Security and encryption configuration
	 *
	 * - salt - A random string used in security hashing methods.
	 *   The salt value is also used as the encryption key.
	 *   You should treat it as extremely sensitive data.
	 */
	'Security' => [
		'salt' => env('SECURITY_SALT', 'a30817f37ea92c24528e179ef2e25c125209b0719700ff4b0bed301ee697067e'),
	],

	'Session' => [
		'cookie' => 'foobarcustomer',
	]
];
