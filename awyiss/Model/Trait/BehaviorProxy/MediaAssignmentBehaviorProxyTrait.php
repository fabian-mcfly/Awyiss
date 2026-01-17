<?php declare(strict_types=1);


namespace Awyiss\Model\Trait\BehaviorProxy;


use Cake\Datasource\EntityInterface;


/**
 * Proxy methods for MediaAssignmentBehavior
 */
trait MediaAssignmentBehaviorProxyTrait {
	/**
	 * Rebuild media assignments for an entity.
	 *
	 * @param \Cake\Datasource\EntityInterface|array $entity
	 * @param bool $useMediaEntity
	 * @return \Cake\Datasource\EntityInterface|array
	 * @see \Awyiss\Model\Behavior\MediaAssignmentBehavior::rebuildMediaAssignments()
	 */
	public function rebuildMediaAssignments(EntityInterface|array $entity, bool $useMediaEntity = false): EntityInterface|array {
		return $this->getBehavior('MediaAssignment')->rebuildMediaAssignments($entity, $useMediaEntity);
	}
}
