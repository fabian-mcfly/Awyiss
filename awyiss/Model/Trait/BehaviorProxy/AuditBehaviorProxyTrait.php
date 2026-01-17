<?php declare(strict_types=1);


namespace Awyiss\Model\Trait\BehaviorProxy;


use Cake\Datasource\EntityInterface;


/**
 * Proxy methods for AuditBehavior
 */
trait AuditBehaviorProxyTrait {
	/**
	 * Count the audit data for the provided entity.
	 *
	 * @param \Cake\Datasource\EntityInterface $entity
	 * @return int
	 * @see \Awyiss\Model\Behavior\AuditBehavior::countAuditData()
	 */
	public function countAuditData(EntityInterface $entity): int {
		return $this->getBehavior('Audit')->countAuditData($entity);
	}


	/**
	 * Returns the audit data for the provided entity.
	 *
	 * @param \Cake\Datasource\EntityInterface $entity
	 * @return array
	 * @see \Awyiss\Model\Behavior\AuditBehavior::getAuditData()
	 */
	public function getAuditData(EntityInterface $entity): array {
		return $this->getBehavior('Audit')->getAuditData($entity);
	}


	/**
	 * Returns the history fields for the table.
	 *
	 * @return array
	 * @see \Awyiss\Model\Behavior\AuditBehavior::getHistoryFields()
	 */
	public function getAuditHistoryFields(): array {
		return $this->getBehavior('Audit')->getHistoryFields();
	}
}
