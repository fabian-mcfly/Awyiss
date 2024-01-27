<?php declare(strict_types=1);


namespace Awyiss\Model\Behavior;


use ArrayObject;
use Awyiss\ORM\Behavior;
use Awyiss\ORM\RulesChecker;
use Cake\Collection\Collection;
use Cake\Collection\CollectionInterface;
use Cake\Datasource\EntityInterface;
use Cake\Event\EventInterface;
use Cake\Utility\Hash;
use Cake\Utility\Inflector;
use RuntimeException;


/**
 * The NestBehavior exposes four methods on the table object:
 * `getNestedChildren()`, `getChildren()`, `getParent()` and `getParents()`
 *
 * For this, it uses the config options `children`/`parent` with the following options
 *    - associationName: the alias of a valid hasMany/belongsTo-association, set on the table
 *    - finder: the finder that's being used to get all children/parent(s)
 *    - maxLevel: maximum depth of items to return
 *
 * It also moves all nested children to a new scope in the `afterSave`-event, using the `relatedColumns`-option
 */
class NestBehavior extends Behavior {
	/**
	 * Default configuration
	 * These are merged with user-provided configuration when the behavior is used.
	 *
	 * @var array
	 */
	protected array $_defaultConfig = [
		'alias' => null,
		'buildRules' => true,
		'children' => [
			'associationName' => null,
			'bindingKey' => 'id',
			'finder' => 'withMatchingAttributes',
			'foreignKey' => 'parent_id',
			'maxLevel' => null,
		],
		'enabled' => true,
		'implementedEvents' => [
			'buildRules',
			'beforeSave',
			'afterSave',
		],
		'implementedMethods' => [
			'getNestedChildren' => 'getNestedChildren',
			'getChildren' => 'getChildren',
			'getParent' => 'getParent',
			'getParents' => 'getParents',
		],
		'parent' => [
			'associationName' => null,
			'bindingKey' => 'id',
			'finder' => 'withMatchingAttributes',
			'foreignKey' => 'parent_id',
			'maxLevel' => null,
		],
		'relatedColumns' => [],
		'skip' => false,
	];
	/**
	 * @var array Remembered data from existing entities in the beforeSave method
	 */
	protected array $rememberedData = [];


	/**
	 * @inheritDoc
	 */
	public function initialize(array $aa_config): void {
		parent::initialize($aa_config);

		if (!$this->getConfig('alias')) {
			$ls_alias = $this->table()->getAlias();

			if (str_starts_with($ls_alias, 'Child')) {
				$ls_alias = substr($ls_alias, 5);
			}
			elseif (str_starts_with($ls_alias, 'Parent')) {
				$ls_alias = substr($ls_alias, 6);
			}

			$this->setConfig('alias', $ls_alias);
		}

		$this->buildAssociations();
	}


