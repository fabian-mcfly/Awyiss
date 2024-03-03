<?php declare(strict_types=1);


namespace Awyiss\Event\Backend;


use ArrayObject;
use Awyiss\Event\EventListenerTrait;
use Awyiss\Model\Entity\Content;
use Cake\Event\Event;
use Cake\Event\EventListenerInterface;


/**
 * Event listeners for the Contents scope of the backend
 */
class ContentsListener implements EventListenerInterface {
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
			'Model.Contents.beforeCopy' => 'beforeCopy',
		];
	}


	/**
	 * @param \Cake\Event\Event $ao_event
	 * @param \Awyiss\Model\Entity\Content $ao_entity
	 * @param \ArrayObject $ao_options
	 * @return void
	 */
	public function beforeCopy(Event $ao_event, Content $ao_entity, ArrayObject $ao_options): void {
		if ($ao_options['_primary'] !== true) {
			return;
		}

		/** @var \Awyiss\Model\Table\ContentsTable $lo_table */
		$lo_table = $ao_event->getSubject();

		/** @var \Awyiss\Model\Entity\Content $lo_originalEntity */
		$lo_originalEntity = $ao_entity->originalEntity;
		$lo_children = $lo_originalEntity->getNestedChildren();

		if (!$lo_children?->count()) {
			return;
		}

		$lo_nestedChildren = $lo_children->nest('id', 'parentId', 'childContents')->toList();

		$la_relatedColumns = $lo_table->getBehavior('Nest')->getConfig('relatedColumns');

		/** @var \Awyiss\Model\Entity\Content $lo_childContent */
		foreach ($lo_children as $lo_childContent) {
			$la_primaryKeys = $lo_childContent->extract((array)$lo_table->getPrimaryKey());
			$lo_childContent->originalPrimaryKeys = $la_primaryKeys;

			$lo_childContent->unset((array)$lo_table->getPrimaryKey());
			$lo_childContent->setNew(true);

			$lo_childContent->set($ao_entity->extract($la_relatedColumns));
		}

		$ao_entity->childContents = $lo_nestedChildren;

		$lo_table->ChildContents->getBehavior('Nest')->setConfig('buildRules', false);
	}
}
