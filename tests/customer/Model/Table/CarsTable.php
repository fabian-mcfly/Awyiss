<?php declare(strict_types=1);


namespace Customer\Model\Table;


use Awyiss\Annotation\MediaElementAssignable;
use Awyiss\Model\Table\GenericDatatablesTable;


/**
 * Cars Model
 *
 * @method \FoobarCustomer\Model\Entity\Car newDefaultEntity(array $additionalData = [], array $options = [])
 * @method \Cake\Collection\CollectionInterface|null getNestedChildren(\Cake\Datasource\EntityInterface $entity, array $options = [], int $currentLevel = 0)
 * @method \Cake\Collection\CollectionInterface|null getChildren(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \FoobarCustomer\Model\Entity\Car getParent(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \Cake\Collection\CollectionInterface|null getParents(\Cake\Datasource\EntityInterface $entity, array $options = [], int $currentLevel = 0)
 * @method \Cake\Collection\CollectionInterface getPossibleParents(\Awyiss\Model\Entity $entity, \Cake\Collection\CollectionInterface $threadedEntities)
 */
#[MediaElementAssignable(MediaElementAssignable::MODEL_LEVEL)]
class CarsTable extends GenericDatatablesTable {
	/**
	 * @inheritDoc
	 */
	public const TABLE = 'cars';
}
