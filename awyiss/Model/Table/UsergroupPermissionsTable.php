<?php declare(strict_types=1);


namespace Awyiss\Model\Table;


use Awyiss\Model\Entity\UsergroupPermission;
use Awyiss\Model\Table;
use Cake\Database\Schema\TableSchemaInterface;
use Awyiss\ORM\RulesChecker;
use Cake\Validation\Validator;


/**
 * UsergroupPermissions Model
 *
 * @property \Awyiss\Model\Table\UsergroupsTable&\Cake\ORM\Association\BelongsTo $Usergroups
 *
 * @method UsergroupPermission newDefaultEntity(array $aa_additionalData = [])
 */
class UsergroupPermissionsTable extends Table {
	/**
	 * @inheritDoc
	 */
	public const ATTRIBUTABLE = FALSE;
	/**
	 * @inheritDoc
	 */
	public const TABLE = 'usergroup_permissions';
	/**
	 * @inheritDoc
	 */
	protected array $_defaultConfig = [
		'access' => [
			'identifiers' => [
				//We use the usergroups-scope, creating a permission will occur when creating or updating a usergroup
				'Entity.create' => [['create', 'update']],
				'Entity.update' => 'update',
				'Model.beforeFind' => [['read', 'create', 'update', 'delete']],
				//We use the usergroups-scope, deleting a permission will occur when updating or deleting a usergroup
				'Model.beforeDelete' => [['update', 'delete']],
			],
			'scope' => 'usergroups',
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

		$ao_validator->scalar('scope')->maxLength('scope', 50)->requirePresence('scope', 'create')->notEmptyString('scope');

		$ao_validator->scalar('identifier')->maxLength('identifier', 50)->requirePresence('identifier', 'create')->notEmptyString('identifier');

		$ao_validator->integer('access');

		$ao_validator->isArray('settings')->allowEmptyArray('settings');

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

		return $ao_rules;
	}


	/**
	 * @inheritDoc
	 *
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	protected function _initializeSchema (TableSchemaInterface $ao_schema): TableSchemaInterface {
		$ao_schema->setColumnType('access', 'integer');
		$ao_schema->setColumnType('settings', 'json');

		return $ao_schema;
	}
}
