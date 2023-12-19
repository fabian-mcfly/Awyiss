<?php declare(strict_types=1);


namespace Awyiss\Model\Table;


use Awyiss\Model\Entity\UsergroupsUser;
use Awyiss\Model\Table;
use Awyiss\ORM\RulesChecker;
use Cake\Validation\Validator;


/**
 * UsergroupsUsers Model
 *
 * @property \Awyiss\Model\Table\UsergroupsTable&\Cake\ORM\Association\BelongsTo $Usergroups
 * @property \Awyiss\Model\Table\UsersTable&\Cake\ORM\Association\BelongsTo $Users
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
	protected array $_defaultConfig = [
		'access' => [
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

		$this->setTable(static::TABLE);
		$this->setDisplayField('id');
		$this->setPrimaryKey('id');

		$this->belongsTo('Usergroups', [
			//'foreignKey' => 'usergroup_id',
			'joinType' => 'INNER',
		]);

		$this->belongsTo('Users', [
			//'foreignKey' => 'user_id',
			'joinType' => 'INNER',
		]);
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

		return $ao_validator;
	}


	/**
	 * Returns a RulesChecker object after modifying the one that was supplied.
	 *
	 * @param \Awyiss\ORM\RulesChecker|\Cake\ORM\RulesChecker $ao_rules The rules object to be modified.
	 *
	 * @return \Awyiss\ORM\RulesChecker
	 *
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function buildRules (RulesChecker|\Cake\ORM\RulesChecker $ao_rules): RulesChecker {
		$ao_rules->add($ao_rules->existsIn(['usergroup_id'], 'Usergroups'), ['errorField' => 'usergroup_id']);
		$ao_rules->add($ao_rules->existsIn(['user_id'], 'Users'), ['errorField' => 'user_id']);

		return $ao_rules;
	}
}
