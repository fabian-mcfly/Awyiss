<?php declare(strict_types=1);


use Authentication\Middleware\AuthenticationMiddleware;
use Awyiss\Awyiss;
use Awyiss\Authentication\Authentication;
use Awyiss\Authorization\Authorization;
use Awyiss\Middleware\AuthorizationMiddleware;
use Awyiss\Middleware\LocaleMiddleware;
use Awyiss\Middleware\EventListenersMiddleware;
use Awyiss\Routing\Route\AwyissRoute;
use Cake\Routing\RouteBuilder;

/** @var RouteBuilder $ao_routes */
$ao_routes->prefix('Backend', function(RouteBuilder $ao_routeBuilder) {
	$ao_routeBuilder->setRouteClass(AwyissRoute::class);

	Awyiss::setRealm(Awyiss::REALM_BACKEND);

	$ao_routeBuilder->registerMiddleware('eventListeners', new EventListenersMiddleware(Awyiss::getRealm()));
	$ao_routeBuilder->applyMiddleware('eventListeners');

	$ao_routeBuilder->registerMiddleware('requestLocale', new LocaleMiddleware(Awyiss::getRealm()));
	$ao_routeBuilder->applyMiddleware('requestLocale');

	$lo_authentication = new Authentication(Awyiss::REALM_BACKEND);
	$ao_routeBuilder->registerMiddleware('authentication', new AuthenticationMiddleware($lo_authentication));
	$ao_routeBuilder->applyMiddleware('authentication');

	$lo_authorization = new Authorization(Awyiss::REALM_BACKEND);
	$ao_routeBuilder->registerMiddleware('authorization', new AuthorizationMiddleware($lo_authorization));
	$ao_routeBuilder->applyMiddleware('authorization');

	$ao_routeBuilder->connect('/{lang}/{controller}/{action}/*', ['action' => 'overview'], ['_name' => Awyiss::REALM_BACKEND])->setPatterns([
		'lang' => '[a-zA-Z]{2}',
		'controller' => '[a-zA-Z0-9-_]+',
		'action' => '[a-zA-Z0-9-_]+',
	])->setPersist(['lang', 'controller', 'action']);

	$ao_routeBuilder->connect('/{lang}/{controller}/*', ['action' => 'overview'])->setPatterns([
		'lang' => '[a-zA-Z]{2}',
		'controller' => '[a-zA-Z0-9-_]+',
	])->setPersist(['lang', 'controller']);

	$ao_routeBuilder->connect('/{lang}/*', ['controller' => 'dashboard', 'action' => 'overview'])->setPatterns([
		'lang' => '[a-zA-Z]{2}',
	])->setPersist(['lang']);

	$ao_routeBuilder->connect('/*', ['controller' => 'dashboard', 'action' => 'overview']);
});

/*$ao_routes->scope('/rest', function(RouteBuilder $ao_routeBuilder) {
	$ao_routeBuilder->setRouteClass(AwyissRoute::class);

	$ao_routeBuilder->connect('/{controller}/{action}/*', ['prefix' => 'Backend', 'action' => 'overview'], ['_name' => 'rest'])->setPatterns([
		'controller' => '[a-zA-Z0-9-_]+',
		'action' => '[a-zA-Z0-9-_]+',
	])->setPersist(['controller', 'action']);
});*/