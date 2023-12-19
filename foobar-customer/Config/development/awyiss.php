<?php declare(strict_types=1);

return [
	'debug' => filter_var(env('DEBUG', TRUE), FILTER_VALIDATE_BOOLEAN),

	'Datasources' => [
		'default' => [
			'log' => TRUE,
		],
	],

	'EmailTransport' => [
		'default' => [
			'className' => \Cake\Mailer\Transport\DebugTransport::class, //To not send any mails
			//'className' => \Cake\Mailer\Transport\MailTransport::class, //To use the default php mail()
			//'className' => \Cake\Mailer\Transport\SmtpTransport::class, //To use a smtp server
		],
	],

	'Error' => [
		'errorLevel' => E_ALL,
	],
];
