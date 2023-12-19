<?php

declare(strict_types=1);


namespace Awyiss;


use Cake\Core\Configure;
use Cake\Core\ContainerInterface;
use Cake\Core\Exception\MissingPluginException;
use Cake\Core\Plugin;
use Cake\Datasource\FactoryLocator;
use Cake\Error\Middleware\ErrorHandlerMiddleware;
use Cake\Event\EventManager;
use Cake\Event\EventManagerInterface;
use Cake\Http\BaseApplication;
use Cake\Http\ControllerFactoryInterface;
use Cake\Http\Middleware\BodyParserMiddleware;
use Cake\Http\MiddlewareQueue;
use Cake\ORM\Locator\TableLocator;
use Cake\Routing\Middleware\AssetMiddleware;
use Cake\Routing\Middleware\RoutingMiddleware;
use Cake\Routing\RouteBuilder;
use Cake\Routing\Router;
use Composer\Autoload\ClassLoader;


class Application extends BaseApplication {
	private $ls_configDir;
	private $lo_loader;


	/**
	 * Constructor
	 *
	 * @param \Cake\Event\EventManagerInterface $eventManager Application event manager instance.
	 * @param \Cake\Http\ControllerFactoryInterface $controllerFactory Controller factory.
	 *
	 * @noinspection PhpMissingParentConstructorInspection
	 */
	public function __construct (ClassLoader $loader, ?EventManagerInterface $eventManager = NULL, ?ControllerFactoryInterface $controllerFactory = NULL) {
		$this->lo_loader = $loader;
		$this->plugins = Plugin::getCollection();
		$this->_eventManager = $eventManager ?: EventManager::instance();
		$this->controllerFactory = $controllerFactory;

		$this->ls_configDir = dirname(__DIR__) . DS . 'awyiss' . DS . 'Config' . DS;

		//Might be set in awyiss/bin/cake.php
		if ( ! defined('CONFIG_ENV')) {
			define('CONFIG_ENV', env('CONFIG_ENV', 'production'));
		}

		//Might be set in awyiss/bin/cake.php
		if ( ! defined('CUSTOM_DIR')) {
			if ( ! $ls_customDir = env('CUSTOM_DIR')) {
				$ls_cliHint = NULL;
				if (PHP_SAPI === 'cli') {
					$ls_cliHint = 'Set it in awyiss/bin/cake.php ' . PHP_EOL;
				}
				exit('Environment Variable CUSTOM_DIR is not set.' . PHP_EOL . $ls_cliHint);
			}
			define('CUSTOM_DIR', $ls_customDir);
		}
	}


	/**
	 * Load all the application configuration and bootstrap logic.
	 *
	 * @return void
	 */
	public function bootstrap (): void {
		require_once $this->ls_configDir . 'bootstrap.php';


		if (is_file($ls_file = CUSTOM_CONFIG . 'bootstrap.php')) {
			require_once $ls_file;
		}
		if (is_file($ls_file = ENV_CUSTOM_CONFIG . 'bootstrap.php')) {
			require_once $ls_file;
		}


		/*
		 * At this point we know where the customer-specific files will be.
		 * so we add the path to the autoloader for both the \Awyiss
		 * and the customer-specific namespace
		 */
		//$this->lo_loader->addPsr4('Awyiss\\', [ROOT . DS . CUSTOM_DIR], TRUE);
		$this->lo_loader->addPsr4(CUSTOM_NAMESPACE . '\\', [ROOT . DS . CUSTOM_DIR], TRUE);


		if (PHP_SAPI === 'cli') {
			$this->addPlugin('IdeHelper');
			$this->bootstrapCli();
		}
		else {
			FactoryLocator::add('Table', (new TableLocator())->allowFallbackClass(FALSE));
		}


		$this->addPlugin('Authentication');


		/*
		 * Only try to load DebugKit in development mode
		 * Debug Kit should not be installed on a production system
		 */
		if (Configure::read('debug')) {
			$this->addPlugin('DebugKit');
		}


		if (is_file($ls_file = CUSTOM_CONFIG . 'plugins.php')) {
			require_once $ls_file;
		}
		if (is_file($ls_file = ENV_CUSTOM_CONFIG . 'plugins.php')) {
			require_once $ls_file;
		}
	}


	/**
	 * Bootstrapping for CLI application.
	 *
	 * That is when running commands.
	 *
	 * @return void
	 */
	protected function bootstrapCli (): void {
		try {
			Configure::write('Bake.theme', 'AwyissBake');
			$this->addPlugin('Bake');
			$this->addPlugin('AwyissBake');
		}
		catch (MissingPluginException $e) {
			exit($e->getMessage());
			// Do not halt if the plugin is missing
		}

		$this->addPlugin('Migrations');
	}


	/**
	 * Setup the middleware queue
	 *
	 * @param \Cake\Http\MiddlewareQueue $middlewareQueue The middleware queue to setup.
	 *
	 * @return \Cake\Http\MiddlewareQueue The updated middleware queue.
	 */
	public function middleware (MiddlewareQueue $middlewareQueue): MiddlewareQueue {
		$middlewareQueue->add(new ErrorHandlerMiddleware(Configure::read('Error')))->add(new AssetMiddleware([
			'cacheTime' => Configure::read('Asset.cacheTime'),
		]))->add(new RoutingMiddleware($this))->add(new BodyParserMiddleware());

		return $middlewareQueue;
	}


	/**
	 * {@inheritDoc}
	 *
	 * @param \Cake\Routing\RouteBuilder $routes A route builder to add routes into.
	 *
	 * @return void
	 */
	public function routes (RouteBuilder $routes): void {
		// Only load routes if the router is empty
		if ( ! Router::routes()) {
			/**
			 * This logic is tricky: we first need to load backend-related routes
			 * - for the environment
			 * - for the custom_dir
			 * - for Awyiss
			 */

			if (is_file($ls_file = ENV_CUSTOM_CONFIG . 'routes_backend.php')) {
				require_once $ls_file;
			}

			if (is_file($ls_file = CUSTOM_CONFIG . 'routes_backend.php')) {
				require_once $ls_file;
			}

			require $this->ls_configDir . 'routes_backend.php';

			/**
			 * Now after the backend-related routes were loaded, we can load general routes
			 * - for the environment
			 * - for the custom_dir
			 * - for Awyiss
			 */

			if (is_file($ls_file = ENV_CUSTOM_CONFIG . 'routes.php')) {
				require_once $ls_file;
			}

			if (is_file($ls_file = CUSTOM_CONFIG . 'routes.php')) {
				require_once $ls_file;
			}

			require $this->ls_configDir . 'routes.php';
			/**
			 * The reason we're doing this is because a custom config might overwrite routes for the frontend using the * placeholder
			 * which are required for the Awyiss backend
			 *
			 * But since we want to be able to set custom backend routes in the custom config as well,
			 * we cannot load the custom config in between the backend and the regular routes.
			 *
			 * Sorry?
			 */
		}
	}


	/**
	 * Register application container services.
	 *
	 * @param \Cake\Core\ContainerInterface $container The Container to update.
	 *
	 * @return void
	 * @link https://book.cakephp.org/4/en/development/dependency-injection.html#dependency-injection
	 */
	public function services (ContainerInterface $container): void {
		if (is_file($ls_file = CUSTOM_CONFIG . 'services.php')) {
			require_once $ls_file;
		}

		if (is_file($ls_file = ENV_CUSTOM_CONFIG . 'services.php')) {
			require_once $ls_file;
		}
	}
}
