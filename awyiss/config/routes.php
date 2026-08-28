<?php declare(strict_types=1);


use Awyiss\Awyiss;
use Awyiss\Core\App;
use Cake\Core\Configure;
use Cake\Routing\RouteBuilder;


/**  @var \Cake\Routing\RouteBuilder $routes */

/** @var class-string<\Awyiss\Middleware\ConfigMiddleware> $configMiddlewareClass */
$configMiddlewareClass = App::className('Config', 'Middleware', 'Middleware');
$routes->registerMiddleware('config', new $configMiddlewareClass());

/** @var class-string<\Cake\Http\Middleware\CspMiddleware> $cspMiddlewareClass */
$cspMiddlewareClass = App::className('Csp', 'Http/Middleware', 'Middleware');
$routes->registerMiddleware(
	'csp',
	new $cspMiddlewareClass(
		[
			'base-uri' => [
				'allow' => Configure::read('Csp.baseUri.allow'),
				'self' => true,
			],
			'connect-src' => [
				'allow' => Configure::read('Csp.connectSrc.allow'),
				'self' => true,
			],
			'child-src' => [
				'allow' => Configure::read('Csp.childSrc.allow'),
				'self' => true,
				'blob' => true,
			],
			'default-src' => [
				'allow' => Configure::read('Csp.defaultSrc.allow'),
				'self' => true,
			],
			'font-src' => [
				'allow' => Configure::read('Csp.fontSrc.allow'),
				'self' => true,
			],
			'frame-src' => [
				'allow' => Configure::read('Csp.frameSrc.allow'),
				'self' => true,
			],
			'img-src' => [
				'allow' => Configure::read('Csp.imgSrc.allow'),
				'blob' => true,
				'self' => true,
				'data' => true
			],
			'script-src' => [
				'allow' => Configure::read('Csp.scriptSrc.allow'),
				'blob' => true,
				'self' => true,
				'unsafe-inline' => false,
				'unsafe-eval' => false,
			],
			'style-src' => [
				'allow' => Configure::read('Csp.styleSrc.allow'),
				'self' => true,
				'unsafe-inline' => true,
				'unsafe-eval' => false,
			],
			'style-src-attr' => [
				'allow' => Configure::read('Csp.styleSrcAttr.allow'),
				'self' => true,
				'unsafe-inline' => true,
				'unsafe-eval' => false,
			],
			'style-src-elem' => [
				'allow' => Configure::read('Csp.styleSrcElem.allow'),
				'self' => true,
				'unsafe-inline' => true,
				'unsafe-eval' => false,
			],
			'worker-src' => [
				'allow' => Configure::read('Csp.workerSrcElem.allow'),
				'blob' => true,
				'self' => true,
				'unsafe-inline' => false,
				'unsafe-eval' => false,
			],
		], [
			'scriptNonce' => true,
			'styleNonce' => true,
		]
	)
);

/** @var class-string<\Awyiss\Middleware\EventListenersMiddleware> $eventListenersMiddlewareClass */
$eventListenersMiddlewareClass = App::className('EventListeners', 'Middleware', 'Middleware');
$routes->registerMiddleware('eventListeners', new $eventListenersMiddlewareClass());

/** @var class-string<\Awyiss\Middleware\DesignMiddleware> $designMiddlewareClass */
$designMiddlewareClass = App::className('Design', 'Middleware', 'Middleware');
$routes->registerMiddleware('design', new $designMiddlewareClass());

/** @var class-string<\Awyiss\Middleware\LocaleMiddleware> $localeMiddlewareClass */
$localeMiddlewareClass = App::className('Locale', 'Middleware', 'Middleware');
$routes->registerMiddleware('requestLocale', new $localeMiddlewareClass());


