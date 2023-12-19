<?php declare(strict_types=1);


namespace Awyiss\Event;


use Cake\Event\EventListenerInterface;
use Cake\Event\EventManager;
use Cake\Utility\Inflector;
use ReflectionClass;
use RuntimeException;


/**
 * Provider of EventListeners
 *
 * This is used to retreive or load the EventListeners for a specific scope and area
 *
 * Controller:
 * - `EventListenersProvider::loadListener($this->getName(), 'backend');`
 *
 * CLI:
 * - `EventListenersProvider::loadListener('general_events', 'bake');`
 */
class EventListenersProvider {
	protected static array $eventListeners = [];
	protected static array $loadedListeners = [];


	private function __construct () {
		throw new RuntimeException(sprintf('The class `%s` cannot be instantiated', self::class));
	}


	/**
	 * @param string $as_type
	 *
	 * @return array
	 * @throws \ReflectionException
	 *
	 * @noinspection PhpUnused
	 */
	public static function getListeners (string $as_type): array {
		$ls_type = Inflector::camelize($as_type);

		static::$eventListeners[ $ls_type ] = static::findListener('*', $ls_type);

		return static::$eventListeners[ $ls_type ] ?? [];
	}


	/**
	 * @param string $as_name
	 * @param string $as_type
	 *
	 * @return null|string
	 * @throws \ReflectionException
	 */
	public static function getListener (string $as_name, string $as_type): ?string {
		$ls_name = Inflector::underscore($as_name);
		$ls_type = Inflector::camelize($as_type);

		if (!isset(static::$eventListeners[ $ls_type ])) {
			static::$eventListeners[ $ls_type ] = [];
		}

		if (empty(static::$eventListeners[ $ls_type ][ $ls_name ])) {
			static::$eventListeners[ $ls_type ] += static::findListener($ls_name, $ls_type);
		}

		return static::$eventListeners[ $ls_type ][ $ls_name ] ?? NULL;
	}


	/**
	 * @param string $as_name
	 * @param string $as_type
	 *
	 * @return void
	 * @throws \ReflectionException
	 */
	public static function loadListener (string $as_name, string $as_type): void {
		$ls_name = Inflector::underscore($as_name);
		$ls_type = Inflector::camelize($as_type);

		if (!isset(static::$loadedListeners[ $ls_type ])) {
			static::$loadedListeners[ $ls_type ] = [];
		}

		if (array_key_exists($ls_name, static::$loadedListeners[ $ls_type ])) {
			return;
		}

		$ls_listenerClass = static::getListener($ls_name, $as_type);
		if ( ! $ls_listenerClass) {
			static::$loadedListeners[ $ls_type ][ $ls_name ] = FALSE;
			return;
		}

		static::$loadedListeners[ $ls_type ][ $ls_name ] = TRUE;

		EventManager::instance()->on(new $ls_listenerClass());
	}


	/**
	 * @param string $as_name
	 * @param string $as_type
	 *
	 * @return array
	 * @throws \ReflectionException
	 */
	protected static function findListener (string $as_name, string $as_type): array {
		$la_listeners = [];
		$ls_name = Inflector::camelize($as_name);
		$ls_type = Inflector::camelize($as_type);

		$la_paths = [
			'\\' . CUSTOM_NAMESPACE . '\Event\\' . $ls_type . '\\' => implode(DS, [ROOT, CUSTOM_DIR, 'Event', $ls_type, $ls_name . 'Listener.php',]),
			'\Awyiss\Event\\' . $ls_type . '\\' => implode(DS, [ROOT, APP_DIR, 'Event', $ls_type, $ls_name . 'Listener.php']),
		];

		foreach ($la_paths as $ls_namespace => $ls_path) {
			foreach (glob($ls_path) as $ls_filePath) {
				$ls_listenerName = substr($ls_filePath, strrpos($ls_filePath, DS) + 1, -4);
				$ls_listenerClass = $ls_namespace . $ls_listenerName;

				$lo_reflection = new ReflectionClass($ls_listenerClass);

				if ( ! $lo_reflection->implementsInterface(EventListenerInterface::class)) {
					throw new RuntimeException(sprintf('The provided Listener class `%s` does not extend the `%s` class.', $ls_listenerClass, EventListenerInterface::class));
				}

				/** @var \Awyiss\Event\EventListenerTrait $ls_listenerClass */
				$ls_scope = Inflector::underscore($ls_listenerClass::getScope());

				if (isset($la_listeners[ $ls_scope ])) {
					continue;
				}

				$la_listeners[ $ls_scope ] = $ls_listenerClass;
			}
		}

		return $la_listeners;
	}
}