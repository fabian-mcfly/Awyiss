<?php declare(strict_types=1);


namespace Awyiss\Event\Global;


use Awyiss\Event\EventListenersProvider;
use Awyiss\Event\EventListenerTrait;
use Awyiss\Model\Table;
use Cake\Event\EventInterface;
use Cake\Event\EventListenerInterface;


/**
 * Event listeners for the general events of the backend
 */
class GeneralEventsListener implements EventListenerInterface {
	use EventListenerTrait;


	/**
	 * @var array
	 */
	protected array $initializedModels = [];
	/**
	 * @var string
	 */
	protected string $realm;
	/**
	 * @var string
	 */
	protected static string $scope;


	/**
	 * @inheritDoc
	 */
	public function implementedEvents(): array {
		return [
			'Awyiss.getRealm' => 'awyissGetRealm',
			'Awyiss.setRealm' => 'awyissSetRealm',
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

		/** @var Table $lo_model */
		foreach ($this->initializedModels as $lo_model) {
			EventListenersProvider::loadListener($lo_model->getAlias(), $this->realm);
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
		/** @var Table $lo_model */
		$lo_model = $event->getSubject();

		if ($lo_model instanceof Table) {
			if (!isset($this->realm)) {
				$this->initializedModels[] = $lo_model;
			}
			else {
				EventListenersProvider::loadListener($lo_model->getAlias(), $this->realm);
			}
		}
	}
}
