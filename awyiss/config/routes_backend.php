<?php declare(strict_types=1);


use Awyiss\Awyiss;
use Awyiss\Core\App;
use Cake\Routing\RouteBuilder;


/** @var \Cake\Routing\RouteBuilder $routes */
$routes->prefix('Backend', function (RouteBuilder $routeBuilder): void {
	/** @uses \Awyiss\Routing\Route\AwyissRoute */
	$routeBuilder->setRouteClass(App::className('Awyiss', 'Routing/Route', 'Route'));

	/** @var class-string<\Awyiss\Middleware\RealmMiddleware> $ls_realmMiddlewareClass */
	$ls_realmMiddlewareClass = App::className('Realm', 'Middleware', 'Middleware');
	$routeBuilder->registerMiddleware('backendRealm', new $ls_realmMiddlewareClass(Awyiss::REALM_BACKEND));
	$routeBuilder->applyMiddleware('backendRealm');

	// Load the configuration as early as possible to make it available for all other middleware
	$routeBuilder->applyMiddleware('config');

	// Load the event listeners as early as possible to possibly listen to middleware events
	$routeBuilder->applyMiddleware('eventListeners');

	/** @var class-string<\Awyiss\Authentication\AuthenticationService> $ls_authenticationClass */
	$ls_authenticationClass = App::className('Authentication', 'Authentication');
	$lo_authentication = new $ls_authenticationClass(Awyiss::REALM_BACKEND);
	/** @var class-string<\Awyiss\Middleware\AuthenticationMiddleware> $ls_authenticationMiddlewareClass */
	$ls_authenticationMiddlewareClass = App::className('Authentication', 'Middleware', 'Middleware');
	$routeBuilder->registerMiddleware('backendAuthentication', new $ls_authenticationMiddlewareClass($lo_authentication));
	$routeBuilder->applyMiddleware('backendAuthentication');

	/** @var class-string<\Awyiss\Authorization\Authorization> $ls_authorizationClass */
	$ls_authorizationClass = App::className('Authorization', 'Authorization');
	$lo_authorization = new $ls_authorizationClass(Awyiss::REALM_BACKEND);
	/** @var class-string<\Awyiss\Middleware\AuthorizationMiddleware> $ls_authorizationMiddlewareClass */
	$ls_authorizationMiddlewareClass = App::className('Authorization', 'Middleware', 'Middleware');
	$routeBuilder->registerMiddleware('backendAuthorization', new $ls_authorizationMiddlewareClass($lo_authorization));
	$routeBuilder->applyMiddleware('backendAuthorization');

	$routeBuilder->applyMiddleware('csp');

	$routeBuilder->applyMiddleware('design');

	$routeBuilder->applyMiddleware('requestLocale');


	/**
	 * Load the backend-related routes
	 * - for the environment in the custom_dir
	 * - for the custom_dir
	 */
	if (defined('CUSTOM_CONFIG')) {
		$ls_file = ENV_CUSTOM_CONFIG . 'routes_backend.php';
		if (is_file($ls_file)) {
			include $ls_file;
		}

		$ls_file = CUSTOM_CONFIG . 'routes_backend.php';
		if (is_file($ls_file)) {
			include $ls_file;
		}
	}


	$routeBuilder->connect('/{lang}/{controller}/{action}/id:{id}/*')
	->setPatterns([
		'lang' => '[a-zA-Z]{2}',
		'controller' => '[a-zA-Z0-9-_]+',
		'action' => '(edit|restart|delete)',
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
