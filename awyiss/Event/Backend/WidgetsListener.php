<?php declare(strict_types=1);


namespace Awyiss\Event\Backend;


use ArrayObject;
use Awyiss\Event\EventListenerTrait;
use Awyiss\Model\Entity\Widget;
use Cake\Event\Event;
use Cake\Event\EventListenerInterface;


/**
 * Event listeners for the Widgets scope of the backend
 */
class WidgetsListener implements EventListenerInterface {
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
			'Model.Widgets.beforeCopy' => 'beforeCopy',
		];
	}


	/**
	 * @param \Cake\Event\Event $event
	 * @param \Awyiss\Model\Entity\Widget $entity
	 * @param \ArrayObject $options
	 * @return void
	 */
	public function beforeCopy(Event $event, Widget $entity, ArrayObject $options): void {
		if ($options['_primary'] !== true) {
			return;
		}

		/** @var \Awyiss\Model\Table\WidgetsTable $lo_table */
		$lo_table = $event->getSubject();

		/** @var \Awyiss\Model\Entity\Widget $lo_originalEntity */
		$lo_originalEntity = $entity->originalEntity;
		$lo_children = $lo_originalEntity->getNestedChildren();

		if (!$lo_children?->count()) {
			return;
		}

		$lo_nestedChildren = $lo_children->nest('id', 'parentId', 'childWidgets')->toList();

		$la_relatedColumns = $lo_table->getBehavior('Nest')->getConfig('relatedColumns');

		/** @var \Awyiss\Model\Entity\Widget $lo_childWidget */
		foreach ($lo_children as $lo_childWidget) {
			$la_primaryKeys = $lo_childWidget->extract((array)$lo_table->getPrimaryKey());
			$lo_childWidget->originalPrimaryKeys = $la_primaryKeys;

			$lo_childWidget->unset((array)$lo_table->getPrimaryKey());
			$lo_childWidget->setNew(true);

			$lo_childWidget->set($entity->extract($la_relatedColumns));
		}

		$entity->childWidgets = $lo_nestedChildren;

		$lo_table->ChildWidgets->getBehavior('Nest')->setConfig('buildRules', false);
	}
}
