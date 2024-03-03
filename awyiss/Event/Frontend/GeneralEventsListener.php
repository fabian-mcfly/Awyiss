<?php declare(strict_types=1);


namespace Awyiss\Event\Frontend;


use Awyiss\Event\EventListenerTrait;
use Cake\Datasource\EntityInterface;
use Cake\Event\EventInterface;
use Cake\Event\EventListenerInterface;


/**
 * Event listeners for the general events of the frontend
 */
class GeneralEventsListener implements EventListenerInterface {
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
			'Model.beforeSave' => 'beforeSave',
		];
	}


	/**
	 * Before saving a page, make sure its slug is unique.
	 *
	 * @param EventInterface $ao_event
	 * @param \Awyiss\Model\Entity\Media $ao_entity
	 * @param \ArrayObject $ao_options
	 */
	public function beforeSave(EventInterface $ao_event, EntityInterface $ao_entity/*, ArrayObject $ao_options*/): void {
		$ao_event->stopPropagation();
		$ao_entity->setError('_general', 'Saving inside the Frontend Realm is not allowed.');
	}
}
