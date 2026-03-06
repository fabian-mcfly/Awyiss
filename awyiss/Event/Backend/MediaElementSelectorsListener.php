<?php declare(strict_types=1);


namespace Awyiss\Event\Backend;


use Awyiss\Model\Entity\MediaElementSelector;
use Cake\Event\Event;
use Cake\Event\EventListenerInterface;
use Cake\ORM\Locator\LocatorAwareTrait;


/**
 * Event listeners for the MediaElementSelectors scope of the backend
 */
class MediaElementSelectorsListener implements EventListenerInterface {
	use LocatorAwareTrait;


	/**
	 * @inheritDoc
	 */
	public function implementedEvents(): array {
		return [
			'Model.MediaElementSelectors.afterSave' => 'afterSave',
		];
	}


	/**
	 * @param \Cake\Event\Event $event
	 * @param \Awyiss\Model\Entity\MediaElementSelector $entity
	 * @return void
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function afterSave(Event $event, MediaElementSelector $entity): void {
		if (!$entity->isDirty('identifier') || $entity->isNew()) {
			return;
		}

		/**
		 * Update the identifier in the assignments
		 */
		$mediaAssignmentsTable = $this->fetchTable('MediaAssignments');
		$mediaAssignmentsTable->updateAll(
			[
				'mediaElementSelectorIdentifier' => $entity->get('identifier'),
			],
			[
				'mediaElementId' => $entity->get('mediaElementId'),
				'mediaElementSelectorIdentifier' => $entity->getOriginal('identifier'),
			]
		);
	}
}
