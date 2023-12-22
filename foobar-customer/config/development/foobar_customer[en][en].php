<?php declare(strict_types=1);

return [
	'Awyiss' => [
		'ContentTemplates' => [
			'Frontend' => [
				'contentrowExcludedContentTemplateIds' => '13',
			],
			'Backend' => [
				'paginate' => [
					'limit' => 20,
				],
			],
		],
		'Designers' => [
			'Frontend' => [
				'pageFullwidth' => '1350',
				'singlecolumnBreakpoint' => '860',
			],
		],
		'Matches' => [
			'Backend' => [
				'enddate' => '1',
				'endtime' => '0',
				'starttime' => '1',
			],
		],
		'Media' => [
			'Frontend' => [
				'defaultBreakpoints' => [
					2560,
					1920,
					1680,
					1280,
					1024,
					768,
					640,
					480,
					360,
				],
			],
		],
		'News' => [
			'Backend' => [
				'starttime' => '2',
			],
		],
		'PageRoles' => [
			'Backend' => [
				'autoCreateMenuEntries' => true,
			],
		],
		'System' => [
			'Backend' => [
				'lockTimeout' => 600,
				'meta' => [
					'titleAppendix' => 'Bäckänd auf änglisch',
					'titleSeparator' => ' | ',
				],
			],
			'Frontend' => [
				'meta' => [
					'titleAppendix' => 'Würzburger Kickers Football',
					'titleSeparator' => ' | ',
				],
				'editlinks' => true,
			],
		],
		'Usergroups' => [
			'Backend' => [
				'paginate' => [
					'limit' => 10,
				],
				'search' => true,
			],
		],
		'PageTemplates' => [
			'Backend' => [
				'paginate' => [
					'limit' => 20,
				],
			],
		],
		'Users' => [
			'Backend' => [
				'search' => true,
				'paginate' => [
					'limit' => 20,
				],
			],
		],
	],
];