<?php declare(strict_types=1);


namespace Awyiss\Model\Table;


use Awyiss\Model\Entity\UsergroupsUser;
use Awyiss\Model\Table;
use Awyiss\ORM\RulesChecker;
use Cake\ORM\Association\BelongsTo;
use Cake\ORM\RulesChecker as BaseRulesChecker;
use Cake\Validation\Validator;


/**
 * UsergroupsUsers Model
 *
 * @property UsergroupsTable&BelongsTo $Usergroups
 * @property UsersTable&BelongsTo      $Users
 *
 * @method UsergroupsUser newDefaultEntity(array $aa_additionalData = [])
 */
class UsergroupsUsersTable extends Table {
	/**
	 * @inheritDoc
	 */
	public const ATTRIBUTABLE = FALSE;
	/**
	 * @inheritDoc
	 */
	public const TABLE = 'usergroups_users';
	/**
	 * @var array|array[]
	 */
	protected array $_defaultConfig = [
		'authorize' => [
			'identifiers' => [
				//We use the users-scope, creating an association will occur when creating or updating a user
				'Entity.create' => [['create', 'update']],
				'Entity.update' => 'update',
				'Model.beforeFind' => [['read', 'create', 'update', 'delete']],
				//We use the users-scope, deleting an association will occur when updating or deleting a user
				'Model.beforeDelete' => [['update', 'delete']],
			],
			'scope' => 'users',
		],
	];


	/**
	 * @inheritDoc
	 */
	public function initialize (array $aa_config): void {
		parent::initialize($aa_config);

		$this->belongsTo('Usergroups', [
			'joinType' => 'INNER',
		]);

		$this->belongsTo('Users', [
			'joinType' => 'INNER',
		]);
	}


	/**
	 * Returns the default validator object.
	 *
	 * @param Validator $ao_validator The validator that can be modified to
	 * add some rules to it.
	 *
	 * @return Validator
	 */
	public function validationDefault (Validator $ao_validator): Validator {
		parent::validationDefault($ao_validator);


		$ao_validator->add('id', [
			'isInteger' => ['rule' => 'isInteger'],
			'maxLength' => ['rule' => ['maxLength', 11]],
		]);


		$ao_validator->add('usergroupId', [
			'isInteger' => ['rule' => 'isInteger'],
			'maxLength' => ['rule' => ['maxLength', 11]],
		]);


		$ao_validator->add('userId', [
			'isInteger' => ['rule' => 'isInteger'],
			'maxLength' => ['rule' => ['maxLength', 11]],
		]);


		return $ao_validator;
	}


	/**
	 * Returns a RulesChecker object after modifying the one that was supplied.
	 *
	 * @param RulesChecker|BaseRulesChecker $ao_rules The rules object to be modified.
	 *
	 * @return RulesChecker
	 *
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function buildRules (RulesChecker|BaseRulesChecker $ao_rules): RulesChecker {
		$ao_rules->add($ao_rules->existsIn(['usergroupId'], 'Usergroups'), 'usergroupExists', [
			'errorField' => 'usergroupId',
			'message' => __d($this->getI18nDomain(), 'error_usergroup_exists'),
		]);


		$ao_rules->add($ao_rules->existsIn(['userId'], 'Users'), 'userExists', [
			'errorField' => 'userId',
			'message' => __d($this->getI18nDomain(), 'error_user_exists'),
		]);


		return $ao_rules;
	}
}
