<?php declare(strict_types=1);


namespace Customer\Event\Global;


use Cake\Event\EventInterface;
use Cake\Event\EventListenerInterface;


/**
 * Event listeners for the global general events
 */
class CarsListener implements EventListenerInterface {
	/**
	 * @inheritDoc
	 */
	public function implementedEvents(): array {
		return [
			'Model.Cars.dummyListener' => 'dummyListener',
		];
	}


	/**
	 * Dummy listener for Cars model
	 *
	 * @param \Cake\Event\EventInterface $event
	 * @return void
	 * @noinspection PhpUnused
	 */
	public function dummyListener(EventInterface $event): void {
		$event->setResult('dummyListener called in CarsListener');
	}
}
