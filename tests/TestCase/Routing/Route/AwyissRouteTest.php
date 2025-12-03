<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Routing\Route;


use Awyiss\Routing\Route\AwyissRoute;
use Awyiss\Test\TestSuite\TestCase;


/**
 * Test case for AwyissRoute
 *
 * @see \Awyiss\Routing\Route\AwyissRoute
 */
class AwyissRouteTest extends TestCase {
	/**
	 * @return array
	 */
	public static function parseDataProvider(): array {
		return [
			'robots' => [
				'template' => '/robots',
				'defaults' => [
					'prefix' => 'Frontend',
					'controller' => 'Sitemap',
					'action' => 'robots',
					'plugin' => null,
				],
				'options' => [
					'_ext' => [],
				],
				'testUrls' => ['/robots'],
				'expectedResults' => [
					[
						'parts' => [],
						'slug' => '',
						'lang' => null,
						'controller' => 'Sitemap',
						'action' => 'robots',
						'prefix' => 'Frontend',
					],
				],
			],
			'sitemap' => [
				'template' => '/sitemap',
				'defaults' => [
					'prefix' => 'Frontend',
					'controller' => 'Sitemap',
					'action' => 'index',
					'plugin' => null,
				],
				'options' => [
					'_ext' => [],
				],
				'testUrls' => ['/sitemap'],
				'expectedResults' => [
					[
						'parts' => [],
						'slug' => '',
						'lang' => null,
						'controller' => 'Sitemap',
						'action' => 'index',
						'prefix' => 'Frontend',
					],
				],
			],
			'third_party_consent' => [
				'template' => '/_third-party-consent',
				'defaults' => [
					'prefix' => 'Frontend',
					'controller' => 'ThirdPartyConsents',
					'action' => 'track',
					'plugin' => null,
				],
				'options' => [
					'_ext' => [],
				],
				'testUrls' => ['/_third-party-consent'],
				'expectedResults' => [
					[
						'parts' => [],
						'slug' => '',
						'lang' => null,
						'controller' => 'ThirdPartyConsents',
						'action' => 'track',
						'prefix' => 'Frontend',
					],
				],
			],
			'form_anti_spam_post' => [
				'template' => '/{lang}/_form/*',
				'defaults' => [
					'prefix' => 'Frontend',
					'controller' => 'Form',
					'action' => 'antiSpam',
					'plugin' => null,
				],
				'options' => [
					'_name' => 'FrontendFormAntiSpamPost',
					'_ext' => [],
				],
				'testUrls' => ['/en/_form/token:abc123', '/de/_form/data:value/type:submit'],
				'expectedResults' => [
					[
						'parts' => ['token' => 'abc123'],
						'slug' => '',
						'lang' => 'en',
						'controller' => 'Form',
						'action' => 'antiSpam',
						'prefix' => 'Frontend',
					],
					[
						'parts' => ['data' => 'value', 'type' => 'submit'],
						'slug' => '',
						'lang' => 'de',
						'controller' => 'Form',
						'action' => 'antiSpam',
						'prefix' => 'Frontend',
					],
				],
			],
			'form_anti_spam_get' => [
				'template' => '/{lang}/_form/{formEntry}',
				'defaults' => [
					'prefix' => 'Frontend',
					'controller' => 'Form',
					'action' => 'antiSpam',
					'plugin' => null,
				],
				'options' => [
					'_name' => 'FrontendFormAntiSpamGet',
					'_ext' => [],
				],
				'testUrls' => ['/en/_form/contact-form', '/de/_form/newsletter-signup'],
				'expectedResults' => [
					[
						'parts' => [],
						'slug' => '',
						'lang' => 'en',
						'controller' => 'Form',
						'action' => 'antiSpam',
						'prefix' => 'Frontend',
					],
					[
						'parts' => [],
						'slug' => '',
						'lang' => 'de',
						'controller' => 'Form',
						'action' => 'antiSpam',
						'prefix' => 'Frontend',
					],
				],
			],
			'route_with_params' => [
				'template' => '/{lang}/_route/start:{start}/end:{end}/*',
				'defaults' => [
					'prefix' => 'Frontend',
					'controller' => 'Route',
					'action' => 'route',
					'plugin' => null,
				],
				'options' => [
					'_ext' => [],
				],
				'testUrls' => ['/en/_route/start:berlin/end:munich/mode:car', '/de/_route/start:hamburg/end:cologne/via:dortmund/type:fastest'],
				'expectedResults' => [
					[
						'parts' => ['mode' => 'car'],
						'slug' => '',
						'lang' => 'en',
						'controller' => 'Route',
						'action' => 'route',
						'prefix' => 'Frontend',
					],
					[
						'parts' => ['via' => 'dortmund', 'type' => 'fastest'],
						'slug' => '',
						'lang' => 'de',
						'controller' => 'Route',
						'action' => 'route',
						'prefix' => 'Frontend',
					],
				],
			],
			'find_coordinates' => [
				'template' => '/{lang}/_route/find-coordinates/{search}',
				'defaults' => [
					'prefix' => 'Frontend',
					'controller' => 'Route',
					'action' => 'findCoordinates',
					'plugin' => null,
				],
				'options' => [
					'_ext' => [],
				],
				'testUrls' => ['/en/_route/find-coordinates/berlin-mitte', '/de/_route/find-coordinates/munich-center'],
				'expectedResults' => [
					[
						'parts' => [],
						'slug' => '',
						'lang' => 'en',
						'controller' => 'Route',
						'action' => 'findCoordinates',
						'prefix' => 'Frontend',
					],
					[
						'parts' => [],
						'slug' => '',
						'lang' => 'de',
						'controller' => 'Route',
						'action' => 'findCoordinates',
						'prefix' => 'Frontend',
					],
				],
			],
			'frontend_with_slug' => [
				'template' => '/{lang}/{slug}/*',
				'defaults' => [
					'prefix' => 'Frontend',
					'controller' => 'Frontend',
					'action' => 'index',
					'plugin' => null,
				],
				'options' => [
					'_name' => 'Frontend',
					'_ext' => [],
				],
				'testUrls' => ['/en/about-us/section:team', '/de/products/category:electronics/brand:apple'],
				'expectedResults' => [
					[
						'parts' => ['section' => 'team'],
						'slug' => 'about-us',
						'lang' => 'en',
						'controller' => 'Frontend',
						'action' => 'index',
						'prefix' => 'Frontend',
					],
					[
						'parts' => ['category' => 'electronics', 'brand' => 'apple'],
						'slug' => 'products',
						'lang' => 'de',
						'controller' => 'Frontend',
						'action' => 'index',
						'prefix' => 'Frontend',
					],
				],
			],
			'frontend_language_root' => [
				'template' => '/{lang}/*',
				'defaults' => [
					'prefix' => 'Frontend',
					'controller' => 'Frontend',
					'action' => 'index',
					'plugin' => null,
				],
				'options' => [
					'_name' => 'FrontendLanguageRoot',
					'_ext' => [],
				],
				'testUrls' => ['/en/page:homepage', '/de/section:news/category:tech'],
				'expectedResults' => [
					[
						'parts' => ['page' => 'homepage'],
						'slug' => '',
						'lang' => 'en',
						'controller' => 'Frontend',
						'action' => 'index',
						'prefix' => 'Frontend',
					],
					[
						'parts' => ['section' => 'news', 'category' => 'tech'],
						'slug' => '',
						'lang' => 'de',
						'controller' => 'Frontend',
						'action' => 'index',
						'prefix' => 'Frontend',
					],
				],
			],
			'frontend_slug_only' => [
				'template' => '/{slug}/*',
				'defaults' => [
					'prefix' => 'Frontend',
					'controller' => 'Frontend',
					'action' => 'index',
					'plugin' => null,
				],
				'options' => [
					'_ext' => [],
				],
				'testUrls' => ['/home/section:main', '/contact/form:enabled/type:general'],
				'expectedResults' => [
					[
						'parts' => ['section' => 'main'],
						'slug' => 'home',
						'lang' => null,
						'controller' => 'Frontend',
						'action' => 'index',
						'prefix' => 'Frontend',
					],
					[
						'parts' => ['form' => 'enabled', 'type' => 'general'],
						'slug' => 'contact',
						'lang' => null,
						'controller' => 'Frontend',
						'action' => 'index',
						'prefix' => 'Frontend',
					],
				],
			],
			'frontend_root' => [
				'template' => '/*',
				'defaults' => [
					'prefix' => 'Frontend',
					'controller' => 'Frontend',
					'action' => 'index',
					'plugin' => null,
				],
				'options' => [
					'_name' => 'FrontendRoot',
					'_ext' => [],
				],
				'testUrls' => ['/welcome:true', '/feature:new/status:active'],
				'expectedResults' => [
					[
						'parts' => ['welcome' => 'true'],
						'slug' => '',
						'lang' => null,
						'controller' => 'Frontend',
						'action' => 'index',
						'prefix' => 'Frontend',
					],
					[
						'parts' => ['feature' => 'new', 'status' => 'active'],
						'slug' => '',
						'lang' => null,
						'controller' => 'Frontend',
						'action' => 'index',
						'prefix' => 'Frontend',
					],
				],
			],
			'backend_with_id' => [
				'template' => '/backend/{lang}/{controller}/{action}/id:{id}/*',
				'defaults' => [
					'prefix' => 'Backend',
					'plugin' => null,
					'action' => 'index',
				],
				'options' => [
					'_ext' => [],
				],
				'testUrls' => ['/backend/en/users/edit/id:123/tab:profile', '/backend/de/products/view/id:456/mode:detailed'],
				'expectedResults' => [
					[
						'parts' => ['tab' => 'profile'],
						'slug' => '',
						'lang' => 'en',
						'controller' => 'Users',
						'action' => 'edit',
						'prefix' => 'Backend',
					],
					[
						'parts' => ['mode' => 'detailed'],
						'slug' => '',
						'lang' => 'de',
						'controller' => 'Products',
						'action' => 'view',
						'prefix' => 'Backend',
					],
				],
			],
			'backend_with_action' => [
				'template' => '/backend/{lang}/{controller}/{action}/*',
				'defaults' => [
					'action' => 'overview',
					'prefix' => 'Backend',
					'plugin' => null,
				],
				'options' => [
					'_name' => 'Backend',
					'_ext' => [],
				],
				'testUrls' => ['/backend/en/users/list/filter:active', '/backend/de/orders/export/format:csv'],
				'expectedResults' => [
					[
						'parts' => ['filter' => 'active'],
						'slug' => '',
						'lang' => 'en',
						'controller' => 'Users',
						'action' => 'list',
						'prefix' => 'Backend',
					],
					[
						'parts' => ['format' => 'csv'],
						'slug' => '',
						'lang' => 'de',
						'controller' => 'Orders',
						'action' => 'export',
						'prefix' => 'Backend',
					],
				],
			],
			'backend_controller_only' => [
				'template' => '/backend/{lang}/{controller}/*',
				'defaults' => [
					'action' => 'overview',
					'prefix' => 'Backend',
					'plugin' => null,
				],
				'options' => [
					'_ext' => [],
				],
				'testUrls' => ['/backend/en/dashboard/widget:stats', '/backend/de/reports/type:monthly'],
				'expectedResults' => [
					[
						'parts' => ['widget' => 'stats'],
						'slug' => '',
						'lang' => 'en',
						'controller' => 'Dashboard',
						'action' => 'overview',
						'prefix' => 'Backend',
					],
					[
						'parts' => ['type' => 'monthly'],
						'slug' => '',
						'lang' => 'de',
						'controller' => 'Reports',
						'action' => 'overview',
						'prefix' => 'Backend',
					],
				],
			],
			'backend_language_only' => [
				'template' => '/backend/{lang}/*',
				'defaults' => [
					'controller' => 'dashboard',
					'action' => 'overview',
					'prefix' => 'Backend',
					'plugin' => null,
				],
				'options' => [
					'_ext' => [],
				],
				'testUrls' => ['/backend/en/view:main', '/backend/de/theme:dark/layout:grid'],
				'expectedResults' => [
					[
						'parts' => ['view' => 'main'],
						'slug' => '',
						'lang' => 'en',
						'controller' => 'Dashboard',
						'action' => 'overview',
						'prefix' => 'Backend',
					],
					[
						'parts' => ['theme' => 'dark', 'layout' => 'grid'],
						'slug' => '',
						'lang' => 'de',
						'controller' => 'Dashboard',
						'action' => 'overview',
						'prefix' => 'Backend',
					],
				],
			],
			'backend_root' => [
				'template' => '/backend/*',
				'defaults' => [
					'controller' => 'dashboard',
					'action' => 'overview',
					'prefix' => 'Backend',
					'plugin' => null,
				],
				'options' => [
					'_ext' => [],
				],
				'testUrls' => ['/backend/redirect:home', '/backend/mode:admin/access:full'],
				'expectedResults' => [
					[
						'parts' => ['redirect' => 'home'],
						'slug' => '',
						'lang' => null,
						'controller' => 'Dashboard',
						'action' => 'overview',
						'prefix' => 'Backend',
					],
					[
						'parts' => ['mode' => 'admin', 'access' => 'full'],
						'slug' => '',
						'lang' => null,
						'controller' => 'Dashboard',
						'action' => 'overview',
						'prefix' => 'Backend',
					],
				],
			],
		];
	}


