<?php declare(strict_types=1);


namespace Awyiss;


use Awyiss\Configuration\ConfigOptionsProvider;
use Awyiss\Controller\ControllerFactory;
use Awyiss\Event\EventListenersProvider;
use Awyiss\Event\EventManager;
use Awyiss\Middleware\RoutingMiddleware;
use Awyiss\Model\Table;
use Awyiss\ORM\Locator\TableLocator;
use Awyiss\Routing\Router;
use Cake\Console\CommandCollection;
use Cake\Core\Configure;
use Cake\Core\ContainerInterface;
use Cake\Core\Plugin;
use Cake\Database\Expression\QueryExpression;
use Cake\Datasource\FactoryLocator;
use Cake\Error\Middleware\ErrorHandlerMiddleware;
use Cake\Event\Event;
use Cake\Event\EventManagerInterface;
use Cake\Http\BaseApplication;
use Cake\Http\ControllerFactoryInterface;
use Cake\Http\Middleware\BodyParserMiddleware;
use Cake\Http\MiddlewareQueue;
use Cake\Routing\Middleware\AssetMiddleware;
use Cake\Routing\RouteBuilder;
use Cake\Utility\Hash;
use Cake\Utility\Inflector;
use Composer\Autoload\ClassLoader;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;


/**
 * @inheritDoc
 */
class Awyiss extends BaseApplication {
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
	 * @var \Composer\Autoload\ClassLoader
	 */
	protected ClassLoader $classLoader;


