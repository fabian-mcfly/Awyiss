<?php declare(strict_types=1);


namespace Awyiss\Event\Backend;


use ArrayObject;
use Awyiss\Event\EventListenerTrait;
use Awyiss\Model\Entity\MenuEntry;
use Cake\Event\Event;
use Cake\Event\EventListenerInterface;


/**
 * Event listeners for the MenuEntries scope of the backend
 */
class MenuEntriesListener implements EventListenerInterface {
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
			'Model.MenuEntries.beforeCopy' => 'beforeCopy',
		];
	}


	/**
	 * @param \Cake\Event\Event $event
	 * @param \Awyiss\Model\Entity\MenuEntry $entity
	 * @param \ArrayObject $options
	 * @return void
	 */
	public function beforeCopy(Event $event, MenuEntry $entity, ArrayObject $options): void {
		if ($options['_primary'] !== true) {
			return;
		}

		/** @var \Awyiss\Model\Table\MenuEntriesTable $lo_table */
		$lo_table = $event->getSubject();

		/** @var \Awyiss\Model\Entity\MenuEntry $lo_originalEntity */
		$lo_originalEntity = $entity->originalEntity;
		$lo_children = $lo_originalEntity->getNestedChildren([
			'finder' => 'translations',
		]);

		if (!$lo_children?->count()) {
			return;
		}

		$lo_nestedChildren = $lo_children->nest('id', 'parentId', 'childMenuEntries')->toList();

		$la_relatedColumns = $lo_table->getBehavior('Nest')->getConfig('relatedColumns');

		/** @var \Awyiss\Model\Entity\MenuEntry $lo_childMenuEntry */
		foreach ($lo_children as $lo_childMenuEntry) {
			$la_primaryKeys = $lo_childMenuEntry->extract((array)$lo_table->getPrimaryKey());
			$lo_childMenuEntry->originalPrimaryKeys = $la_primaryKeys;

			$lo_childMenuEntry->unset((array)$lo_table->getPrimaryKey());
			$lo_childMenuEntry->setNew(true);

			$lo_childMenuEntry->set($entity->extract($la_relatedColumns));
		}

		$entity->childMenuEntries = $lo_nestedChildren;
	}
}
