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
	 * @param \Cake\Event\Event $ao_event
	 * @param \Awyiss\Model\Entity\Menu $ao_entity
	 * @param \ArrayObject $ao_options
	 * @return void
	 * @throws \Exception
	 */
	public function afterCopy(Event $ao_event, Menu $ao_entity, ArrayObject $ao_options): void {
		if ($ao_options['_primary'] !== true) {
			return;
		}

		/** @var \Awyiss\Model\Table\MenusTable $lo_table */
		$lo_table = $ao_event->getSubject();

		/** @var \Awyiss\Model\Entity\Menu $lo_originalEntity */
		$lo_originalEntity = $ao_entity->originalEntity;

		$lo_entries = $lo_table->AllMenuEntries->find('threaded', nestingKey: 'childMenuEntries')->where(['menu_id' => $lo_originalEntity->id])->all();

		$lo_listedEntries = $lo_entries->listNested('desc', 'childMenuEntries');
		/** @var \Awyiss\Model\Entity\MenuEntry $lo_menuEntry */
		foreach ($lo_listedEntries as $lo_menuEntry) {
			$lo_menuEntry->unset((array)$lo_table->getPrimaryKey());
			$lo_menuEntry->unset(['menuId']);
			$lo_menuEntry->setNew(true);

			$lo_menuEntry->menuId = $ao_entity->id;
		}

		$lo_table->MenuEntries->saveMany($lo_entries->toList(), [
			'checkRules' => false,
		]);
	}


	/**
	 * @param \Cake\Event\Event $ao_event
	 * @return void
	 */
	public function beforeSoftDelete(Event $ao_event): void {
		/** @var \Awyiss\Model\Table\MenusTable $lo_table */
		$lo_table = $ao_event->getSubject();

		$lo_table->AllMenuEntries->disableCascadeCallbacks();
	}


	/**
	 * @param \Cake\Event\Event $ao_event
	 * @return void
	 */
	public function beforeDelete(Event $ao_event): void {
		/** @var \Awyiss\Model\Table\MenusTable $lo_table */
		$lo_table = $ao_event->getSubject();

		$lo_table->AllMenuEntries->disableCascadeCallbacks();
	}


	/**
	 * @param \Cake\Event\Event $ao_event
	 * @return void
	 */
	public function afterSoftDelete(Event $ao_event): void {
		/** @var \Awyiss\Model\Table\MenusTable $lo_table */
		$lo_table = $ao_event->getSubject();

		$lo_table->AllMenuEntries->enableCascadeCallbacks();
	}


	/**
	 * @param \Cake\Event\Event $ao_event
	 * @return void
	 */
	public function afterDelete(Event $ao_event): void {
		/** @var \Awyiss\Model\Table\MenusTable $lo_table */
		$lo_table = $ao_event->getSubject();

		$lo_table->AllMenuEntries->enableCascadeCallbacks();
	}
}
