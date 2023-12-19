<?php declare(strict_types=1);


namespace Awyiss\Model\Table;


use Cake\Datasource\EntityInterface;
use Cake\ORM\Query;
use Cake\Validation\Validator;


/**
 * UsersExternal Model
 *
 * @method \Awyiss\Model\Entity\UsersExternal newDefaultEntity(array $aa_additionalData = [])
 * @method \Awyiss\Model\Entity\UsersExternal patchEntity(EntityInterface $ao_entity, array $aa_data, array $aa_options = [])
 */
class UsersExternalTable extends \Awyiss\Model\Table {
	/**
	 * @inheritDoc
	 */
	public function initialize (array $aa_config): void {
		parent::initialize($aa_config);

		$this->setTable('users_external');
		$this->setDisplayField('id');
		$this->setPrimaryKey('id');
	}


	/**
	 * @noinspection PhpUnused
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function findActiveWithUsergroups (Query $ao_query, array $aa_options): Query {
		//TODO: fix this
		/*$ao_query->where([
			'active' => 1,
			'deleted' => 0,
			'OR' => [
				'failed_attempts <' => 5,
				'last_login <=' => \Cake\I18n\Time::now()->subMinutes(10),
			]
		])->contain('Usergroups');*/

		return $ao_query;
	}


	/**
	 * @inheritDoc
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
