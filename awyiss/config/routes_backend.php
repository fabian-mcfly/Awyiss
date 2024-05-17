<?php declare(strict_types=1);


use Awyiss\Authentication\Authentication;
use Awyiss\Authorization\Authorization;
use Awyiss\Awyiss;
use Awyiss\Middleware\AuthenticationMiddleware;
use Awyiss\Middleware\AuthorizationMiddleware;
use Awyiss\Middleware\ConfigMiddleware;
use Awyiss\Middleware\DesignMiddleware;
use Awyiss\Middleware\EventListenersMiddleware;
use Awyiss\Middleware\LocaleMiddleware;
use Awyiss\Routing\Route\AwyissRoute;
use Cake\Core\Configure;
use Cake\Http\Middleware\CspMiddleware;
use Cake\Routing\RouteBuilder;


/** @var RouteBuilder $routes */
$routes->prefix('Backend', function (RouteBuilder $routeBuilder): void {
	$routeBuilder->setRouteClass(AwyissRoute::class);

	$routeBuilder->registerMiddleware('csp', new CspMiddleware(
		[
			'script-src' => [
				'allow' => Configure::read('Csp.scriptSrc.allow'),
				'self' => true,
				'unsafe-inline' => false,
				'unsafe-eval' => false,
			],
			'style-src' => [
				'allow' => Configure::read('Csp.styleSrc.allow'),
				'self' => true,
				'unsafe-inline' => false,
				'unsafe-eval' => false,
			],
		],
		[
			'scriptNonce' => true,
			'styleNonce' => true,
		]
	));
	$routeBuilder->applyMiddleware('csp');

	$routeBuilder->registerMiddleware('config', new ConfigMiddleware(Awyiss::REALM_BACKEND));
	$routeBuilder->applyMiddleware('config');

	$routeBuilder->registerMiddleware('eventListeners', new EventListenersMiddleware(Awyiss::REALM_BACKEND));
	$routeBuilder->applyMiddleware('eventListeners');

	$routeBuilder->registerMiddleware('requestLocale', new LocaleMiddleware(Awyiss::REALM_BACKEND));
	$routeBuilder->applyMiddleware('requestLocale');

	$lo_authentication = new Authentication(Awyiss::REALM_BACKEND);
	$routeBuilder->registerMiddleware('authentication', new AuthenticationMiddleware($lo_authentication, Awyiss::REALM_BACKEND));
	$routeBuilder->applyMiddleware('authentication');

	$lo_authorization = new Authorization(Awyiss::REALM_BACKEND);
	$routeBuilder->registerMiddleware('authorization', new AuthorizationMiddleware($lo_authorization, Awyiss::REALM_BACKEND));
	$routeBuilder->applyMiddleware('authorization');

	$routeBuilder->registerMiddleware('design', new DesignMiddleware(Awyiss::REALM_BACKEND));
	$routeBuilder->applyMiddleware('design');

	$routeBuilder->connect('/{lang}/{controller}/{action}/id:{id}/*')->setPatterns([
		'lang' => '[a-zA-Z]{2}',
		'controller' => '[a-zA-Z0-9-_]+',
		'action' => '(edit|delete)',
		'id' => '[0-9]+',
	])->setPass(['id'])->setPersist(['lang', 'controller']);

	$routeBuilder->connect('/{lang}/{controller}/{action}/*', ['action' => 'overview'], ['_name' => Awyiss::REALM_BACKEND])->setPatterns([
		'lang' => '[a-zA-Z]{2}',
		'controller' => '[a-zA-Z0-9-_]+',
		'action' => '[a-zA-Z0-9-_]+',
	])->setPersist(['lang', 'controller', 'action']);

	$routeBuilder->connect('/{lang}/{controller}/*', ['action' => 'overview'])->setPatterns([
		'lang' => '[a-zA-Z]{2}',
		'controller' => '[a-zA-Z0-9-_]+',
	])->setPersist(['lang', 'controller']);

	$routeBuilder->connect('/{lang}/*', ['controller' => 'dashboard', 'action' => 'overview'])->setPatterns([
		'lang' => '[a-zA-Z]{2}',
	])->setPersist(['lang']);

	$routeBuilder->connect('/*', ['controller' => 'dashboard', 'action' => 'overview']);
});
