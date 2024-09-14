<?php declare(strict_types=1);


namespace Awyiss\Event\Backend;


use ArrayObject;
use Awyiss\Event\EventListenerTrait;
use Awyiss\Model\Entity\Menu;
use Cake\Event\Event;
use Cake\Event\EventListenerInterface;


/**
 * Event listeners for the Menus scope of the backend
 */
class MenusListener implements EventListenerInterface {
	use EventListenerTrait;


	/**
	 * @var string
	 */
	protected static string $scope;


	protected string $menuEntriesFinder;


	/**
	 * @inheritDoc
	 */
	public function implementedEvents(): array {
		return [
			'Model.Menus.afterCopy' => 'afterCopy',
			'Model.Menus.beforeSoftDelete' => 'beforeSoftDelete',
			'Model.Menus.beforeDelete' => 'beforeDelete',
			'Model.Menus.afterSoftDelete' => 'afterSoftDelete',
			'Model.Menus.afterDelete' => 'afterDelete',
		];
	}


	/**
	 * @param \Cake\Event\Event $event
	 * @param \Awyiss\Model\Entity\Menu $entity
	 * @param \ArrayObject $options
	 * @return void
	 * @throws \Exception
	 */
	public function afterCopy(Event $event, Menu $entity, ArrayObject $options): void {
		if ($options['_primary'] !== true) {
			return;
		}

		/** @var \Awyiss\Model\Table\MenusTable $lo_table */
		$lo_table = $event->getSubject();

		/** @var \Awyiss\Model\Entity\Menu $lo_originalEntity */
		$lo_originalEntity = $entity->originalEntity;

		$lo_entries = $lo_table->AllMenuEntries->find('threaded', nestingKey: 'childMenuEntries')
		->find('translations')
		->where(['menu_id' => $lo_originalEntity->id])
		->all();

		$lo_listedEntries = $lo_entries->listNested('desc', 'childMenuEntries');
		/** @var \Awyiss\Model\Entity\MenuEntry $lo_menuEntry */
		foreach ($lo_listedEntries as $lo_menuEntry) {
			$lo_menuEntry->unset((array)$lo_table->getPrimaryKey());
			$lo_menuEntry->unset(['menuId']);
			$lo_menuEntry->setNew(true);

			$lo_menuEntry->menuId = $entity->id;
		}

		$lo_table->MenuEntries->saveMany($lo_entries->toList(), [
			'checkRules' => false,
			'isCopy' => true,
		]);
	}


	/**
	 * @param \Cake\Event\Event $event
	 * @return void
	 */
	public function beforeSoftDelete(Event $event): void {
		/** @var \Awyiss\Model\Table\MenusTable $lo_table */
		$lo_table = $event->getSubject();

		$lo_table->AllMenuEntries->disableCascadeCallbacks();
	}


	/**
	 * @param \Cake\Event\Event $event
	 * @return void
	 */
	public function beforeDelete(Event $event): void {
		/** @var \Awyiss\Model\Table\MenusTable $lo_table */
		$lo_table = $event->getSubject();

		$lo_table->AllMenuEntries->disableCascadeCallbacks();
	}


	/**
	 * @param \Cake\Event\Event $event
	 * @return void
	 */
	public function afterSoftDelete(Event $event): void {
		/** @var \Awyiss\Model\Table\MenusTable $lo_table */
		$lo_table = $event->getSubject();

		$lo_table->AllMenuEntries->enableCascadeCallbacks();
	}


	/**
	 * @param \Cake\Event\Event $event
	 * @return void
	 */
	public function afterDelete(Event $event): void {
		/** @var \Awyiss\Model\Table\MenusTable $lo_table */
		$lo_table = $event->getSubject();

		$lo_table->AllMenuEntries->enableCascadeCallbacks();
	}
}
