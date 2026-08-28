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
	 * The name of the frontend realm
	 */
	final public const string REALM_FRONTEND = 'Frontend';
	/**
	 * The name of the backend realm
	 */
	final public const string REALM_BACKEND = 'Backend';
	/**
	 * The version of Awyiss
	 */
	final public const string VERSION = '0.3.1';
	/**
	 * The name of the version
	 */
	final public const string VERSION_NAME = 'Interface';


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
			$file = CUSTOM_CONFIG . 'bootstrap.php';
			if (is_file($file)) {
				require_once $file;
			}

			$file = ENV_CUSTOM_CONFIG . 'bootstrap.php';
			if (is_file($file)) {
				require_once $file;
			}
		}

		/*
		 * At this point we know where the customer-specific files will be.
		 * so we add the path to the autoloader for the customer-specific namespace
		 */
		if (defined('CUSTOM_NAMESPACE')) {
			$this->classLoader?->addPsr4(CUSTOM_NAMESPACE . '\\', [ROOT . DS . CUSTOM_DIR], true);
		}


		EventListenersProvider::loadListener('generalEvents', 'Global');


		if (PHP_SAPI === 'cli') {
			EventListenersProvider::loadListener('generalEvents', 'Bake');

			FactoryLocator::add('Table', new TableLocator()->allowFallbackClass(true)->setFallbackClassName(Table::class));
		}
		else {
			FactoryLocator::add('Table', new TableLocator()->allowFallbackClass(false));
		}

		$plugins = include $this->configDir . 'plugins.php';
		if (is_array($plugins)) {
			$this->plugins->addFromConfig($plugins);
		}

		if (defined('CUSTOM_CONFIG')) {
			$file = CUSTOM_CONFIG . 'plugins.php';
			if (is_file($file)) {
				$plugins = include $file;
				if (is_array($plugins)) {
					$this->plugins->addFromConfig($plugins);
				}
			}

			$file = ENV_CUSTOM_CONFIG . 'plugins.php';
			if (is_file($file)) {
				$plugins = include $file;
				if (is_array($plugins)) {
					$this->plugins->addFromConfig($plugins);
				}
			}
		}

		$this->registerEvents();
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
		$classes = App::classes('*', 'Command', 'Command', CommandInterface::class, '*');

		$foundCommands = [];
		/** @var class-string<\Cake\Console\CommandInterface::class> $commandClass */
		foreach ($classes as $subNamespace => $commandClass) {
			$command = $commandClass::defaultName();

			if (str_contains($subNamespace, '\\')) {
				$subPath = substr($subNamespace, 0, strpos($subNamespace, '\\'));
				$subPath = Inflector::underscore($subPath);
				if (!str_starts_with($command, $subPath . ' ')) {
					$command = $subPath . ' ' . $command;
				}
			}

			if (!isset($foundCommands[ $command ])) {
				$foundCommands[ $command ] = $commandClass;
			}
		}

		if ($foundCommands) {
			$commands->addMany($foundCommands);
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

		require $this->configDir . 'routes.php';

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

		$file = CUSTOM_CONFIG . 'services.php';
		if (is_file($file)) {
			require_once $file;
		}

		$file = ENV_CUSTOM_CONFIG . 'services.php';
		if (is_file($file)) {
			require_once $file;
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
		$container = $this->getContainer();
		$container->add(ServerRequest::class, $request);
		$container->add(ContainerInterface::class, $container);

		$this->controllerFactory ??= new ControllerFactory($container);

		if (Router::getRequest() !== $request) {
			/** @noinspection PhpParamsInspection */
			Router::setRequest($request);
		}

		$controller = $this->controllerFactory->create($request);

		return $this->controllerFactory->invoke($controller);
	}


	/**
	 * return bool
	 */
	public static function hasRealm(): bool {
		return isset(static::$realm);
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

		$eventManager = EventManager::instance();
		$event = new Event('Awyiss.setRealm', null, [
			'realm' => static::$realm,
		]);
		$eventManager->dispatch($event);
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
		$fileName = Inflector::underscore(CUSTOM_NAMESPACE);
		$fileName .= '[' . $frontendLanguage . ']';
		$fileName .= '[' . $backendLanguage . ']';

		if (!$forceReload) {
			/*
			 * If the config path `Awyiss` is not empty, we do have a config file
			 * Therefore loading the database config is skipped
			 */
			Configure::load($fileName, 'default', false);
			if (Configure::read('Awyiss')) {
				return;
			}

			/**
			 * Trigger the creation of the custom configuration
			 *
			 * @see \Awyiss\Event\Backend\ConfigurationListener::createCustomConfiguration()
			 */
			$eventManager = EventManager::instance();
			$eventManager->dispatch('Awyiss.Configuration.createCustomConfiguration');

			// Try to load the config file again
			Configure::load($fileName, 'default', false);
			if (Configure::read('Awyiss')) {
				return;
			}
		}

		Configure::delete('Awyiss');

		$config = static::getDatabaseConfiguration($frontendLanguage, $backendLanguage);
		$config = static::getFileConfiguration($config);

		ksort($config);

		Configure::write($config);
	}


	/**
	 * Adds the user configuration to the Awyiss configuration
	 *
	 * @return void
	 */
	public static function loadUserConfiguration(): void {
		$event = EventManager::instance()->dispatch('Authentication.requestIdentity');

		$identity = $event->getResult();
		$userConfig = $identity?->getConfiguration();

		if (!$userConfig) {
			return;
		}

		$userConfig = array_map(function (array $config) {
			return ['Backend' => $config];
		}, $userConfig);

		$config = Configure::read('Awyiss', []);
		$config = Hash::merge($config, $userConfig);


		// Make sure only unique values are written to the config
		$uniqueConfig = function ($data) use (&$uniqueConfig) {
			if (!is_array($data)) {
				return $data;
			}

			// Check if array contains only scalar values
			$hasOnlyScalars = array_all($data, function (mixed $value): bool {
				return is_scalar($value);
			});

			// If array contains only scalars, apply array_unique
			if ($hasOnlyScalars) {
				return array_unique($data);
			}

			// Otherwise, recurse into nested arrays
			return array_map($uniqueConfig, $data);
		};

		$config = $uniqueConfig($config);

		Configure::write('Awyiss', $config);
	}


	/**
	 * @param string $frontendLanguage
	 * @param string $backendLanguage
	 * @return array
	 */
	public static function getDatabaseConfiguration(string $frontendLanguage, string $backendLanguage): array {
		/** @var \Awyiss\Model\Table\ConfigurationTable $configurationTable */
		$configurationTable = FactoryLocator::get('Table')->get('Configuration');

		$query = $configurationTable->find()->enableHydration(false);
		$query->where(function (QueryExpression $exp) use ($frontendLanguage, $backendLanguage) {
			return $exp->or([
				['languageShortcode IS' => null],
				$exp->and([['realm' => Awyiss::REALM_BACKEND], ['languageShortcode' => $backendLanguage]]),
				$exp->and([['realm' => Awyiss::REALM_FRONTEND], ['languageShortcode' => $frontendLanguage]]),
			]);
		});

		$query->orderBy([
			'scope' => 'ASC',
			'realm' => 'ASC',
			'identifier' => 'ASC',
			'languageShortcode IS null' => 'ASC',
			'languageShortcode' => 'ASC',
		]);

		$config = [];

		foreach ($query->all() as $item) {
			$item['identifier'] = array_map(function (string $identifier) {
				return ConfigOptionsProvider::sanitizeIdentifier($identifier);
			}, explode('.', $item['identifier']));

			$path = implode('.', [
				'Awyiss',
				ConfigOptionsProvider::sanitizeScope($item['scope']),
				Inflector::camelize($item['realm']),
				...$item['identifier'],
			]);

			$item['value'] = ConfigOptionsProvider::typecastConfigValue(
				$item['scope'],
				$item['realm'],
				implode('.', $item['identifier']),
				$item['value'],
				$item['languageShortcode']
			);

			if (!isset($config[ $path ])) {
				$config[ $path ] = $item['value'];
			}
		}

		return $config;
	}


	/**
	 * @param array $config
	 * @return array
	 */
	public static function getFileConfiguration(array $config): array {
		/** @var \Awyiss\Configuration\AbstractConfigOptions $configOptionsFile */
		foreach (ConfigOptionsProvider::getConfigOptionsFiles(true) as $scope => $configOptionsFile) {
			if (!$configOptionsFile) {
				continue;
			}

			/**
			 * @var string $realm
			 * @var \Awyiss\Configuration\ConfigOptionsCollection $realmConfigOptionCollection
			 */
			foreach ($configOptionsFile->getConfigOptions() as $realm => $realmConfigOptionCollection) {
				/** @var \Awyiss\Configuration\ConfigOption $configOption */
				foreach ($realmConfigOptionCollection->getConfigOptions() as $key => $configOption) {
					$key = 'Awyiss.' . $scope . '.' . $realm . '.' . $key;
					if (!array_key_exists($key, $config)) {
						$config[ $key ] = $configOption->getDefaultValue();
					}
				}
			}
		}

		return $config;
	}
}
