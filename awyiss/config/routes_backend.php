<?php declare(strict_types=1);


use Awyiss\Authentication\Authentication;
use Awyiss\Authorization\Authorization;
use Awyiss\Awyiss;
use Awyiss\Middleware\AuthenticationMiddleware;
use Awyiss\Middleware\AuthorizationMiddleware;
use Awyiss\Middleware\RealmMiddleware;
use Awyiss\Routing\Route\AwyissRoute;
use Cake\Routing\RouteBuilder;


/** @var RouteBuilder $routes */
$routes->prefix('Backend', function (RouteBuilder $routeBuilder): void {
	$routeBuilder->setRouteClass(AwyissRoute::class);

	$routeBuilder->registerMiddleware('backendRealm', new RealmMiddleware(Awyiss::REALM_BACKEND));
	$routeBuilder->applyMiddleware('backendRealm');

	// Load the configuration as early as possible to make it available for all other middleware
	$routeBuilder->applyMiddleware('config');

	// Load the event listeners as early as possible to possibly listen to middleware events
	$routeBuilder->applyMiddleware('eventListeners');

	$lo_authentication = new Authentication(Awyiss::REALM_BACKEND);
	$routeBuilder->registerMiddleware('backendAuthentication', new AuthenticationMiddleware($lo_authentication));
	$routeBuilder->applyMiddleware('backendAuthentication');

	$lo_authorization = new Authorization(Awyiss::REALM_BACKEND);
	$routeBuilder->registerMiddleware('backendAuthorization', new AuthorizationMiddleware($lo_authorization));
	$routeBuilder->applyMiddleware('backendAuthorization');

	$routeBuilder->applyMiddleware('csp');

	$routeBuilder->applyMiddleware('design');

	$routeBuilder->applyMiddleware('requestLocale');

	$routeBuilder->connect('/{lang}/{controller}/{action}/id:{id}/*')
	->setPatterns([
		'lang' => '[a-zA-Z]{2}',
		'controller' => '[a-zA-Z0-9-_]+',
		'action' => '(edit|delete)',
		'id' => '[0-9]+',
	])->setPass(['id'])->setPersist(['lang', 'controller']);

	$routeBuilder->connect(
		'/{lang}/{controller}/{action}/*',
		['action' => 'overview'],
		['_name' => Awyiss::REALM_BACKEND]
	)->setPatterns([
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
