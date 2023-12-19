<?php declare(strict_types=1);


namespace Awyiss\Model\Table;


use Cake\Database\Schema\TableSchemaInterface;
use Cake\Datasource\EntityInterface;
use Cake\ORM\RulesChecker;
use Cake\Validation\Validator;


/**
 * UsergroupPermissions Model
 *
 * @property \Awyiss\Model\Table\UsergroupsTable&\Cake\ORM\Association\BelongsTo $Usergroups
 *
 * @method \Awyiss\Model\Entity\UsergroupPermission newDefaultEntity(array $aa_additionalData = [])
 * @method \Awyiss\Model\Entity\UsergroupPermission patchEntity(EntityInterface $ao_entity, array $aa_data, array $aa_options = [])
 */
class UsergroupPermissionsTable extends \Awyiss\Model\Table {
	/**
	 * @inheritDoc
	 */
	protected array $_defaultConfig = [
		'access' => [
			'identifiers' => [
				'Entity.create' => ['create', 'update'], //Since we use the usergroups-scope, creating a permission will occur when creating or updating a usergroup
				'Entity.update' => 'update',
				'Model.beforeFind' => ['create', 'update', 'delete'],
				'Model.beforeDelete' => ['update', 'delete'], //Since we use the usergroups-scope, deleting a permission will occur when updating or deleting a usergroup
			],
			'scope' => 'usergroups',
		],
	];


	/**
	 * @inheritDoc
	 */
	public function initialize (array $aa_config): void {
		parent::initialize($aa_config);

		$this->setTable('usergroup_permissions');
		$this->setDisplayField('id');
		$this->setPrimaryKey('id');

		$this->belongsTo('Usergroups', [
			'foreignKey' => 'usergroup_id',
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

		$ao_validator->scalar('scope')->maxLength('scope', 50)->requirePresence('scope', 'create')->notEmptyString('scope');

		$ao_validator->scalar('identifier')->maxLength('identifier', 50)->requirePresence('identifier', 'create')->notEmptyString('identifier');

		$ao_validator->integer('access');

		$ao_validator->isArray('settings')->allowEmptyArray('settings');

		return $ao_validator;
	}


	/**
	 * @inheritDoc
	 *
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function buildRules (RulesChecker $ao_rules): RulesChecker {
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
