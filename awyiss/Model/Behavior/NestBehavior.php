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
 * It also moves all nested children to a new scope in the `afterSaveCommit`-event, using the `relatedColumns`-option
 */
class NestBehavior extends Behavior {
	/**
	 * Default configuration
	 *     *
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
			'finder' => null,
			'foreignKey' => 'parent_id',
			'maxLevel' => null,
		],
		'enabled' => true,
		'implementedEvents' => [
			'buildRules',
			'afterSaveCommit',
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
			'foreignKey' => 'parent_id',
			'finder' => null,
			'maxLevel' => null,
		],
		'relatedColumns' => [],
		'skip' => false,
	];


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
			$la_bindingKeys = $ls_entityClass::unmapFields($la_bindingKeys);

			$la_foreignKeys = array_merge((array)$this->getConfig('children.foreignKey'), $this->getConfig('relatedColumns'));
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
			$la_bindingKeys = $ls_entityClass::unmapFields($la_bindingKeys);

			$la_foreignKeys = array_merge((array)$this->getConfig('parent.foreignKey'), $this->getConfig('relatedColumns'));
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
	public function getChildren(EntityInterface $ao_entity): ?CollectionInterface {
		if (!$this->getConfig('enabled') || !$this->getConfig('children')) {
			return null;
		}

		$ls_associationName = $this->getConfig('children.associationName');
		if (!$ls_associationName || !$this->table()->hasAssociation($ls_associationName)) {
			throw new RuntimeException(sprintf('Expected option for `children.associationName` to be a valid assocation on table `%s`', $this->table()->getAlias()));
		}

		$lo_association = $this->table()->getAssociation($ls_associationName);

		$lx_finder = $this->getConfig('children.finder') ?: null;

		$lo_query = $lo_association->find($lx_finder);

		$la_bindingKeys = (array)$lo_association->getBindingKey();

		/** @var \Awyiss\Model\Entity $ls_entityClass */
		$ls_entityClass = $lo_association->getSource()->getEntityClass();
		foreach ((array)$lo_association->getForeignKey() as $li_key => $ls_field) {
			$ls_field = $ls_entityClass::unmapField($ls_field);

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

		foreach ($this->getChildren($ao_entity) as $lo_entity) {
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
	public function getParent(EntityInterface $ao_entity): ?EntityInterface {
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

		$lx_finder = $this->getConfig('parent.finder') ?: null;


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

		$lo_entity = $this->getParent($ao_entity);
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

			$lo_table = $this->table();
			/** @var \Awyiss\Model\Entity $ls_entityClass */
			$ls_entityClass = $lo_table->getEntityClass();

			$la_foreignKeys = array_merge((array)$ls_foreignKey, $this->getConfig('relatedColumns'));
			$la_foreignKeys = $ls_entityClass::unmapFields($la_foreignKeys);

			$lo_existsIn = $ao_rules->existsIn($la_foreignKeys, $this->getConfig('parent.associationName'), [
				'errorField' => '__dummy',
			]);

			if ($ao_entity->isNew()) {
				$lb_exists = $lo_existsIn($ao_entity, $aa_options);
			}
			else {
				$la_nestedChildrenIds = $this->getNestedChildren($ao_entity)->extract($this->getConfig('parent.bindingKey'))->toArray();

				$lb_exists = $lo_existsIn($ao_entity, $aa_options) && !in_array($ao_entity->get($ls_foreignKey), $la_nestedChildrenIds);
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
	 * When the option `relatedColumns` is set and one those columns/properties of the entity is dirty,
	 * the entity was moved to a new scope.
	 * This method handles this change and also moves all nested children to the new scope.
	 *
	 * For example:
	 * - Moving a page from one language to another one results in all children pages also being moved to the new language
	 * - Moving a content from one contentAreas to another one results in all children contents also being moved to the new contentArea
	 *
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function afterSaveCommit(
		EventInterface $ao_event,
		EntityInterface $ao_entity,
		ArrayObject $ao_options
	): void {
		if (!$this->getConfig('enabled') || !$this->getConfig('relatedColumns')) {
			return;
		}

		$la_options = Hash::merge($this->getConfig(), Hash::get($ao_options, 'nest'));

		if ($la_options['skip'] === true) {
			return;
		}

		$la_relatedColumns = $this->getConfig('relatedColumns');
		$la_dirtyRelatedColumns = array_intersect($ao_entity->getDirty(), $la_relatedColumns);

		if (!$la_dirtyRelatedColumns) {
			return;
		}

		$lo_children = $this->getNestedChildren($ao_entity);

		if ($lo_children->isEmpty()) {
			return;
		}

		$la_data = array_combine(
			$la_dirtyRelatedColumns,
			array_intersect_key(
				$ao_entity->extract(null, false, false),
				array_flip($la_dirtyRelatedColumns)
			)
		);

		$lo_table = $this->table();
		/** @var \Awyiss\Model\Entity $ls_entityClass */
		$ls_entityClass = $lo_table->getEntityClass();
		$la_data = $ls_entityClass::unmapFields($la_data, true);

		$la_ids = $lo_children->extract('id')->toArray();
		$this->table()->updateAll($la_data, ['id IN' => $la_ids]);
	}
}
