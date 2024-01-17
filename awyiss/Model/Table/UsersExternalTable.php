<?php declare(strict_types=1);


namespace Awyiss\Model\Table;


use Awyiss\Model\Table;
use Cake\Validation\Validator;


/**
 * UsersExternal Model
 *
 * @method \Awyiss\Model\Entity\UsersExternal newDefaultEntity(array $aa_additionalData = [])
 */
class UsersExternalTable extends Table {
	/**
	 * @inheritDoc
	 */
	public const TABLE = 'users_external';


	/**
	 * Returns the default validator object.
	 *
	 * @param Validator $ao_validator The validator that can be modified to
	 * add some rules to it.
	 * @return Validator
	 */
	public function validationDefault(Validator $ao_validator): Validator {
		parent::validationDefault($ao_validator);


		$ao_validator->add('id', [
			'isInteger' => ['rule' => 'isInteger'],
			'maxLength' => ['rule' => ['maxLength', 11]],
		]);

		//$ao_validator->integer('id')->allowEmptyString('id', null, 'create');
		$ao_validator->scalar('provider')->maxLength('provider', 50)->requirePresence('provider', 'create')->notEmptyString('provider');
		$ao_validator->scalar('username')->maxLength('username', 50)->requirePresence('username', 'create')->notEmptyString('username');
		$ao_validator->dateTime('lastLogin')->notEmptyDateTime('lastLogin');


		return $ao_validator;
	}
}
