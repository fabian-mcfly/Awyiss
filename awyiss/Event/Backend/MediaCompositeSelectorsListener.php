<?php declare(strict_types=1);


namespace Awyiss\Event\Backend;


use Awyiss\Event\EventListenerTrait;
use Awyiss\Model\Entity\MediaCompositeSelector;
use Cake\Event\Event;
use Cake\Event\EventListenerInterface;
use Cake\ORM\Locator\LocatorAwareTrait;


/**
 * Event listeners for the MediaCompositeSelectors scope of the backend
 */
class MediaCompositeSelectorsListener implements EventListenerInterface {
	use EventListenerTrait;
	use LocatorAwareTrait;


	/**
	 * @var string
	 */
	protected static string $scope;


	/**
	 * @inheritDoc
	 */
	public function implementedEvents(): array {
		return [
			'Model.MediaCompositeSelectors.afterSave' => 'afterSave',
		];
	}


	/**
	 * @param \Cake\Event\Event $event
	 * @param \Awyiss\Model\Entity\Language $entity
	 * @return void
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function afterSave(Event $event, MediaCompositeSelector $entity): void {
		if (!$entity->isDirty('identifier')) {
			return;
		}

		/**
		 * Update the identifier in the assignments
		 */
		$lo_table = $this->fetchTable('MediaAssignments');
		$lo_table->updateAll(
			[
				'media_composite_selector_identifier' => $entity->get('identifier'),
			],
			[
				'media_composite_id' => $entity->get('mediaCompositeId'),
				'media_composite_selector_identifier' => $entity->getOriginal('identifier'),
			]
		);
	}
}
