<?php declare(strict_types=1);


namespace Awyiss\Event\Backend;


use ArrayObject;
use Awyiss\Event\EventListenerTrait;
use Awyiss\Model\Entity\FormElement;
use Cake\Event\Event;
use Cake\Event\EventListenerInterface;


/**
 * Event listeners for the FormElements scope of the backend
 */
class FormElementsListener implements EventListenerInterface {
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
			'Model.FormElements.beforeCopy' => 'beforeCopy',
		];
	}


	/**
	 * @param \Cake\Event\Event $event
	 * @param \Awyiss\Model\Entity\FormElement $entity
	 * @param \ArrayObject $options
	 * @return void
	 */
	public function beforeCopy(Event $event, FormElement $entity, ArrayObject $options): void {
		if ($options['_primary'] !== true) {
			return;
		}

		/** @var \Awyiss\Model\Table\FormElementsTable $lo_table */
		$lo_table = $event->getSubject();

		/** @var \Awyiss\Model\Entity\FormElement $lo_originalEntity */
		$lo_originalEntity = $entity->originalEntity;
		$lo_children = $lo_originalEntity->getNestedChildren();

		if (!$lo_children?->count()) {
			return;
		}

		$lo_nestedChildren = $lo_children->nest('id', 'parentId', 'childFormElements')->toList();

		$la_relatedColumns = $lo_table->getBehavior('Nest')->getConfig('relatedColumns');

		/** @var \Awyiss\Model\Entity\FormElement $lo_childFormElement */
		foreach ($lo_children as $lo_childFormElement) {
			$la_primaryKeys = $lo_childFormElement->extract((array)$lo_table->getPrimaryKey());
			$lo_childFormElement->originalPrimaryKeys = $la_primaryKeys;

			$lo_childFormElement->unset((array)$lo_table->getPrimaryKey());
			$lo_childFormElement->setNew(true);

			$lo_childFormElement->set($entity->extract($la_relatedColumns));
			$lo_childFormElement->identifier .= '-copy-' . time();
		}

		$entity->childFormElements = $lo_nestedChildren;

		$lo_table->ChildFormElements->getBehavior('Nest')->setConfig('buildRules', false);
	}
}
