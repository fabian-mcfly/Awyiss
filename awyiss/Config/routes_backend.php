<?php declare(strict_types=1);


use Awyiss\Middleware\LocaleMiddleware;
use Awyiss\Middleware\EventListenersMiddleware;
use Awyiss\Routing\Route\AwyissRoute;
use Cake\Routing\RouteBuilder;

/** @var RouteBuilder $ao_routes */
$ao_routes->prefix('Backend', function(RouteBuilder $ao_routeBuilder) {
	$ao_routeBuilder->setRouteClass(AwyissRoute::class);

	$ao_routeBuilder->registerMiddleware('requestLocale', new LocaleMiddleware('backend', LocaleMiddleware::SOURCE_SESSION));
	$ao_routeBuilder->applyMiddleware('requestLocale');

	$ao_routeBuilder->registerMiddleware('eventListeners', new EventListenersMiddleware('backend'));
	$ao_routeBuilder->applyMiddleware('eventListeners');

	$lo_authentication = new \Awyiss\Authentication\Authentication('backend');
	$ao_routeBuilder->registerMiddleware('authentication', new \Authentication\Middleware\AuthenticationMiddleware($lo_authentication));
	$ao_routeBuilder->applyMiddleware('authentication');

	$lo_authorization = new \Awyiss\Authorization\Authorization('backend');
	$ao_routeBuilder->registerMiddleware('authorization', new \Awyiss\Middleware\AuthorizationMiddleware($lo_authorization));
	$ao_routeBuilder->applyMiddleware('authorization');

	$ao_routeBuilder->connect('/{lang}/{controller}/{action}/*', ['action' => 'overview'], ['_name' => 'backend'])->setPatterns([
		'lang' => '[a-zA-Z]{2}',
		'controller' => '[a-zA-Z0-9-_]+',
		'action' => '[a-zA-Z0-9-_]+',
	])->setPersist(['lang', 'controller', 'action']);

	/*$ao_routeBuilder->connect('/{lang}/{controller}/*', ['controller' => 'Dashboard', 'action' => 'overview'])->setPatterns([
		'lang' => '[a-zA-Z]{2}',
		'controller' => '[a-zA-Z0-9-_]+',
	])->setPersist(['lang', 'controller']);*/

	$ao_routeBuilder->connect('/{lang}/*', ['controller' => 'dashboard', 'action' => 'overview'])->setPatterns([
		'lang' => '[a-zA-Z]{2}',
	])->setPersist(['lang']);

	/** @var \Awyiss\Middleware\LocaleMiddleware $lo_locale */
	//$lo_locale = $this->request->getAttribute('locale');
	//$la_languages = $lo_locale->getLanguages('backend');

	$ao_routeBuilder->connect('/*', ['controller' => 'dashboard', 'action' => 'overview']);
});