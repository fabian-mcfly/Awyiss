<?php declare(strict_types=1);


namespace Awyiss;


use Awyiss\Configuration\ConfigOptionsProvider;
use Awyiss\Controller\ControllerFactory;
use Awyiss\Event\EventListenersProvider;
use Awyiss\Middleware\RoutingMiddleware;
use Awyiss\Model\Table;
use Awyiss\ORM\Locator\TableLocator;
use Awyiss\Routing\Router;
use Cake\Core\Configure;
use Cake\Core\ContainerInterface;
use Cake\Core\Plugin;
use Cake\Database\Expression\QueryExpression;
use Cake\Datasource\FactoryLocator;
use Cake\Error\Middleware\ErrorHandlerMiddleware;
use Cake\Event\Event;
use Cake\Event\EventManager;
use Cake\Event\EventManagerInterface;
use Cake\Http\BaseApplication;
use Cake\Http\ControllerFactoryInterface;
use Cake\Http\Middleware\BodyParserMiddleware;
use Cake\Http\MiddlewareQueue;
use Cake\Routing\Middleware\AssetMiddleware;
use Cake\Routing\RouteBuilder;
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
	protected ClassLoader $classLoader;
	protected static ?string $realm = null;
	protected static ?string $language = null;


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

		$ls_file = CUSTOM_CONFIG . 'bootstrap.php';
		if (is_file($ls_file)) {
			require_once $ls_file;
		}

		$ls_file = ENV_CUSTOM_CONFIG . 'bootstrap.php';
		if (is_file($ls_file)) {
			require_once $ls_file;
		}

		/*
		 * At this point we know where the customer-specific files will be.
		 * so we add the path to the autoloader for the customer-specific namespace
		 */
		$this->classLoader->addPsr4(CUSTOM_NAMESPACE . '\\', [ROOT . DS . CUSTOM_DIR], true);


		EventListenersProvider::loadListener('general_events', 'Global');


		if (PHP_SAPI === 'cli') {
			Configure::write('Bake.theme', 'AwyissBake');
			EventListenersProvider::loadListener('general_events', 'Bake');

			FactoryLocator::add('Table', (new TableLocator())->allowFallbackClass(true)->setFallbackClassName(Table::class));
		}
		else {
			FactoryLocator::add('Table', (new TableLocator())->allowFallbackClass(false));
		}

		//$this->addPlugin('Authentication');
		//$this->addPlugin('Queue');

		$la_plugins = include $this->configDir . 'plugins.php';
		if (is_array($la_plugins)) {
			$this->plugins->addFromConfig($la_plugins);
		}

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

		static::loadConstants();
	}


	/**
	 * @inheritDoc
	 * @param MiddlewareQueue $ao_middlewareQueue The middleware queue to set up.
	 * @return MiddlewareQueue The updated middleware queue.
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function middleware(MiddlewareQueue $ao_middlewareQueue): MiddlewareQueue {
		$ao_middlewareQueue->add(new ErrorHandlerMiddleware(Configure::read('Error'), $this));

		$ao_middlewareQueue->add(new AssetMiddleware([
			'cacheTime' => Configure::read('Asset.cacheTime'),
		]));

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

			$ls_file = ENV_CUSTOM_CONFIG . 'routes_backend.php';
			if (is_file($ls_file)) {
				require_once $ls_file;
			}

			$ls_file = CUSTOM_CONFIG . 'routes_backend.php';
			if (is_file($ls_file)) {
				require_once $ls_file;
			}

			require $this->configDir . 'routes_backend.php';

			/**
			 * Now after the backend-related routes were loaded, we can load general routes
			 * - for the environment
			 * - for the custom_dir
			 * - for Awyiss
			 */

			$ls_file = ENV_CUSTOM_CONFIG . 'routes.php';
			if (is_file($ls_file)) {
				require_once $ls_file;
			}

			$ls_file = CUSTOM_CONFIG . 'routes.php';
			if (is_file($ls_file)) {
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
	 * @inheritDoc
	 * @param ContainerInterface $ao_container The Container to update.
	 * @return void
	 * @link https://book.cakephp.org/4/en/development/dependency-injection.html#dependency-injection
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function services(ContainerInterface $ao_container): void {
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
	public static function loadConfiguration(?string $as_frontendLanguage = null, ?string $as_backendLanguage = null): void {
		$lo_configurationTable = FactoryLocator::get('Table')->get('Configuration');

		$ls_fileName = Inflector::underscore(CUSTOM_NAMESPACE);
		if ($as_frontendLanguage) {
			$ls_fileName .= '[' . $as_frontendLanguage . ']';

			if ($as_backendLanguage) {
				$ls_fileName .= '[' . $as_backendLanguage . ']';
			}
		}

		/*
		 * If the config path `Awyiss` is not empty, we do have a config file
		 * Therefore loading the database config is skipped
		 */
		Configure::load($ls_fileName, 'default', false);
		if (Configure::read('Awyiss')) {
			return;
		}

		if ($as_frontendLanguage && $as_backendLanguage) {
			$lo_query = $lo_configurationTable->find()->enableHydration(false);
			$lo_query->where(function (QueryExpression $ao_exp) use ($as_frontendLanguage, $as_backendLanguage) {
				//$lo_scopeNegated = $lo_query->newExpr()->and(['identifier NOT LIKE' => 'frontend.%'])->add(['identifier NOT LIKE' => 'backend.%']);

				return $ao_exp->or([
					['language_shortcode IS' => null],
					$ao_exp->and([['realm' => Awyiss::REALM_BACKEND], ['language_shortcode' => $as_backendLanguage]]),
					$ao_exp->and([['realm' => Awyiss::REALM_FRONTEND], ['language_shortcode' => $as_frontendLanguage]]),
					//$ao_exp->and([$lo_scopeNegated, ['language_shortcode IS NOT' => null]]),
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
			dd('foobar', __FILE__, __LINE__);
		}

		$la_config = [];
		foreach ($lo_query->all() as $la_item) {
			$ls_path = 'Awyiss.' . ConfigOptionsProvider::sanitizeScope($la_item['scope']) . '.' . $la_item['realm'] . '.' . $la_item['identifier'];

			$la_item['value'] = ConfigOptionsProvider::typecastConfigValue(
				$la_item['scope'],
				$la_item['realm'],
				$la_item['identifier'],
				$la_item['value']
			);

			if (!isset($la_config[ $ls_path ])) {
				$la_config[ $ls_path ] = $la_item['value'];
			}
		}

		/** @var \Awyiss\Configuration\AbstractConfigOptions $lo_configOptions */
		foreach (ConfigOptionsProvider::getConfigOptionsFiles(true) as $lo_configOptionsFiles) {
			if (!$lo_configOptionsFiles) {
				continue;
			}

			$ls_scope = $lo_configOptionsFiles::getScope();
			/**
			 * @var string $ls_realm
			 * @var \Awyiss\Configuration\ConfigOptionCollection $lo_realmConfigOptionCollection
			 */
			foreach ($lo_configOptionsFiles->getConfigOptions() as $ls_realm => $lo_realmConfigOptionCollection) {
				/** @var \Awyiss\Configuration\ConfigOption $lo_configOption */
				foreach ($lo_realmConfigOptionCollection->getConfigOptions() as $ls_key => $lo_configOption) {
					$ls_key = 'Awyiss.' . $ls_scope . '.' . $ls_realm . '.' . $ls_key;
					if (!array_key_exists($ls_key, $la_config)) {
						$la_config[ $ls_key ] = $lo_configOption->getDefaultValue();
					}
				}
			}
		}

		Configure::delete('Awyiss');
		Configure::write($la_config);
	}


	/**
	 * @param bool $ab_useFile
	 * @return void
	 */
	public static function loadConstants(bool $ab_useFile = true): void {
		$ls_filePath = ENV_CUSTOM_CONFIG . 'constants.php';

		if (file_exists($ls_filePath) && $ab_useFile) {
			require_once $ls_filePath;


			return;
		}

		$lo_pageRolesTable = FactoryLocator::get('Table')->get('PageRoles');
		/** @var \Awyiss\Model\Entity\PageRole $lo_pageRole */
		foreach ($lo_pageRolesTable->find() as $lo_pageRole) {
			$ls_constant = 'PAGEROLE_' . strtoupper($lo_pageRole->identifier);
			defined($ls_constant) || define($ls_constant, $lo_pageRole->id);
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
	 * @return string|null
	 */
	public static function getLanguage(): ?string {
		return self::$language;
	}


	/**
	 * @param string|null $as_language
	 * @noinspection PhpUnused
	 */
	public static function setLanguage(?string $as_language): void {
		self::$language = $as_language;
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
}