	/**
	 * Build the association for the table
	 *
	 * @return $this
	 */
	public function buildAssociations(): static {
		if (!$this->getConfig('enabled')) {
			return $this;
		}

		$lo_table = $this->table();
		$ls_alias = $this->getConfig('alias');
		if (!$this->getConfig('children.associationName') || !$this->table()->hasAssociation($this->getConfig('children.associationName'))) {
			$ls_associationName = $this->getConfig('children.associationName') ?: 'Child' . $ls_alias;
			/** @var \Awyiss\Model\Entity $ls_entityClass */
			$ls_entityClass = $lo_table->getEntityClass();

			$la_bindingKeys = array_merge((array)$this->getConfig('children.bindingKey'), $this->getConfig('relatedColumns'));
			$la_bindingKeys = array_filter($la_bindingKeys, fn ($as_field) => !str_starts_with($as_field, 'attributes.'));
			$la_bindingKeys = $ls_entityClass::unmapFields($la_bindingKeys);

			$la_foreignKeys = array_merge((array)$this->getConfig('children.foreignKey'), $this->getConfig('relatedColumns'));
			$la_foreignKeys = array_filter($la_foreignKeys, fn ($as_field) => !str_starts_with($as_field, 'attributes.'));
			$la_foreignKeys = $ls_entityClass::unmapFields($la_foreignKeys);

			$this->table()->hasMany($ls_associationName, [
				'bindingKey' => $la_bindingKeys,
				'cascadeCallbacks' => true,
				'className' => $ls_alias,
				'dependent' => true,
				'foreignKey' => $la_foreignKeys,
			]);

			$this->setConfig('children.associationName', $ls_associationName);
		}

		if (!$this->getConfig('parent.associationName') || !$this->table()->hasAssociation($this->getConfig('parent.associationName'))) {
			$ls_associationName = $this->getConfig('parent.associationName') ?: 'Parent' . $ls_alias;
			/** @var \Awyiss\Model\Entity $ls_entityClass */
			$ls_entityClass ??= $lo_table->getEntityClass();

			$la_bindingKeys = array_merge((array)$this->getConfig('parent.bindingKey'), $this->getConfig('relatedColumns'));
			$la_bindingKeys = array_filter($la_bindingKeys, fn ($as_field) => !str_starts_with($as_field, 'attributes.'));
			$la_bindingKeys = $ls_entityClass::unmapFields($la_bindingKeys);

			$la_foreignKeys = array_merge((array)$this->getConfig('parent.foreignKey'), $this->getConfig('relatedColumns'));
			$la_foreignKeys = array_filter($la_foreignKeys, fn ($as_field) => !str_starts_with($as_field, 'attributes.'));
			$la_foreignKeys = $ls_entityClass::unmapFields($la_foreignKeys);

			$this->table()->belongsTo($ls_associationName, [
				'bindingKey' => $la_bindingKeys,
				'className' => $ls_alias,
				'foreignKey' => $la_foreignKeys,
			]);

			$this->setConfig('parent.associationName', $ls_associationName);
		}


		return $this;
	}


	/**
	 * Returns a collection containing all direct children of the given entity.
	 *
	 * @noinspection PhpUnused
	 */
	public function getChildren(EntityInterface $ao_entity, array $aa_options = []): ?CollectionInterface {
		if (!$this->getConfig('enabled') || !$this->getConfig('children')) {
			return null;
		}

		$ls_associationName = $this->getConfig('children.associationName');
		if (!$ls_associationName || !$this->table()->hasAssociation($ls_associationName)) {
			throw new RuntimeException(sprintf('Expected option for `children.associationName` to be a valid assocation on table `%s`', $this->table()->getAlias()));
		}

		$lo_association = $this->table()->getAssociation($ls_associationName);

		$lx_finder = $aa_options['finder'] ?? $this->getConfig('children.finder');

		$lo_query = $lo_association->find($lx_finder, $ao_entity, $this->getConfig('relatedColumns'));

		$la_bindingKeys = (array)$lo_association->getBindingKey();

		/** @var \Awyiss\Model\Entity $ls_entityClass */
		$ls_entityClass = $lo_association->getSource()->getEntityClass();
		$la_skipFields = $ls_entityClass::unmapFields($aa_options['skipFields'] ?? []);
		foreach ((array)$lo_association->getForeignKey() as $li_key => $ls_field) {
			$ls_field = $ls_entityClass::unmapField($ls_field);

			if (in_array($ls_field, $la_skipFields)) {
				continue;
			}

			$lx_value = $ao_entity->hasOriginal($la_bindingKeys[ $li_key ]) ? $ao_entity->getOriginal($la_bindingKeys[ $li_key ]) : $ao_entity->get($la_bindingKeys[ $li_key ]);

			if ($lx_value === null) {
				$ls_field .= ' IS';
			}

			$lo_query->where([
				$lo_association->getAlias() . '.' . $ls_field => $lx_value,
			]);
		}


		return $lo_query->all();
	}


