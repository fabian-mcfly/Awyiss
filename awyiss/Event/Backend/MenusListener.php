<?php declare(strict_types=1);


namespace Awyiss\Event\Backend;


use Awyiss\Event\EventListenerTrait;
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


	/**
	 * @inheritDoc
	 */
	public function implementedEvents(): array {
		return [
			'Model.Menus.beforeSoftDelete' => 'beforeSoftDelete',
			'Model.Menus.beforeDelete' => 'beforeDelete',
			'Model.Menus.afterSoftDelete' => 'afterSoftDelete',
			'Model.Menus.afterDelete' => 'afterDelete',
		];
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
