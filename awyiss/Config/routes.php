<?php declare(strict_types=1);


use Awyiss\Routing\Route\AwyissRoute;
use Cake\Routing\RouteBuilder;


/** @var \Cake\Routing\RouteBuilder $routes */
$routes->scope('/', function(RouteBuilder $ao_routes) {
	$ao_routes->setRouteClass(AwyissRoute::class);

	$ao_routes->connect('/{lang}/*', [/*'prefix' => 'Frontend',*/ 'controller' => 'Frontend', 'action' => 'index'], ['_name' => 'frontend'])->setPatterns([
		'lang' => '[a-z]{2}',
	])->setPersist(['lang', 'slug']);

	$ao_routes->connect('/*', [/*'prefix' => 'Frontend',*/ 'controller' => 'Frontend', 'action' => 'noLanguageFound']);
});