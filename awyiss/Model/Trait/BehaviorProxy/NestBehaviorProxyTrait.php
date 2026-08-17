<?php declare(strict_types=1);


namespace Awyiss\Model\Trait\BehaviorProxy;


use Awyiss\Model\Entity;
use Cake\Collection\CollectionInterface;
use Cake\Collection\Iterator\TreeIterator;
use Cake\Datasource\EntityInterface;
use Cake\ORM\Query\SelectQuery;


/**
 * Proxy methods for NestBehavior
 */
trait NestBehaviorProxyTrait {
	/**
	 * Returns a collection containing all direct children of the given entity.
	 *
	 * @param \Cake\Datasource\EntityInterface $entity
	 * @param array $options
	 * @return \Cake\Collection\CollectionInterface|null
	 * @see \Awyiss\Model\Behavior\NestBehavior::getChildren()
	 */
	public function getChildren(EntityInterface $entity, array $options = []): ?CollectionInterface {
		return $this->getBehavior('Nest')->getChildren($entity, $options);
	}


	/**
	 * Returns a collection containing all nested children of the given entity.
	 *
	 * @param \Cake\Datasource\EntityInterface $entity
	 * @param array $options
	 * @param int $currentLevel
	 * @return \Cake\Collection\CollectionInterface|null
	 * @see \Awyiss\Model\Behavior\NestBehavior::getNestedChildren()
	 */
	public function getNestedChildren(EntityInterface $entity, array $options = [], int $currentLevel = 0): ?CollectionInterface {
		return $this->getBehavior('Nest')->getNestedChildren($entity, $options, $currentLevel);
	}


	/**
	 * Returns the direct parent entity of the given entity.
	 *
	 * @param \Cake\Datasource\EntityInterface $entity
	 * @param array $options
	 * @return \Cake\Datasource\EntityInterface|null
	 * @see \Awyiss\Model\Behavior\NestBehavior::getParent()
	 */
	public function getParent(EntityInterface $entity, array $options = []): ?EntityInterface {
		return $this->getBehavior('Nest')->getParent($entity, $options);
	}


	/**
	 * Returns a collection containing all parent entities.
	 *
	 * @param \Cake\Datasource\EntityInterface $entity
	 * @param array $options
	 * @param int $currentLevel
	 * @return \Cake\Collection\CollectionInterface|null
	 * @see \Awyiss\Model\Behavior\NestBehavior::getParents()
	 */
	public function getParents(EntityInterface $entity, array $options = [], int $currentLevel = 0): ?CollectionInterface {
		return $this->getBehavior('Nest')->getParents($entity, $options, $currentLevel);
	}


	/**
	 * Get possible parents for the given entity.
	 *
	 * @param \Awyiss\Model\Entity $entity
	 * @param \Cake\Collection\CollectionInterface $threadedEntities
	 * @return \Cake\Collection\CollectionInterface
	 * @see \Awyiss\Model\Behavior\NestBehavior::getPossibleParents()
	 */
	public function getPossibleParents(Entity $entity, CollectionInterface $threadedEntities): CollectionInterface {
		return $this->getBehavior('Nest')->getPossibleParents($entity, $threadedEntities);
	}


	/**
	 * List nested entities.
	 *
	 * @param \Cake\ORM\Query\SelectQuery|\Cake\Collection\Iterator\TreeIterator $query
	 * @param string $nestingKey
	 * @param string $direction
	 * @return \Cake\Collection\CollectionInterface
	 * @see \Awyiss\Model\Behavior\NestBehavior::listNested()
	 */
	public function listNested(
		SelectQuery|TreeIterator $query,
		string $nestingKey = 'children',
		string $direction = 'desc'
	): CollectionInterface {
		return $this->getBehavior('Nest')->listNested($query, $nestingKey, $direction);
	}
}
