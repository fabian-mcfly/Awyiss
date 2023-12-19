<?php declare(strict_types=1);


namespace Awyiss\Model\Table;


use Cake\Database\Schema\TableSchemaInterface;
use Cake\ORM\RulesChecker;
use Cake\Validation\Validator;


/**
 * UsergroupsPermissions Model
 *
 * @property \Awyiss\Model\Table\UsergroupsTable&\Cake\ORM\Association\BelongsTo $Usergroups
 *
 * @method \Awyiss\Model\Entity\UsergroupsPermission newEmptyEntity()
 * @method \Awyiss\Model\Entity\UsergroupsPermission newEntity(array $data, array $options = [])
 * @method \Awyiss\Model\Entity\UsergroupsPermission[] newEntities(array $data, array $options = [])
 * @method \Awyiss\Model\Entity\UsergroupsPermission get($primaryKey, $options = [])
 * @method \Awyiss\Model\Entity\UsergroupsPermission findOrCreate($search, ?callable $callback = null, $options = [])
 * @method \Awyiss\Model\Entity\UsergroupsPermission patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \Awyiss\Model\Entity\UsergroupsPermission[] patchEntities(iterable $entities, array $data, array $options = [])
 * @method \Awyiss\Model\Entity\UsergroupsPermission|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \Awyiss\Model\Entity\UsergroupsPermission saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \Awyiss\Model\Entity\UsergroupsPermission[]|\Cake\Datasource\ResultSetInterface|false saveMany(iterable $entities, $options = [])
 * @method \Awyiss\Model\Entity\UsergroupsPermission[]|\Cake\Datasource\ResultSetInterface saveManyOrFail(iterable $entities, $options = [])
 * @method \Awyiss\Model\Entity\UsergroupsPermission[]|\Cake\Datasource\ResultSetInterface|false deleteMany(iterable $entities, $options = [])
 * @method \Awyiss\Model\Entity\UsergroupsPermission[]|\Cake\Datasource\ResultSetInterface deleteManyOrFail(iterable $entities, $options = [])
 */
class UsergroupsPermissionsTable extends \Awyiss\Model\Table {
	/**
	 * Initialize method
	 *
	 * @param array $aa_config The configuration for the Table.
	 *
	 * @return void
	 */
	public function initialize (array $aa_config): void {
		parent::initialize($aa_config);

		$this->setTable('usergroups_permissions');
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
