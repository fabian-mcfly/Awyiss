<?php declare(strict_types=1);


namespace Awyiss\View\Cell\Backend;


use Awyiss\Authorization\IdentityPermissionsInterface;
use Awyiss\Awyiss;
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
 * Shows the media elements assigned to an entity or its related entities,
 * saved in `media_element_assignments` using the `display`-method.
 *
 * The `elementAssignments`-method is used to allow the user to
 * assign new media elements to the entity.
 */
class MediaElementsCell extends Cell {
	use LocatorAwareTrait;


	/**
	 * @var \Cake\Collection\Collection $elements
	 */
	protected static Collection $elements;


	/**
	 * Shows all media elements that are either
	 * - assigned to the model (modelLevel) of the provided entity
	 * - assigned to a related entity (entityLevel)
	 *
	 * `modelLevel`: The media elements are assigned to the model and are available for all records.
	 * For example: generic data tables have their media elements assigned on the model level,
	 * since the media elements are the same for all records.
	 * Use case: a list of employees with a profile picture.
	 * Adding or modifying employees will always use the same media elements.
	 *
	 * `entityLevel`: The media elements are assigned to the entity and are only available for this record.
	 * For example: pages and contents have their media elements assigned on the entity level
	 * of their assigned template.
	 * Use case: a page with template "Standard" has no need for the media elements of template "News".
	 * Selecting a different template will change the visible media elements.
	 *
	 * @param \Cake\Datasource\EntityInterface $entity
	 * @return void
	 * @throws \ReflectionException
	 */
	public function display(EntityInterface $entity): void {
		// Get the user's identity and session
		$identity = $this->_getIdentity();

		// Set the template for the view
		$this->viewBuilder()->setTemplatePath('Backend/cell/MediaElements')->setTemplate('elements');

		/** @var \Awyiss\Model\Table $table */
		$table = $this->fetchTable($entity->getSource());
		if (!$table->hasBehavior('MediaElementAssignment') || !$identity->scopeIsAccessible('media', [], 'read')) {
			return;
		}

		$mediaElementAssignmentBehavior = $table->getBehavior('MediaElementAssignment');

		$elements = $this->getElements();

		$assignedElements = new Collection([]);

		if ($mediaElementAssignmentBehavior->getConfig('assignable.modelLevel')) {
			// Get all assigned elements on the model level
			/** @noinspection PhpPossiblePolymorphicInvocationInspection */
			$assignedElements = $assignedElements->append($table->MediaElementAssignments->find()->all());
		}

		// Get all assigned elements on the entity level of related entities
		$relatedEntityElements = $this->getRelatedEntityElements($table, $entity);
		if ($relatedEntityElements->count() > 0) {
			$assignedElements = $assignedElements->append($relatedEntityElements);
		}

		$assignedElements = $assignedElements->compile();

		if (!$assignedElements->count()) {
			return;
		}

		if ($entity->isDirty('mediaAssignments')) {
			/** @noinspection PhpPossiblePolymorphicInvocationInspection */
			$mediaIds = array_column($entity->mediaAssignments, 'mediaId');
			if ($mediaIds) {
				$media = $this->fetchTable('Media')->find()->where(['id IN' => $mediaIds])->all()->indexBy('id')->toArray();

				/** @noinspection PhpPossiblePolymorphicInvocationInspection */
				foreach ($entity->mediaAssignments as $mediaAssignment) {
					$mediaAssignment->media = $media[ $mediaAssignment->mediaId ] ?? null;
				}

				/** @noinspection PhpPossiblePolymorphicInvocationInspection */
				$table->getBehavior('MediaAssignment')->rebuildMediaAssignments($entity);
			}
		}

		$this->set([
			'assignedElements' => $assignedElements,
			'autoCreateFolder' => LocalConfig::read('mediaFolders.autoCreate', false, $entity->getSource()),
			'elements' => $elements,
			'entity' => $entity,
			'ProcessStatus' => ProcessStatus::class,
			'ResizeStrategy' => ResizeStrategy::class,
		]);
	}


