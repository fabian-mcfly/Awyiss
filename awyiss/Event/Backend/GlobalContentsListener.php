<?php declare(strict_types=1);


namespace Awyiss\Event\Backend;


use Awyiss\Model\Entity\GlobalContent;
use Cake\Event\Event;
use Cake\Event\EventListenerInterface;


/**
 * Event listeners for the GlobalContents scope of the backend
 */
class GlobalContentsListener implements EventListenerInterface {
	/**
	 * @inheritDoc
	 */
	public function implementedEvents(): array {
		return [
			'Model.GlobalContents.beforeSave' => 'beforeSave',
		];
	}


	/**
	 * @param \Cake\Event\Event $event
	 * @param \Awyiss\Model\Entity\GlobalContent $entity
	 * @return void
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function beforeSave(Event $event, GlobalContent $entity): void {
		// Unset titleTag and subtitleTag if title and subtitle are empty
		if (!$entity->title && $entity->titleTag) {
			$entity->titleTag = null;
		}

		if (!$entity->subtitle && $entity->subtitleTag) {
			$entity->subtitleTag = null;
		}
	}
}
