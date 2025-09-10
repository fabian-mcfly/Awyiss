<?php declare(strict_types=1);


namespace Customer\Event\Global;


use Awyiss\Event\EventListenerTrait;
use Cake\Event\EventInterface;
use Cake\Event\EventListenerInterface;


/**
 * Event listeners for the global general events
 */
class CarsListener implements EventListenerInterface {
	use EventListenerTrait;


	/**
	 * @var string
	 */
	protected static string $scope;


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
	 * @return void
	 */
	public function dummyListener(EventInterface $event): void {
		$event->setResult('dummyListener called in CarsListener');
	}
}
