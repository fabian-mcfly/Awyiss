<?php declare(strict_types=1);


namespace Awyiss\Model\Table;


use Awyiss\Model\Table;
use Cake\Validation\Validator;


/**
 * UsersExternal Model
 *
 * @method \Awyiss\Model\Entity\UsersExternal newDefaultEntity(array $additionalData = [], array $options = [])
 * @noinspection PhpFullyQualifiedNameUsageInspection
 */
class UsersExternalTable extends Table {
	/**
	 * @inheritDoc
	 */
	public const ATTRIBUTABLE = false;
	/**
	 * @inheritDoc
	 */
	public const TABLE = 'users_external';


	/**
	 * Returns the default validator object.
	 *
	 * @param Validator $validator The validator that can be modified to
	 * add some rules to it.
	 * @return Validator
	 */
	public function validationDefault(Validator $validator): Validator {
		parent::validationDefault($validator);


		$validator->add('id', [
			'isInteger' => ['rule' => 'isInteger'],
			'maxLength' => ['rule' => ['maxLength', 11]],
		]);

		//$validator->integer('id')->allowEmptyString('id', null, 'create');
		$validator->scalar('provider')->maxLength('provider', 50)->requirePresence('provider', 'create')->notEmptyString('provider');
		$validator->scalar('username')->maxLength('username', 50)->requirePresence('username', 'create')->notEmptyString('username');
		$validator->dateTime('lastLogin')->notEmptyDateTime('lastLogin');


		return $validator;
	}
}
