<?php declare(strict_types=1);


namespace Awyiss\Model\Table;


use Cake\Datasource\EntityInterface;
use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\Validation\Validator;


/**
 * Users Model
 *
 * @property \Awyiss\Model\Table\UsergroupsTable&\Cake\ORM\Association\BelongsToMany $Usergroups
 * @property \Awyiss\Model\Table\UsergroupsUsersTable&\Cake\ORM\Association\HasMany $UsergroupsUsers
 *
 * @method \Awyiss\Model\Entity\User newDefaultEntity(array $aa_additionalData = [])
 * @method \Awyiss\Model\Entity\User patchEntity(EntityInterface $ao_entity, array $aa_data, array $aa_options = [])
 */
class UsersTable extends \Awyiss\Model\Table {
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
	 * @noinspection PhpUnused
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function findActive (Query $ao_query, array $aa_options): Query {
		$ao_query->where([
			'active' => 1,
			'OR' => [
				'failed_attempts <' => 5,
				'last_login <=' => \Cake\I18n\FrozenTime::now()->subMinutes(10),
			],
		]);

		return $ao_query;
	}


	/**
	 * @noinspection PhpUnused
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function findActiveWithUsergroups (Query $ao_query, array $aa_options): Query {
		$ao_query->where([
			'active' => 1,
			'OR' => [
				'failed_attempts <' => 5,
				'last_login <=' => \Cake\I18n\FrozenTime::now()->subMinutes(10),
			],
		])/*->contain(['Usergroups.UsergroupPermissions'])*/
		;

		return $ao_query;
	}


	/**
	 * @inheritDoc
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
	 * @inheritDoc
	 *
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function buildRules (RulesChecker $ao_rules): RulesChecker {
		$ao_rules->add($ao_rules->isUnique(['username']), ['errorField' => 'username']);
		$ao_rules->add($ao_rules->isUnique(['email']), ['errorField' => 'email']);

		return $ao_rules;
	}
}
