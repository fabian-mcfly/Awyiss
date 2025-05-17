<?php declare(strict_types=1);


namespace Awyiss;


use Awyiss\Configuration\ConfigOptionsProvider;
use Awyiss\Controller\ControllerFactory;
use Awyiss\Core\App;
use Awyiss\Event\EventListenersProvider;
use Awyiss\Event\EventManager;
use Awyiss\Middleware\RoutingMiddleware;
use Awyiss\Model\Table;
use Awyiss\ORM\Locator\TableLocator;
use Awyiss\Routing\Router;
use Awyiss\Utility\Inflector;
use Cake\Console\CommandCollection;
use Cake\Console\CommandInterface;
use Cake\Core\Configure;
use Cake\Core\ContainerInterface;
use Cake\Core\Plugin;
use Cake\Core\PluginCollection;
use Cake\Database\Expression\QueryExpression;
use Cake\Datasource\FactoryLocator;
use Cake\Error\Middleware\ErrorHandlerMiddleware;
use Cake\Event\Event;
use Cake\Event\EventManagerInterface;
use Cake\Http\BaseApplication;
use Cake\Http\ControllerFactoryInterface;
use Cake\Http\Middleware\BodyParserMiddleware;
use Cake\Http\MiddlewareQueue;
use Cake\Http\ServerRequest;
use Cake\Routing\Middleware\AssetMiddleware;
use Cake\Routing\RouteBuilder;
use Cake\Utility\Hash;
use Composer\Autoload\ClassLoader;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;


/**
 * @inheritDoc
 */
class Awyiss extends BaseApplication {
	/**
	 * The class name for inactive elements in preview mode
	 */
	final public const PREVIEW_MODE_ELEMENT_CLASSNAME = 'AwyissFrontendPreview-InactiveElement';
	/**
	 * The name of the frontend realm
	 */
	final public const REALM_FRONTEND = 'Frontend';
	/**
	 * The name of the backend realm
	 */
	final public const REALM_BACKEND = 'Backend';
	/**
	 * The version of Awyiss
	 */
	final public const VERSION = '0.1.0';
	/**
	 * The name of the version
	 */
	final public const VERSION_NAME = 'Interface';


	/**
	 * @var string|null
	 */
	protected static ?string $realm = null;


	/**
	 * @var \Composer\Autoload\ClassLoader|null
	 */
	protected ?ClassLoader $classLoader;


	/**
	 * @inheritDoc
	 * @param string $configDir The directory the bootstrap configuration is held in.
	 * @param EventManagerInterface|null $eventManager Application event manager instance.
	 * @param ControllerFactoryInterface|null $controllerFactory Controller factory.
	 * @param \Composer\Autoload\ClassLoader|null $loader
	 * @noinspection PhpMissingParentConstructorInspection
	 */
	public function __construct(
		string $configDir,
		?EventManagerInterface $eventManager = null,
		?ControllerFactoryInterface $controllerFactory = null,
		?ClassLoader $loader = null,
	) {
		$this->configDir = rtrim($configDir, DS) . DS;
		$this->classLoader = $loader;
		$this->plugins = new PluginCollection();
		$this->_eventManager = $eventManager ?: EventManager::instance();
		$this->controllerFactory = $controllerFactory;
		Plugin::setCollection($this->plugins);
	}


