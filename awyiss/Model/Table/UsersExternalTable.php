<?php declare(strict_types=1);


namespace Awyiss\Model\Table;


use Awyiss\Model\Entity\UsersExternal;
use Awyiss\Model\Table;
use Cake\Validation\Validator;


/**
 * UsersExternal Model
 *
 * @method UsersExternal newDefaultEntity(array $aa_additionalData = [])
 */
class UsersExternalTable extends Table {
	public const TABLE = 'users_external';


	/**
	 * @inheritDoc
	 */
	public function initialize (array $aa_config): void {
		parent::initialize($aa_config);

		$this->setTable(static::TABLE);
		$this->setDisplayField('id');
		$this->setPrimaryKey('id');
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

		$ao_validator->scalar('provider')->maxLength('provider', 50)->requirePresence('provider', 'create')->notEmptyString('provider');

		$ao_validator->scalar('username')->maxLength('username', 50)->requirePresence('username', 'create')->notEmptyString('username');

		$ao_validator->dateTime('last_login')->notEmptyDateTime('last_login');

		return $ao_validator;
	}
}
