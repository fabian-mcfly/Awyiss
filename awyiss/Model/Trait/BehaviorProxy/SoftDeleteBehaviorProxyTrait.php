<?php declare(strict_types=1);


namespace Awyiss\Model\Trait\BehaviorProxy;


use ArrayObject;
use Cake\Datasource\EntityInterface;
use Cake\Event\EventInterface;


/**
 * Proxy methods for SoftDeleteBehavior
 */
trait SoftDeleteBehaviorProxyTrait {
	/**
	 * Soft delete an entity.
	 *
	 * @param \Cake\Datasource\EntityInterface $entity
	 * @param \ArrayObject $options
	 * @param \Cake\Event\EventInterface|null $event
	 * @return bool
	 * @see \Awyiss\Model\Behavior\SoftDeleteBehavior::softDelete()
	 */
	public function softDelete(EntityInterface $entity, ArrayObject $options, ?EventInterface $event = null): bool {
		return $this->getBehavior('SoftDelete')->softDelete($entity, $options, $event);
	}
}