	/**
	 * @inheritDoc
	 * @param ClassLoader $loader
	 * @param EventManagerInterface|null $eventManager Application event manager instance.
	 * @param ControllerFactoryInterface|null $controllerFactory Controller factory.
	 * @noinspection PhpMissingParentConstructorInspection
	 */
	public function __construct(ClassLoader $loader, ?EventManagerInterface $eventManager = null, ?ControllerFactoryInterface $controllerFactory = null) {
		$this->classLoader = $loader;
		$this->plugins = Plugin::getCollection();
		$this->_eventManager = $eventManager ?: EventManager::instance();
		$this->controllerFactory = $controllerFactory;

		$this->configDir = dirname(__DIR__) . DS . 'awyiss' . DS . 'config' . DS;
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
			$this->classLoader->addPsr4(CUSTOM_NAMESPACE . '\\', [ROOT . DS . CUSTOM_DIR], true);
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
	 * @param \Cake\Console\CommandCollection $commands
	 * @return \Cake\Console\CommandCollection
	 */
	public function console(CommandCollection $commands): CommandCollection {
		$la_paths = [];

		if (defined('CUSTOM_NAMESPACE')) {
			$la_paths[ implode('\\', [CUSTOM_NAMESPACE, 'Command']) ] = implode(DS, [ROOT, CUSTOM_DIR, 'Command', '*', '*' . 'Command.php']);
		}

		$la_paths[ implode('\\', ['Awyiss', 'Command']) ] = implode(DS, [ROOT, APP_DIR, 'Command', '*', '*' . 'Command.php']);

		$la_commands = [];
		foreach ($la_paths as $ls_namespace => $ls_path) {
			foreach (glob($ls_path) as $ls_filePath) {
				$la_parts = explode(DS, $ls_filePath);

				$ls_commandName = array_pop($la_parts);
				$ls_subPath = array_pop($la_parts);

				/** @var class-string<\Cake\Console\BaseCommand> $ls_className */
				$ls_className = $ls_namespace . '\\' . $ls_subPath . '\\' . substr($ls_commandName, 0, -4);
				$ls_command = $ls_className::defaultName();

				$ls_subPath = Inflector::underscore($ls_subPath);
				if (!str_starts_with($ls_command, $ls_subPath . ' ')) {
					$ls_command = $ls_subPath . ' ' . $ls_command;
				}

				if (!isset($la_commands[ $ls_command ])) {
					$la_commands[ $ls_command ] = $ls_className;
				}
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
	 * @param RouteBuilder $routes A route builder to add routes into.
	 * @return void
	 */
	public function routes(RouteBuilder $routes): void {
		// Only load routes if the router is empty
		if (!Router::routes()) {
			/**
			 * This logic is tricky: we first need to load backend-related routes
			 * - for the environment
			 * - for the custom_dir
			 * - for Awyiss
			 */

			if (defined('CUSTOM_CONFIG')) {
				$ls_file = ENV_CUSTOM_CONFIG . 'routes_backend.php';
				if (is_file($ls_file)) {
					require_once $ls_file;
				}

				$ls_file = CUSTOM_CONFIG . 'routes_backend.php';
				if (is_file($ls_file)) {
					require_once $ls_file;
				}
			}

			require $this->configDir . 'routes_backend.php';

			/**
			 * Now after the backend-related routes were loaded, we can load general routes
			 * - for the environment
			 * - for the custom_dir
			 * - for Awyiss
			 */

			if (defined('CUSTOM_CONFIG')) {
				$ls_file = ENV_CUSTOM_CONFIG . 'routes.php';
				if (is_file($ls_file)) {
					require_once $ls_file;
				}

				$ls_file = CUSTOM_CONFIG . 'routes.php';
				if (is_file($ls_file)) {
					require_once $ls_file;
				}
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
	 * @inheritDoc
	 * @param ServerRequestInterface $request The request
	 * @return ResponseInterface
	 * @throws \Psr\Container\ContainerExceptionInterface
	 * @throws \Psr\Container\NotFoundExceptionInterface
	 * @throws \ReflectionException
	 */
	public function handle(ServerRequestInterface $request): ResponseInterface {
		if ($this->controllerFactory === null) {
			$this->controllerFactory = new ControllerFactory($this->getContainer());
		}

		if (Router::getRequest() !== $request) {
			/** @noinspection PhpParamsInspection */
			Router::setRequest($request);
		}

		$lo_controller = $this->controllerFactory->create($request);

		return $this->controllerFactory->invoke($lo_controller);
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
	 * @throws \ReflectionException
	 */
	public static function loadConfiguration(string $frontendLanguage, string $backendLanguage, bool $forceReload = false): void {
		/** @var \Awyiss\Model\Table\ConfigurationTable $lo_configurationTable */
		$lo_configurationTable = FactoryLocator::get('Table')->get('Configuration');

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
				static::addUserConfiguration();

				return;
			}

			/**
			 * Trigger the creation of the custom configuriation
			 *
			 * @see \Awyiss\Event\Backend\ConfigurationListener::createCustomConfiguration()
			 */
			$lo_eventManager = EventManager::instance();
			$lo_eventManager->dispatch('Configuration.createCustomConfiguration');

			Configure::load($ls_fileName, 'default', false);
			if (Configure::read('Awyiss')) {
				static::addUserConfiguration();

				return;
			}
		}

		Configure::delete('Awyiss');

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
					if (!array_key_exists($ls_key, $la_config)) {
						$la_config[ $ls_key ] = $lo_configOption->getDefaultValue();
					}
				}
			}
		}

		ksort($la_config);

		Configure::write($la_config);

		if (!$forceReload) {
			static::addUserConfiguration();
		}
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
	 * Adds the user configuration to the Awyiss configuration
	 *
	 * @return void
	 */
	public static function addUserConfiguration(): void {
		$lo_event = EventManager::instance()->dispatch('Authentication.requestIdentity');

		$lo_identity = $lo_event->getResult();
		$la_userConfig = $lo_identity?->getConfiguration();

		if (!$la_userConfig) {
			return;
		}

		$la_userConfig = array_map(function (array $config) {
			return ['Backend' => $config];
		}, $la_userConfig);

		$la_config = Configure::read('Awyiss');
		$la_config = Hash::merge($la_config, $la_userConfig);

		Configure::write('Awyiss', $la_config);
	}
}
