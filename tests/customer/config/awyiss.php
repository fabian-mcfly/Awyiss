<?php declare(strict_types=1);


use Awyiss\Utility\Design\ScssVariableType;
use Cake\Mailer\Transport\DebugTransport;
use Cake\Mailer\Transport\MailTransport;
use Cake\Mailer\Transport\SmtpTransport;


return [
	'Design' => [
		/**
		 * Blocklisted variables that should not be shown in the designer.
		 *
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
		'previewScssFiles' => [
			ROOT . DS . CUSTOM_DIR . '/assets/scss/test.scss',
		],
		'scssFiles' => [
			ROOT . DS . CUSTOM_DIR . '/assets/scss/_variables.scss',
		],
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
	 * Email transport profiles.
	 *
	 * @see https://book.cakephp.org/5/en/core-libraries/email.html#email-transport
	 */
	'EmailTransport' => [
		'default' => [
			'className' => MailTransport::class,
		],
		'smtp' => [
			'className' => SmtpTransport::class, //To use a smtp server
			'host' => '',
			'password' => '',
			'port' => 25,
			'tls' => true,
			'username' => '',
		],
		'debug' => [
			'className' => DebugTransport::class, //To not send any mails
		],
	],
	'SomeDebugKey' => 'someDebugValue',
];