	/**
	 * @inheritDoc
	 * @return void
	 * @throws \ReflectionException
	 */
	public function bootstrap(): void {
		require_once $this->configDir . 'bootstrap.php';

		if (defined('CUSTOM_CONFIG')) {
			$ls_file = CUSTOM_CONFIG . 'bootstrap.php';
			if (is_file($ls_file)) {
				require_once $ls_file;
			}

			$ls_file = ENV_CUSTOM_CONFIG . 'bootstrap.php';
			if (is_file($ls_file)) {
				require_once $ls_file;
			}
		}

		/*
		 * At this point we know where the customer-specific files will be.
		 * so we add the path to the autoloader for the customer-specific namespace
		 */
		if (defined('CUSTOM_NAMESPACE')) {
			$this->classLoader?->addPsr4(CUSTOM_NAMESPACE . '\\', [ROOT . DS . CUSTOM_DIR], true);
		}


		EventListenersProvider::loadListener('general_events', 'Global');


		if (PHP_SAPI === 'cli') {
			EventListenersProvider::loadListener('general_events', 'Bake');

			FactoryLocator::add('Table', (new TableLocator())->allowFallbackClass(true)->setFallbackClassName(Table::class));
		}
		else {
			FactoryLocator::add('Table', (new TableLocator())->allowFallbackClass(false));
		}

		$la_plugins = include $this->configDir . 'plugins.php';
		if (is_array($la_plugins)) {
			$this->plugins->addFromConfig($la_plugins);
		}

		if (defined('CUSTOM_CONFIG')) {
			$ls_file = CUSTOM_CONFIG . 'plugins.php';
			if (is_file($ls_file)) {
				$la_plugins = include $ls_file;
				if (is_array($la_plugins)) {
					$this->plugins->addFromConfig($la_plugins);
				}
			}

			$ls_file = ENV_CUSTOM_CONFIG . 'plugins.php';
			if (is_file($ls_file)) {
				$la_plugins = include $ls_file;
				if (is_array($la_plugins)) {
					$this->plugins->addFromConfig($la_plugins);
				}
			}
		}
	}



	/**
	 * Finds all commands inside subfolders of
	 * - the custom namespace
	 * - the Awyiss namespace
	 *
	 * The commands are added to the command collection
	 *
	 * @param \Cake\Console\CommandCollection $commands
	 * @return \Cake\Console\CommandCollection
	 */
	public function console(CommandCollection $commands): CommandCollection {
		$la_classes = App::classes('*', 'Command', 'Command', CommandInterface::class, '*');

		$la_commands = [];
		/** @var class-string<\Cake\Console\CommandInterface::class> $ls_commandClass */
		foreach ($la_classes as $ls_subNamespace => $ls_commandClass) {
			$ls_command = $ls_commandClass::defaultName();

			if (str_contains($ls_subNamespace, '\\')) {
				$ls_subPath = substr($ls_subNamespace, 0, strpos($ls_subNamespace, '\\'));
				$ls_subPath = Inflector::underscore($ls_subPath);
				if (!str_starts_with($ls_command, $ls_subPath . ' ')) {
					$ls_command = $ls_subPath . ' ' . $ls_command;
				}
			}

			if (!isset($la_commands[ $ls_command ])) {
				$la_commands[ $ls_command ] = $ls_commandClass;
			}
		}

		if ($la_commands) {
			$commands->addMany($la_commands);
		}

		return parent::console($commands);
	}


	/**
	 * @inheritDoc
	 * @param MiddlewareQueue $middlewareQueue The middleware queue to set up.
	 * @return MiddlewareQueue The updated middleware queue.
	 */
	public function middleware(MiddlewareQueue $middlewareQueue): MiddlewareQueue {
		$middlewareQueue->add(new ErrorHandlerMiddleware(Configure::read('Error'), $this));

		if (Configure::read('debug')) {
			// CakePHP's AssetMiddleware is not used in Awyiss directly,
			// only by DebugKit
			$middlewareQueue->add(new AssetMiddleware([
				'cacheTime' => Configure::read('Asset.cacheTime'),
			]));
		}

		$middlewareQueue->add(new RoutingMiddleware($this));

		$middlewareQueue->add(new BodyParserMiddleware());


		return $middlewareQueue;
	}