$routes->scope('/', function (RouteBuilder $routeBuilder): void {
	/** @uses \Awyiss\Routing\Route\AwyissRoute */
	$routeBuilder->setRouteClass(App::className('Awyiss', 'Routing/Route', 'Route'));

	/** @var class-string<\Awyiss\Middleware\RealmMiddleware> $realmMiddlewareClass */
	$realmMiddlewareClass = App::className('Realm', 'Middleware', 'Middleware');
	$routeBuilder->registerMiddleware('frontendRealm', new $realmMiddlewareClass(Awyiss::REALM_FRONTEND));
	$routeBuilder->applyMiddleware('frontendRealm');

	// Load the configuration as early as possible to make it available for all other middleware
	$routeBuilder->applyMiddleware('config');

	// Load the event listeners as early as possible to possibly listen to middleware events
	$routeBuilder->applyMiddleware('eventListeners');

	/** @var class-string<\Awyiss\Authentication\AuthenticationService> $authenticationClass */
	$authenticationClass = App::className('Authentication', 'Authentication');
	$authentication = new $authenticationClass(Awyiss::REALM_FRONTEND);
	/** @var class-string<\Awyiss\Middleware\AuthenticationMiddleware> $authenticationMiddlewareClass */
	$authenticationMiddlewareClass = App::className('Authentication', 'Middleware', 'Middleware');
	$routeBuilder->registerMiddleware('frontendAuthentication', new $authenticationMiddlewareClass($authentication));
	$routeBuilder->applyMiddleware('frontendAuthentication');

	$routeBuilder->applyMiddleware('csp');

	$routeBuilder->applyMiddleware('design');

	$routeBuilder->applyMiddleware('requestLocale');


	/**
	 * Load the general routes
	 * - for the environment in the custom_dir
	 * - for the custom_dir
	 */
	if (defined('CUSTOM_CONFIG')) {
		$file = ENV_CUSTOM_CONFIG . 'routes.php';
		if (is_file($file)) {
			include $file;
		}

		$file = CUSTOM_CONFIG . 'routes.php';
		if (is_file($file)) {
			include $file;
		}
	}


	/**
	 * Sitemap and robots.txt routes
	 */
	$routeBuilder->connect(
		'/robots',
		['prefix' => 'Frontend', 'controller' => 'Sitemap', 'action' => 'robots'],
	)->setExtensions(['txt']);

	$routeBuilder->connect(
		'/sitemap',
		['prefix' => 'Frontend', 'controller' => 'Sitemap', 'action' => 'index'],
	)->setExtensions(['xml']);


	/**
	 * Third-party consent tracking route
	 */
	$routeBuilder->connect(
		'/_third-party-consent',
		['prefix' => 'Frontend', 'controller' => 'ThirdPartyConsents', 'action' => 'track'],
	)->setMethods([
		'POST',
	]);


	/**
	 * Form and route planner routes always
	 * include the language shortcode as it is
	 * the only way to identify the language
	 */
	$routeBuilder->connect(
		'/{lang}/_form/*',
		['prefix' => 'Frontend', 'controller' => 'Form', 'action' => 'antiSpam'],
		['_name' => Awyiss::REALM_FRONTEND . 'FormAntiSpamPost']
	)->setMethods([
		'POST',
	])->setPatterns([
		'lang' => '[a-z]{2}',
	])->setPersist(['lang']);

	$routeBuilder->connect(
		'/{lang}/_form/{formEntry}',
		['prefix' => 'Frontend', 'controller' => 'Form', 'action' => 'antiSpam'],
		['_name' => Awyiss::REALM_FRONTEND . 'FormAntiSpamGet']
	)->setMethods([
		'GET',
	])->setPatterns([
		'lang' => '[a-z]{2}',
		'formEntry' => '[a-z0-9]{32}',
	])->setPersist(['lang']);


	/**
	 * Route planner routes
	 */
	$routeBuilder->connect(
		'/{lang}/_route/start:{start}/end:{end}/*',
		['prefix' => 'Frontend', 'controller' => 'Route', 'action' => 'route'],
	)->setMethods([
		'GET',
	])->setPass(['start', 'end'])->setPatterns([
		'lang' => '[a-z]{2}',
	])->setPersist(['lang']);

	$routeBuilder->connect(
		'/{lang}/_route/find-coordinates/{search}',
		['prefix' => 'Frontend', 'controller' => 'Route', 'action' => 'findCoordinates'],
	)->setMethods([
		'GET',
	])->setPass(['search'])->setPatterns([
		'lang' => '[a-z]{2}',
	])->setPersist(['lang']);


	/**
	 * Open Graph routes
	 */
	$routeBuilder->connect(
		'/_open-graph-image/id:{id}/',
		['prefix' => 'Frontend', 'controller' => 'OpenGraph', 'action' => 'image'],
	)->setMethods([
		'GET',
	])->setPatterns([
		'id' => '[0-9]+',
	])->setPass(['id']);

	$routeBuilder->connect(
		'/_open-graph-template/id:{id}/',
		['prefix' => 'Frontend', 'controller' => 'OpenGraph', 'action' => 'template'],
	)->setMethods([
		'GET',
	])->setPatterns([
		'id' => '[0-9]+',
	])->setPass(['id']);


	/**
	 * Survey route always
	 * include the language shortcode as it is
	 * the only way to identify the language
	 */
	$routeBuilder->connect(
		'/{lang}/_survey/{identifier}/{hash}',
		['prefix' => 'Frontend', 'controller' => 'Survey', 'action' => 'ajaxStep'],
		//['_name' => Awyiss::REALM_FRONTEND . 'SurveyAjaxStep']
	)->setMethods([
		'POST',
	])->setPass([
		'identifier',
		'hash',
	])->setPatterns([
		'lang' => '[a-z]{2}',
	])->setPersist(['lang']);


	/**
	 * Load customer center routes from separate file
	 */
	$file = APP . 'config' . DS . 'routes_customer_center.php';
	if (is_file($file)) {
		include $file;
	}

	/**
	 * Frontend catch-all route, either with or without language shortcode
	 */
	if (Configure::read('Route.includeLanguageShortcode')) {
		$routeBuilder->connect(
			'/{lang}/{slug}/*',
			['prefix' => 'Frontend', 'controller' => 'Frontend', 'action' => 'index'],
			['_name' => Awyiss::REALM_FRONTEND]
		)->setPatterns([
			'lang' => '[a-z]{2}',
			'slug' => '[^:]{3,}',
		])->setPersist(['lang', 'slug']);

		$routeBuilder->connect(
			'/{lang}/*',
			['prefix' => 'Frontend', 'controller' => 'Frontend', 'action' => 'index'],
			['_name' => Awyiss::REALM_FRONTEND . 'LanguageRoot']
		)->setPatterns([
			'lang' => '[a-z]{2}',
		])->setPersist(['lang']);

		$routeBuilder->connect(
			'/{slug}/*',
			['prefix' => 'Frontend', 'controller' => 'Frontend', 'action' => 'index'],
		)->setPatterns([
			'slug' => '[^:]{3,}',
		])->setPersist(['slug']);
	}
	else {
		$routeBuilder->connect(
			'/{slug}/*',
			['prefix' => 'Frontend', 'controller' => 'Frontend', 'action' => 'index'],
			['_name' => Awyiss::REALM_FRONTEND]
		)->setPatterns([
			'slug' => '[^:]{3,}',
		])->setPersist(['slug']);
	}


	$routeBuilder->connect(
		'/*',
		['prefix' => 'Frontend', 'controller' => 'Frontend', 'action' => 'index'],
		['_name' => Awyiss::REALM_FRONTEND . 'Root']
	);
});
