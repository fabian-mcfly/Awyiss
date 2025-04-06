<?php declare(strict_types=1);


namespace Awyiss\Event\Backend;


use ArrayObject;
use Awyiss\Core\App;
use Awyiss\Event\EventListenerTrait;
use Awyiss\Model\Entity\Form;
use Cake\Core\Configure;
use Cake\Event\Event;
use Cake\Event\EventListenerInterface;


/**
 * Event listeners for the Forms scope of the backend
 */
class FormsListener implements EventListenerInterface {
	use EventListenerTrait;


	/**
	 * @var string
	 */
	protected static string $scope;


	protected string $formElementsFinder;


	/**
	 * @inheritDoc
	 */
	public function implementedEvents(): array {
		return [
			'Model.Forms.beforeRules' => 'beforeRules',
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
	 * @throws \DOMException
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function beforeRules(Event $event, Form $entity, ArrayObject $options): void {
		// Do not clean HTML if this is not the primary entity
		if ($options['_primary'] === false) {
			return;
		}

		if (Configure::read('Awyiss.System.Backend.htmlCleaning') !== 'none') {
			/** @var \Awyiss\Utility\Content\HtmlCleaner $ls_className */
			$ls_className = App::className('HtmlCleaner', 'Utility/Content');
			$ls_className::clean($entity, Configure::read('Awyiss.System.Backend.htmlCleaning'));
		}
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

		/** @var \Awyiss\Model\Table\FormsTable $lo_table */
		$lo_table = $event->getSubject();

		/** @var \Awyiss\Model\Entity\Form $lo_originalEntity */
		$lo_originalEntity = $entity->originalEntity;

		$lo_elements = $lo_table->FormElements->find('threaded', nestingKey: 'childFormElements')
		->find('mediaAssignments', formatResult: false)
		->find('translations')
		->where(['form_id' => $lo_originalEntity->id])
		->all();

		$lo_listedElements = $lo_elements->listNested('desc', 'childFormElements');
		/** @var \Awyiss\Model\Entity\FormElement $lo_formElement */
		foreach ($lo_listedElements as $lo_formElement) {
			$lo_formElement->unset((array)$lo_table->getPrimaryKey());
			$lo_formElement->unset(['formId']);
			$lo_formElement->setNew(true);

			$lo_formElement->formId = $entity->id;
		}

		$lo_table->FormElements->saveMany($lo_elements->toList(), [
			'checkRules' => false,
			'isCopy' => true,
		]);
	}


	/**
	 * @param \Cake\Event\Event $event
	 * @return void
	 */
	public function beforeSoftDelete(Event $event): void {
		/** @var \Awyiss\Model\Table\FormsTable $lo_table */
		$lo_table = $event->getSubject();

		$lo_table->FormElements->disableCascadeCallbacks();
	}


	/**
	 * @param \Cake\Event\Event $event
	 * @return void
	 */
	public function beforeDelete(Event $event): void {
		/** @var \Awyiss\Model\Table\FormsTable $lo_table */
		$lo_table = $event->getSubject();

		$lo_table->FormElements->disableCascadeCallbacks();
	}


	/**
	 * @param \Cake\Event\Event $event
	 * @return void
	 */
	public function afterSoftDelete(Event $event): void {
		/** @var \Awyiss\Model\Table\FormsTable $lo_table */
		$lo_table = $event->getSubject();

		$lo_table->FormElements->enableCascadeCallbacks();
	}


	/**
	 * @param \Cake\Event\Event $event
	 * @return void
	 */
	public function afterDelete(Event $event): void {
		/** @var \Awyiss\Model\Table\FormsTable $lo_table */
		$lo_table = $event->getSubject();

		$lo_table->FormElements->enableCascadeCallbacks();
	}
}
