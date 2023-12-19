<?php declare(strict_types=1);


namespace Awyiss\Model\Table;


/**
 * Attributes Model
 *
 * @method \Awyiss\Model\Entity\Attribute newDefaultEntity()
 * @method \Awyiss\Model\Entity\Attribute newEmptyEntity()
 * @method \Awyiss\Model\Entity\Attribute newEntity(array $data, array $options = [])
 * @method \Awyiss\Model\Entity\Attribute[] newEntities(array $data, array $options = [])
 * @method \Awyiss\Model\Entity\Attribute get($primaryKey, $options = [])
 * @method \Awyiss\Model\Entity\Attribute findOrCreate($search, ?callable $callback = NULL, $options = [])
 * @method \Awyiss\Model\Entity\Attribute patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \Awyiss\Model\Entity\Attribute[] patchEntities(iterable $entities, array $data, array $options = [])
 * @method \Awyiss\Model\Entity\Attribute|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \Awyiss\Model\Entity\Attribute saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \Awyiss\Model\Entity\Attribute[]|\Cake\Datasource\ResultSetInterface|false saveMany(iterable $entities, $options = [])
 * @method \Awyiss\Model\Entity\Attribute[]|\Cake\Datasource\ResultSetInterface saveManyOrFail(iterable $entities, $options = [])
 * @method \Awyiss\Model\Entity\Attribute[]|\Cake\Datasource\ResultSetInterface|false deleteMany(iterable $entities, $options = [])
 * @method \Awyiss\Model\Entity\Attribute[]|\Cake\Datasource\ResultSetInterface deleteManyOrFail(iterable $entities, $options = [])
 */
class Attributes extends \Awyiss\Model\Table {
	/**
	 * Initialize method
	 *
	 * @param array $aa_config The configuration for the Table.
	 *
	 * @return void
	 */
	public function initialize (array $aa_config): void {
		parent::initialize($aa_config);

		$this->setDisplayField('parent_id');
		$this->setPrimaryKey('parent_id');
	}
}
