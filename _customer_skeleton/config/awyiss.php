<?php declare(strict_types=1);

/*
 * Local configuration file to provide any overrides to our awyiss.php configuration
 * Note: It is not recommended committing files with credentials into source code version control.
 */


use Cake\Mailer\Transport\MailTransport;


return [
	'debug' => false,

	'Email' => [
		'default' => [
			'from' => null,
		],
	],

	/*
	 * Email configuration.
	 *
	 * Host and credential configuration in case you are using SmtpTransport
	 *
	 * See awyiss/config/awyiss.php for more configuration options.
	 */
	'EmailTransport' => [
		'default' => [
			//'className' => \Cake\Mailer\Transport\DebugTransport::class, //To not send any mails
			'className' => MailTransport::class, //To use the default php mail()
			//'className' => \Cake\Mailer\Transport\SmtpTransport::class, //To use a smtp server
			'client' => null,
			'host' => 'localhost',
			'password' => null,
			'port' => 25,
			'url' => env('EMAIL_TRANSPORT_DEFAULT_URL'),
			'username' => null,
		],
	],

	'MimeTypes' => [
		//Example to add a valid file extension to a mimetype
		'application/zip' => ['sh3d'],
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
		'salt' => env('SECURITY_SALT', 'dummy-salt'),
	],

	'Session' => [
		'cookie' => '',
	],
];
