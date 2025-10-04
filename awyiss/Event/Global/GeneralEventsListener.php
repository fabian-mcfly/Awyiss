<?php declare(strict_types=1);


namespace Awyiss\Event\Global;


use Awyiss\Event\EventListenersProvider;
use Awyiss\Model\Table;
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
		foreach (static::$initializedModels as $li_key => $lo_model) {
			EventListenersProvider::loadListener($lo_model->getAlias(), $this->realm);
			unset(static::$initializedModels[ $li_key ]);
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

		if (!$lo_model instanceof Table) {
			return;
		}

		if (!isset($this->realm)) {
			if (!in_array($lo_model, static::$initializedModels, true)) {
				static::$initializedModels[] = $lo_model;
			}

			return;
		}

		EventListenersProvider::loadListener($lo_model->getAlias(), $this->realm);
	}
}
