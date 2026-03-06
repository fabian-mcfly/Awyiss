<?php declare(strict_types=1);


namespace Awyiss\Event\Backend;


use ArrayObject;
use Awyiss\Model\Entity\Menu;
use Cake\Event\Event;
use Cake\Event\EventListenerInterface;


/**
 * Event listeners for the Menus scope of the backend
 */
class MenusListener implements EventListenerInterface {
	/**
	 * @var array|string
	 */
	protected array|string $originalMenuEntriesFinder = 'all';


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

		/** @var \Awyiss\Model\Table\MenusTable $menusTable */
		$menusTable = $event->getSubject();

		/**
		 * @var \Awyiss\Model\Entity\Menu $originalEntity
		 * @noinspection PhpUndefinedFieldInspection
		 */
		$originalEntity = $entity->originalEntity;

		$this->originalMenuEntriesFinder = $menusTable->MenuEntries->getFinder();
		$menusTable->MenuEntries->setFinder('all');

		/**
		 * @uses \Awyiss\Model\Behavior\MediaAssignmentBehavior::findMediaAssignments()
		 * @uses \Awyiss\Model\Table::findTranslations()
		 */
		$entries = $menusTable->MenuEntries->find('threaded', nestingKey: 'childMenuEntries')
		->find('mediaAssignments', formatResult: false)
		->find('translations')
		->where(['menuId' => $originalEntity->id])
		->all();

		$listedEntries = $entries->listNested('desc', 'childMenuEntries');
		/** @var \Awyiss\Model\Entity\MenuEntry $menuEntry */
		foreach ($listedEntries as $menuEntry) {
			$menuEntry->menuId = $entity->id;
		}

		$menusTable->MenuEntries->saveMany($entries->toList(), [
			'checkRules' => false,
			'isCopy' => true,
			'_primary' => false,
		]);

		$menusTable->MenuEntries->setFinder($this->originalMenuEntriesFinder);
	}


	/**
	 * @param \Cake\Event\Event $event
	 * @return void
	 */
	public function beforeSoftDelete(Event $event): void {
		/** @var \Awyiss\Model\Table\MenusTable $menusTable */
		$menusTable = $event->getSubject();

		$this->originalMenuEntriesFinder = $menusTable->MenuEntries->getFinder();
		$menusTable->MenuEntries->setFinder('all');

		$menusTable->MenuEntries->disableCascadeCallbacks();
	}


	/**
	 * @param \Cake\Event\Event $event
	 * @return void
	 */
	public function beforeDelete(Event $event): void {
		/** @var \Awyiss\Model\Table\MenusTable $menusTable */
		$menusTable = $event->getSubject();

		$this->originalMenuEntriesFinder = $menusTable->MenuEntries->getFinder();
		$menusTable->MenuEntries->setFinder('all');

		$menusTable->MenuEntries->disableCascadeCallbacks();
	}


	/**
	 * @param \Cake\Event\Event $event
	 * @return void
	 */
	public function afterSoftDelete(Event $event): void {
		/** @var \Awyiss\Model\Table\MenusTable $menusTable */
		$menusTable = $event->getSubject();

		$menusTable->MenuEntries->enableCascadeCallbacks();

		$menusTable->MenuEntries->setFinder($this->originalMenuEntriesFinder);
	}


	/**
	 * @param \Cake\Event\Event $event
	 * @return void
	 */
	public function afterDelete(Event $event): void {
		/** @var \Awyiss\Model\Table\MenusTable $menusTable */
		$menusTable = $event->getSubject();

		$menusTable->MenuEntries->enableCascadeCallbacks();

		$menusTable->MenuEntries->setFinder($this->originalMenuEntriesFinder);
	}
}
