<?php declare(strict_types=1);


namespace Awyiss\Event\Backend;


use Awyiss\Model\Entity\Content;
use Cake\Event\Event;
use Cake\Event\EventListenerInterface;


/**
 * Event listeners for the Contents scope of the backend
 */
class ContentsListener implements EventListenerInterface {
	/**
	 * @inheritDoc
	 */
	public function implementedEvents(): array {
		return [
			'Model.Contents.beforeSave' => 'beforeSave',
		];
	}


	/**
	 * @param \Cake\Event\Event $event
	 * @param \Awyiss\Model\Entity\Content $entity
	 * @return void
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function beforeSave(Event $event, Content $entity): void {
		// Unset titleTag and subtitleTag if title and subtitle are empty
		if (!$entity->title && $entity->titleTag) {
			$entity->titleTag = null;
		}

		if (!$entity->subtitle && $entity->subtitleTag) {
			$entity->subtitleTag = null;
		}
	}
}
