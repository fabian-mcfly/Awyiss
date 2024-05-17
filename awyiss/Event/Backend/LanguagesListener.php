<?php declare(strict_types=1);


namespace Awyiss\Event\Backend;


use Awyiss\Awyiss;
use Awyiss\Event\EventListenerTrait;
use Awyiss\Event\EventManager;
use Awyiss\Model\Entity\Language;
use Cake\Event\Event;
use Cake\Event\EventListenerInterface;


/**
 * Event listeners for the Languages scope of the backend
 */
class LanguagesListener implements EventListenerInterface {
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
			'Model.Languages.afterSaveCommit' => 'afterSaveCommit',
			'Model.Languages.beforeSoftDelete' => 'beforeSoftDelete',
			'Model.Languages.afterSoftDelete' => 'afterSoftDelete',
		];
	}


	/**
	 * @param \Cake\Event\Event $event
	 * @param \Awyiss\Model\Entity\Language $entity
	 * @return void
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function afterSaveCommit(Event $event, Language $entity): void {
		if (
			$entity->isNew() ||
			$entity->isDirty('realm') ||
			$entity->isDirty('shortcode')
		) {
			/**
			 * Trigger the creation of the custom configuriation
			 *
			 * @see \Awyiss\Event\Backend\ConfigurationListener::createCustomConfiguration()
			 */
			$lo_eventManager = EventManager::instance();
			$lo_eventManager->dispatch('Configuration.deleteCustomConfiguration');
		}
	}


	/**
	 * @param \Cake\Event\Event $event
	 * @param \Awyiss\Model\Entity\Language $entity
	 * @return void
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function beforeSoftDelete(Event $event, Language $entity): void {
		if ($entity->realm === Awyiss::REALM_FRONTEND) {
			/** @var \Awyiss\Model\Table\LanguagesTable $lo_table */
			$lo_table = $event->getSubject();
			$lo_table->MenuEntries->setDependent(true);
			$lo_table->Pages->setDependent(true);
			$lo_table->Pages->ChildPages->setDependent(false)->setCascadeCallbacks(false);
		}
	}


	/**
	 * @param \Cake\Event\Event $event
	 * @param \Awyiss\Model\Entity\Language $entity
	 * @return void
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function afterSoftDelete(Event $event, Language $entity): void {
		/** @var \Awyiss\Model\Table\LanguagesTable $lo_table */
		$lo_table = $event->getSubject();
		$lo_table->MenuEntries->setDependent(false);
		$lo_table->Pages->setDependent(false);
		$lo_table->Pages->ChildPages->setDependent(true)->setCascadeCallbacks(true);


		/**
		 * Trigger the creation of the custom configuriation
		 *
		 * @see \Awyiss\Event\Backend\ConfigurationListener::createCustomConfiguration()
		 */
		$lo_eventManager = EventManager::instance();
		$lo_eventManager->dispatch('Configuration.deleteCustomConfiguration');
	}
}
