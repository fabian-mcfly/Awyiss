<?php declare(strict_types=1);


use Awyiss\Authentication\Authentication;
use Awyiss\Authorization\Authorization;
use Awyiss\Awyiss;
use Awyiss\Middleware\AuthenticationMiddleware;
use Awyiss\Middleware\AuthorizationMiddleware;
use Awyiss\Middleware\DesignMiddleware;
use Awyiss\Middleware\EventListenersMiddleware;
use Awyiss\Middleware\LocaleMiddleware;
use Awyiss\Routing\Route\AwyissRoute;
use Cake\Routing\RouteBuilder;


/**
 * @param \Cake\Routing\RouteBuilder $ao_routeBuilder
 * @return void
 */
function addUserConfigurationRoutes(RouteBuilder $ao_routeBuilder): void {
	$ao_routeBuilder->connect(
		'/{lang}/user/configuration/{action}/id:{id}/*',
		['controller' => 'configuration', 'prefix' => 'Backend/User'],
	)
	->setPatterns([
		'lang' => '[a-zA-Z]{2}',
		'action' => '(edit|delete)',
		'id' => '[0-9]+',
	])
	->setPass(['id'])
	->setPersist(['lang', 'controller']);

	$ao_routeBuilder->connect(
		'/{lang}/user/configuration/{action}/*',
		[
			'controller' => 'configuration',
			'action' => 'overview',
			'prefix' => 'Backend/User',
		],
		[
			'_name' => Awyiss::REALM_BACKEND . '::user_configuration',
		],
	)
	->setPatterns([
		'lang' => '[a-zA-Z]{2}',
		'action' => '[a-zA-Z0-9-_]+',
	])
	->setPersist(['lang', 'controller', 'action']);

	$ao_routeBuilder->connect(
		'/{lang}/user/configuration/*',
		['controller' => 'configuration', 'action' => 'overview', 'prefix' => 'Backend/User'],
	)
	->setPatterns([
		'lang' => '[a-zA-Z]{2}',
	])
	->setPersist(['lang', 'controller']);
}


/** @var RouteBuilder $ao_routes */
$ao_routes->prefix('Backend', function (RouteBuilder $ao_routeBuilder): void {
	$ao_routeBuilder->setRouteClass(AwyissRoute::class);

	Awyiss::setRealm(Awyiss::REALM_BACKEND);

	$ao_routeBuilder->registerMiddleware('design', new DesignMiddleware(Awyiss::REALM_BACKEND));
	$ao_routeBuilder->applyMiddleware('design');

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

	addUserConfigurationRoutes($ao_routeBuilder);

	$ao_routeBuilder->connect('/{lang}/{controller}/{action}/id:{id}/*')->setPatterns([
		'lang' => '[a-zA-Z]{2}',
		'controller' => '[a-zA-Z0-9-_]+',
		'action' => '(edit|delete)',
		'id' => '[0-9]+',
	])->setPass(['id'])->setPersist(['lang', 'controller']);

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
