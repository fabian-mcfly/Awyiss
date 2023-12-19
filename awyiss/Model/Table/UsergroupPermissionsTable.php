<?php declare(strict_types=1);


namespace Awyiss\Model\Table;


use Cake\Database\Schema\TableSchemaInterface;
use Cake\ORM\RulesChecker;
use Cake\Validation\Validator;


/**
 * UsergroupPermissions Model
 *
 * @property \Awyiss\Model\Table\UsergroupsTable&\Cake\ORM\Association\BelongsTo $Usergroups
 *
 * @method \Awyiss\Model\Entity\UsergroupPermission newDefaultEntity()
 * @method \Awyiss\Model\Entity\UsergroupPermission newEmptyEntity()
 * @method \Awyiss\Model\Entity\UsergroupPermission newEntity(array $data, array $options = [])
 * @method \Awyiss\Model\Entity\UsergroupPermission[] newEntities(array $data, array $options = [])
 * @method \Awyiss\Model\Entity\UsergroupPermission get($primaryKey, $options = [])
 * @method \Awyiss\Model\Entity\UsergroupPermission findOrCreate($search, ?callable $callback = NULL, $options = [])
 * @method \Awyiss\Model\Entity\UsergroupPermission patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \Awyiss\Model\Entity\UsergroupPermission[] patchEntities(iterable $entities, array $data, array $options = [])
 * @method \Awyiss\Model\Entity\UsergroupPermission|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \Awyiss\Model\Entity\UsergroupPermission saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \Awyiss\Model\Entity\UsergroupPermission[]|\Cake\Datasource\ResultSetInterface|false saveMany(iterable $entities, $options = [])
 * @method \Awyiss\Model\Entity\UsergroupPermission[]|\Cake\Datasource\ResultSetInterface saveManyOrFail(iterable $entities, $options = [])
 * @method \Awyiss\Model\Entity\UsergroupPermission[]|\Cake\Datasource\ResultSetInterface|false deleteMany(iterable $entities, $options = [])
 * @method \Awyiss\Model\Entity\UsergroupPermission[]|\Cake\Datasource\ResultSetInterface deleteManyOrFail(iterable $entities, $options = [])
 */
class UsergroupPermissionsTable extends \Awyiss\Model\Table {
	/**
	 * Initialize method
	 *
	 * @param array $aa_config The configuration for the Table.
	 *
	 * @return void
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
	 * Default validation rules.
	 *
	 * @param \Cake\Validation\Validator $ao_validator Validator instance.
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

		$ao_validator->allowEmptyString('settings');

		return $ao_validator;
	}


	/**
	 * Returns a rules checker object that will be used for validating
	 * application integrity.
	 *
	 * @param \Cake\ORM\RulesChecker $rules The rules object to be modified.
	 *
	 * @return \Cake\ORM\RulesChecker
	 */
	public function buildRules (RulesChecker $rules): RulesChecker {
		$rules->add($rules->existsIn(['usergroup_id'], 'Usergroups'), ['errorField' => 'usergroup_id']);

		return $rules;
	}


	/**
	 * {@inheritDoc}
	 */
	protected function _initializeSchema (TableSchemaInterface $schema): TableSchemaInterface {
		$schema->setColumnType('access', 'integer');
		$schema->setColumnType('settings', 'json');

		return $schema;
	}
}
