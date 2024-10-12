<?php declare(strict_types=1);


namespace Customer\Model\Table;


use Awyiss\Model\Table;
use Awyiss\ORM\RulesChecker;
use Cake\ORM\RulesChecker as BaseRulesChecker;
use Cake\Validation\Validator;


/**
 * DummyUsers Model
 *
 * @method \Customer\Model\Entity\DummyUser newDefaultEntity(array $additionalData = [], array $options = [])
 */
class DummyUsersTable extends Table {
	/**
	 * @inheritDoc
	 */
	public const ATTRIBUTABLE = true;
	/**
	 * @inheritDoc
	 */
	public const TABLE = 'dummy_users';


	/**
	 * @inheritDoc
	 */
	public function initialize(array $config): void {
		parent::initialize($config);
	}


	/**
	 * Returns the default validator object.
	 *
	 * @param \Cake\Validation\Validator $validator The validator that can be modified to
	 * add some rules to it.
	 * @return \Cake\Validation\Validator
	 */
	public function validationDefault(Validator $validator): Validator {
		parent::validationDefault($validator);

		$validator
			->scalar('username')
			->maxLength('username', 50)
			->requirePresence('username', 'create')
			->notEmptyString('username');

		$validator
			->scalar('password')
			->maxLength('password', 255)
			->allowEmptyString('password');

		$validator
			->scalar('firstname')
			->maxLength('firstname', 50)
			->allowEmptyString('firstname');

		$validator
			->scalar('lastname')
			->maxLength('lastname', 50)
			->allowEmptyString('lastname');

		$validator
			->email('email')
			->allowEmptyString('email');

		$validator
			->dateTime('lastLogin')
			->allowEmptyDateTime('lastLogin');

		$validator
			->integer('failedAttempts')
			->notEmptyString('failedAttempts');

		$validator
			->boolean('active')
			->notEmptyString('active');

		$validator
			->boolean('deleted')
			->notEmptyString('deleted');

		$validator
			->integer('createdBy')
			->allowEmptyString('createdBy');

		$validator
			->dateTime('createdOn')
			->allowEmptyDateTime('createdOn');

		$validator
			->integer('changedBy')
			->allowEmptyString('changedBy');

		$validator
			->dateTime('changedOn')
			->allowEmptyDateTime('changedOn');

		$validator
			->integer('deletedBy')
			->allowEmptyString('deletedBy');

		$validator
			->dateTime('deletedOn')
			->allowEmptyDateTime('deletedOn');

		return $validator;
	}


	/**
	 * Returns a RulesChecker object after modifying the one that was supplied.
	 *
	 * @param \Cake\ORM\RulesChecker $rules The rules object to be modified.
	 * @param \Awyiss\ORM\RulesChecker|\Cake\ORM\RulesChecker $rules The rules object to be modified.
	 */
	public function buildRules(RulesChecker|BaseRulesChecker $rules): RulesChecker {
		$rules->add($rules->isUnique(['username']), 'validUsername', ['errorField' => 'username']);

		return $rules;
	}
}
