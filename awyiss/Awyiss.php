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
use UnexpectedValueException;


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
	 * @param ClassLoader $ao_loader
	 * @param EventManagerInterface|null $ao_eventManager Application event manager instance.
	 * @param ControllerFactoryInterface|null $ao_controllerFactory Controller factory.
	 * @noinspection PhpMissingParentConstructorInspection
	 */
	public function __construct(ClassLoader $ao_loader, ?EventManagerInterface $ao_eventManager = null, ?ControllerFactoryInterface $ao_controllerFactory = null) {
		$this->classLoader = $ao_loader;
		$this->plugins = Plugin::getCollection();
		$this->_eventManager = $ao_eventManager ?: EventManager::instance();
		$this->controllerFactory = $ao_controllerFactory;

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
	 * @param \Cake\Console\CommandCollection $ao_commands
	 * @return \Cake\Console\CommandCollection
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function console(CommandCollection $ao_commands): CommandCollection {
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
			$ao_commands->addMany($la_commands);
		}

		return parent::console($ao_commands);
	}


	/**
	 * @inheritDoc
	 * @param MiddlewareQueue $ao_middlewareQueue The middleware queue to set up.
	 * @return MiddlewareQueue The updated middleware queue.
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function middleware(MiddlewareQueue $ao_middlewareQueue): MiddlewareQueue {
		$ao_middlewareQueue->add(new ErrorHandlerMiddleware(Configure::read('Error'), $this));

		if (Configure::read('debug')) {
			$ao_middlewareQueue->add(new AssetMiddleware([
				'cacheTime' => Configure::read('Asset.cacheTime'),
			]));
		}

		$ao_middlewareQueue->add(new RoutingMiddleware($this));

		$ao_middlewareQueue->add(new BodyParserMiddleware());


		return $ao_middlewareQueue;
	}


	/**
	 * @inheritDoc
	 * @param RouteBuilder $ao_routes A route builder to add routes into.
	 * @return void
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function routes(RouteBuilder $ao_routes): void {
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
	 * @param ContainerInterface $ao_container The Container to update.
	 * @return void
	 * @link https://book.cakephp.org/4/en/development/dependency-injection.html#dependency-injection
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function services(ContainerInterface $ao_container): void {
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
	 * @param ServerRequestInterface $ao_request The request
	 * @return ResponseInterface
	 * @throws \Psr\Container\ContainerExceptionInterface
	 * @throws \Psr\Container\NotFoundExceptionInterface
	 * @throws \ReflectionException
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function handle(ServerRequestInterface $ao_request): ResponseInterface {
		if ($this->controllerFactory === null) {
			$this->controllerFactory = new ControllerFactory($this->getContainer());
		}

		if (Router::getRequest() !== $ao_request) {
			/** @noinspection PhpParamsInspection */
			Router::setRequest($ao_request);
		}

		$lo_controller = $this->controllerFactory->create($ao_request);

		return $this->controllerFactory->invoke($lo_controller);
	}


	/**
	 * Loads the Awyiss configuration from either the database or a config file inside the custom namespace.
	 * When loaded from the database, the configuration is dumped into a php config file.
	 *
	 * The filename is the underscored name of the custom namespace,
	 * followed by the frontend language and the backend language, both in square brackets.
	 *
	 * For example:
	 * `example_customer[de][en].php`
	 *
	 * @throws \Exception
	 */
	public static function loadConfiguration(?string $as_frontendLanguage = null, ?string $as_backendLanguage = null, bool $ab_forceReload = false): void {
		/** @var \Awyiss\Model\Table\ConfigurationTable $lo_configurationTable */
		$lo_configurationTable = FactoryLocator::get('Table')->get('Configuration');

		$ls_fileName = Inflector::underscore(CUSTOM_NAMESPACE);
		if ($as_frontendLanguage) {
			$ls_fileName .= '[' . $as_frontendLanguage . ']';

			if ($as_backendLanguage) {
				$ls_fileName .= '[' . $as_backendLanguage . ']';
			}
		}

		if (!$ab_forceReload) {
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

		if ($as_frontendLanguage && $as_backendLanguage) {
			$lo_query = $lo_configurationTable->find()->enableHydration(false);
			$lo_query->where(function (QueryExpression $ao_exp) use ($as_frontendLanguage, $as_backendLanguage) {
				return $ao_exp->or([
					['language_shortcode IS' => null],
					$ao_exp->and([['realm' => Awyiss::REALM_BACKEND], ['language_shortcode' => $as_backendLanguage]]),
					$ao_exp->and([['realm' => Awyiss::REALM_FRONTEND], ['language_shortcode' => $as_frontendLanguage]]),
				]);
			});

			$lo_query->orderBy([
				'scope' => 'ASC',
				'realm' => 'ASC',
				'identifier' => 'ASC',
				'language_shortcode IS null' => 'ASC',
				'language_shortcode' => 'ASC',
			]);
		}
		else {
			throw new UnexpectedValueException(sprintf('Expected string values for frontend and backend language. `%s`/`%s` given', gettype($as_frontendLanguage), gettype($as_backendLanguage)));
		}

		$la_config = [];
		foreach ($lo_query->all() as $la_item) {
			$la_item['identifier'] = array_map(function (string $as_identifier) {
				return ConfigOptionsProvider::sanitizeIdentifier($as_identifier);
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

		if (!$ab_forceReload) {
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
	 * @param string|null $as_realm
	 * @return void
	 */
	public static function setRealm(?string $as_realm): void {
		static::$realm = $as_realm;

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

		$la_userConfig = array_map(function (array $aa_config) {
			return ['Backend' => $aa_config];
		}, $la_userConfig);

		$la_config = Configure::read('Awyiss');
		$la_config = Hash::merge($la_config, $la_userConfig);

		Configure::write('Awyiss', $la_config);
	}
}
