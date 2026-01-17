<?php declare(strict_types=1);


namespace Awyiss\Model\Trait\BehaviorProxy;


use Cake\Datasource\EntityInterface;
use Cake\ORM\Query\SelectQuery;


/**
 * Proxy methods for SystemOrderBehavior
 */
trait SystemOrderBehaviorProxyTrait {
	/**
	 * Add system order query conditions.
	 *
	 * @param \Cake\ORM\Query\SelectQuery|null $query
	 * @param \Cake\Datasource\EntityInterface $entity
	 * @param bool $preferOriginal
	 * @return \Cake\ORM\Query\SelectQuery|false
	 * @see \Awyiss\Model\Behavior\SystemOrderBehavior::addQueryConditions()
	 */
	public function addSystemOrderQueryConditions(?SelectQuery $query, EntityInterface $entity, bool $preferOriginal = false): SelectQuery|false {
		return $this->getBehavior('SystemOrder')->addQueryConditions($query, $entity, $preferOriginal);
	}


	/**
	 * Retrieve the current highest system order for the scope of the provided entity.
	 *
	 * @param \Cake\Datasource\EntityInterface $entity
	 * @return int
	 * @see \Awyiss\Model\Behavior\SystemOrderBehavior::getHighestSystemOrder()
	 */
	public function getHighestSystemOrder(EntityInterface $entity): int {
		return $this->getBehavior('SystemOrder')->getHighestSystemOrder($entity);
	}


	/**
	 * Return the columns related to the system order.
	 *
	 * @return array
	 * @see \Awyiss\Model\Behavior\SystemOrderBehavior::getRelatedColumns()
	 */
	public function getSystemOrderRelatedColumns(): array {
		return $this->getBehavior('SystemOrder')->getRelatedColumns();
	}


	/**
	 * Returns whether the entity has dirty related columns.
	 *
	 * @param \Cake\Datasource\EntityInterface $entity
	 * @return bool
	 * @see \Awyiss\Model\Behavior\SystemOrderBehavior::hasDirtyRelatedColumns()
	 */
	public function hasDirtySystemOrderRelatedColumns(EntityInterface $entity): bool {
		return $this->getBehavior('SystemOrder')->hasDirtyRelatedColumns($entity);
	}
}
