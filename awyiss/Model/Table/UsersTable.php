<?php declare(strict_types=1);


namespace Awyiss\Model\Table;


use Cake\ORM\RulesChecker;
use Cake\ORM\Query;
use Cake\Validation\Validator;


/**
 * Users Model
 *
 * @method \Awyiss\Model\Entity\User newEmptyEntity()
 * @method \Awyiss\Model\Entity\User newEntity(array $data, array $options = [])
 * @method \Awyiss\Model\Entity\User[] newEntities(array $data, array $options = [])
 * @method \Awyiss\Model\Entity\User get($primaryKey, $options = [])
 * @method \Awyiss\Model\Entity\User findOrCreate($search, ?callable $callback = null, $options = [])
 * @method \Awyiss\Model\Entity\User patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \Awyiss\Model\Entity\User[] patchEntities(iterable $entities, array $data, array $options = [])
 * @method \Awyiss\Model\Entity\User|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \Awyiss\Model\Entity\User saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \Awyiss\Model\Entity\User[]|\Cake\Datasource\ResultSetInterface|false saveMany(iterable $entities, $options = [])
 * @method \Awyiss\Model\Entity\User[]|\Cake\Datasource\ResultSetInterface saveManyOrFail(iterable $entities, $options = [])
 * @method \Awyiss\Model\Entity\User[]|\Cake\Datasource\ResultSetInterface|false deleteMany(iterable $entities, $options = [])
 * @method \Awyiss\Model\Entity\User[]|\Cake\Datasource\ResultSetInterface deleteManyOrFail(iterable $entities, $options = [])
 * @property \Awyiss\Model\Table\UsergroupsTable&\Cake\ORM\Association\BelongsToMany $Usergroups
 * @property \Awyiss\Model\Table\UsergroupsUsersTable&\Cake\ORM\Association\HasMany $UsergroupsUsers
 */
class UsersTable extends \Awyiss\Model\Table {
	/**
	 * Initialize method
	 *
	 * @param array $aa_config The configuration for the Table.
	 *
	 * @return void
	 */
	public function initialize (array $aa_config): void {
		parent::initialize($aa_config);

		$this->setTable('users');
		$this->setDisplayField('id');
		$this->setPrimaryKey('id');

		$this->belongsToMany('Usergroups');
	}


	/*public function findActive (Query $ao_query, array $aa_options): Query {
		$ao_query->where([
			'active' => 1,
			'deleted' => 0,
			'OR' => [
				'failed_attempts <' => 5,
				'last_login <=' => \Cake\I18n\Time::now()->subMinutes(10),
			]
		]);

		return $ao_query;
	}*/


	public function findActiveWithUsergroups (Query $ao_query, array $aa_options): Query {
		/*$ao_query->where([
			'active' => 1,
			'deleted' => 0,
			'OR' => [
				'failed_attempts <' => 5,
				'last_login <=' => \Cake\I18n\Time::now()->subMinutes(10),
			]
		])->contain(['Usergroups.UsergroupsPermissions']);*/

		return $ao_query;
	}


	/**
	 * Default validation rules.
	 *
	 * @param \Awyiss\Validation\Validator $ao_validator Validator instance.
	 *
	 * @return \Awyiss\Validation\Validator
	 */
	public function validationDefault (Validator $ao_validator): Validator {
		$ao_validator->integer('id')->allowEmptyString('id', NULL, 'create');

		$ao_validator->scalar('username')->maxLength('username', 50)->requirePresence('username', 'create')->notEmptyString('username');

		$ao_validator->scalar('password')->maxLength('password', 255)->requirePresence('password', 'create')->allowEmptyString('password')->minLength('password', 8);

		$ao_validator->sameAs('password', 'password_confirm');

		$ao_validator->scalar('firstname')->maxLength('firstname', 50)->allowEmptyString('firstname');

		$ao_validator->scalar('lastname')->maxLength('lastname', 50)->allowEmptyString('lastname');

		$ao_validator->email('email')->allowEmptyString('email');

		$ao_validator->boolean('active')->notEmptyString('active');

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
		$rules->add($rules->isUnique(['username']), ['errorField' => 'username']);
		$rules->add($rules->isUnique(['email']), ['errorField' => 'email']);

		return $rules;
	}
}
