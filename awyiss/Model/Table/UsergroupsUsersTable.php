<?php declare(strict_types=1);


namespace Awyiss\Model\Table;


use Cake\ORM\RulesChecker;
use Cake\Validation\Validator;


/**
 * UsergroupsUsers Model
 *
 * @property \Awyiss\Model\Table\UsergroupsTable&\Cake\ORM\Association\BelongsTo $Usergroups
 * @property \Awyiss\Model\Table\UsersTable&\Cake\ORM\Association\BelongsTo $Users
 *
 * @method \Awyiss\Model\Entity\UsergroupsUser newEmptyEntity()
 * @method \Awyiss\Model\Entity\UsergroupsUser newEntity(array $data, array $options = [])
 * @method \Awyiss\Model\Entity\UsergroupsUser[] newEntities(array $data, array $options = [])
 * @method \Awyiss\Model\Entity\UsergroupsUser get($primaryKey, $options = [])
 * @method \Awyiss\Model\Entity\UsergroupsUser findOrCreate($search, ?callable $callback = null, $options = [])
 * @method \Awyiss\Model\Entity\UsergroupsUser patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \Awyiss\Model\Entity\UsergroupsUser[] patchEntities(iterable $entities, array $data, array $options = [])
 * @method \Awyiss\Model\Entity\UsergroupsUser|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \Awyiss\Model\Entity\UsergroupsUser saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \Awyiss\Model\Entity\UsergroupsUser[]|\Cake\Datasource\ResultSetInterface|false saveMany(iterable $entities, $options = [])
 * @method \Awyiss\Model\Entity\UsergroupsUser[]|\Cake\Datasource\ResultSetInterface saveManyOrFail(iterable $entities, $options = [])
 * @method \Awyiss\Model\Entity\UsergroupsUser[]|\Cake\Datasource\ResultSetInterface|false deleteMany(iterable $entities, $options = [])
 * @method \Awyiss\Model\Entity\UsergroupsUser[]|\Cake\Datasource\ResultSetInterface deleteManyOrFail(iterable $entities, $options = [])
 */
class UsergroupsUsersTable extends \Awyiss\Model\Table {
	/**
	 * Initialize method
	 *
	 * @param array $aa_config The configuration for the Table.
	 *
	 * @return void
	 */
	public function initialize (array $aa_config): void {
		parent::initialize($aa_config);

		$this->setTable('usergroups_users');
		$this->setDisplayField('id');
		$this->setPrimaryKey('id');

		$this->belongsTo('Usergroups', [
			'foreignKey' => 'usergroup_id',
			'joinType' => 'INNER',
		]);
		$this->belongsTo('Users', [
			'foreignKey' => 'user_id',
			'joinType' => 'INNER',
		]);
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

		return $ao_validator;
	}


	/**
	 * Returns a rules checker object that will be used for validating
	 * application integrity.
	 *
	 * @param \Cake\ORM\RulesChecker $rules The rules object to be modified.
	 *
	 * @return \Cake\ORM\RulesChecker
	 */
	public function buildRules (RulesChecker $rules): RulesChecker {
		$rules->add($rules->existsIn(['usergroup_id'], 'Usergroups'), ['errorField' => 'usergroup_id']);
		$rules->add($rules->existsIn(['user_id'], 'Users'), ['errorField' => 'user_id']);

		return $rules;
	}
}
