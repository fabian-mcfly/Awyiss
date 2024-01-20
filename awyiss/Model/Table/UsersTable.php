<?php declare(strict_types=1);


namespace Awyiss\Model\Table;


use Awyiss\Model\Table;
use Awyiss\ORM\RulesChecker;
use Cake\I18n\FrozenTime;
use Cake\ORM\Query\SelectQuery;
use Cake\ORM\RulesChecker as BaseRulesChecker;
use Cake\Validation\Validator;


/**
 * Users Model
 *
 * @property UsergroupsTable&\Awyiss\ORM\Association\BelongsToMany $Usergroups
 * @property UsergroupsUsersTable&\Awyiss\ORM\Association\HasMany $UsergroupsUsers
 * @method \Awyiss\Model\Entity\User newDefaultEntity(array $aa_additionalData = [])
 */
class UsersTable extends Table {
	/**
	 * @inheritDoc
	 */
	public const TABLE = 'users';


	/**
	 * @inheritDoc
	 */
	public function initializeAssociations(): void {
		$this->belongsToMany('Usergroups');
	}


	/**
	 * Finder that will only find users that are both active and with no more than 4 failed login attemps in the last ten minutes.
	 *
	 * @noinspection PhpUnused
	 */
	public function findActive(SelectQuery $ao_query): SelectQuery {
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
	 * @param Validator $ao_validator The validator that can be modified to
	 * add some rules to it.
	 * @return Validator
	 */
	public function validationDefault(Validator $ao_validator): Validator {
		parent::validationDefault($ao_validator);


		$ao_validator->requirePresence([
			'username',
			'password',
		], 'create');


		$ao_validator->add('id', [
			'isInteger' => ['rule' => 'isInteger'],
			'maxLength' => ['rule' => ['maxLength', 11]],
		]);


		$ao_validator->notEmptyString('username');
		$ao_validator->add('username', [
			'isScalar' => ['rule' => 'isScalar'],
			'maxLength' => ['rule' => ['maxLength', 50]],
		]);


		$ao_validator->allowEmptyString('password', null, 'update');
		$ao_validator->add('password', [
			'isScalar' => ['rule' => 'isScalar'],
			'minLength' => ['rule' => ['minLength', 8]],
			'maxLength' => ['rule' => ['maxLength', 100]],
			'compareWith' => ['rule' => ['compareWith', 'password_confirm']],
		]);


		$ao_validator->add('failedAttempts', [
			'isInteger' => ['rule' => 'isInteger'],
			'maxLength' => ['rule' => ['maxLength', 1]],
		]);


		$ao_validator->allowEmptyDateTime('lastLogin');
		$ao_validator->add('lastLogin', [
			'dateTime' => ['rule' => 'dateTime'],
		]);


		$ao_validator->add('firstname', [
			'isScalar' => ['rule' => 'isScalar'],
			'maxLength' => ['rule' => ['maxLength', 50]],
		]);


		$ao_validator->add('lastname', [
			'isScalar' => ['rule' => 'isScalar'],
			'maxLength' => ['rule' => ['maxLength', 50]],
		]);


		$ao_validator->add('email', [
			'isScalar' => ['rule' => 'isScalar'],
			'maxLength' => ['rule' => ['maxLength', 50]],
		]);


		$ao_validator->add('active', [
			'boolean' => ['rule' => 'boolean'],
		]);


		$ao_validator->add('deleted', [
			'boolean' => ['rule' => 'boolean'],
		]);


		return $ao_validator;
	}


	/**
	 * Returns a RulesChecker object after modifying the one that was supplied.
	 *
	 * @param RulesChecker|BaseRulesChecker $ao_rules The rules object to be modified.
	 * @return RulesChecker
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function buildRules(RulesChecker|BaseRulesChecker $ao_rules): RulesChecker {
		$ao_rules->add($ao_rules->isUnique(['username']), 'usernameUnique', [
			'errorField' => 'username',
			'message' => __dfx($this->getI18nDomain(), 'validation', 'username', 'error_username_unique'),
		]);


		$ao_rules->add($ao_rules->isUnique(['email'], ['allowMultipleNulls' => true]), 'emailUnique', [
			'errorField' => 'email',
			'message' => __df($this->getI18nDomain(), 'validation', 'error_email_unique'),
		]);


		return $ao_rules;
	}
}