	/**
	 * @inheritDoc
	 */
	public function routes(RouteBuilder $routes): void {
		// Only load routes if the router is empty
		if (Router::routes()) {
			return;
		}

		/**
		 * Load the general routes
		 * - for the environment
		 * - for the custom_dir
		 * - for Awyiss
		 */
		if (defined('CUSTOM_CONFIG')) {
			$ls_file = ENV_CUSTOM_CONFIG . 'routes.php';
			if (is_file($ls_file)) {
				include $ls_file;
			}

			$ls_file = CUSTOM_CONFIG . 'routes.php';
			if (is_file($ls_file)) {
				include $ls_file;
			}
		}

		require $this->configDir . 'routes.php';

		/**
		 * Load the backend-related routes
		 * - for the environment
		 * - for the custom_dir
		 * - for Awyiss
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

		require $this->configDir . 'routes_backend.php';
	}


	/**
	 * @inheritDoc
	 * @param ContainerInterface $container The Container to update.
	 * @return void
	 * @link https://book.cakephp.org/4/en/development/dependency-injection.html#dependency-injection
	 */
	public function services(ContainerInterface $container): void {
		if (!defined('CUSTOM_CONFIG')) {
			return;
		}

		$ls_file = CUSTOM_CONFIG . 'services.php';
		if (is_file($ls_file)) {
			require_once $ls_file;
		}

		$ls_file = ENV_CUSTOM_CONFIG . 'services.php';
		if (is_file($ls_file)) {
			require_once $ls_file;
		}
	}


	/**
	 * Use the Awyiss controller factory
	 *
	 * @inheritDoc
	 * @param ServerRequestInterface $request The request
	 * @return ResponseInterface
	 * @throws \Psr\Container\ContainerExceptionInterface
	 * @throws \Psr\Container\NotFoundExceptionInterface
	 * @throws \ReflectionException
	 */
	public function handle(ServerRequestInterface $request): ResponseInterface {
		$lo_container = $this->getContainer();
		$lo_container->add(ServerRequest::class, $request);
		$lo_container->add(ContainerInterface::class, $lo_container);

		$lo_eventManager = $this->events($this->getEventManager());
		$this->setEventManager($this->pluginEvents($lo_eventManager));

		$this->controllerFactory ??= new ControllerFactory($lo_container);

		if (Router::getRequest() !== $request) {
			/** @noinspection PhpParamsInspection */
			Router::setRequest($request);
		}

		$lo_controller = $this->controllerFactory->create($request);

		return $this->controllerFactory->invoke($lo_controller);
	}


	/**
	 * @return string|null
	 */
	public static function getRealm(): ?string {
		return static::$realm;
	}


	/**
	 * @param string|null $realm
	 * @return void
	 */
	public static function setRealm(?string $realm): void {
		static::$realm = $realm;

		$lo_eventManager = EventManager::instance();
		$lo_event = new Event('Awyiss.setRealm', null, [
			'realm' => static::$realm,
		]);
		$lo_eventManager->dispatch($lo_event);
	}


	/**
	 * @return array<int, string>
	 */
	public static function getRealms(): array {
		return [
			static::REALM_FRONTEND,
			static::REALM_BACKEND,
		];
	}


	/**
	 * Loads the Awyiss configuration from either the database or a config file inside the custom namespace.
	 * When loaded from the database, the configuration is dumped into a php config file.
	 *
	 * The filename is the underscored name of the custom namespace,
	 * followed by the frontend language and the backend language, both in square brackets.
	 *
	 * @param string $frontendLanguage
	 * @param string $backendLanguage
	 * @param bool $forceReload
	 */
	public static function loadConfiguration(string $frontendLanguage, string $backendLanguage, bool $forceReload = false): void {
		$ls_fileName = Inflector::underscore(CUSTOM_NAMESPACE);
		$ls_fileName .= '[' . $frontendLanguage . ']';
		$ls_fileName .= '[' . $backendLanguage . ']';

		if (!$forceReload) {
			/*
			 * If the config path `Awyiss` is not empty, we do have a config file
			 * Therefore loading the database config is skipped
			 */
			Configure::load($ls_fileName, 'default', false);
			if (Configure::read('Awyiss')) {
				return;
			}

			/**
			 * Trigger the creation of the custom configuration
			 *
			 * @see \Awyiss\Event\Backend\ConfigurationListener::createCustomConfiguration()
			 */
			$lo_eventManager = EventManager::instance();
			$lo_eventManager->dispatch('Configuration.createCustomConfiguration');

			// Try to load the config file again
			Configure::load($ls_fileName, 'default', false);
			if (Configure::read('Awyiss')) {
				return;
			}
		}

		Configure::delete('Awyiss');

		$la_config = static::getDatabaseConfiguration($frontendLanguage, $backendLanguage);
		$la_config = static::getFileConfiguration($la_config);

		ksort($la_config);

		Configure::write($la_config);
	}


	/**
	 * Adds the user configuration to the Awyiss configuration
	 *
	 * @return void
	 */
	public static function loadUserConfiguration(): void {
		$lo_event = EventManager::instance()->dispatch('Authentication.requestIdentity');

		$lo_identity = $lo_event->getResult();
		$la_userConfig = $lo_identity?->getConfiguration();

		if (!$la_userConfig) {
			return;
		}

		$la_userConfig = array_map(function (array $config) {
			return ['Backend' => $config];
		}, $la_userConfig);

		$la_config = Configure::read('Awyiss', []);
		$la_config = Hash::merge($la_config, $la_userConfig);

		Configure::write('Awyiss', $la_config);
	}


	/**
	 * @param string $frontendLanguage
	 * @param string $backendLanguage
	 * @return array
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public static function getDatabaseConfiguration(string $frontendLanguage, string $backendLanguage): array {
		/** @var \Awyiss\Model\Table\ConfigurationTable $lo_configurationTable */
		$lo_configurationTable = FactoryLocator::get('Table')->get('Configuration');

		$lo_query = $lo_configurationTable->find()->enableHydration(false);
		$lo_query->where(function (QueryExpression $exp) use ($frontendLanguage, $backendLanguage) {
			return $exp->or([
				['language_shortcode IS' => null],
				$exp->and([['realm' => Awyiss::REALM_BACKEND], ['language_shortcode' => $backendLanguage]]),
				$exp->and([['realm' => Awyiss::REALM_FRONTEND], ['language_shortcode' => $frontendLanguage]]),
			]);
		});

		$lo_query->orderBy([
			'scope' => 'ASC',
			'realm' => 'ASC',
			'identifier' => 'ASC',
			'language_shortcode IS null' => 'ASC',
			'language_shortcode' => 'ASC',
		]);

		$la_config = [];

		foreach ($lo_query->all() as $la_item) {
			$la_item['identifier'] = array_map(function (string $identifier) {
				return ConfigOptionsProvider::sanitizeIdentifier($identifier);
			}, explode('.', $la_item['identifier']));

			$ls_path = implode('.', [
				'Awyiss',
				ConfigOptionsProvider::sanitizeScope($la_item['scope']),
				Inflector::camelize($la_item['realm']),
				...$la_item['identifier'],
			]);

			$la_item['value'] = ConfigOptionsProvider::typecastConfigValue(
				$la_item['scope'],
				$la_item['realm'],
				implode('.', $la_item['identifier']),
				$la_item['value'],
				$la_item['language_shortcode']
			);

			if (!isset($la_config[ $ls_path ])) {
				$la_config[ $ls_path ] = $la_item['value'];
			}
		}

		return $la_config;
	}


	/**
	 * @param array $config
	 * @return array
	 */
	public static function getFileConfiguration(array $config): array {
		/** @var \Awyiss\Configuration\AbstractConfigOptions $lo_configOptions */
		foreach (ConfigOptionsProvider::getConfigOptionsFiles(true) as $ls_scope => $lo_configOptionsFile) {
			if (!$lo_configOptionsFile) {
				continue;
			}

			/**
			 * @var string $ls_realm
			 * @var \Awyiss\Configuration\ConfigOptionCollection $lo_realmConfigOptionCollection
			 */
			foreach ($lo_configOptionsFile->getConfigOptions() as $ls_realm => $lo_realmConfigOptionCollection) {
				/** @var \Awyiss\Configuration\ConfigOption $lo_configOption */
				foreach ($lo_realmConfigOptionCollection->getConfigOptions() as $ls_key => $lo_configOption) {
					$ls_key = 'Awyiss.' . $ls_scope . '.' . $ls_realm . '.' . $ls_key;
					if (!array_key_exists($ls_key, $config)) {
						/** @noinspection PhpVariableNamingConventionInspection */
						$config[ $ls_key ] = $lo_configOption->getDefaultValue();
					}
				}
			}
		}

		return $config;
	}
}
