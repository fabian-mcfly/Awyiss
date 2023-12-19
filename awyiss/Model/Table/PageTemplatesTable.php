<?php declare(strict_types=1);


namespace Awyiss\Model\Table;


use Cake\Database\Schema\TableSchemaInterface;
use Cake\ORM\RulesChecker;
use Cake\Validation\Validator;

/**
 * PageTemplates Model
 *
 * @property \Awyiss\Model\Table\PageRolesTable&\Cake\ORM\Association\BelongsTo $PageRoles
 *
 * @method \Awyiss\Model\Entity\PageTemplate newEmptyEntity()
 * @method \Awyiss\Model\Entity\PageTemplate newEntity(array $data, array $options = [])
 * @method \Awyiss\Model\Entity\PageTemplate[] newEntities(array $data, array $options = [])
 * @method \Awyiss\Model\Entity\PageTemplate get($primaryKey, $options = [])
 * @method \Awyiss\Model\Entity\PageTemplate findOrCreate($search, ?callable $callback = null, $options = [])
 * @method \Awyiss\Model\Entity\PageTemplate patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \Awyiss\Model\Entity\PageTemplate[] patchEntities(iterable $entities, array $data, array $options = [])
 * @method \Awyiss\Model\Entity\PageTemplate|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \Awyiss\Model\Entity\PageTemplate saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \Awyiss\Model\Entity\PageTemplate[]|\Cake\Datasource\ResultSetInterface|false saveMany(iterable $entities, $options = [])
 * @method \Awyiss\Model\Entity\PageTemplate[]|\Cake\Datasource\ResultSetInterface saveManyOrFail(iterable $entities, $options = [])
 * @method \Awyiss\Model\Entity\PageTemplate[]|\Cake\Datasource\ResultSetInterface|false deleteMany(iterable $entities, $options = [])
 * @method \Awyiss\Model\Entity\PageTemplate[]|\Cake\Datasource\ResultSetInterface deleteManyOrFail(iterable $entities, $options = [])
 */
class PageTemplatesTable extends \Awyiss\Model\Table {
	/**
	 * Initialize method
	 *
	 * @param array $aa_config The configuration for the Table.
	 * @return void
	 */
	public function initialize (array $aa_config): void {
		parent::initialize($aa_config);

		$this->setTable('page_templates');
		$this->setDisplayField('title');
		$this->setPrimaryKey('id');

		$this->belongsTo('PageRoles', [
            'foreignKey' => 'page_roles_id',
            'joinType' => 'INNER',
        ]);
	}

	/**
	 * Default validation rules.
	 *
	 * @param \Cake\Validation\Validator $ao_validator Validator instance.
	 * @return \Cake\Validation\Validator
	 */
	public function validationDefault (Validator $ao_validator): Validator {
		$ao_validator
			->integer('id')
			->allowEmptyString('id', null, 'create');

		$ao_validator
			->scalar('title')
			->maxLength('title', 100)
			->requirePresence('title', 'create')
			->notEmptyString('title');

		$ao_validator
			->scalar('filename')
			->maxLength('filename', 100)
			->requirePresence('filename', 'create')
			->notEmptyFile('filename');

		$ao_validator
			->requirePresence('contentareas', 'create')
			->notEmptyString('contentareas');

		$ao_validator
			->boolean('active')
			->notEmptyString('active');

		$ao_validator
			->boolean('deleted')
			->notEmptyString('deleted');

		$ao_validator
			->integer('system_order')
			->notEmptyString('system_order');

		$ao_validator
			->integer('created_by')
			->allowEmptyString('created_by');

		$ao_validator
			->dateTime('created_on')
			->allowEmptyDateTime('created_on');

		$ao_validator
			->integer('changed_by')
			->allowEmptyString('changed_by');

		$ao_validator
			->dateTime('changed_on')
			->allowEmptyDateTime('changed_on');

		$ao_validator
			->integer('deleted_by')
			->allowEmptyString('deleted_by');

		$ao_validator
			->dateTime('deleted_on')
			->allowEmptyDateTime('deleted_on');

		return $ao_validator;
	}

	/**
	 * Returns a rules checker object that will be used for validating
	 * application integrity.
	 *
	 * @param \Cake\ORM\RulesChecker $rules The rules object to be modified.
	 * @return \Cake\ORM\RulesChecker
	 */
	public function buildRules (RulesChecker $rules): RulesChecker {
		$rules->add($rules->existsIn(['page_roles_id'], 'PageRoles'), ['errorField' => 'page_roles_id']);

		return $rules;
	}

	/**
	 * {@inheritDoc}
	 */
	protected function _initializeSchema (TableSchemaInterface $schema): TableSchemaInterface {
		$schema->setColumnType('contentareas', 'json');

		return $schema;
	}
}
