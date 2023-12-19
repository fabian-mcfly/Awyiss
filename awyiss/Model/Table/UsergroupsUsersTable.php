<?php declare(strict_types=1);


namespace Awyiss\Model\Table;


use Cake\Datasource\EntityInterface;
use Cake\ORM\RulesChecker;
use Cake\Validation\Validator;


/**
 * UsergroupsUsers Model
 *
 * @property \Awyiss\Model\Table\UsergroupsTable&\Cake\ORM\Association\BelongsTo $Usergroups
 * @property \Awyiss\Model\Table\UsersTable&\Cake\ORM\Association\BelongsTo $Users
 *
 * @method \Awyiss\Model\Entity\UsergroupsUser newDefaultEntity(array $aa_additionalData = [])
 * @method \Awyiss\Model\Entity\UsergroupsUser patchEntity(EntityInterface $ao_entity, array $aa_data, array $aa_options = [])
 */
class UsergroupsUsersTable extends \Awyiss\Model\Table {
	protected array $_defaultConfig = [
		'access' => [
			'identifiers' => [
				'Entity.create' => ['create', 'update'], //Since we use the users-scope, creating an association will occur when creating or updating a user
				'Entity.update' => 'update',
				'Model.beforeFind' => ['create', 'update', 'delete'],
				'Model.beforeDelete' => ['update', 'delete'], //Since we use the users-scope, deleting an association will occur when updating or deleting a user
			],
			'scope' => 'users',
		],
	];


	/**
	 * @inheritDoc
	 */
	public function initialize (array $aa_config): void {
		parent::initialize($aa_config);

		$this->setTable('usergroups_users');
		$this->setDisplayField('id');
		$this->setPrimaryKey('id');

		$this->belongsTo('Usergroups', [
			'foreignKey' => 'usergroup_id',
			'joinType' => 'INNER',
		]);

		$this->belongsTo('Users', [
			'foreignKey' => 'user_id',
			'joinType' => 'INNER',
		]);
	}


	/**
	 * @inheritDoc
	 *
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function validationDefault (Validator $ao_validator): Validator {
		$ao_validator->integer('id')->allowEmptyString('id', NULL, 'create');

		return $ao_validator;
	}


	/**
	 * @inheritDoc
	 *
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function buildRules (RulesChecker $ao_rules): RulesChecker {
		$ao_rules->add($ao_rules->existsIn(['usergroup_id'], 'Usergroups'), ['errorField' => 'usergroup_id']);
		$ao_rules->add($ao_rules->existsIn(['user_id'], 'Users'), ['errorField' => 'user_id']);

		return $ao_rules;
	}
}
