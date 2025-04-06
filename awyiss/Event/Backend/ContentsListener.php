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
			'Model.Contents.beforeSave' => 'beforeSave',
		];
	}


	/**
	 * @param \Cake\Event\Event $event
	 * @param \Awyiss\Model\Entity\Content $entity
	 * @param \ArrayObject $options
	 * @return void
	 */
	public function beforeCopy(Event $event, Content $entity, ArrayObject $options): void {
		if ($options['_primary'] !== true) {
			return;
		}

		/** @var \Awyiss\Model\Table\ContentsTable $lo_table */
		$lo_table = $event->getSubject();

		/** @var \Awyiss\Model\Entity\Content $lo_originalEntity */
		$lo_originalEntity = $entity->originalEntity;
		$lo_children = $lo_originalEntity->getNestedChildren([
			'finders' => [
				'mediaAssignments' => ['formatResult' => false],
				'translations',
			],
		]);

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

			$lo_childContent->set($entity->extract($la_relatedColumns));
		}

		$entity->childContents = $lo_nestedChildren;

		$lo_table->ChildContents->getBehavior('Nest')->setConfig('buildRules', false);
	}


	/**
	 * @param \Cake\Event\Event $event
	 * @param \Awyiss\Model\Entity\Widget $entity
	 * @param \ArrayObject $options
	 * @return void
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function beforeSave(Event $event, Content $entity, ArrayObject $options): void {
		// Unset titleTag and subtitleTag if title and subtitle are empty
		if (!$entity->title && $entity->titleTag) {
			$entity->titleTag = null;
		}

		if (!$entity->subtitle && $entity->subtitleTag) {
			$entity->subtitleTag = null;
		}
	}
}
