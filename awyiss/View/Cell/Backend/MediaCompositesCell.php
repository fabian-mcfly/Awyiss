<?php declare(strict_types=1);


namespace Awyiss\View\Cell\Backend;


use Awyiss\Model\Table;
use Cake\Collection\Collection;
use Cake\Datasource\EntityInterface;
use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\View\Cell;


/**
 * Provides the backend menu with authorization check
 */
class MediaCompositesCell extends Cell {
	use LocatorAwareTrait;


	/**
	 * @var \Cake\Collection\Collection $composites
	 */
	protected static Collection $composites;


	/**
	 * @param \Cake\Datasource\EntityInterface $entity
	 * @return void
	 */
	public function display(EntityInterface $entity): void {
		// Set the template for the view
		$this->viewBuilder()->setTemplatePath('Backend/cell/MediaComposites')->setTemplate('composites');

		/** @var \Awyiss\Model\Table $lo_table */
		$lo_table = $this->fetchTable($entity->getSource());
		if (!$lo_table->hasBehavior('MediaCompositeAssignment')) {
			$this->viewBuilder()->setTemplate('composite_disabled');
			return;
		}

		$lo_behavior = $lo_table->getBehavior('MediaCompositeAssignment');

		$lo_composites = $this->getComposites();

		$lo_assignedComposites = new Collection([]);

		if ($lo_behavior->getConfig('assignable.modelLevel')) {
			// Get all assigned composites on the model level
			$lo_assignedComposites = $lo_assignedComposites->append($lo_table->MediaCompositeAssignments->find()->all());
		}

		// Get all assigned composites on the entity level of related entities
		$lo_relatedEntityComposites = $this->getRelatedEntityComposites($lo_table, $entity);
		if ($lo_relatedEntityComposites->count() > 0) {
			$lo_assignedComposites = $lo_assignedComposites->append($lo_relatedEntityComposites);
		}

		$lo_assignedComposites = $lo_assignedComposites->compile();

		if (!$lo_assignedComposites->count()) {
			$this->viewBuilder()->setTemplate('composite_disabled');

			return;
		}

		if ($entity->isDirty('mediaAssignments')) {
			$la_mediaIds = array_column($entity->mediaAssignments, 'media_id');
			if ($la_mediaIds) {
				$la_media = $this->fetchTable('Media')->find()->where(['id IN' => $la_mediaIds])->all()->indexBy('id')->toArray();

				foreach ($entity->mediaAssignments as $lo_mediaAssignment) {
					$lo_mediaAssignment->media = $la_media[ $lo_mediaAssignment->mediaId ];
				}

				$lo_table->getBehavior('MediaAssignment')->rebuildMediaAssignments($entity);
			}
		}

		$this->set([
			'composites' => $lo_composites,
			'entity' => $entity,
			'assignedComposites' => $lo_assignedComposites,
		]);
	}


	/**
	 * Builds the composite assignments view
	 *
	 * @param \Cake\Datasource\EntityInterface $entity
	 * @return void
	 */
	public function compositeAssignments(EntityInterface $entity): void {
		// Set the template for the view
		$this->viewBuilder()->setTemplatePath('Backend/cell/MediaComposites')->setTemplate('composite_assignments');

		$lo_availableComposites = $this->getComposites();
		$la_assignedComposites = $entity->mediaCompositeAssignments ?? false;

		if ($la_assignedComposites === false) {
			$this->viewBuilder()->setTemplate('composite_assignments_disabled');
			return;
		}

		$la_assignedCompositeIds = array_column($la_assignedComposites ?: [], 'mediaCompositeId');

		$lo_availableComposites = $lo_availableComposites->sortBy(function ($composite) use ($la_assignedCompositeIds) {
			return array_search($composite->id, $la_assignedCompositeIds);
		}, SORT_ASC);

		$this->set([
			'entity' => $entity,
			'availableComposites' => $lo_availableComposites,
			'assignedComposites' => $la_assignedComposites,
		]);
	}


	/**
	 * Fetches the MediaComposites records
	 *
	 * @return \Cake\Collection\Collection
	 */
	protected function getComposites(): Collection {
		if (!isset(static::$composites)) {
			static::$composites = $this->fetchTable('MediaComposites')->find('active')->contain([
				'MediaCompositeSelectors' => [
					'MediaSelectors',
				],
			])->all()->compile();
		}

		return static::$composites;
	}


	/**
	 * Find all assigned composites to related entities
	 * Find all belongsTo associations of the table
	 *
	 * @param \Awyiss\Model\Table $table
	 * @param \Cake\Datasource\EntityInterface $entity
	 * @return \Cake\Collection\Collection
	 */
	protected function getRelatedEntityComposites(Table $table, EntityInterface $entity): Collection {
		$lo_relatedEntityComposites = new Collection([]);

		$la_where = [];

		$la_associations = $table->associations()->getByType('BelongsTo');
		foreach ($la_associations as $lo_association) {
			$ls_foreignKey = $lo_association->getForeignKey();

			if (is_array($ls_foreignKey)) {
				$ls_foreignKey = array_shift($ls_foreignKey);
			}

			// Skip parent_id as the parent cannot provide different composites
			// and skip if the property is empty
			if ($ls_foreignKey === 'parent_id' || !$entity->get($ls_foreignKey)) {
				continue;
			}

			$lo_relatedEntityTable = $lo_association->getTarget();
			if (!$lo_relatedEntityTable->hasBehavior('MediaCompositeAssignment')) {
				continue;
			}

			$lo_behavior = $lo_relatedEntityTable->getBehavior('MediaCompositeAssignment');

			if (!$lo_behavior->getConfig('assignable.entityLevel')) {
				continue;
			}

			$la_where[] = [
				'scope' => $lo_relatedEntityTable->getTable(),
				'foreign_key' => $entity->get($ls_foreignKey),
			];
		}

		if ($la_where) {
			$lo_table = $this->fetchTable('MediaCompositeAssignments');

			$lo_relatedEntityComposites = $lo_table->find()->where(['OR' => $la_where])->groupBy('media_composite_id')->all();
		}

		/** @noinspection PhpIncompatibleReturnTypeInspection */
		return $lo_relatedEntityComposites->compile();
	}
}
