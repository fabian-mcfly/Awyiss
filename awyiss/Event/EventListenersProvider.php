<?php declare(strict_types=1);


namespace Awyiss\Event;


use Awyiss\Core\App;
use Awyiss\Utility\Inflector;
use Cake\Event\EventListenerInterface;
use Cake\Event\EventManager;
use Cake\Utility\Text;
use RuntimeException;


/**
 * Provider of EventListeners
 * This is used to retrieve or load the EventListeners for a specific scope and area
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
	 */
	public static function getListener(string $scope, string $realm): ?string {
		$scope = static::sanitizeScope($scope);

		if (!isset(static::$eventListeners[ $realm ])) {
			static::$eventListeners[ $realm ] = [];
		}

		if (empty(static::$eventListeners[ $realm ][ $scope ])) {
			static::$eventListeners[ $realm ] += static::findListener($scope, $realm);
		}


		return static::$eventListeners[ $realm ][ $scope ] ?? null;
	}


	/**
	 * @param string $scope
	 * @param string $realm
	 * @return bool
	 */
	public static function loadListener(string $scope, string $realm): bool {
		$scope = static::sanitizeScope($scope);

		if (!isset(static::$loadedListeners[ $realm ])) {
			static::$loadedListeners[ $realm ] = [];
		}

		if (array_key_exists($scope, static::$loadedListeners[ $realm ])) {
			return static::$loadedListeners[ $realm ][ $scope ];
		}

		$listenerClass = static::getListener($scope, $realm);

		if (!$listenerClass) {
			static::$loadedListeners[ $realm ][ $scope ] = false;


			return false;
		}

		static::$loadedListeners[ $realm ][ $scope ] = true;

		EventManager::instance()->on(new $listenerClass());


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
	 */
	protected static function findListener(string $scope, string $realm): array {
		$classes = App::classes($scope, 'Event/' . $realm, 'Listener', EventListenerInterface::class);

		$listeners = [];

		/** @var class-string<\Cake\Event\EventListenerInterface> $className */
		foreach ($classes as $className) {
			$scope = static::extractScopeFromClassName($className);

			$listeners[ $scope ] ??= $className;
		}

		return $listeners;
	}


	/**
	 * @param string $scope
	 * @param int $suffixLength
	 * @return string
	 */
	public static function extractScopeFromClassName(string $scope, int $suffixLength = 8): string {
		$parts = explode('\\', trim($scope, '\\'));
		$scope = array_pop($parts);
		$scope = substr($scope, 0, -$suffixLength);

		return static::sanitizeScope($scope);
	}
}
