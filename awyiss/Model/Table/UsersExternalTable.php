<?php

declare(strict_types=1);


namespace Awyiss\Model\Table;


use Cake\ORM\RulesChecker;
use Cake\Validation\Validator;


/**
 * UsersExternal Model
 *
 * @method \Awyiss\Model\Entity\UsersExternal newEmptyEntity()
 * @method \Awyiss\Model\Entity\UsersExternal newEntity(array $data, array $options = [])
 * @method \Awyiss\Model\Entity\UsersExternal[] newEntities(array $data, array $options = [])
 * @method \Awyiss\Model\Entity\UsersExternal get($primaryKey, $options = [])
 * @method \Awyiss\Model\Entity\UsersExternal findOrCreate($search, ?callable $callback = null, $options = [])
 * @method \Awyiss\Model\Entity\UsersExternal patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \Awyiss\Model\Entity\UsersExternal[] patchEntities(iterable $entities, array $data, array $options = [])
 * @method \Awyiss\Model\Entity\UsersExternal|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \Awyiss\Model\Entity\UsersExternal saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \Awyiss\Model\Entity\UsersExternal[]|\Cake\Datasource\ResultSetInterface|false saveMany(iterable $entities, $options = [])
 * @method \Awyiss\Model\Entity\UsersExternal[]|\Cake\Datasource\ResultSetInterface saveManyOrFail(iterable $entities, $options = [])
 * @method \Awyiss\Model\Entity\UsersExternal[]|\Cake\Datasource\ResultSetInterface|false deleteMany(iterable $entities, $options = [])
 * @method \Awyiss\Model\Entity\UsersExternal[]|\Cake\Datasource\ResultSetInterface deleteManyOrFail(iterable $entities, $options = [])
 */
class UsersExternalTable extends \Awyiss\Model\Table {
	/**
	 * Initialize method
	 *
	 * @param array $aa_config The configuration for the Table.
	 *
	 * @return void
	 */
	public function initialize (array $aa_config): void {
		parent::initialize($aa_config);

		$this->setTable('users_external');
		$this->setDisplayField('id');
		$this->setPrimaryKey('id');
	}


	/**
	 * Default validation rules.
	 *
	 * @param \Cake\Validation\Validator $ao_validator Validator instance.
	 *
	 * @return \Cake\Validation\Validator
	 */
	public function validationDefault (Validator $ao_validator): Validator {
		$ao_validator->integer('id')->allowEmptyString('id', NULL, 'create');

		$ao_validator->scalar('provider')->maxLength('provider', 50)->requirePresence('provider', 'create')->notEmptyString('provider');

		$ao_validator->scalar('username')->maxLength('username', 50)->requirePresence('username', 'create')->notEmptyString('username');

		$ao_validator->dateTime('last_login')->notEmptyDateTime('last_login');

		return $ao_validator;
	}
}
