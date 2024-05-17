<?php declare(strict_types=1);


use Awyiss\Awyiss;
use Awyiss\Routing\Route\AwyissRoute;
use Cake\Routing\RouteBuilder;


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
