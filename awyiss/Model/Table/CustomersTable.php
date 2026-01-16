<?php declare(strict_types=1);


namespace Awyiss\Model\Table;


use Awyiss\Model\Table;
use Awyiss\ORM\RulesChecker;
use Cake\I18n\DateTime;
use Cake\ORM\Query\SelectQuery;
use Cake\ORM\RulesChecker as BaseRulesChecker;
use Cake\Validation\Validator;


/**
 * Customers Model
 *
 * @property \Awyiss\Model\Table\CustomerGroupsTable&\Awyiss\ORM\Association\BelongsToMany $CustomerGroups
 * @method \Awyiss\Model\Entity\Customer newDefaultEntity(array $additionalData = [], array $options = [])
 * @noinspection PhpFullyQualifiedNameUsageInspection
 */
class CustomersTable extends Table {
	/**
	 * @inheritDoc
	 */
	public const string TABLE = 'customers';


	/**
	 * @inheritDoc
	 */
	protected array $categories = [
		'allowUnassigned' => true,
		'associationName' => 'CustomerGroups',
		'enabled' => true,
		'identifier' => 'customerGroup',
		'threaded' => false,
	];


	/**
	 * @inheritDoc
	 */
	public function initialize(array $config): void {
		parent::initialize($config);

		$auditBehavior = $this->getBehavior('Audit');
		$auditBehavior->setConfig('historyFields', ['customerGroups']);
	}


	/**
	 * @inheritDoc
	 */
	public function initializeAssociations(): void {
		$this->belongsToMany('CustomerGroups', [
			'cascadeCallbacks' => true,
			'dependent' => true,
		]);
	}


	/**
	 * Finder that will only find customers that are both active and with no more than 4 failed login attempts in the last ten minutes.
	 *
	 * @param \Cake\ORM\Query\SelectQuery $query
	 * @return \Cake\ORM\Query\SelectQuery
	 */
	public function findActive(SelectQuery $query): SelectQuery {
		$query->where([
			'active' => true,
			'verified' => true,
			'password IS NOT' => null,
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
			'email',
		], 'create');


		$validator->add('id', [
			'isInteger' => ['rule' => 'isInteger'],
			'maxLength' => ['rule' => ['maxLength', 11]],
		]);


		$validator->allowEmptyString('email');
		$validator->add('email', [
			'isScalar' => ['rule' => 'isScalar'],
			'notBoolean' => ['rule' => 'notBoolean'],
			'email' => ['rule' => 'email'],
			'maxLength' => ['rule' => ['maxLength', 254]],
		]);


		$validator->allowEmptyString('password');
		$validator->add('password', [
			'isScalar' => ['rule' => 'isScalar'],
			'notBoolean' => ['rule' => 'notBoolean'],
			'minLength' => ['rule' => ['minLength', 8]],
			'maxLength' => ['rule' => ['maxLength', 100]],
			'compareWith' => ['rule' => ['compareWith', 'password_confirm']],
		]);


		$validator->add('firstname', [
			'isScalar' => ['rule' => 'isScalar'],
			'notBoolean' => ['rule' => 'notBoolean'],
			'maxLength' => ['rule' => ['maxLength', 255]],
		]);


		$validator->add('lastname', [
			'isScalar' => ['rule' => 'isScalar'],
			'notBoolean' => ['rule' => 'notBoolean'],
			'maxLength' => ['rule' => ['maxLength', 255]],
		]);


		$validator->allowEmptyDateTime('lastLogin');
		$validator->add('lastLogin', [
			'dateTime' => ['rule' => 'dateTime'],
		]);


		$validator->add('failedAttempts', [
			'isInteger' => ['rule' => 'isInteger'],
			'maxLength' => ['rule' => ['maxLength', 1]],
		]);


		$validator->add('verified', [
			'boolean' => ['rule' => 'boolean'],
		]);


		$validator->allowEmptyString('verificationCode');
		$validator->add('verificationCode', [
			'isScalar' => ['rule' => 'isScalar'],
			'notBoolean' => ['rule' => 'notBoolean'],
			'maxLength' => ['rule' => ['maxLength', 64]],
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
	 * @inheritDoc
	 * @noinspection DuplicatedCode
	 */
	public function validationRegistration(Validator $validator): Validator {
		parent::validationDefault($validator);


		$validator->requirePresence([
			'email',
			'password',
			'password_confirm',
		]);


		$validator->add('id', [
			'isInteger' => ['rule' => 'isInteger'],
			'maxLength' => ['rule' => ['maxLength', 11]],
		]);


		$validator->notEmptyString('email');
		$validator->add('email', [
			'isScalar' => ['rule' => 'isScalar'],
			'notBoolean' => ['rule' => 'notBoolean'],
			'email' => ['rule' => 'email'],
			'maxLength' => ['rule' => ['maxLength', 254]],
		]);


		$validator->notEmptyString('password');
		$validator->add('password', [
			'isScalar' => ['rule' => 'isScalar'],
			'notBoolean' => ['rule' => 'notBoolean'],
			'minLength' => ['rule' => ['minLength', 8]],
			'maxLength' => ['rule' => ['maxLength', 100]],
			'compareWith' => ['rule' => ['compareWith', 'password_confirm']],
		]);


		$validator->allowEmptyString('firstname');
		$validator->add('firstname', [
			'isScalar' => ['rule' => 'isScalar'],
			'notBoolean' => ['rule' => 'notBoolean'],
			'maxLength' => ['rule' => ['maxLength', 255]],
		]);


		$validator->allowEmptyString('lastname');
		$validator->add('lastname', [
			'isScalar' => ['rule' => 'isScalar'],
			'notBoolean' => ['rule' => 'notBoolean'],
			'maxLength' => ['rule' => ['maxLength', 255]],
		]);


		$validator->add('verified', [
			'boolean' => ['rule' => 'boolean'],
		]);


		$validator->allowEmptyString('verificationCode');
		$validator->add('verificationCode', [
			'isScalar' => ['rule' => 'isScalar'],
			'notBoolean' => ['rule' => 'notBoolean'],
			'maxLength' => ['rule' => ['maxLength', 64]],
		]);


		$validator->allowEmptyDateTime('verifiedOn');
		$validator->add('verifiedOn', [
			'dateTime' => ['rule' => 'dateTime'],
		]);


		$validator->allowEmptyString('passwordResetCode');
		$validator->add('passwordResetCode', [
			'isScalar' => ['rule' => 'isScalar'],
			'notBoolean' => ['rule' => 'notBoolean'],
			'maxLength' => ['rule' => ['maxLength', 64]],
		]);


		$validator->allowEmptyDateTime('passwordResetOn');
		$validator->add('passwordResetOn', [
			'dateTime' => ['rule' => 'dateTime'],
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
	 * @param \Awyiss\ORM\RulesChecker|BaseRulesChecker $rules The rules object to be modified.
	 * @return \Awyiss\ORM\RulesChecker
	 */
	public function buildRules(RulesChecker|BaseRulesChecker $rules): RulesChecker {
		$rules->add(
			$rules->isUnique(['email']),
			'emailUnique',
			[
				'errorField' => 'email',
				'message' => __df($this->getI18nDomain(), 'validation', 'error_email_unique'),
			]
		);


		return $rules;
	}
}
