<?php declare(strict_types=1);


use Awyiss\Awyiss;
use Awyiss\Middleware\ConfigMiddleware;
use Awyiss\Middleware\DesignMiddleware;
use Awyiss\Middleware\EventListenersMiddleware;
use Awyiss\Middleware\LocaleMiddleware;
use Awyiss\Middleware\RealmMiddleware;
use Awyiss\Routing\Route\AwyissRoute;
use Cake\Core\Configure;
use Cake\Http\Middleware\CspMiddleware;
use Cake\Routing\RouteBuilder;


/** @var RouteBuilder $routes */

$routes->registerMiddleware('config', new ConfigMiddleware());
$routes->registerMiddleware(
	'csp',
	new CspMiddleware(
		[
			'base-uri' => [
				'allow' => Configure::read('Csp.baseUri.allow'),
				'self' => true,
			],
			'connect-src' => [
				'allow' => Configure::read('Csp.connectSrc.allow'),
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
		], [
			'scriptNonce' => true,
			'styleNonce' => true,
		]
	)
);
$routes->registerMiddleware('eventListeners', new EventListenersMiddleware());
$routes->registerMiddleware('design', new DesignMiddleware());
$routes->registerMiddleware('requestLocale', new LocaleMiddleware());


$routes->scope('/', function (RouteBuilder $routeBuilder): void {
	$routeBuilder->setRouteClass(AwyissRoute::class);

	$routeBuilder->registerMiddleware('frontendRealm', new RealmMiddleware(Awyiss::REALM_FRONTEND));
	$routeBuilder->applyMiddleware('frontendRealm');

	// Load the configuration as early as possible to make it available for all other middleware
	$routeBuilder->applyMiddleware('config');

	// Load the event listeners as early as possible to possibly listen to middleware events
	$routeBuilder->applyMiddleware('eventListeners');

	$routeBuilder->applyMiddleware('csp');

	$routeBuilder->applyMiddleware('design');

	$routeBuilder->applyMiddleware('requestLocale');

	$routeBuilder->connect(
		'/sitemap',
		['prefix' => 'Frontend', 'controller' => 'Sitemap', 'action' => 'index'],
	)->setExtensions(['xml']);

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
		['prefix' => 'Frontend', 'controller' => 'Frontend', 'action' => 'incompleteUrl'],
		['_name' => Awyiss::REALM_FRONTEND . 'LanguageRoot']
	)->setPatterns([
		'lang' => '[a-z]{2}',
	])->setPersist(['lang']);

	$routeBuilder->connect(
		'/{slug}/*',
		['prefix' => 'Frontend', 'controller' => 'Frontend', 'action' => 'incompleteUrl'],
	)->setPatterns([
		'slug' => '[^:]{3,}',
	])->setPersist(['slug']);

	$routeBuilder->connect(
		'/*',
		['prefix' => 'Frontend', 'controller' => 'Frontend', 'action' => 'incompleteUrl'],
		['_name' => Awyiss::REALM_FRONTEND . 'Root']
	);
});