	/**
	 * @return array
	 */
	public static function matchDataProvider(): array {
		return [
			'robots' => [
				'template' => '/robots',
				'defaults' => [
					'prefix' => 'Frontend',
					'controller' => 'Sitemap',
					'action' => 'robots',
					'plugin' => null,
				],
				'options' => [
					'_ext' => [],
				],
				'pattern' => [],
				'urls' => [
					['prefix' => 'Frontend', 'plugin' => null, 'controller' => 'Sitemap', 'action' => 'robots'],
					['prefix' => 'Frontend', 'plugin' => null, 'controller' => 'Sitemap', 'action' => 'other'],
				],
				'matches' => [
					'/robots/',
					'/robots/', // Action is not used, so it matches regardless of the action.
				],
			],
			'sitemap' => [
				'template' => '/sitemap',
				'defaults' => [
					'prefix' => 'Frontend',
					'controller' => 'Sitemap',
					'action' => 'index',
					'plugin' => null,
				],
				'options' => [
					'_ext' => [],
				],
				'pattern' => [],
				'urls' => [
					['prefix' => 'Frontend', 'plugin' => null, 'controller' => 'Sitemap', 'action' => 'sitemap'],
					['prefix' => 'Frontend', 'plugin' => null, 'controller' => 'Sitemap', 'action' => 'index'],
				],
				'matches' => [
					'/sitemap/', // Action is not used, so it matches regardless of the action.
					'/sitemap/',
				],
			],
			'third_party_consent' => [
				'template' => '/_third-party-consent',
				'defaults' => [
					'prefix' => 'Frontend',
					'controller' => 'ThirdPartyConsents',
					'action' => 'track',
					'plugin' => null,
				],
				'options' => [
					'_ext' => [],
				],
				'pattern' => [],
				'urls' => [
					['prefix' => 'Frontend', 'plugin' => null, 'controller' => 'ThirdPartyConsents', 'action' => 'track'],
					['prefix' => 'Frontend', 'plugin' => null, 'controller' => 'ThirdPartyConsents', 'action' => 'untrack'],
				],
				'matches' => [
					'/_third-party-consent/',
					'/_third-party-consent/', // Action is not used, so it matches regardless of the action.
				],
			],
			'form_anti_spam_post' => [
				'template' => '/{lang}/_form/*',
				'defaults' => [
					'prefix' => 'Frontend',
					'controller' => 'Form',
					'action' => 'antiSpam',
					'plugin' => null,
				],
				'options' => [
					'_name' => 'FrontendFormAntiSpamPost',
					'_ext' => [],
				],
				'pattern' => [
					'lang' => '[a-z]{2}',
				],
				'urls' => [
					[
						'prefix' => 'Frontend',
						'plugin' => null,
						'controller' => 'Form',
						'action' => 'antiSpam',
						'lang' => 'en',
						'token' => 'abc123',
					],
					[
						'prefix' => 'Frontend',
						'plugin' => null,
						'controller' => 'Other',
						'action' => 'antiSpam',
						'lang' => 'de',
						'data' => 'value',
						'type' => 'submit',
					],
					[
						'prefix' => 'Frontend',
						'plugin' => null,
						'controller' => 'Form',
						'action' => 'antiSpam',
						'lang' => 'de',
						'data' => 'value',
						'type' => 'submit',
					],
				],
				'matches' => [
					'/en/_form/token:abc123/',
					'/de/_form/data:value/type:submit/', // Controller is not used
					'/de/_form/data:value/type:submit/',
				],
			],
			'form_anti_spam_get' => [
				'template' => '/{lang}/_form/{formEntry}',
				'defaults' => [
					'prefix' => 'Frontend',
					'controller' => 'Form',
					'action' => 'antiSpam',
					'plugin' => null,
				],
				'options' => [
					'_name' => 'FrontendFormAntiSpamGet',
					'_ext' => [],
				],
				'pattern' => [
					'lang' => '[a-z]{2}',
					'formEntry' => '[a-z0-9]{32}',
				],
				'urls' => [
					[
						'prefix' => 'Frontend',
						'plugin' => null,
						'controller' => 'Form',
						'action' => 'antiSpam',
						'lang' => 'en',
						'formEntry' => md5((string)time()),
					],
					[
						'prefix' => 'Frontend',
						'plugin' => null,
						'controller' => 'Form',
						'action' => 'antiAntiSpam',
						'lang' => 'de',
						'formEntry' => 'newsletter-signup',
					],
				],
				'matches' => [
					'/en/_form/' . md5((string)time()) . '/',
					false, // Invalid form entry, does not match the pattern.
				],
			],
			'route_with_params' => [
				'template' => '/{lang}/_route/start:{start}/end:{end}/*',
				'defaults' => [
					'prefix' => 'Frontend',
					'controller' => 'Route',
					'action' => 'route',
					'plugin' => null,
				],
				'options' => [
					'_ext' => [],
				],
				'pattern' => [
					'lang' => '[a-z]{2}',
				],
				'urls' => [
					[
						'prefix' => 'Frontend',
						'plugin' => null,
						'controller' => 'Route',
						'action' => 'route',
						'lang' => 'en',
						'start' => 'berlin, central station',
						'end' => 'munich, hofbrauhaus',
						'mode' => 'car',
					],
					[
						'prefix' => 'Frontend',
						'plugin' => null,
						'controller' => 'Route',
						'action' => 'route',
						'lang' => 'abcdef',
						'start' => 'hamburg',
						'end' => 'cologne',
						'via' => 'dortmund',
						'type' => 'fastest',
					],
				],
				'matches' => [
					'/en/_route/start:berlin, central station/end:munich, hofbrauhaus/mode:car/',
					false, // Invalid language code, does not match the pattern.
				],
			],
			'find_coordinates' => [
				'template' => '/{lang}/_route/find-coordinates/{search}',
				'defaults' => [
					'prefix' => 'Frontend',
					'controller' => 'Route',
					'action' => 'findCoordinates',
					'plugin' => null,
				],
				'options' => [
					'_ext' => [],
				],
				'pattern' => [
					'lang' => '[a-z]{2}',
				],
				'urls' => [
					['prefix' => 'Frontend', 'plugin' => null, 'controller' => 'Route', 'action' => 'findCoordinates', 'lang' => 'en', 'search' => 'berlin-mitte'],
					['prefix' => 'Frontend', 'plugin' => null, 'controller' => 'Route', 'action' => 'findCoordinates', 'lang' => 'de', 'search' => 'munich-center'],
					['prefix' => 'Frontend', 'plugin' => null, 'controller' => 'Route', 'action' => 'findCoordinates', 'lang' => '123', 'search' => 'munich-center'],
				],
				'matches' => [
					'/en/_route/find-coordinates/berlin-mitte/',
					'/de/_route/find-coordinates/munich-center/',
					false, // Invalid language code, does not match the pattern.
				],
			],
			'frontend_with_slug' => [
				'template' => '/{lang}/{slug}/*',
				'defaults' => [
					'prefix' => 'Frontend',
					'controller' => 'Frontend',
					'action' => 'index',
					'plugin' => null,
				],
				'options' => [
					'_name' => 'Frontend',
					'_ext' => [],
				],
				'pattern' => [
					'lang' => '[a-z]{2}',
					'slug' => '[^:]{3,}',
				],
				'urls' => [
					[
						'prefix' => 'Frontend',
						'plugin' => null,
						'controller' => 'Frontend',
						'action' => 'index',
						'lang' => 'en',
						'slug' => 'about-us',
						'section' => 'team',
					],
					[
						'prefix' => 'Frontend',
						'plugin' => null,
						'controller' => 'Frontend',
						'action' => 'index',
						'lang' => 'abcde',
						'slug' => 'product-listing',
						'category' => 'electronics',
						'brand' => 'apple',
					],
					[
						'prefix' => 'Frontend',
						'plugin' => null,
						'controller' => 'Frontend',
						'action' => 'index',
						'lang' => 'de',
						'slug' => 'product-listing',
						'category' => 'electronics',
						'brand' => 'apple',
					],
				],
				'matches' => [
					'/en/about-us/section:team/',
					false, // Invalid language code, does not match the pattern.
					'/de/product-listing/category:electronics/brand:apple/',
				],
			],
			'frontend_language_root' => [
				'template' => '/{lang}/*',
				'defaults' => [
					'prefix' => 'Frontend',
					'controller' => 'Frontend',
					'action' => 'index',
					'plugin' => null,
				],
				'options' => [
					'_name' => 'FrontendLanguageRoot',
					'_ext' => [],
				],
				'pattern' => [
					'lang' => '[a-z]{2}',
				],
				'urls' => [
					[
						'prefix' => 'Frontend',
						'plugin' => null,
						'controller' => 'Frontend',
						'action' => 'index',
						'lang' => 'abcdef',
						'page' => 'home-page',
					],
					[
						'prefix' => 'Frontend',
						'plugin' => null,
						'controller' => 'Frontend',
						'action' => 'index',
						'lang' => 'de',
						'section' => 'news',
						'category' => 'tech',
					],
				],
				'matches' => [
					false, // Invalid language code, does not match the pattern.
					'/de/section:news/category:tech/',
				],
			],
			'frontend_slug_only' => [
				'template' => '/{slug}/*',
				'defaults' => [
					'prefix' => 'Frontend',
					'controller' => 'Frontend',
					'action' => 'index',
					'plugin' => null,
				],
				'options' => [
					'_ext' => [],
				],
				'pattern' => [
					'slug' => '[^:]{3,}',
				],
				'urls' => [
					['prefix' => 'Frontend', 'plugin' => null, 'controller' => 'Frontend', 'action' => 'index', 'slug' => 'home-page', 'section' => 'main'],
					['prefix' => 'Frontend', 'plugin' => null, 'controller' => 'Frontend', 'action' => 'index', 'slug' => 'contact-page', 'form' => 'enabled', 'type' => 'general'],
					['prefix' => 'Frontend', 'plugin' => null, 'controller' => 'Frontend', 'action' => 'index', 'slug' => false],
				],
				'matches' => [
					'/home-page/section:main/',
					'/contact-page/form:enabled/type:general/',
					false, // Slug cannot be false
				],
			],
			'frontend_root' => [
				'template' => '/*',
				'defaults' => [
					'prefix' => 'Frontend',
					'controller' => 'Frontend',
					'action' => 'index',
					'plugin' => null,
				],
				'options' => [
					'_name' => 'FrontendRoot',
					'_ext' => [],
				],
				'pattern' => [],
				'urls' => [
					['prefix' => 'Frontend', 'plugin' => null, 'controller' => 'Frontend', 'action' => 'index', 'welcome-param' => 'true'],
					['prefix' => 'Frontend', 'plugin' => null, 'controller' => 'Frontend', 'action' => 'index', 'feature' => 'new', 'status' => 'really-active'],
				],
				'matches' => [
					'/welcome-param:true/',
					'/feature:new/status:really-active/',
				],
			],
			'backend_with_id' => [
				'template' => '/backend/{lang}/{controller}/{action}/id:{id}/*',
				'defaults' => [
					'prefix' => 'Backend',
					'plugin' => null,
					'action' => 'index',
				],
				'options' => [
					'_ext' => [],
				],
				'pattern' => [
					'lang' => '[a-zA-Z]{2}',
					'controller' => '[a-zA-Z0-9-_]+',
					'action' => '(edit|restart|delete)',
					'id' => '[0-9]+',
				],
				'urls' => [
					['prefix' => 'Backend', 'plugin' => null, 'lang' => 'en', 'controller' => 'some-users', 'action' => 'edit', 'id' => '123', 'tab' => 'profile'],
					['prefix' => 'Backend', 'plugin' => null, 'lang' => 'de', 'controller' => 'products', 'action' => 'other-view', 'id' => '456', 'mode' => 'detailed'],
					['prefix' => 'Backend', 'plugin' => null, 'lang' => 'de', 'controller' => 'products', 'action' => 'delete', 'id' => '456', 'mode' => 'detailed'],
					['prefix' => 'Backend', 'plugin' => null, 'lang' => 'abcdef', 'controller' => 'products', 'action' => 'restart', 'id' => '456', 'mode' => 'detailed'],
				],
				'matches' => [
					'/backend/en/some-users/edit/id:123/tab:profile/',
					false, // Action must be 'edit', 'restart', or 'delete'
					'/backend/de/products/delete/id:456/mode:detailed/',
					false, // Language must be valid
				],
			],
			'backend_with_action' => [
				'template' => '/backend/{lang}/{controller}/{action}/*',
				'defaults' => [
					'action' => 'overview',
					'prefix' => 'Backend',
					'plugin' => null,
				],
				'options' => [
					'_name' => 'Backend',
					'_ext' => [],
				],
				'pattern' => [
					'lang' => '[a-zA-Z]{2}',
					'controller' => '[a-zA-Z0-9-_]+',
					'action' => '[a-zA-Z0-9-_]+',
				],
				'urls' => [
					['prefix' => 'Backend', 'plugin' => null, 'lang' => 'a', 'controller' => 'users', 'action' => 'other-list', 'filter' => 'active'],
					['prefix' => 'Backend', 'plugin' => null, 'lang' => 'en', 'controller' => 'users', 'action' => 'list-action', 'filter' => 'active'],
					['prefix' => 'Backend', 'plugin' => null, 'lang' => 'de', 'controller' => 'orders', 'action' => 'export', 'format' => 'csv'],
				],
				'matches' => [
					false, // Language must be valid
					'/backend/en/users/list-action/filter:active/',
					'/backend/de/orders/export/format:csv/',
				],
			],
			'backend_controller_only' => [
				'template' => '/backend/{lang}/{controller}/*',
				'defaults' => [
					'action' => 'overview',
					'prefix' => 'Backend',
					'plugin' => null,
				],
				'options' => [
					'_ext' => [],
				],
				'pattern' => [
					'lang' => '[a-zA-Z]{2}',
					'controller' => '[a-zA-Z0-9-_]+',
				],
				'urls' => [
					['prefix' => 'Backend', 'plugin' => null, 'action' => 'other-overview', 'lang' => 'en', 'controller' => 'dashboard-overview', 'widget' => 'stats'],
					['prefix' => 'Backend', 'plugin' => null, 'action' => 'other-overview', 'lang' => 'de', 'controller' => 'yearly-reports', 'type' => 'monthly'],
					['prefix' => 'Backend', 'plugin' => null, 'action' => 'other-overview', 'lang' => 'de', 'controller' => 'yearly/reports', 'type' => 'monthly'],
				],
				'matches' => [
					'/backend/en/dashboard-overview/widget:stats/',
					'/backend/de/yearly-reports/type:monthly/',
					false, // Controller must be valid
				],
			],
			'backend_language_only' => [
				'template' => '/backend/{lang}/*',
				'defaults' => [
					'controller' => 'dashboard',
					'action' => 'overview',
					'prefix' => 'Backend',
					'plugin' => null,
				],
				'options' => [
					'_ext' => [],
				],
				'pattern' => [
					'lang' => '[a-zA-Z]{2}',
				],
				'urls' => [
					['prefix' => 'Backend', 'plugin' => null, 'controller' => 'dashboard', 'action' => 'overview', 'lang' => 'en', 'view' => 'main'],
					['prefix' => 'Backend', 'plugin' => null, 'controller' => 'dashboard', 'action' => 'overview', 'lang' => 'de', 'theme' => 'dark', 'layout' => 'grid'],
					['prefix' => 'Backend', 'plugin' => null, 'controller' => 'dashboard', 'action' => 'overview', 'lang' => 'a', 'theme' => 'dark', 'layout' => 'grid'],
				],
				'matches' => [
					'/backend/en/view:main/',
					'/backend/de/theme:dark/layout:grid/',
					false, // Language must be valid
				],
			],
			'backend_root' => [
				'template' => '/backend/*',
				'defaults' => [
					'controller' => 'dashboard',
					'action' => 'overview',
					'prefix' => 'Backend',
					'plugin' => null,
				],
				'options' => [
					'_ext' => [],
				],
				'pattern' => [],
				'urls' => [
					['prefix' => 'Backend', 'plugin' => null, 'controller' => 'some-dashboard', 'action' => 'overview-action', 'redirect' => 'home'],
					['prefix' => 'Backend', 'plugin' => null, 'controller' => 'some-dashboard', 'action' => 'overview-action', 'mode' => 'admin', 'access' => 'full'],
				],
				'matches' => [
					'/backend/redirect:home/',
					'/backend/mode:admin/access:full/',
				],
			],
		];
	}


