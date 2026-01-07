<?php declare(strict_types=1);


use Awyiss\Awyiss;
use Awyiss\Middleware\LocaleMiddleware;
use Awyiss\Utility\Inflector;
use Cake\Core\Configure;
use Cake\Utility\Text;


/**
 * @var \Cake\Routing\RouteBuilder $routeBuilder
 */

$customerCenterConfig = Configure::read('Route.CustomerCenter');

if (!$customerCenterConfig || !is_array($customerCenterConfig)) {
	return;
}

// Get default path and actions
$defaultPath = $customerCenterConfig['path'] ?? 'customer-center';
$defaultActions = $customerCenterConfig['actions'] ?? [];

if (empty($defaultActions)) {
	return;
}

// Get language configuration
$languageConfigs = $customerCenterConfig['languages'] ?? [];

$languages = LocaleMiddleware::getLanguages(Awyiss::REALM_FRONTEND);
foreach ($languages as $language) {
	// Get language-specific config or use default
	$langConfig = $languageConfigs[ $language->shortcode ] ?? [];
	$path = $langConfig['path'] ?? $defaultPath;

	// Merge language-specific actions with defaults, so any non-localized actions still get registered
	$langActions = $langConfig['actions'] ?? [];
	$actions = array_merge($defaultActions, $langActions);

	// Always create a route for the dashboard action
	$routeBuilder->connect(
		'/' . $language->shortcode . '/' . Text::slug($path) . '/*',
		[
			'prefix' => 'Frontend',
			'controller' => 'CustomerCenter',
			'action' => 'dashboard',
			'lang' => $language->shortcode,
		],
		[
			'_name' => Awyiss::REALM_FRONTEND . 'CustomerCenter' . Inflector::camelize($language->shortcode),
		]
	);

	// Register each action individually with hardcoded language
	foreach ($actions as $actionName => $visibleName) {
		$routeBuilder->connect(
			'/' . $language->shortcode . '/' . Text::slug($path) . '/' . Text::slug($visibleName) . '/*',
			[
				'prefix' => 'Frontend',
				'controller' => 'CustomerCenter',
				'action' => $actionName,
				'lang' => $language->shortcode,
			],
			[
				'_name' => Awyiss::REALM_FRONTEND . 'CustomerCenter' . Inflector::camelize($actionName) . Inflector::camelize($language->shortcode),
			]
		);
	}
}
