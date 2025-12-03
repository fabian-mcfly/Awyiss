<?php declare(strict_types=1);


namespace Awyiss\Event\Backend;


use ArrayObject;
use Awyiss\Model\Entity\Form;
use Cake\Event\Event;
use Cake\Event\EventListenerInterface;


/**
 * Event listeners for the Forms scope of the backend
 */
class FormsListener implements EventListenerInterface {
	/**
	 * @inheritDoc
	 */
	public function implementedEvents(): array {
		return [
			'Model.Forms.afterCopy' => 'afterCopy',
			'Model.Forms.beforeSoftDelete' => 'beforeSoftDelete',
			'Model.Forms.beforeDelete' => 'beforeDelete',
			'Model.Forms.afterSoftDelete' => 'afterSoftDelete',
			'Model.Forms.afterDelete' => 'afterDelete',
		];
	}


	/**
	 * @param \Cake\Event\Event $event
	 * @param \Awyiss\Model\Entity\Form $entity
	 * @param \ArrayObject $options
	 * @return void
	 * @throws \Exception
	 */
	public function afterCopy(Event $event, Form $entity, ArrayObject $options): void {
		if ($options['_primary'] !== true) {
			return;
		}

		/** @var \Awyiss\Model\Table\FormsTable $formsTable */
		$formsTable = $event->getSubject();

		/**
		 * @var \Awyiss\Model\Entity\Form $originalEntity
		 * @noinspection PhpUndefinedFieldInspection
		 */
		$originalEntity = $entity->originalEntity;

		/** @uses \Awyiss\Model\Table::findTranslations() */
		$elements = $formsTable->FormElements->find('threaded', nestingKey: 'childFormElements')
			->find('mediaAssignments', formatResult: false)
			->find('translations')
			->where(['form_id' => $originalEntity->id])
			->all();

		$listedElements = $elements->listNested('desc', 'childFormElements');
		/** @var \Awyiss\Model\Entity\FormElement $formElement */
		foreach ($listedElements as $formElement) {
			$formElement->formId = $entity->id;
		}

		$formsTable->FormElements->saveMany($elements->toList(), [
			'checkRules' => false,
			'isCopy' => true,
			'_primary' => false,
		]);
	}


	/**
	 * @param \Cake\Event\Event $event
	 * @return void
	 */
	public function beforeSoftDelete(Event $event): void {
		/** @var \Awyiss\Model\Table\FormsTable $formsTable */
		$formsTable = $event->getSubject();

		$formsTable->FormElements->disableCascadeCallbacks();
	}


	/**
	 * @param \Cake\Event\Event $event
	 * @return void
	 */
	public function beforeDelete(Event $event): void {
		/** @var \Awyiss\Model\Table\FormsTable $formsTable */
		$formsTable = $event->getSubject();

		$formsTable->FormElements->disableCascadeCallbacks();
	}


	/**
	 * @param \Cake\Event\Event $event
	 * @return void
	 */
	public function afterSoftDelete(Event $event): void {
		/** @var \Awyiss\Model\Table\FormsTable $formsTable */
		$formsTable = $event->getSubject();

		$formsTable->FormElements->enableCascadeCallbacks();
	}


	/**
	 * @param \Cake\Event\Event $event
	 * @return void
	 */
	public function afterDelete(Event $event): void {
		/** @var \Awyiss\Model\Table\FormsTable $formsTable */
		$formsTable = $event->getSubject();

		$formsTable->FormElements->enableCascadeCallbacks();
	}
}