	/**
	 * Builds the element assignments view
	 *
	 * Shows all media elements that are available for assignment,
	 * as well as all already assigned media elements.
	 *
	 * @param \Cake\Datasource\EntityInterface $entity
	 * @return void
	 */
	public function elementAssignments(EntityInterface $entity): void {
		// Set the template for the view
		$this->viewBuilder()->setTemplatePath('Backend/cell/MediaElements')->setTemplate('element_assignments');

		$availableElements = $this->getElements();
		$assignedElements = $entity->mediaElementAssignments ?? false;

		if ($assignedElements === false) {
			return;
		}

		$assignedElementIds = array_column($assignedElements ?: [], 'mediaElementId');

		$availableElements = $availableElements->sortBy(function ($element) use ($assignedElementIds) {
			return array_search($element->id, $assignedElementIds);
		}, SORT_ASC);

		$this->set([
			'entity' => $entity,
			'availableElements' => $availableElements,
			'assignedElements' => $assignedElements,
		]);
	}


	/**
	 * Fetches the MediaElements records
	 *
	 * @return \Cake\Collection\Collection
	 */
	protected function getElements(): Collection {
		if (!isset(static::$elements)) {
			/**
			 * @uses \Awyiss\Model\Table::findActive()
			 * @noinspection PhpFieldAssignmentTypeMismatchInspection
			 */
			static::$elements = $this->fetchTable('MediaElements')->find('active')
			->where(['internal' => 0])
			->contain([
				'MediaElementSelectors' => [
					'MediaSelectors',
				],
			])
			->all()
			->compile();
		}

		return static::$elements;
	}


	/**
	 * Find all assigned elements to related entities
	 * in belongsTo associations
	 *
	 * @param \Awyiss\Model\Table $table
	 * @param \Cake\Datasource\EntityInterface $entity
	 * @return \Cake\Collection\Collection
	 */
	protected function getRelatedEntityElements(Table $table, EntityInterface $entity): Collection {
		$relatedEntityElements = new Collection([]);

		$where = [];

		$associations = $table->associations()->getByType('BelongsTo');
		foreach ($associations as $association) {
			$foreignKey = $association->getForeignKey();

			if (is_array($foreignKey)) {
				$foreignKey = array_shift($foreignKey);
			}

			// Skip parent_id as the parent cannot provide different elements
			// and skip if the property is empty
			if ($foreignKey === 'parent_id' || !$entity->get($foreignKey)) {
				continue;
			}

			$relatedEntityTable = $association->getTarget();
			if (!$relatedEntityTable->hasBehavior('MediaElementAssignment')) {
				continue;
			}

			$behavior = $relatedEntityTable->getBehavior('MediaElementAssignment');

			if (!$behavior->getConfig('assignable.entityLevel')) {
				continue;
			}

			$where[] = [
				'scope' => $relatedEntityTable->getTable(),
				'foreign_key' => $entity->get($foreignKey),
			];
		}

		if ($where) {
			$mediaElementAssignmentsTable = $this->fetchTable('MediaElementAssignments');

			$relatedEntityElements = $mediaElementAssignmentsTable->find()->where(['OR' => $where])->groupBy('media_element_id')->all();
		}

		/** @noinspection PhpIncompatibleReturnTypeInspection */
		return $relatedEntityElements->compile();
	}


	/**
	 * Retrieve the identity attribute from the current request
	 */
	protected function _getIdentity(): IdentityPermissionsInterface {
		/** @var IdentityPermissionsInterface|\Awyiss\Model\Entity\User $identity */
		$identity = $this->request->getAttribute(Awyiss::REALM_BACKEND . 'Identity');

		if (!$identity) {
			throw new RuntimeException('No identity found in the request.');
		}

		if (!($identity instanceof IdentityPermissionsInterface)) {
			throw new RuntimeException(sprintf('Object `%s` does not implement `%s`', get_class($identity), IdentityPermissionsInterface::class));
		}


		return $identity;
	}
}
