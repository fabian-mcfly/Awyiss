<?php declare(strict_types=1);

/*
 * Local configuration file to provide any overrides to our awyiss.php configuration
 * Note: It is not recommended committing files with credentials into source code version control.
 */


use Awyiss\Utility\Design\ScssVariableType;
use Cake\Mailer\Transport\MailTransport;


return [
	'Csp' => [
		'connectSrc' => [
			'allow' => [
				'https://tiles.versatiles.org',
			],
		],
		'fontSrc' => [
			'allow' => [
				'https://fonts.gstatic.com',
			],
		],
		'frameSrc' => [
			'allow' => [
				'https://player.vimeo.com',
				'https://www.youtube-nocookie.com',
			],
		],
		'styleSrcElem' => [
			'allow' => [
				'https://fonts.googleapis.com',
			],
		],
	],

	'debug' => false,

	'Design' => [
		/**
		 * Blocklisted variables that should not be shown in the designer.
		 * If a variable name contains a regex pattern but the exact variable name
		 * is set in the variableMapping, the variable will still be shown.
		 */
		'blocklistedVariables' => ['includeColumnSystem'],
		'fontStacks' => [
			'sans-serif' => [
				'Arial, Helvetica, sans-serif',
				'Verdana, Geneva, sans-serif',
				'Trebuchet MS, Helvetica, sans-serif',
				'Gill Sans, Arial, sans-serif',
				'Calibri, Arial, sans-serif',
				'Tahoma, Geneva, sans-serif',
			],
			'serif' => [
				'Georgia, Times, Times New Roman, serif',
				'Palatino, Palatino Linotype, Georgia, serif',
				'Times New Roman, Times, Georgia, serif',
				'MS Serif, New York, serif',
				'Book Antiqua, Palatino, serif',
			],
			'monospace' => [
				'Courier, Courier New, monospace',
				'Lucida Console, Monaco, monospace',
				'Consolas, Courier New, monospace',
			],
			'display' => [
				'Impact, Charcoal, sans-serif',
				'Arial Black, Gadget, sans-serif',
				'Comic Sans MS, cursive, sans-serif',
			],
			'handwriting' => [
				'Comic Sans MS, cursive',
				'Lucida Handwriting, cursive',
				'Brush Script MT, cursive',
			],
		],
		'previewScssFiles' => defined('CUSTOM_DIR') ? [ROOT . DS . CUSTOM_DIR . '/assets/scss/full.scss'] : null,
		'scssFiles' => defined('CUSTOM_DIR') ? [ROOT . DS . CUSTOM_DIR . '/assets/scss/helper/_variables.scss'] : null,
		'units' => [
			'px' => [
				'range' => [
					'min' => 0,
					'max' => 100,
				],
				'step' => 1,
			],
			'rem' => [
				'range' => [
					'min' => 0,
					'max' => 10,
				],
				'step' => .001,
			],
			'vw' => [
				'range' => [
					'min' => 0,
					'max' => 100,
				],
				'step' => .01,
			],
			'%' => [
				'range' => [
					'min' => 0,
					'max' => 100,
				],
				'step' => .01,
			],
		],
		'variableMapping' => [
			'fontName([A-Z]\w+)' => [
				'associatedVariables' => [
					'fontStackFallback$1',
					'fontStack$1',
					'fontWeight$1',
					'fontStyle$1',
					'fontSize$1',
					'lineHeight$1',
					'fontSizeClamp$1',
				],
				'category' => 'fonts',
				'group' => '$1',
				'type' => ScssVariableType::FontName,
			],
			'fontStackFallback([A-Z]\w+)' => [
				'category' => 'fonts',
				'group' => '$1',
				'type' => ScssVariableType::FontStack,
			],
			'fontSize(?!Clamp)([A-Z]\w+)' => [
				'category' => 'fonts',
				'group' => '$1',
				'inputType' => 'range',
				'type' => ScssVariableType::Number,
				'units' => [
					'rem' => [
						'range' => [
							'max' => 20,
						],
					],
					'em' => [
						'range' => [
							'min' => 0,
							'max' => 20,
						],
						'step' => .001,
					],
				],
			],
			'fontStyle([A-Z]\w+)' => [
				'category' => 'fonts',
				'group' => '$1',
				'options' => [
					'normal',
					'italic',
				],
				'type' => ScssVariableType::String,
			],
			'fontWeight([A-Z]\w+)' => [
				'category' => 'fonts',
				'group' => '$1',
				'type' => ScssVariableType::FontWeight,
			],
			'lineHeight([A-Z]\w+)' => [
				'category' => 'fonts',
				'group' => '$1',
				'inputType' => 'range',
				'type' => ScssVariableType::Number,
				'units' => [
					'%' => [
						'range' => [
							'max' => 1000,
						],
						'step' => 1,
					],
					'' => [
						'range' => [
							'min' => 0,
							'max' => 10,
						],
						'step' => .01,
					],
				],
			],
			'color([A-Z]\w+)' => [
				'category' => 'colors',
				'type' => ScssVariableType::Color,
			],
			'([a-z]\w+)Width' => [
				'forcedUnit' => 'px',
				'inputType' => 'range',
				'stripUnit' => true,
				'type' => ScssVariableType::Number,
				'units' => [
					'px' => [
						'range' => [
							'min' => 320,
							'max' => 3840,
						],
					],
				],
			],
			'([a-z]\w+)Padding' => [
				'inputType' => 'range',
				'type' => ScssVariableType::Number,
				'units' => [
					'%' => false,
				],
			],
			'([a-z]\w+)Breakpoint' => [
				'forcedUnit' => 'px',
				'inputType' => 'range',
				'stripUnit' => true,
				'type' => ScssVariableType::Number,
				'units' => [
					'px' => [
						'range' => [
							'min' => 0,
							'max' => 3840,
						],
					],
				],
			],
			'([a-z]\w+)Margin' => [
				'inputType' => 'range',
				'type' => ScssVariableType::Number,
			],
		],
	],

	/**
	 * Email profiles
	 *
	 * @see https://book.cakephp.org/5/en/core-libraries/email.html#configuration-profiles
	 */
	'Email' => [],

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

	'Instagram' => [
		'userName' => null,
		'password' => null,
		'mediaFolderId' => 1,
		'imapUserName' => null,
		'imapPassword' => null,
		'imapServer' => null,
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
