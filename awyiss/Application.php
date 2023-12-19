<?php /** @noinspection PhpMultipleClassDeclarationsInspection */

declare(strict_types=1);


namespace Awyiss;


use Awyiss\Controller\ControllerFactory;
use Awyiss\ORM\Locator\TableLocator;
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
use Cake\Routing\Middleware\RoutingMiddleware;
use Cake\Routing\RouteBuilder;
use Cake\Routing\Router;
use Composer\Autoload\ClassLoader;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;


class Application extends BaseApplication {
	protected ClassLoader $classLoader;


	/**
	 * Constructor
	 *
	 * @param \Composer\Autoload\ClassLoader $ao_loader
	 * @param null|\Cake\Event\EventManagerInterface $ao_eventManager Application event manager instance.
	 * @param null|\Cake\Http\ControllerFactoryInterface $ao_controllerFactory Controller factory.
	 *
	 * @noinspection PhpMissingParentConstructorInspection
	 */
	public function __construct (ClassLoader $ao_loader, ?EventManagerInterface $ao_eventManager = NULL, ?ControllerFactoryInterface $ao_controllerFactory = NULL) {
		$this->classLoader = $ao_loader;
		$this->plugins = Plugin::getCollection();
		$this->_eventManager = $ao_eventManager ?: EventManager::instance();
		$this->controllerFactory = $ao_controllerFactory;

		$this->configDir = dirname(__DIR__) . DS . 'awyiss' . DS . 'config' . DS;

		//Might be set in awyiss/bin/cake.php
		if ( ! defined('CONFIG_ENV')) {
			define('CONFIG_ENV', env('CONFIG_ENV', 'production'));
		}

		//Might be set in awyiss/bin/cake.php
		if ( ! defined('CUSTOM_DIR')) {
			if ( ! $ls_customDir = env('CUSTOM_DIR')) {
				$ls_cliHint = NULL;
				if (PHP_SAPI === 'cli') {
					$ls_cliHint = 'Set it in bin/cake.php ' . PHP_EOL;
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
	 * @throws \ReflectionException
	 */
	public function bootstrap (): void {
		require_once $this->configDir . 'bootstrap.php';


		if (is_file($ls_file = CUSTOM_CONFIG . 'bootstrap.php')) {
			require_once $ls_file;
		}
		if (is_file($ls_file = ENV_CUSTOM_CONFIG . 'bootstrap.php')) {
			require_once $ls_file;
		}


		/*
		 * At this point we know where the customer-specific files will be.
		 * so we add the path to the autoloader for the customer-specific namespace
		 */
		$this->classLoader->addPsr4(CUSTOM_NAMESPACE . '\\', [ROOT . DS . CUSTOM_DIR], TRUE);


		if (PHP_SAPI === 'cli') {
			$this->addPlugin('IdeHelper');
			$this->bootstrapCli();
			FactoryLocator::add('Table', (new TableLocator())->allowFallbackClass(TRUE)->setFallbackClassName(\Awyiss\Model\Table::class));
		}
		else {
			FactoryLocator::add('Table', (new TableLocator())->allowFallbackClass(FALSE));
		}


		$this->addPlugin('Authentication');
		$this->addPlugin('Queue');

		/*
		 * Only try to load DebugKit in development mode
		 * Debug Kit should not be installed on a production system
		 */
		/*if (Configure::read('debug')) {
			$this->addPlugin('DebugKit');
		}*/


		if (is_file($ls_file = CUSTOM_CONFIG . 'plugins.php')) {
			require_once $ls_file;
		}
		if (is_file($ls_file = ENV_CUSTOM_CONFIG . 'plugins.php')) {
			require_once $ls_file;
		}

		static::loadConstants();
	}


	/**
	 * Bootstrapping for CLI application.
	 *
	 * That is when running commands.
	 *
	 * @return void
	 * @throws \ReflectionException
	 */
	protected function bootstrapCli (): void {
		try {
			Configure::write('Bake.theme', 'AwyissBake');


			$this->addPlugin('Bake');
			$this->addPlugin('Migrations');

			$this->addPlugin('AwyissBake');

			\Awyiss\Event\EventListenersProvider::loadListener('general_events', 'bake');

		}
		catch (MissingPluginException $ex) {
			exit($ex->getMessage());
		}



		/*$lo_onsoleOptionParser = new ConsoleOptionParser('');
		$lo_onsoleOptionParser->addOption('prefix', [
			'help' => 'The namespace prefix to use.',
			'default' => false,
		]);
		dd($lo_onsoleOptionParser->parse($_SERVER['argv']));*/
	}


	/**
	 * Setup the middleware queue
	 *
	 * @param \Cake\Http\MiddlewareQueue $ao_middlewareQueue The middleware queue to set up.
	 *
	 * @return \Cake\Http\MiddlewareQueue The updated middleware queue.
	 *
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function middleware (MiddlewareQueue $ao_middlewareQueue): MiddlewareQueue {
		$ao_middlewareQueue->add(new ErrorHandlerMiddleware(Configure::read('Error')));
		/*$ao_middlewareQueue->add(new AssetMiddleware([
			'cacheTime' => Configure::read('Asset.cacheTime'),
		]));*/
		$ao_middlewareQueue->add(new RoutingMiddleware($this));
		$ao_middlewareQueue->add(new BodyParserMiddleware());

		return $ao_middlewareQueue;
	}


	/**
	 * @inheritDoc
	 *
	 * @param \Cake\Routing\RouteBuilder $ao_routes A route builder to add routes into.
	 *
	 * @return void
	 *
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function routes (RouteBuilder $ao_routes): void {
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

			require $this->configDir . 'routes_backend.php';

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

			require $this->configDir . 'routes.php';
			/**
			 * The reason we're doing this is that a custom config might overwrite routes for the frontend using the * placeholder
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
	 * @param \Cake\Core\ContainerInterface $ao_container The Container to update.
	 *
	 * @return void
	 * @link https://book.cakephp.org/4/en/development/dependency-injection.html#dependency-injection
	 *
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function services (ContainerInterface $ao_container): void {
		if (is_file($ls_file = CUSTOM_CONFIG . 'services.php')) {
			require_once $ls_file;
		}

		if (is_file($ls_file = ENV_CUSTOM_CONFIG . 'services.php')) {
			require_once $ls_file;
		}
	}


	/**
	 * Invoke the application.
	 *
	 * - Convert the PSR response into CakePHP equivalents.
	 * - Create the controller that will handle this request.
	 * - Invoke the controller.
	 *
	 * @param \Psr\Http\Message\ServerRequestInterface $ao_request The request
	 *
	 * @return \Psr\Http\Message\ResponseInterface
	 * @throws \Psr\Container\ContainerExceptionInterface
	 * @throws \Psr\Container\NotFoundExceptionInterface
	 * @throws \ReflectionException
	 *
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function handle (ServerRequestInterface $ao_request): ResponseInterface {
		if ($this->controllerFactory === NULL) {
			$this->controllerFactory = new ControllerFactory($this->getContainer());
		}

		if (Router::getRequest() !== $ao_request) {
			/** @noinspection PhpParamsInspection */
			Router::setRequest($ao_request);
		}

		$controller = $this->controllerFactory->create($ao_request);

		return $this->controllerFactory->invoke($controller);
	}


	public static function loadConstants () {
		$ls_filePath = ENV_CUSTOM_CONFIG . 'constants.php';

		//dd(FactoryLocator::get('Table'));

		if (file_exists($ls_filePath)) {
			require_once $ls_filePath;
			return;
		}

		$ls_constantsContents = '<?php declare(strict_types=1);' . PHP_EOL . PHP_EOL;

		$lo_PageRolesTable = FactoryLocator::get('Table')->get('PageRoles');
		/** @var \Awyiss\Model\Entity\PageRole $lo_pageRole */
		foreach ($lo_PageRolesTable->find('all')->applyOptions(['access' => ['skip' => TRUE]]) AS $lo_pageRole) {
			$ls_constant = 'PAGEROLE_' . strtoupper($lo_pageRole->identifier);
			$ls_constantsContents .= 'defined(\'' . $ls_constant . '\') || define(\'' . $ls_constant . '\', ' . $lo_pageRole->id . ');' . PHP_EOL;
			defined($ls_constant) || define($ls_constant, $lo_pageRole->id);
		}

		if (file_put_contents($ls_filePath, $ls_constantsContents) > 0) {
			chmod($ls_filePath, fileperms($ls_filePath) | 128 + 16 + 2);
		}
	}
}
