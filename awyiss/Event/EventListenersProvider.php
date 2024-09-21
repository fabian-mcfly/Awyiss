<?php declare(strict_types=1);


namespace Awyiss\Event;


use Awyiss\Utility\Inflector;
use Cake\Event\EventListenerInterface;
use Cake\Event\EventManager;
use Cake\Utility\Text;
use ReflectionClass;
use RuntimeException;


/**
 * Provider of EventListeners
 * This is used to retreive or load the EventListeners for a specific scope and area
 * Controller:
 * - `EventListenersProvider::loadListener($this->getName(), Awyiss::REALM_BACKEND);`
 * CLI:
 * - `EventListenersProvider::loadListener('general_events', 'Bake');`
 */
class EventListenersProvider {
	/**
	 * @var array
	 */
	protected static array $eventListeners = [];
	/**
	 * @var array
	 */
	protected static array $loadedListeners = [];


	/**
	 * Throw an exception on initialization
	 */
	private function __construct() {
		throw new RuntimeException(sprintf('The class `%s` cannot be instantiated', self::class));
	}


	/**
	 * @param string $realm
	 * @return array
	 * @throws \ReflectionException
	 * @noinspection PhpUnused
	 */
	public static function getListeners(string $realm): array {
		static::$eventListeners[ $realm ] = static::findListener('*', $realm);


		return static::$eventListeners[ $realm ] ?? [];
	}


	/**
	 * @param string $scope
	 * @param string $realm
	 * @return string|null
	 * @throws \ReflectionException
	 */
	public static function getListener(string $scope, string $realm): ?string {
		$ls_scope = static::sanitizeScope($scope);

		if (!isset(static::$eventListeners[ $realm ])) {
			static::$eventListeners[ $realm ] = [];
		}

		if (empty(static::$eventListeners[ $realm ][ $ls_scope ])) {
			static::$eventListeners[ $realm ] += static::findListener($ls_scope, $realm);
		}


		return static::$eventListeners[ $realm ][ $ls_scope ] ?? null;
	}


	/**
	 * @param string $scope
	 * @param string $realm
	 * @return bool
	 * @throws \ReflectionException
	 */
	public static function loadListener(string $scope, string $realm): bool {
		$ls_scope = static::sanitizeScope($scope);

		if (!isset(static::$loadedListeners[ $realm ])) {
			static::$loadedListeners[ $realm ] = [];
		}

		if (array_key_exists($ls_scope, static::$loadedListeners[ $realm ])) {
			return static::$loadedListeners[ $realm ][ $ls_scope ];
		}

		$ls_listenerClass = static::getListener($ls_scope, $realm);

		if (!$ls_listenerClass) {
			static::$loadedListeners[ $realm ][ $ls_scope ] = false;


			return false;
		}

		static::$loadedListeners[ $realm ][ $ls_scope ] = true;

		EventManager::instance()->on(new $ls_listenerClass());


		return true;
	}


	/**
	 * Sanitize the provided scope by removing all non-ascii characters
	 * Returns a camelBacked string
	 *
	 * @param string $scope
	 * @return string
	 */
	public static function sanitizeScope(string $scope): string {
		return Inflector::camelize(Text::slug($scope, '_'));
	}


	/**
	 * @return void
	 */
	public static function reset(): void {
		static::$eventListeners = [];
		static::$loadedListeners = [];
	}


	/**
	 * @param string $scope
	 * @param string $realm
	 * @return array
	 * @throws \ReflectionException
	 */
	protected static function findListener(string $scope, string $realm): array {
		$la_paths = [];

		if (defined('CUSTOM_NAMESPACE')) {
			$la_paths['\\' . CUSTOM_NAMESPACE . '\Event\\' . $realm . '\\'] = implode(DS, [ROOT, CUSTOM_DIR, 'Event', $realm, $scope . 'Listener.php']);
		}

		$la_paths['\Awyiss\Event\\' . $realm . '\\'] = implode(DS, [ROOT, APP_DIR, 'Event', $realm, $scope . 'Listener.php']);

		$la_listeners = [];
		foreach ($la_paths as $ls_namespace => $ls_path) {
			foreach (glob($ls_path) as $ls_filePath) {
				$ls_listenerName = substr($ls_filePath, strrpos($ls_filePath, DS) + 1, -4);

				if (str_starts_with($ls_listenerName, '_')) {
					continue;
				}

				$ls_listenerClass = $ls_namespace . $ls_listenerName;

				$lo_reflection = new ReflectionClass($ls_listenerClass);

				if (!$lo_reflection->implementsInterface(EventListenerInterface::class)) {
					throw new RuntimeException(sprintf('The provided Listener class `%s` does not extend the `%s` class.', $ls_listenerClass, EventListenerInterface::class));
				}

				/** @var EventListenerTrait $ls_listenerClass */
				$ls_scope = $ls_listenerClass::getScope();

				if (isset($la_listeners[ $ls_scope ])) {
					continue;
				}

				$la_listeners[ $ls_scope ] = $ls_listenerClass;
			}
		}


		return $la_listeners;
	}
}