	/**
	 * Returns a collection containing all nested children of the given entity.
	 *
	 * The depth can be limited using the `maxLevel` option in either the `children`-config array or
	 * as an array key in the second parameter of the method call.
	 *
	 * Calling `$Comments->getNestedChildren($comment, ['maxLevel' => 2]);` returns all direct children of $comment as well as
	 * all direct children of those.
	 *
	 * @noinspection PhpUnused
	 */
	public function getNestedChildren(EntityInterface $ao_entity, array $aa_options = [], int $ai_currentLevel = 0): ?CollectionInterface {
		if (!$this->getConfig('enabled') || !$this->getConfig('children')) {
			return null;
		}

		$lo_collection = new Collection([]);

		foreach ($this->getChildren($ao_entity, $aa_options) as $lo_entity) {
			$lo_collection = $lo_collection->appendItem($lo_entity);

			$li_maxLevel = $aa_options['maxLevel'] ?? $this->getConfig('children.maxLevel');
			if (isset($li_maxLevel) && $li_maxLevel <= $ai_currentLevel + 1) {
				continue;
			}

			$lo_children = $this->getNestedChildren($lo_entity, $aa_options, $ai_currentLevel + 1);
			if (!$lo_children->count()) {
				continue;
			}

			$lo_collection = $lo_collection->append($lo_children);
		}


		return $lo_collection->compile(false);
	}


	/**
	 * Returns the direct parent entity of the given entity or `null` if none exists
	 *
	 * @noinspection PhpUnused
	 */
	public function getParent(EntityInterface $ao_entity, array $aa_options = []): ?EntityInterface {
		if (!$this->getConfig('enabled') || !$this->getConfig('parent')) {
			return null;
		}

		$ls_associationName = $this->getConfig('parent.associationName');
		if (!$ls_associationName || !$this->table()->hasAssociation($ls_associationName)) {
			throw new RuntimeException(sprintf('Expected option for `parent.associationName` to be a valid assocation on table `%s`', $this->table()->getAlias()));
		}

		$lo_association = $this->table()->getAssociation($ls_associationName);

		if (!$ao_entity->get($lo_association->getForeignKey())) {
			return null;
		}

		$lx_finder = $aa_options['finder'] ?? $this->getConfig('parent.finder');


		return $lo_association->find($lx_finder)->where([
			$lo_association->getAlias() . '.' . $lo_association->getBindingKey() => $ao_entity->get($lo_association->getForeignKey()),
		])->first();
	}


	/**
	 * Returns a collection containing all parents of the given entity.
	 *
	 * The depth can be limited using the `maxLevel` option in either the `parent`-config array or
	 * in the second parameter of the method call.
	 *
	 * Calling `$Comments->getParents($comment, ['maxLevel' => 2]);` returns the direct parent of $comment as well as
	 * all direct parent of this.
	 *
	 * @noinspection PhpUnused
	 */
	public function getParents(EntityInterface $ao_entity, array $aa_options = [], int $ai_currentLevel = 0): ?CollectionInterface {
		if (!$this->getConfig('enabled') || !$this->getConfig('parent')) {
			return null;
		}

		$lo_collection = new Collection([]);

		$lo_entity = $this->getParent($ao_entity, $aa_options);
		if (!$lo_entity) {
			return $lo_collection->compile(false);
		}

		$lo_collection = $lo_collection->appendItem($lo_entity);

		$li_maxLevel = $aa_options['maxLevel'] ?? $this->getConfig('parent.maxLevel');
		if (isset($li_maxLevel) && $li_maxLevel <= $ai_currentLevel + 1) {
			return $lo_collection->compile(false);
		}

		$lo_parent = $this->getParents($lo_entity, $aa_options, $ai_currentLevel + 1);
		if ($lo_parent) {
			$lo_collection = $lo_collection->append($lo_parent);
		}


		return $lo_collection->compile(false);
	}


	/**
	 * @param EventInterface $ao_event
	 * @param RulesChecker $ao_rules
	 * @return RulesChecker
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function buildRules(EventInterface $ao_event, RulesChecker $ao_rules): RulesChecker {
		if (!$this->getConfig('enabled') || !$this->getConfig('buildRules')) {
			return $ao_rules;
		}

		$ls_foreignKey = $this->getConfig('parent.foreignKey');

		$ao_rules->add(function (EntityInterface $ao_entity, array $aa_options) use ($ao_rules, $ls_foreignKey): string|bool {
			if (!$ao_entity->get($ls_foreignKey)) {
				return true;
			}

			/** @var \Awyiss\Model\Table $lo_table */
			$lo_table = $this->table();
			/** @var \Awyiss\Model\Entity $ls_entityClass */
			$ls_entityClass = $lo_table->getEntityClass();

