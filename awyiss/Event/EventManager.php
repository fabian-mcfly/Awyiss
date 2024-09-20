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
		'current' => [],
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
	 * @throws \ReflectionException
	 */
	public function dispatch(EventInterface|string $event): EventInterface {
		$lo_event = $event;
		if (is_string($event)) {
			$lo_event = new Event($event);
		}

		if (!$this->listeners($lo_event->getName())) {
			$this->lazyLoadListeners($lo_event->getName());
		}


		return parent::dispatch($lo_event);
	}


	/**
	 * For fired events without listeners, try to lazy load the listeners in
	 * - the global realm
	 * - the current Awyiss realm
	 *
	 * @param string $name
	 * @return void
	 * @throws \ReflectionException
	 */
	protected function lazyLoadListeners(string $name): void {
		if (!isset(static::$pageRoleEnum)) {
			static::$pageRoleEnum = App::className('PageRole', 'Model/Enum');
		}

		$la_parts = explode('.', $name);

		if (!$la_parts) {
			return;
		}

		$ls_scope = $la_parts[0];
		//Don't use general event scopes, as they aren't what Awyiss understands as scopes.
		$lb_generalScope = in_array($ls_scope, [
			'Application',
			'Awyiss',
			'Bake',
			'Command',
			'Console',
			'Controller',
			'Error',
			'Exception',
			'Model',
			'Server',
			'View',
		]);

		if ($lb_generalScope) {
			if (
				empty($la_parts[1]) ||
				$la_parts[1][0] !== strtoupper($la_parts[1][0])
			) {
				//No second part, or it starts with a lower case letter: do nothing as it's not a scope
				return;
			}

			$ls_scope = $la_parts[1];
		}

		$ls_scope = EventListenersProvider::sanitizeScope($ls_scope);

		//If a regex-matching event for the scope exists, do nothing. This means that this event was most likely never set anywhere
		if (
			$this->matchingListeners('/(?<=\.|^)' . $ls_scope . '\./') ||
			static::instance()->matchingListeners('/(?<=\.|^)' . $ls_scope . '\./')
		) {
			return;
		}

		if (!in_array($ls_scope, static::$lazyLoadAttempts['global'])) {
			static::$lazyLoadAttempts['global'][] = $ls_scope;

			//Try loading the scope from for the global realm
			//\Cake\Log\Log::debug(sprintf('Trying to lazyload global event listeners for: `%s` (`%s` fired)', $ls_scope, $name));
			$lb_loaded = EventListenersProvider::loadListener($ls_scope, 'Global'); // phpcs:ignore
			//\Cake\Log\Log::debug(sprintf('Loaded: %s', $lb_loaded ? 'true' : 'false'));
		}

		if (Awyiss::getRealm() && !in_array($ls_scope, static::$lazyLoadAttempts['current'])) {
			static::$lazyLoadAttempts['current'][] = $ls_scope;

			//Try loading the scope from for the current realm
			//\Cake\Log\Log::debug(sprintf('Trying to lazyload `%s` event listeners for: `%s` (`%s` fired)', Awyiss::getRealm(), $ls_scope, $name));
			$lb_loaded = EventListenersProvider::loadListener($ls_scope, Awyiss::getRealm()); // phpcs:ignore
			//\Cake\Log\Log::debug(sprintf('Loaded: %s', $lb_loaded ? 'true' : 'false'));

			if (!$lb_loaded && static::$pageRoleEnum::tryFromName($ls_scope)) {
				//\Cake\Log\Log::debug(sprintf('Found a page role for scope `%s`', $ls_scope));
				//Try loading the pages listener from for the current realm
				//\Cake\Log\Log::debug('Trying to lazyload fallback event listeners for pages');
				$lb_loaded = EventListenersProvider::loadListener('Pages', Awyiss::getRealm()); // phpcs:ignore
				//\Cake\Log\Log::debug(sprintf('Loaded: %s', $lb_loaded ? 'true' : 'false'));
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
			'current' => [],
		];
	}
}
