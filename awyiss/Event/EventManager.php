<?php declare(strict_types=1);


namespace Awyiss\Event;


use Awyiss\Awyiss;
use Awyiss\Core\App;
use Cake\Event\Event;
use Cake\Event\EventInterface;
use Cake\Event\EventManager as BaseEventManager;


/**
 * EventManger that extends the CakePHP one.
 * Before dispatching any event, try loading corresponding listeners.
 */
class EventManager extends BaseEventManager {
	/**
	 * @var array The event names that have already been tried to be lazy loaded
	 */
	protected static array $lazyLoadAttempts = [
		'global' => [],
		Awyiss::REALM_FRONTEND => [],
		Awyiss::REALM_BACKEND => [],
	];
	/**
	 * @var class-string<\Awyiss\Model\Enum\PageRoleEnumInterface>
	 */
	protected static string $pageRoleEnum;


	/**
	 * Initialize the EventManager
	 */
	public function __construct(bool $resetStatic = false) {
		if ($resetStatic) {
			$this->reset();
			EventListenersProvider::reset();
		}
	}


	/**
	 * @inheritDoc
	 */
	public function dispatch(EventInterface|string $event): EventInterface {
		if (is_string($event)) {
			$event = new Event($event);
		}

		if (!$this->listeners($event->getName())) {
			$this->lazyLoadListeners($event->getName());
		}


		return parent::dispatch($event);
	}


	/**
	 * For fired events without listeners, try to lazy load the listeners in
	 * - the global realm
	 * - the current Awyiss realm
	 *
	 * @param string $name
	 * @return void
	 */
	protected function lazyLoadListeners(string $name): void {
		if (!isset(static::$pageRoleEnum)) {
			static::$pageRoleEnum = App::className('PageRole', 'Model/Enum');
		}

		$parts = explode('.', $name);

		if (!$parts) {
			return;
		}

		$scope = $parts[0];
		// Don't use general event scopes, as they aren't what Awyiss understands as scopes.
		$generalScope = in_array($scope, [
			'Application',
			'Awyiss',
			'Bake',
			'Command',
			'Configuration',
			'Console',
			'Controller',
			'Error',
			'Exception',
			'Model',
			'Server',
			'View',
		]);

		if ($generalScope) {
			if (
				empty($parts[1]) ||
				$parts[1][0] !== strtoupper($parts[1][0])
			) {
				// No second part, or it starts with a lower case letter: do nothing as it's not a scope
				return;
			}

			$scope = $parts[1];
		}

		$scope = EventListenersProvider::sanitizeScope($scope);

		if (!in_array($scope, static::$lazyLoadAttempts['global'])) {
			static::$lazyLoadAttempts['global'][] = $scope;

			// Try loading the scope from for the global realm
			EventListenersProvider::loadListener($scope, 'Global');

			if (static::$pageRoleEnum::tryFromName($scope)) {
				//Try loading the pages listener from for the current realm
				EventListenersProvider::loadListener('Pages', 'Global');
			}
		}

		if (Awyiss::getRealm() && !in_array($scope, static::$lazyLoadAttempts[ Awyiss::getRealm() ])) {
			static::$lazyLoadAttempts[ Awyiss::getRealm() ][] = $scope;

			// Try loading the scope from for the current realm
			EventListenersProvider::loadListener($scope, Awyiss::getRealm());

			if (static::$pageRoleEnum::tryFromName($scope)) {
				//Try loading the pages listener from for the current realm
				EventListenersProvider::loadListener('Pages', Awyiss::getRealm());
			}
		}
	}


	/**
	 * @param string $eventKeyPattern
	 * @return array
	 */
	public function matchingListeners(string $eventKeyPattern): array {
		return array_intersect_key(
			$this->_listeners,
			array_flip(
				preg_grep($eventKeyPattern, array_keys($this->_listeners)) ?: []
			)
		);
	}


	/**
	 * @return void
	 */
	public function reset(): void {
		static::$lazyLoadAttempts = [
			'global' => [],
			Awyiss::REALM_FRONTEND => [],
			Awyiss::REALM_BACKEND => [],
		];
	}
}