			$la_foreignKeys = array_merge((array)$ls_foreignKey, $this->getConfig('relatedColumns'));
			$la_foreignKeys = $ls_entityClass::unmapFields($la_foreignKeys);

			$la_attributeKeys = [];
			foreach ($la_foreignKeys as $li_key => $ls_key) {
				if (str_starts_with($ls_key, 'attributes.')) {
					$la_attributeKeys[] = substr($ls_key, 11);
					unset($la_foreignKeys[ $li_key ]);
				}
			}

			$lo_association = $lo_table->getAssociation($this->getConfig('parent.associationName'));

			if ($la_attributeKeys) {
				$lx_finder = $lo_association->getFinder();
				$lo_association->setFinder([
					'withMatchingAttributes' => [
						'fields' => $la_attributeKeys,
						'entity' => $ao_entity,
					],
				]);
			}

			$lo_existsIn = $ao_rules->existsIn($la_foreignKeys, $lo_association, [
				'errorField' => '__dummy',
			]);

			if ($ao_entity->isNew()) {
				$lb_exists = $lo_existsIn($ao_entity, $aa_options);
			}
			else {
				$la_nestedChildrenIds = $this->getNestedChildren($ao_entity)->extract($this->getConfig('parent.bindingKey'))->toArray();

				$lb_exists = $lo_existsIn($ao_entity, $aa_options) && !in_array($ao_entity->get($ls_foreignKey), $la_nestedChildrenIds);
			}

			if ($la_attributeKeys) {
				$lo_association->setFinder($lx_finder);
			}

			if (!$lb_exists) {
				/** @noinspection PhpPossiblePolymorphicInvocationInspection */
				return __dfx($this->table()->getI18nDomain(), 'validation', 'menu_entries', 'error_valid_' . Inflector::underscore($this->getConfig('parent.foreignKey')));
			}

