<?php declare(strict_types=1);


namespace Awyiss\Model\Table;


use Awyiss\Model\Table;
use Awyiss\ORM\RulesChecker;
use Cake\I18n\DateTime;
use Cake\ORM\Query\SelectQuery;
use Cake\ORM\RulesChecker as BaseRulesChecker;
use Cake\Validation\Validator;


/**
 * Users Model
 *
 * @property \Awyiss\Model\Table\UsergroupsTable&\Awyiss\ORM\Association\BelongsToMany $Usergroups
 * @method \Awyiss\Model\Entity\User newDefaultEntity(array $additionalData = [], array $options = [])
 * @noinspection PhpFullyQualifiedNameUsageInspection
 */
class UsersTable extends Table {
	/**
	 * @inheritDoc
	 */
	public const TABLE = 'users';


	/**
	 * @inheritDoc
	 */
	protected array $categories = [
		'allowUnassigned' => true,
		'associationName' => 'Usergroups',
		'enabled' => true,
		'identifier' => 'usergroup',
	];


	/**
	 * @inheritDoc
	 */
	public function initializeAssociations(): void {
		$this->belongsToMany('Usergroups');

		$this->hasMany('UserConfiguration', [
			'cascadeCallbacks' => true,
			'dependent' => true,
		]);
	}


	/**
	 * Finder that will only find users that are both active and with no more than 4 failed login attempts in the last ten minutes.
	 *
	 * @param \Cake\ORM\Query\SelectQuery $query
	 * @return \Cake\ORM\Query\SelectQuery
	 */
	public function findActive(SelectQuery $query): SelectQuery {
		$query->where([
			'active' => 1,
			'OR' => [
				'failed_attempts <' => 5,
				'last_login <=' => DateTime::now()->subMinutes(10),
			],
		]);


		return $query;
	}


	/**
	 * @inheritDoc
	 * @noinspection DuplicatedCode
	 */
	public function validationDefault(Validator $validator): Validator {
		parent::validationDefault($validator);


		$validator->requirePresence([
			'username',
			'password',
		], 'create');


		$validator->add('id', [
			'isInteger' => ['rule' => 'isInteger'],
			'maxLength' => ['rule' => ['maxLength', 11]],
		]);


		$validator->notEmptyString('username');
		$validator->add('username', [
			'isScalar' => ['rule' => 'isScalar'],
			'notBoolean' => ['rule' => 'notBoolean'],
			'maxLength' => ['rule' => ['maxLength', 50]],
		]);


		$validator->allowEmptyString('password', null, 'update');
		$validator->add('password', [
			'isScalar' => ['rule' => 'isScalar'],
			'notBoolean' => ['rule' => 'notBoolean'],
			'minLength' => ['rule' => ['minLength', 8]],
			'maxLength' => ['rule' => ['maxLength', 100]],
			'compareWith' => ['rule' => ['compareWith', 'password_confirm']],
		]);


		$validator->add('failedAttempts', [
			'isInteger' => ['rule' => 'isInteger'],
			'maxLength' => ['rule' => ['maxLength', 1]],
		]);


		$validator->allowEmptyDateTime('lastLogin');
		$validator->add('lastLogin', [
			'dateTime' => ['rule' => 'dateTime'],
		]);


		$validator->add('firstname', [
			'isScalar' => ['rule' => 'isScalar'],
			'notBoolean' => ['rule' => 'notBoolean'],
			'maxLength' => ['rule' => ['maxLength', 50]],
		]);


		$validator->add('lastname', [
			'isScalar' => ['rule' => 'isScalar'],
			'notBoolean' => ['rule' => 'notBoolean'],
			'maxLength' => ['rule' => ['maxLength', 50]],
		]);


		$validator->add('email', [
			'isScalar' => ['rule' => 'isScalar'],
			'notBoolean' => ['rule' => 'notBoolean'],
			'email' => ['rule' => 'email'],
			'maxLength' => ['rule' => ['maxLength', 50]],
		]);


		$validator->add('active', [
			'boolean' => ['rule' => 'boolean'],
		]);


		$validator->add('deleted', [
			'boolean' => ['rule' => 'boolean'],
		]);


		return $validator;
	}


	/**
	 * Returns a RulesChecker object after modifying the one that was supplied.
	 *
	 * @param \Awyiss\ORM\RulesChecker|\Cake\ORM\RulesChecker $rules The rules object to be modified.
	 * @return \Awyiss\ORM\RulesChecker
	 */
	public function buildRules(RulesChecker|BaseRulesChecker $rules): RulesChecker {
		$rules->add(
			$rules->isUnique(['username']),
			'usernameUnique',
			[
				'errorField' => 'username',
				'message' => __df($this->getI18nDomain(), 'validation', 'error_username_unique'),
			]
		);


		$rules->add(
			$rules->isUnique(['email'], ['allowMultipleNulls' => true]),
			'emailUnique',
			[
				'errorField' => 'email',
				'message' => __df($this->getI18nDomain(), 'validation', 'error_email_unique'),
			]
		);


		return $rules;
	}
}
