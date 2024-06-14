<?php declare(strict_types=1);


use Awyiss\Awyiss;
use Awyiss\Middleware\ConfigMiddleware;
use Awyiss\Middleware\DesignMiddleware;
use Awyiss\Middleware\EventListenersMiddleware;
use Awyiss\Middleware\LocaleMiddleware;
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
			'connect-src' => [
				'self' => true,
				'blob' => true,
			],
			'default-src' => [],
			'font-src' => [
				'self' => true,
			],
			'frame-src' => [
				'self' => true,
			],
			'img-src' => [
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
				'allow' => Configure::read('Csp.styleSrc.allow'),
				'self' => true,
				'unsafe-inline' => true,
				'unsafe-eval' => false,
			],
			'style-src-elem' => [
				'allow' => Configure::read('Csp.styleSrc.allow'),
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


/** @var RouteBuilder $routes */
$routes->scope('/', function (RouteBuilder $routes): void {
	$routes->setRouteClass(AwyissRoute::class);

	$routes->connect(
		'/{lang}/{slug}',
		['prefix' => 'Frontend', 'controller' => 'frontend', 'action' => 'index'],
		['_name' => Awyiss::REALM_FRONTEND]
	)
	->setPatterns(['lang' => '[a-z]{2}'])
	->setPersist(['lang', 'slug']);

	$routes->connect('/*', ['prefix' => 'Frontend', 'controller' => 'frontend', 'action' => 'noLanguageFound']);
});