	/**
	 * @dataProvider parseDataProvider
	 * @param string $template
	 * @param array $defaults
	 * @param array $options
	 * @param array $testUrls
	 * @param array $expectedResults
	 * @return void
	 * @see \Awyiss\Routing\Route\AwyissRoute::parse()
	 */
	public function testParse(string $template, array $defaults, array $options, array $testUrls, array $expectedResults): void {
		$route = new AwyissRoute($template, $defaults, $options);

		// Test with matching URLs
		foreach ($testUrls as $index => $testUrl) {
			$result = $route->parse($testUrl);

			$this->assertIsArray($result);
			foreach ($expectedResults[ $index ] as $key => $value) {
				if ($value === null) {
					// The key might exist, and if it does, it should be null
					if (array_key_exists($key, $result)) {
						$this->assertNull($result[ $key ], sprintf('Failed asserting that the value for key %s is null', $key));
					}

					continue;
				}

				$this->assertArrayHasKey($key, $result);
				$this->assertEquals($value, $result[ $key ], sprintf('Failed asserting that the parsed result matches the expected result for key: %s', $key));
			}
		}
	}


	/**
	 * @dataProvider matchDataProvider
	 * @param string $template
	 * @param array $defaults
	 * @param array $options
	 * @param array $pattern
	 * @param array $urls
	 * @param array $matches
	 * @return void
	 * @see \Awyiss\Routing\Route\AwyissRoute::match()
	 */
	public function testMatch(string $template, array $defaults, array $options, array $pattern, array $urls, array $matches): void {
		$route = new AwyissRoute($template, $defaults, $options);

		if ($pattern) {
			$route->setPatterns($pattern);
		}

		// Test with matching URL arrays
		foreach ($urls as $key => $url) {
			$result = $route->match($url, [
				'_scheme' => 'https',
				'_host' => 'cms.de',
				'_base' => '',
			]);

			if ($matches[ $key ] === false) {
				$this->assertNull($result, sprintf('Expected match to fail for URL: %s', json_encode($url)));
				continue;
			}

			$this->assertSame($matches[ $key ], $result);
		}
	}
}
