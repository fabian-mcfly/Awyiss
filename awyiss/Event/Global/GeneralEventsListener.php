<?php declare(strict_types=1);


namespace Awyiss\Event\Global;


use Awyiss\Event\EventListenersProvider;
use Awyiss\Event\EventManager;
use Awyiss\Model\Table;
use Cake\Cache\Cache;
use Cake\Core\Configure;
use Cake\Event\Event;
use Cake\Event\EventInterface;
use Cake\Event\EventListenerInterface;


/**
 * Event listeners for the general events of the backend
 */
class GeneralEventsListener implements EventListenerInterface {
	/**
	 * @var array
	 */
	protected static array $initializedModels = [];


	/**
	 * @var string
	 */
	protected string $realm;


	/**
	 * @inheritDoc
	 */
	public function implementedEvents(): array {
		return [
			'Awyiss.getRealm' => 'awyissGetRealm',
			'Awyiss.setRealm' => 'awyissSetRealm',
			'Controller.initialize' => 'controllerInitialize',
			'Model.initialize' => 'modelInitialize',
		];
	}


	/**
	 * @param \Cake\Event\EventInterface $event
	 * @return void
	 */
	public function awyissGetRealm(EventInterface $event): void {
		$event->setResult($this->realm);
	}


	/**
	 * @param \Cake\Event\EventInterface $event
	 * @return void
	 */
	public function awyissSetRealm(EventInterface $event): void {
		$this->realm = $event->getData('realm');

		/** @var \Awyiss\Model\Table $model */
		foreach (static::$initializedModels as $key => $model) {
			EventListenersProvider::loadListener($model->getAlias(), $this->realm);
			unset(static::$initializedModels[ $key ]);
		}
	}


	/**
	 * Execute periodic events based on configured intervals
	 *
	 * @param \Cake\Event\EventInterface $event
	 * @return void
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function controllerInitialize(EventInterface $event): void {
		$this->executePeriodicEvents();
	}


	/**
	 * Check and execute periodic events if their interval has passed
	 *
	 * @return void
	 */
	protected function executePeriodicEvents(): void {
		$config = Configure::read('PeriodicEvents');

		if (empty($config)) {
			return;
		}

		$now = time();

		foreach ($config as $interval => $events) {
			if (empty($events)) {
				continue;
			}

			$cacheKey = 'periodic_event_last_run_' . $interval;
			$lastRun = Cache::read($cacheKey, 'persistent');

			// Determine if we should run based on interval
			$shouldRun = false;
			if ($lastRun === null) {
				$shouldRun = true;
			}
			else {
				switch ($interval) {
					case 'hourly':
						$shouldRun = $now - $lastRun >= 3600; // 1 hour in seconds
						break;
					case 'daily':
						$shouldRun = $now - $lastRun >= 86400; // 24 hours in seconds
						break;
				}
			}

			if (!$shouldRun) {
				continue;
			}

			// Execute all events for this interval
			foreach ($events as $event) {
				if (is_callable($event)) {
					call_user_func($event);
					continue;
				}

				$eventManager = EventManager::instance();
				$eventManager->dispatch(new Event($event));
			}

			// Update last run time
			Cache::write($cacheKey, $now, 'persistent');
		}
	}


	/**
	 * For every model that is loaded, load the event listener if the realm is known
	 * If not, save the model to be handled in `awyissSetRealm()`
	 *
	 * @param \Cake\Event\EventInterface $event
	 * @return void
	 */
	public function modelInitialize(EventInterface $event): void {
		/** @var \Awyiss\Model\Table $model */
		$model = $event->getSubject();

		if (!$model instanceof Table) {
			return;
		}

		if (!isset($this->realm)) {
			if (!in_array($model, static::$initializedModels, true)) {
				static::$initializedModels[] = $model;
			}

			return;
		}

		EventListenersProvider::loadListener($model->getAlias(), $this->realm);
	}
}
