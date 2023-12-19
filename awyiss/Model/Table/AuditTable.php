<?php

declare(strict_types=1);


namespace Awyiss\Model\Table;


use Cake\Database\Schema\TableSchemaInterface;
use Cake\Validation\Validator;


/**
 * Audit Model
 *
 * @method \Awyiss\Model\Entity\Audit newEmptyEntity()
 * @method \Awyiss\Model\Entity\Audit newEntity(array $data, array $options = [])
 * @method \Awyiss\Model\Entity\Audit[] newEntities(array $data, array $options = [])
 * @method \Awyiss\Model\Entity\Audit get($primaryKey, $options = [])
 * @method \Awyiss\Model\Entity\Audit findOrCreate($search, ?callable $callback = null, $options = [])
 * @method \Awyiss\Model\Entity\Audit patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \Awyiss\Model\Entity\Audit[] patchEntities(iterable $entities, array $data, array $options = [])
 * @method \Awyiss\Model\Entity\Audit|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \Awyiss\Model\Entity\Audit saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \Awyiss\Model\Entity\Audit[]|\Cake\Datasource\ResultSetInterface|false saveMany(iterable $entities, $options = [])
 * @method \Awyiss\Model\Entity\Audit[]|\Cake\Datasource\ResultSetInterface saveManyOrFail(iterable $entities, $options = [])
 * @method \Awyiss\Model\Entity\Audit[]|\Cake\Datasource\ResultSetInterface|false deleteMany(iterable $entities, $options = [])
 * @method \Awyiss\Model\Entity\Audit[]|\Cake\Datasource\ResultSetInterface deleteManyOrFail(iterable $entities, $options = [])
 */
class AuditTable extends \Awyiss\Model\Table {
	/**
	 * {@inheritDoc}
	 */
	public function initialize (array $aa_config): void {
		parent::initialize($aa_config);

		$this->setTable('audit');
		$this->setDisplayField('id');
		$this->setPrimaryKey('id');
	}


	/**
	 * {@inheritDoc}
	 */
	public function validationDefault (Validator $ao_validator): Validator {
		$ao_validator->integer('id')->allowEmptyString('id', NULL, 'create');

		$ao_validator->scalar('type')->requirePresence('type', 'create')->notEmptyString('type');

		$ao_validator->scalar('model')->maxLength('model', 50)->requirePresence('model', 'create')->notEmptyString('model');

		$ao_validator->allowEmptyString('data_old');

		$ao_validator->allowEmptyString('data_new');

		$ao_validator->integer('created_by')->allowEmptyString('created_by');

		$ao_validator->dateTime('created_on')->requirePresence('created_on', 'create')->notEmptyDateTime('created_on');

		return $ao_validator;
	}


	/**
	 * {@inheritDoc}
	 */
	protected function _initializeSchema (TableSchemaInterface $schema): TableSchemaInterface {
		$schema->setColumnType('data_old', 'json');
		$schema->setColumnType('data_new', 'json');

		return $schema;
	}
}
