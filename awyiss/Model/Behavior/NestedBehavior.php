<?php declare(strict_types=1);


namespace Awyiss\Model\Behavior;


use ArrayObject;
use Awyiss\ORM\Behavior;
use Cake\Collection\Collection;
use Cake\Collection\CollectionInterface;
use Cake\Datasource\EntityInterface;
use Cake\Event\EventInterface;
use Cake\Utility\Hash;
use RuntimeException;


/**
 * The NestedBehavior exposes four methods on the table object:
 * `getChildren()`, `getDirectChildren()`, `getParent()` and `getParents()`
 *
 * For this, it uses the config options `children`/`parent` with the following options
 * 	- associationName: the alias of a valid hasMany/belongsTo-association, set on the table
 * 	- finder: the finder that's being used to get all children/parent(s)
 * 	- maxLevel: maximum depth of items to return
 *
 * It also moves all nested children to a new scope in the `afterSaveCommit`-event, using the `relatedColumns`-option
 */
class NestedBehavior extends Behavior {
	/**
	 * Default configuration
	 * 	 *
	 * These are merged with user-provided configuration when the behavior is used.
	 *
	 * @var array
	 */
	protected $_defaultConfig = [
		'children' => NULL,
		'enabled' => TRUE,
		'implementedEvents' => [
			'Model.afterSaveCommit' => 'afterSaveCommit',
		],
		'implementedMethods' => [
			'getChildren' => 'getChildren',
			'getDirectChildren' => 'getDirectChildren',
			'getParent' => 'getParent',
			'getParents' => 'getParents',
		],
		'parent' => NULL,
		'relatedColumns' => [],
		'skip' => FALSE,
	];


	/**
	 * Returns a collection containing all nested children of the given entity.
	 *
	 * The depth can be limited using the `maxLevel` option in either the `children`-config array or
	 * in the second parameter of the method call.
	 *
	 * Calling `$Comments->getChildren($comment, ['maxLevel' => 2]);` returns all direct children of $comment as well as
	 * all direct children of those.
	 *
	 * @noinspection PhpUnused
	 */
	public function getDirectChildren (EntityInterface $ao_entity): ?CollectionInterface {
		if ( ! $this->getConfig('enabled') || ! $this->getConfig('children')) {
			return NULL;
		}

		$ls_associationName = $this->getConfig('children.associationName');
		if (!$ls_associationName || !$this->table()->hasAssociation($ls_associationName)) {
			throw new RuntimeException(sprintf('Expected option for `children.associationName` to be a valid assocation on table `%s`', $this->table()->getAlias()));
		}

		$lo_association = $this->table()->getAssociation($ls_associationName);

		$lx_finder = $this->getConfig('children.finder') ?? NULL;

		return $lo_association->find($lx_finder)->where([
			$lo_association->getAlias() . '.' . $lo_association->getForeignKey() => $ao_entity->get($lo_association->getBindingKey()),
			//$lo_association->getAlias() . '.' . $lo_association->getBindingKey() =>
		])->all();
	}


	/**
	 * Returns a collection containing all direct children of the given entity.
	 *
	 * @noinspection PhpUnused
	 */
	public function getChildren (EntityInterface $ao_entity, array $aa_options = [], int $ai_currentLevel = 0): ?CollectionInterface {
		if ( ! $this->getConfig('enabled') || ! $this->getConfig('children')) {
			return NULL;
		}

		$lo_collection = new Collection([]);

		foreach ($this->getDirectChildren($ao_entity) AS $lo_entity) {
			$lo_collection = $lo_collection->appendItem($lo_entity);

			$li_maxLevel = $aa_options['maxLevel'] ?? $this->getConfig('children.maxLevel');
			if (isset($li_maxLevel) && $li_maxLevel <= ($ai_currentLevel + 1)) {
				continue;
			}

			$lo_children = $this->getChildren($lo_entity, $aa_options, $ai_currentLevel + 1);
			if (!$lo_children->count()) {
				continue;
			}

			$lo_collection = $lo_collection->append($lo_children);
		}

		return $lo_collection->compile(FALSE);
	}


	/**
	 * Returns the direct parent entity of the given entity or `NULL` if none exists
	 *
	 * @noinspection PhpUnused
	 */
	public function getParent (EntityInterface $ao_entity): ?EntityInterface {
		if ( ! $this->getConfig('enabled') || ! $this->getConfig('parent')) {
			return NULL;
		}

		$ls_associationName = $this->getConfig('parent.associationName');
		if (!$ls_associationName || !$this->table()->hasAssociation($ls_associationName)) {
			throw new RuntimeException(sprintf('Expected option for `parent.associationName` to be a valid assocation on table `%s`', $this->table()->getAlias()));
		}

		$lo_association = $this->table()->getAssociation($ls_associationName);

		if (!$ao_entity->get($lo_association->getForeignKey())) {
			return NULL;
		}

		$lx_finder = $this->getConfig('parent.finder') ?? NULL;

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
	public function getParents (EntityInterface $ao_entity, array $aa_options = [], int $ai_currentLevel = 0): ?CollectionInterface {
		if ( ! $this->getConfig('enabled') || ! $this->getConfig('parent')) {
			return NULL;
		}

		$lo_collection =  new Collection([]);

		$lo_entity = $this->getParent($ao_entity);
		if (!$lo_entity) {
			return $lo_collection->compile(FALSE);
		}

		$lo_collection = $lo_collection->appendItem($lo_entity);

		$li_maxLevel = $aa_options['maxLevel'] ?? $this->getConfig('parent.maxLevel');
		if (isset($li_maxLevel) && $li_maxLevel <= ($ai_currentLevel + 1)) {
			return $lo_collection->compile(FALSE);
		}

		if ($lo_parent = $this->getParents($lo_entity, $aa_options, $ai_currentLevel + 1)) {
			$lo_collection = $lo_collection->append($lo_parent);
		}

		return $lo_collection->compile(FALSE);
	}


	/**
	 * When the option `relatedColumns` is set and one those columns/properties of the entity is dirty,
	 * the entity was moved to a new scope.
	 * This method handles this change and also moves all nested children to the new scope.
	 *
	 * For example:
	 * - Moving a page from one language to another one results in all children pages also being moved to the new language
	 * - Moving a content from one templatePosition to another one results in all children contents also being moved to the new templatePosition
	 *
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function afterSaveCommit (EventInterface $ao_event, EntityInterface $ao_entity, ArrayObject $ao_options): void {
		if ( ! $this->getConfig('enabled') || ! $this->getConfig('relatedColumns')) {
			return;
		}

		$la_options = Hash::merge($this->getConfig(), Hash::get($ao_options, 'nested'));

		if ($la_options['skip'] === TRUE) {
			return;
		}

		$la_relatedColumns = $this->getConfig('relatedColumns');
		$la_dirtyRelatedColumns = array_intersect($ao_entity->getDirty(), $la_relatedColumns);

		if (!$la_dirtyRelatedColumns) {
			return;
		}

		$lo_children = $this->getChildren($ao_entity);
		if ($lo_children->isEmpty()) {
			return;
		}

		$la_data = array_combine($la_dirtyRelatedColumns, array_intersect_key($ao_entity->toArray(), array_flip($la_dirtyRelatedColumns)));

		$la_ids = $lo_children->extract('id')->toArray();
		$this->table()->updateAll($la_data, ['id IN' => $la_ids]);
	}
}