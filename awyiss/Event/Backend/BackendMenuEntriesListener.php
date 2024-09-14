<?php declare(strict_types=1);


namespace Awyiss\Event\Backend;


use ArrayObject;
use Awyiss\Event\EventListenerTrait;
use Awyiss\Model\Entity\BackendMenuEntry;
use Cake\Event\Event;
use Cake\Event\EventListenerInterface;


/**
 * Event listeners for the BackendMenuEntries scope of the backend
 */
class BackendMenuEntriesListener implements EventListenerInterface {
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
			'Model.BackendMenuEntries.beforeCopy' => 'beforeCopy',
		];
	}


	/**
	 * @param \Cake\Event\Event $event
	 * @param \Awyiss\Model\Entity\BackendMenuEntry $entity
	 * @param \ArrayObject $options
	 * @return void
	 */
	public function beforeCopy(Event $event, BackendMenuEntry $entity, ArrayObject $options): void {
		if ($options['_primary'] !== true) {
			return;
		}

		/** @var \Awyiss\Model\Table\MenuEntriesTable $lo_table */
		$lo_table = $event->getSubject();

		/** @var \Awyiss\Model\Entity\BackendMenuEntry $lo_originalEntity */
		$lo_originalEntity = $entity->originalEntity;
		$lo_children = $lo_originalEntity->getNestedChildren([
			'finder' => 'translations',
		]);

		if (!$lo_children?->count()) {
			return;
		}

		$lo_nestedChildren = $lo_children->nest('id', 'parentId', 'childBackendMenuEntries')->toList();

		/** @var \Awyiss\Model\Entity\BackendMenuEntry $lo_childBackendMenuEntry */
		foreach ($lo_children as $lo_childBackendMenuEntry) {
			$la_primaryKeys = $lo_childBackendMenuEntry->extract((array)$lo_table->getPrimaryKey());
			$lo_childBackendMenuEntry->originalPrimaryKeys = $la_primaryKeys;

			$lo_childBackendMenuEntry->unset((array)$lo_table->getPrimaryKey());
			$lo_childBackendMenuEntry->setNew(true);
		}

		$entity->childBackendMenuEntries = $lo_nestedChildren;
	}
}
