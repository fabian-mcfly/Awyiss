<?php declare(strict_types=1);


return [
	/**
	 * Email transport profiles.
	 *
	 * @see https://book.cakephp.org/5/en/core-libraries/email.html#email-transport
	 */
	'EmailTransport' => [
		'default' => [
			'className' => \Cake\Mailer\Transport\MailTransport::class,
		],
		'smtp' => [
			'className' => \Cake\Mailer\Transport\SmtpTransport::class, //To use a smtp server
			'host' => '',
			'password' => '',
			'port' => 25,
			'tls' => true,
			'username' => '',
		],
		'debug' => [
			'className' => \Cake\Mailer\Transport\DebugTransport::class, //To not send any mails
		],
	],
];
