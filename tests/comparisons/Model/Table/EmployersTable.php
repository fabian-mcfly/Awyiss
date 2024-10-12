<?php declare(strict_types=1);


namespace Customer\Model\Table;


use Awyiss\Annotation\MediaElementAssignable;
use Awyiss\Model\Table\GenericDatatablesTable;


/**
 * Employers Model
 *
 * @method \Customer\Model\Entity\Employer newDefaultEntity(array $additionalData = [], array $options = [])
 * @method \Cake\Collection\CollectionInterface|null getNestedChildren(\Cake\Datasource\EntityInterface $entity, array $options = [], int $currentLevel = 0)
 * @method \Cake\Collection\CollectionInterface|null getChildren(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \Customer\Model\Entity\Employer getParent(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \Cake\Collection\CollectionInterface|null getParents(\Cake\Datasource\EntityInterface $entity, array $options = [], int $currentLevel = 0)
 * @method \Cake\Collection\CollectionInterface getPossibleParents(\Awyiss\Model\Entity $entity, \Cake\Collection\CollectionInterface $threadedEntities)
 */
#[MediaElementAssignable(MediaElementAssignable::MODEL_LEVEL)]
class EmployersTable extends GenericDatatablesTable {
	/**
	 * @inheritDoc
	 */
	public const TABLE = 'employers';
}
