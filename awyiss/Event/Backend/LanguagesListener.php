<?php declare(strict_types=1);


namespace Awyiss\Event\Backend;


use Awyiss\Awyiss;
use Awyiss\Event\EventListenerTrait;
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
			'Model.Languages.beforeSoftDelete' => 'beforeSoftDelete',
			'Model.Languages.afterSoftDelete' => 'afterSoftDelete',
		];
	}


	/**
	 * @param \Cake\Event\Event $ao_event
	 * @param \Awyiss\Model\Entity\Language $ao_entity
	 * @return void
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function beforeSoftDelete(Event $ao_event, Language $ao_entity): void {
		if ($ao_entity->realm === Awyiss::REALM_FRONTEND) {
			/** @var \Awyiss\Model\Table\LanguagesTable $lo_table */
			$lo_table = $ao_event->getSubject();
			$lo_table->MenuEntries->setDependent(true);
			$lo_table->Pages->setDependent(true);
			$lo_table->Pages->ChildPages->setDependent(false)->setCascadeCallbacks(false);
		}
	}


	/**
	 * @param \Cake\Event\Event $ao_event
	 * @param \Awyiss\Model\Entity\Language $ao_entity
	 * @return void
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function afterSoftDelete(Event $ao_event, Language $ao_entity): void {
		/** @var \Awyiss\Model\Table\LanguagesTable $lo_table */
		$lo_table = $ao_event->getSubject();
		$lo_table->MenuEntries->setDependent(false);
		$lo_table->Pages->setDependent(false);
		$lo_table->Pages->ChildPages->setDependent(true)->setCascadeCallbacks(true);
	}
}
