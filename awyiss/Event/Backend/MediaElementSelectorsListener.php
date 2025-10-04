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
		$lo_table = $this->fetchTable('MediaAssignments');
		$lo_table->updateAll(
			[
				'media_element_selector_identifier' => $entity->get('identifier'),
			],
			[
				'media_element_id' => $entity->get('mediaElementId'),
				'media_element_selector_identifier' => $entity->getOriginal('identifier'),
			]
		);
	}
}
