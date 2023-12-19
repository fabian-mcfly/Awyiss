<?php declare(strict_types=1);


namespace Awyiss\Model\Table;


use Cake\Validation\Validator;


/**
 * PageRoles Model
 *
 * @method \Awyiss\Model\Entity\PageRole newDefaultEntity()
 * @method \Awyiss\Model\Entity\PageRole newEmptyEntity()
 * @method \Awyiss\Model\Entity\PageRole newEntity(array $data, array $options = [])
 * @method \Awyiss\Model\Entity\PageRole[] newEntities(array $data, array $options = [])
 * @method \Awyiss\Model\Entity\PageRole get($primaryKey, $options = [])
 * @method \Awyiss\Model\Entity\PageRole findOrCreate($search, ?callable $callback = NULL, $options = [])
 * @method \Awyiss\Model\Entity\PageRole patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \Awyiss\Model\Entity\PageRole[] patchEntities(iterable $entities, array $data, array $options = [])
 * @method \Awyiss\Model\Entity\PageRole|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \Awyiss\Model\Entity\PageRole saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \Awyiss\Model\Entity\PageRole[]|\Cake\Datasource\ResultSetInterface|false saveMany(iterable $entities, $options = [])
 * @method \Awyiss\Model\Entity\PageRole[]|\Cake\Datasource\ResultSetInterface saveManyOrFail(iterable $entities, $options = [])
 * @method \Awyiss\Model\Entity\PageRole[]|\Cake\Datasource\ResultSetInterface|false deleteMany(iterable $entities, $options = [])
 * @method \Awyiss\Model\Entity\PageRole[]|\Cake\Datasource\ResultSetInterface deleteManyOrFail(iterable $entities, $options = [])
 */
class PageRolesTable extends \Awyiss\Model\Table {
	/**
	 * Initialize method
	 *
	 * @param array $aa_config The configuration for the Table.
	 *
	 * @return void
	 */
	public function initialize (array $aa_config): void {
		parent::initialize($aa_config);

		$this->setTable('page_roles');
		$this->setDisplayField('title');
		$this->setPrimaryKey('id');
	}


	/**
	 * Default validation rules.
	 *
	 * @param \Cake\Validation\Validator $ao_validator Validator instance.
	 *
	 * @return \Cake\Validation\Validator
	 *
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 * @noinspection DuplicatedCode
	 */
	public function validationDefault (Validator $ao_validator): Validator {
		$ao_validator->integer('id')->allowEmptyString('id', NULL, 'create');

		$ao_validator->scalar('title')->maxLength('title', 32)->requirePresence('title', 'create')->notEmptyString('title');

		$ao_validator->scalar('identifier')->maxLength('identifier', 32)->requirePresence('identifier', 'create')->notEmptyString('identifier');

		$ao_validator->boolean('include_in_linklist')->notEmptyString('include_in_linklist');

		$ao_validator->integer('system_order')->notEmptyString('system_order');

		$ao_validator->boolean('active')->notEmptyString('active');

		$ao_validator->boolean('deleted')->notEmptyString('deleted');

		$ao_validator->integer('created_by')->allowEmptyString('created_by');

		$ao_validator->dateTime('created_on')->allowEmptyDateTime('created_on');

		$ao_validator->integer('changed_by')->allowEmptyString('changed_by');

		$ao_validator->dateTime('changed_on')->allowEmptyDateTime('changed_on');

		$ao_validator->integer('deleted_by')->allowEmptyString('deleted_by');

		$ao_validator->dateTime('deleted_on')->allowEmptyDateTime('deleted_on');

		return $ao_validator;
	}
}
