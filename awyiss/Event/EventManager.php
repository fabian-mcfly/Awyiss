<?php declare(strict_types=1);


namespace Awyiss\Event;


use Awyiss\Awyiss;
use Cake\Event\Event;
use Cake\Event\EventInterface;
use Cake\Event\EventManager as BaseEventManager;
use Cake\Log\Log;


/**
 * EventManger that extends the CakePHP one.
 * Before dispatching any event, try loading corresponding listeners.
 */
class EventManager extends BaseEventManager {
	/**
	 * @inheritDoc
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 * @throws \ReflectionException
	 */
	public function dispatch(EventInterface|string $ax_event): EventInterface {
		$lo_event = $ax_event;
		if (is_string($ax_event)) {
			$lo_event = new Event($ax_event);
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
	 * @param string $as_name
	 * @return void
	 * @throws \ReflectionException
	 */
	protected function lazyLoadListeners(string $as_name): void {
		$la_parts = explode('.', $as_name);

		if (!$la_parts) {
			return;
		}

		$ls_scope = $la_parts[0];
		//Don't use general event scopes, as they aren't what Awyiss understands as scopes.
		$lb_generalScope = in_array($ls_scope, [
			'Application',
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

		//Try loading the scope from for the global realm
		Log::debug(sprintf('Trying to lazyload global event listeners for: `%s` (`%s` fired)', $ls_scope, $as_name));
		$lb_loaded = EventListenersProvider::loadListener($ls_scope, 'Global');
		Log::debug(sprintf('Loaded: %s', $lb_loaded ? 'true' : 'false'));

		if (Awyiss::getRealm()) {
			//Try loading the scope from for the current realm
			Log::debug(sprintf('Trying to lazyload `%s` event listeners for: `%s` (`%s` fired)', Awyiss::getRealm(), $ls_scope, $as_name));
			$lb_loaded = EventListenersProvider::loadListener($ls_scope, Awyiss::getRealm());
			Log::debug(sprintf('Loaded: %s', $lb_loaded ? 'true' : 'false'));
		}
	}
}
