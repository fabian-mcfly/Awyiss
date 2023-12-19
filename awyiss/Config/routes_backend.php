<?php declare(strict_types=1);


use Authentication\Identifier\IdentifierInterface;
use Awyiss\Routing\Route\AwyissRoute;
use Cake\Routing\RouteBuilder;
use Cake\Routing\Router;

/** @var \Cake\Routing\RouteBuilder $routes */
$routes->prefix('Backend', function(RouteBuilder $ao_routeBuilder) {
	$ao_routeBuilder->setRouteClass(AwyissRoute::class);

	$lo_authentication = new \Awyiss\Authentication\Authentication('Backend');
	$lo_authorization = new \Awyiss\Authorization\Authorization('Backend');

	$ao_routeBuilder->registerMiddleware('authentication', new \Authentication\Middleware\AuthenticationMiddleware($lo_authentication));
	$ao_routeBuilder->applyMiddleware('authentication');

	$ao_routeBuilder->registerMiddleware('authorization', new \Awyiss\Middleware\AuthorizationMiddleware($lo_authorization));
	$ao_routeBuilder->applyMiddleware('authorization');

	$ao_routeBuilder->registerMiddleware('customController', new \Awyiss\Middleware\CustomControllerMiddleware($this));
	$ao_routeBuilder->applyMiddleware('customController');

	$ao_routeBuilder->connect('/{lang}/{controller}/{action}/*', [], ['_name' => 'backend'])->setPatterns([
		'lang' => '[a-z]{2}',
	])->setPersist(['lang']);

	$ao_routeBuilder->connect('/{lang}/{controller}', ['action' => 'overview'])->setPatterns([
		'lang' => '[a-z]{2}',
	])->setPersist(['lang']);

	$ao_routeBuilder->connect('/{lang}/*', ['controller' => 'Dashboard', 'action' => 'overview'])->setPatterns([
		'lang' => '[a-z]{2}',
	])->setPersist(['lang']);

	$ao_routeBuilder->connect('/*', ['controller' => 'Error', 'action' => '404']);
});