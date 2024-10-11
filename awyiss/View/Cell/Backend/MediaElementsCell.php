<?php declare(strict_types=1);


namespace Awyiss\View\Cell\Backend;


use Awyiss\Authorization\IdentityPermissionsInterface;
use Awyiss\Core\LocalConfig;
use Awyiss\Model\Enum\ProcessStatus;
use Awyiss\Model\Enum\ResizeStrategy;
use Awyiss\Model\Table;
use Cake\Collection\Collection;
use Cake\Datasource\EntityInterface;
use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\View\Cell;
use RuntimeException;


/**
 * Provides the backend menu with authorization check
 */
class MediaElementsCell extends Cell {
	use LocatorAwareTrait;


	/**
	 * @var \Cake\Collection\Collection $elements
	 */
	protected static Collection $elements;


	/**
	 * @param \Cake\Datasource\EntityInterface $entity
	 * @return void
	 * @throws \ReflectionException
	 */
	public function display(EntityInterface $entity): void {
		// Get the user's identity and session
		$lo_identity = $this->_getIdentity();

		// Set the template for the view
		$this->viewBuilder()->setTemplatePath('Backend/cell/MediaElements')->setTemplate('elements');

		/** @var \Awyiss\Model\Table $lo_table */
		$lo_table = $this->fetchTable($entity->getSource());
		if (!$lo_table->hasBehavior('MediaElementAssignment') || !$lo_identity->scopeIsAccessible('media', [], 'read')) {
			$this->viewBuilder()->setTemplate('element_disabled');

			return;
		}

		$lo_behavior = $lo_table->getBehavior('MediaElementAssignment');

		$lo_elements = $this->getElements();

		$lo_assignedElements = new Collection([]);

		if ($lo_behavior->getConfig('assignable.modelLevel')) {
			// Get all assigned elements on the model level
			/** @noinspection PhpPossiblePolymorphicInvocationInspection */
			$lo_assignedElements = $lo_assignedElements->append($lo_table->MediaElementAssignments->find()->all());
		}

		// Get all assigned elements on the entity level of related entities
		$lo_relatedEntityElements = $this->getRelatedEntityElements($lo_table, $entity);
		if ($lo_relatedEntityElements->count() > 0) {
			$lo_assignedElements = $lo_assignedElements->append($lo_relatedEntityElements);
		}

		$lo_assignedElements = $lo_assignedElements->compile();

		if (!$lo_assignedElements->count()) {
			$this->viewBuilder()->setTemplate('element_disabled');

			return;
		}

		if ($entity->isDirty('mediaAssignments')) {
			/** @noinspection PhpPossiblePolymorphicInvocationInspection */
			$la_mediaIds = array_column($entity->mediaAssignments, 'mediaId');
			if ($la_mediaIds) {
				$la_media = $this->fetchTable('Media')->find()->where(['id IN' => $la_mediaIds])->all()->indexBy('id')->toArray();

				/** @noinspection PhpPossiblePolymorphicInvocationInspection */
				foreach ($entity->mediaAssignments as $lo_mediaAssignment) {
					$lo_mediaAssignment->media = $la_media[ $lo_mediaAssignment->mediaId ] ?? null;
				}

				/** @noinspection PhpPossiblePolymorphicInvocationInspection */
				$lo_table->getBehavior('MediaAssignment')->rebuildMediaAssignments($entity);
			}
		}

		$this->set([
			'assignedElements' => $lo_assignedElements,
			'autoCreateFolder' => LocalConfig::read('mediaFolders.autoCreate', false, $entity->getSource()),
			'elements' => $lo_elements,
			'entity' => $entity,
			'ProcessStatus' => ProcessStatus::class,
			'ResizeStrategy' => ResizeStrategy::class,
		]);
	}


	/**
	 * Builds the element assignments view
	 *
	 * @param \Cake\Datasource\EntityInterface $entity
	 * @return void
	 */
	public function elementAssignments(EntityInterface $entity): void {
		// Set the template for the view
		$this->viewBuilder()->setTemplatePath('Backend/cell/MediaElements')->setTemplate('element_assignments');

		$lo_availableElements = $this->getElements();
		$la_assignedElements = $entity->mediaElementAssignments ?? false;

		if ($la_assignedElements === false) {
			$this->viewBuilder()->setTemplate('element_assignments_disabled');
			return;
		}

		$la_assignedElementIds = array_column($la_assignedElements ?: [], 'mediaElementId');

		$lo_availableElements = $lo_availableElements->sortBy(function ($element) use ($la_assignedElementIds) {
			return array_search($element->id, $la_assignedElementIds);
		}, SORT_ASC);

		$this->set([
			'entity' => $entity,
			'availableElements' => $lo_availableElements,
			'assignedElements' => $la_assignedElements,
		]);
	}


	/**
	 * Fetches the MediaElements records
	 *
	 * @return \Cake\Collection\Collection
	 */
	protected function getElements(): Collection {
		if (!isset(static::$elements)) {
			static::$elements = $this->fetchTable('MediaElements')->find('active')
			->where(['id >' => 0])
			->contain([
				'MediaElementSelectors' => [
					'MediaSelectors',
				],
			])->all()->compile();
		}

		return static::$elements;
	}


	/**
	 * Find all assigned elements to related entities
	 * Find all belongsTo associations of the table
	 *
	 * @param \Awyiss\Model\Table $table
	 * @param \Cake\Datasource\EntityInterface $entity
	 * @return \Cake\Collection\Collection
	 */
	protected function getRelatedEntityElements(Table $table, EntityInterface $entity): Collection {
		$lo_relatedEntityElements = new Collection([]);

		$la_where = [];

		$la_associations = $table->associations()->getByType('BelongsTo');
		foreach ($la_associations as $lo_association) {
			$ls_foreignKey = $lo_association->getForeignKey();

			if (is_array($ls_foreignKey)) {
				$ls_foreignKey = array_shift($ls_foreignKey);
			}

			// Skip parent_id as the parent cannot provide different elements
			// and skip if the property is empty
			if ($ls_foreignKey === 'parent_id' || !$entity->get($ls_foreignKey)) {
				continue;
			}

			$lo_relatedEntityTable = $lo_association->getTarget();
			if (!$lo_relatedEntityTable->hasBehavior('MediaElementAssignment')) {
				continue;
			}

			$lo_behavior = $lo_relatedEntityTable->getBehavior('MediaElementAssignment');

			if (!$lo_behavior->getConfig('assignable.entityLevel')) {
				continue;
			}

			$la_where[] = [
				'scope' => $lo_relatedEntityTable->getTable(),
				'foreign_key' => $entity->get($ls_foreignKey),
			];
		}

		if ($la_where) {
			$lo_table = $this->fetchTable('MediaElementAssignments');

			$lo_relatedEntityElements = $lo_table->find()->where(['OR' => $la_where])->groupBy('media_element_id')->all();
		}

		/** @noinspection PhpIncompatibleReturnTypeInspection */
		return $lo_relatedEntityElements->compile();
	}


	/**
	 * Retreive the identity attribute from the current request
	 */
	protected function _getIdentity(): IdentityPermissionsInterface {
		/** @var IdentityPermissionsInterface|\Awyiss\Model\Entity\User $lo_identity */
		$lo_identity = $this->request->getAttribute('identity');

		if (!$lo_identity) {
			throw new RuntimeException('No identity found in the request.');
		}

		if (!($lo_identity instanceof IdentityPermissionsInterface)) {
			throw new RuntimeException(sprintf('Object `%s` does not implement `%s`', get_class($lo_identity), IdentityPermissionsInterface::class));
		}


		return $lo_identity;
	}
}
