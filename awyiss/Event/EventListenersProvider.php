<?php declare(strict_types=1);


namespace Awyiss\Event;


use Cake\Event\EventListenerInterface;
use Cake\Event\EventManager;
use Cake\Utility\Inflector;
use Cake\Utility\Text;
use ReflectionClass;
use RuntimeException;


/**
 * Provider of EventListeners
 *
 * This is used to retreive or load the EventListeners for a specific scope and area
 *
 * Controller:
 * - `EventListenersProvider::loadListener($this->getName(), Awyiss::DOMAIN_BACKEND);`
 *
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


	private function __construct () {
		throw new RuntimeException(sprintf('The class `%s` cannot be instantiated', self::class));
	}


	/**
	 * @param string $as_realm
	 *
	 * @return array
	 * @throws \ReflectionException
	 *
	 * @noinspection PhpUnused
	 */
	public static function getListeners (string $as_realm): array {
		static::$eventListeners[ $as_realm ] = static::findListener('*', $as_realm);

		return static::$eventListeners[ $as_realm ] ?? [];
	}


	/**
	 * @param string $as_scope
	 * @param string $as_realm
	 *
	 * @return null|string
	 * @throws \ReflectionException
	 */
	public static function getListener (string $as_scope, string $as_realm): ?string {
		$ls_scope = static::sanitizeScope($as_scope);

		if ( ! isset(static::$eventListeners[ $as_realm ])) {
			static::$eventListeners[ $as_realm ] = [];
		}

		if (empty(static::$eventListeners[ $as_realm ][ $ls_scope ])) {
			static::$eventListeners[ $as_realm ] += static::findListener($ls_scope, $as_realm);
		}

		return static::$eventListeners[ $as_realm ][ $ls_scope ] ?? NULL;
	}


	/**
	 * @param string $as_scope
	 * @param string $as_realm
	 *
	 * @return void
	 * @throws \ReflectionException
	 */
	public static function loadListener (string $as_scope, string $as_realm): void {
		$ls_scope = static::sanitizeScope($as_scope);

		if ( ! isset(static::$loadedListeners[ $as_realm ])) {
			static::$loadedListeners[ $as_realm ] = [];
		}

		if (array_key_exists($ls_scope, static::$loadedListeners[ $as_realm ])) {
			return;
		}

		$ls_listenerClass = static::getListener($ls_scope, $as_realm);
		if ( ! $ls_listenerClass) {
			static::$loadedListeners[ $as_realm ][ $ls_scope ] = FALSE;

			return;
		}

		static::$loadedListeners[ $as_realm ][ $ls_scope ] = TRUE;

		EventManager::instance()->on(new $ls_listenerClass());
	}


	/**
	 * @param string $as_scope
	 * @param string $as_realm
	 *
	 * @return array
	 * @throws \ReflectionException
	 */
	protected static function findListener (string $as_scope, string $as_realm): array {
		$la_listeners = [];

		$la_paths = [
			'\\' . CUSTOM_NAMESPACE . '\Event\\' . $as_realm . '\\' => implode(DS, [ROOT, CUSTOM_DIR, 'Event', $as_realm, $as_scope . 'Listener.php',]),
			'\Awyiss\Event\\' . $as_realm . '\\' => implode(DS, [ROOT, APP_DIR, 'Event', $as_realm, $as_scope . 'Listener.php']),
		];

		foreach ($la_paths as $ls_namespace => $ls_path) {
			foreach (glob($ls_path) as $ls_filePath) {
				$ls_listenerName = substr($ls_filePath, strrpos($ls_filePath, DS) + 1, -4);

				if (str_starts_with($ls_listenerName, '_')) {
					continue;
				}

				$ls_listenerClass = $ls_namespace . $ls_listenerName;

				$lo_reflection = new ReflectionClass($ls_listenerClass);

				if ( ! $lo_reflection->implementsInterface(EventListenerInterface::class)) {
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


	/**
	 * Sanitize the provided scope by removing all non-ascii characters
	 * Returns a camelBacked string
	 *
	 * @param string $as_scope
	 *
	 * @return string
	 */
	public static function sanitizeScope (string $as_scope): string {
		return Inflector::camelize(Inflector::pluralize(Text::slug($as_scope, '_')));
	}
}