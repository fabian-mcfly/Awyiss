<?php declare(strict_types=1);


namespace Awyiss\Model\Table;


use Awyiss\Model\Entity\User;
use Awyiss\Model\Table;
use Cake\I18n\FrozenTime;
use Cake\ORM\Query;
use Awyiss\ORM\RulesChecker;
use Cake\Validation\Validator;


/**
 * Users Model
 *
 * @property \Awyiss\Model\Table\UsergroupsTable&\Cake\ORM\Association\BelongsToMany $Usergroups
 * @property \Awyiss\Model\Table\UsergroupsUsersTable&\Cake\ORM\Association\HasMany $UsergroupsUsers
 *
 * @method User newDefaultEntity(array $aa_additionalData = [])
 */
class UsersTable extends Table {
	public const TABLE = 'users';


	/**
	 * @inheritDoc
	 */
	public function initialize (array $aa_config): void {
		parent::initialize($aa_config);

		$this->setTable(static::TABLE);
		$this->setDisplayField('id');
		$this->setPrimaryKey('id');

		$this->belongsToMany('Usergroups');
	}


	/**
	 * Finder that will only find users that are both active and with no more than 4 failed login attemps in the last ten minutes.
	 *
	 * @noinspection PhpUnused
	 */
	public function findActive (Query $ao_query, array $aa_options): Query {
		$ao_query->where([
			'active' => 1,
			'OR' => [
				'failed_attempts <' => 5,
				'last_login <=' => FrozenTime::now()->subMinutes(10),
			],
		]);

		return $ao_query;
	}


	/**
	 * Returns the default validator object.
	 *
	 * @param \Cake\Validation\Validator $ao_validator The validator that can be modified to
	 * add some rules to it.
	 *
	 * @return \Cake\Validation\Validator
	 *
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function validationDefault (Validator $ao_validator): Validator {
		$ao_validator->integer('id')->allowEmptyString('id', NULL, 'create');

		$ao_validator->scalar('username')->maxLength('username', 50)->requirePresence('username', 'create')->notEmptyString('username');

		$ao_validator->scalar('password')
			->maxLength('password', 255)
			->requirePresence('password', 'create')
			->allowEmptyString('password', NULL, 'update')
			->minLength('password', 8);

		$ao_validator->sameAs('password', 'password_confirm');

		$ao_validator->scalar('firstname')->maxLength('firstname', 50)->allowEmptyString('firstname');

		$ao_validator->scalar('lastname')->maxLength('lastname', 50)->allowEmptyString('lastname');

		$ao_validator->email('email')->allowEmptyString('email');

		$ao_validator->boolean('active')->notEmptyString('active');

		$ao_validator->boolean('deleted')->notEmptyString('deleted');

		return $ao_validator;
	}


	/**
	 * Returns a RulesChecker object after modifying the one that was supplied.
	 *
	 * @param \Awyiss\ORM\RulesChecker|\Cake\ORM\RulesChecker $ao_rules The rules object to be modified.
	 *
	 * @return \Awyiss\ORM\RulesChecker
	 *
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function buildRules (RulesChecker|\Cake\ORM\RulesChecker $ao_rules): RulesChecker {
		$ao_rules->add($ao_rules->isUnique(['username']), ['errorField' => 'username']);
		$ao_rules->add($ao_rules->isUnique(['email']), ['errorField' => 'email']);

		return $ao_rules;
	}
}