			return true;
		}, 'valid' . Inflector::camelize($ls_foreignKey), [
			'errorField' => Inflector::variable($ls_foreignKey),
		]);


		return $ao_rules;
	}


	/**
	 * @param \Cake\Event\EventInterface $ao_event
	 * @param \Cake\Datasource\EntityInterface $ao_entity
	 * @param \ArrayObject $ao_options
	 * @return void
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function beforeSave(EventInterface $ao_event, EntityInterface $ao_entity, ArrayObject $ao_options): void {
		if (
			$ao_entity->isNew() ||
			!$this->getConfig('enabled') ||
			!$this->getConfig('relatedColumns')
		) {
			return;
		}

		$la_options = Hash::merge($this->getConfig(), Hash::get($ao_options, 'nest'));

		if ($la_options['skip'] === true) {
			return;
		}

		$la_relatedColumns = $this->getConfig('relatedColumns');
		$this->rememberedData[ $ao_entity->get('id') ] = array_merge(
			$ao_entity->extractOriginalChanged($la_relatedColumns),
			$ao_entity->get('attributes')?->extractOriginalChanged($this->extractAttributeFields($la_relatedColumns), true)
		);
	}


	/**
	 * When the option `relatedColumns` is set and one those columns/properties of the entity is dirty,
	 * the entity was moved to a new scope.
	 * This method handles this change and also moves all nested children to the new scope.
	 *
	 * For example:
	 * - Moving a page from one language to another one results in all children pages also being moved to the new language
	 * - Moving a content from one contentAreas to another one results in all children contents also being moved to the new contentArea
	 *
	 * @noinspection PhpUnusedParameterInspection
	 * @throws \Exception
	 */
	public function afterSave(
		EventInterface $ao_event,
		EntityInterface $ao_entity,
		ArrayObject $ao_options
	): void {
		if (
			$ao_entity->isNew() ||
			empty($this->rememberedData[ $ao_entity->get('id') ])
		) {
			return;
		}

		/** @var \Awyiss\Model\Entity  $ao_entity */
		$lo_entity = $ao_entity;

		$aa_originalData = $this->rememberedData[ $ao_entity->get('id') ];
		unset($this->rememberedData[ $ao_entity->get('id') ]);

		$lo_attributes = $lo_entity->get('attributes');
		/*
		 * If the entity has attributes and original data is provided, create a clone and set both,
		 * the entity and the attribute, to a clean, "old" state.
		 *
		 * This is necessary since related entities (the attribute in this case) will be saved and marked as clean
		 * before the main entity, so attributes, at this point, have no original values and cannot be used
		 * to retreive the records of the old scope.
		 */
		if (
			$aa_originalData &&
			$lo_attributes &&
			array_filter($this->getConfig('relatedColumns'), fn ($as_field) => str_starts_with($as_field, 'attributes.'))
		) {
			$lo_entity = clone $lo_entity;

			$lo_attributes = clone $lo_attributes;
			$lo_entity->set('attributes', $lo_attributes);

			$lo_entity->set($aa_originalData, [
				'asOriginal' => true,
				'guard' => false,
				'setter' => false,
			]);

			$lo_entity->clean();
			if ($lo_attributes) {
				$lo_attributes->clean();
			}
		}

		$lo_children = $this->getNestedChildren($lo_entity);

		if ($lo_children->isEmpty()) {
			return;
		}

		$la_relatedColumns = $this->getConfig('relatedColumns');

		//Extract all data from the entity
		$la_data = $ao_entity->extract(array_filter($la_relatedColumns, fn ($as_field) => !str_starts_with($as_field, 'attributes.')));
		$la_data = $ao_entity::unmapFields($la_data, true);

		/** @var \Awyiss\Model\Table $lo_table */
		$lo_table = $this->table();

		$la_ids = $lo_children->extract('id')->toList();
		$lo_table->updateAll($la_data, ['id IN' => $la_ids]);


		$la_attributeFields = $this->extractAttributeFields($la_relatedColumns);
		if (!$la_attributeFields) {
			return;
		}

		$la_data = $ao_entity->get('attributes')?->extract($la_attributeFields);
		if ($la_data) {
			$la_data = $ao_entity->get('attributes')::unmapFields($la_data, true);

			$lo_association = $lo_table->getAssociation($lo_table->getAttributesTable(true));

			$ls_foreignKey = $lo_association->getForeignKey();

			$li_affectedRows = $lo_association->updateAll($la_data, [$ls_foreignKey . ' IN' => $la_ids]);

			if ($li_affectedRows !== count($la_ids)) {
				/*
				 * Houston, we have a problem
				 * Not all children have an attribute row.
				 */
				$la_existingIds = $lo_association->find()
					->select($ls_foreignKey)
					->where([$ls_foreignKey . ' IN' => $la_ids])
					->disableHydration()
					->all()
					->extract($ls_foreignKey)
					->toList();

				$la_newIds = array_diff($la_ids, $la_existingIds);

				$la_entities = [];
				foreach ($la_newIds as $li_id) {
					/** @var \Awyiss\Model\Table $lo_association */
					$la_entities[] = $lo_association->newDefaultEntity(
						$la_data + [
							$ls_foreignKey => $li_id,
						]
					);
				}

				$lo_association->saveMany($la_entities, [
					'audit' => ['skip' => true],
					'checkRules' => false,
					'nest' => ['skip' => true],
					'systemOrder' => ['skip' => true],
				]);
			}
		}
	}


	/**
	 * Get all attribute fields from an array.
	 *
	 * @param array $aa_relatedColumns
	 * @param bool $ab_inlcudeBaseFields
	 * @return array
	 */
	protected function extractAttributeFields(array $aa_relatedColumns, bool $ab_inlcudeBaseFields = false): array {
		$la_columns = [];

		foreach ($aa_relatedColumns as $ls_column) {
			if (str_starts_with($ls_column, 'attributes.')) {
				$la_columns[] = substr($ls_column, 11);
			}

			if ($ab_inlcudeBaseFields && !str_contains($ls_column, '.')) {
				$la_columns[] = $ls_column;
			}
		}


		return $la_columns;
	}
}
