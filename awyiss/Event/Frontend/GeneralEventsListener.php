<?php declare(strict_types=1);


namespace Awyiss\Event\Frontend;


use ArrayObject;
use Awyiss\Model\Entity;
use Awyiss\Model\Entity\MediaResizedImage;
use Cake\Datasource\EntityInterface;
use Cake\Event\EventInterface;
use Cake\Event\EventListenerInterface;


/**
 * Event listeners for the general events of the frontend
 */
class GeneralEventsListener implements EventListenerInterface {
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
	 * @param \Cake\Event\EventInterface $event
	 * @param \Cake\Datasource\EntityInterface $entity
	 * @param \ArrayObject $options
	 * @return void
	 */
	public function beforeSave(EventInterface $event, EntityInterface $entity, ArrayObject $options): void {
		if (
			!$entity instanceof Entity
			|| $entity instanceof MediaResizedImage
			|| ($options['allowFrontendSave'] ?? false) === true
		) {
			return;
		}

		// Stop the save operation and set an error on the entity
		$event->stopPropagation();
		$entity->setError('_general', 'Saving inside the Frontend Realm is not allowed.');
	}
}
