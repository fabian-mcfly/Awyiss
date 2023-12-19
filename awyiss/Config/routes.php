<?php declare(strict_types=1);


use Awyiss\Routing\Route\AwyissRoute;
use Cake\Routing\RouteBuilder;


return function(RouteBuilder $ao_routes): void {
	/** @var RouteBuilder $ao_routes */
	$ao_routes->scope('/', function(RouteBuilder $ao_routes): void {
		$ao_routes->setRouteClass(AwyissRoute::class);

		$ao_routes->connect('/{lang}/*',
			['prefix' => 'Frontend', 'controller' => 'frontend', 'action' => 'index'],
			['_name' => 'frontend'])->setPatterns([
			'lang' => '[a-z]{2}',
		])->setPersist(['lang', 'slug']);

		$ao_routes->connect('/*', ['prefix' => 'Frontend', 'controller' => 'frontend', 'action' => 'noLanguageFound']);
	});
};
