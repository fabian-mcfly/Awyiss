<?php declare(strict_types=1);


namespace Awyiss\Event\Global;


use Awyiss\Event\EventListenersProvider;
use Awyiss\Event\EventListenerTrait;
use Awyiss\Model\Table;
use Cake\Event\Event;
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
	 * @param Event $ao_event
	 * @return string
	 * @throws \ReflectionException
	 * @noinspection PhpUnused
	 */
	public function awyissGetRealm(Event $ao_event): string {
		return $this->realm;
	}


	/**
	 * @param Event $ao_event
	 * @return void
	 * @throws \ReflectionException
	 * @noinspection PhpUnused
	 */
	public function awyissSetRealm(Event $ao_event): void {
		$this->realm = $ao_event->getData('realm');

		if ($this->initializedModels) {
			dd(__LINE__, __FILE__, debug_backtrace(2), $this->initializedModels);
		}

		/** @var Table $lo_model */
		foreach ($this->initializedModels as $lo_model) {
			EventListenersProvider::loadListener($lo_model->getAlias(), $this->realm);
		}
	}


	/**
	 * For every model that is loaded, load the event listener if the realm is known
	 * If not, save the model to be handled in `awyissSetRealm()`
	 *
	 * @param Event $ao_event
	 * @return void
	 * @noinspection PhpUnused
	 * @throws \ReflectionException
	 */
	public function modelInitialize(Event $ao_event): void {
		/** @var Table $lo_model */
		$lo_model = $ao_event->getSubject();

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
