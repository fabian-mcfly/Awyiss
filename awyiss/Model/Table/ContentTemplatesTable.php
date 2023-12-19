<?php

declare(strict_types=1);


namespace Awyiss\Model\Table;


use Cake\Database\Schema\TableSchemaInterface;
use Cake\Validation\Validator;


/**
 * ContentTemplates Model
 *
 * @method \Awyiss\Model\Entity\ContentTemplate newEmptyEntity()
 * @method \Awyiss\Model\Entity\ContentTemplate newEntity(array $data, array $options = [])
 * @method \Awyiss\Model\Entity\ContentTemplate[] newEntities(array $data, array $options = [])
 * @method \Awyiss\Model\Entity\ContentTemplate get($primaryKey, $options = [])
 * @method \Awyiss\Model\Entity\ContentTemplate findOrCreate($search, ?callable $callback = null, $options = [])
 * @method \Awyiss\Model\Entity\ContentTemplate patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \Awyiss\Model\Entity\ContentTemplate[] patchEntities(iterable $entities, array $data, array $options = [])
 * @method \Awyiss\Model\Entity\ContentTemplate|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \Awyiss\Model\Entity\ContentTemplate saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \Awyiss\Model\Entity\ContentTemplate[]|\Cake\Datasource\ResultSetInterface|false saveMany(iterable $entities, $options = [])
 * @method \Awyiss\Model\Entity\ContentTemplate[]|\Cake\Datasource\ResultSetInterface saveManyOrFail(iterable $entities, $options = [])
 * @method \Awyiss\Model\Entity\ContentTemplate[]|\Cake\Datasource\ResultSetInterface|false deleteMany(iterable $entities, $options = [])
 * @method \Awyiss\Model\Entity\ContentTemplate[]|\Cake\Datasource\ResultSetInterface deleteManyOrFail(iterable $entities, $options = [])
 * @property \Awyiss\Model\Table\Attributes&\Cake\ORM\Association\HasOne $Attributes
 */
class ContentTemplatesTable extends \Awyiss\Model\Table {
	/**
	 * Initialize method
	 *
	 * @param array $aa_config The configuration for the Table.
	 *
	 * @return void
	 */
	public function initialize (array $aa_config): void {
		parent::initialize($aa_config);

		$this->setTable('content_templates');
		$this->setDisplayField('title');
		$this->setPrimaryKey('id');
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

		$ao_validator->scalar('title')->maxLength('title', 100)->requirePresence('title', 'create')->notEmptyString('title');

		$ao_validator->scalar('filename')->maxLength('filename', 100)->requirePresence('filename', 'create')->notEmptyFile('filename');

		$ao_validator->requirePresence('visible_elements', 'create')->notEmptyString('visible_elements');

		$ao_validator->boolean('active')->notEmptyString('active');

		$ao_validator->boolean('deleted')->notEmptyString('deleted');

		$ao_validator->integer('system_order')->notEmptyString('system_order');

		$ao_validator->integer('created_by')->allowEmptyString('created_by');

		$ao_validator->dateTime('created_on')->allowEmptyDateTime('created_on');

		$ao_validator->integer('changed_by')->allowEmptyString('changed_by');

		$ao_validator->dateTime('changed_on')->allowEmptyDateTime('changed_on');

		$ao_validator->integer('deleted_by')->allowEmptyString('deleted_by');

		$ao_validator->dateTime('deleted_on')->allowEmptyDateTime('deleted_on');

		return $ao_validator;
	}


	/**
	 * {@inheritDoc}
	 */
	protected function _initializeSchema (TableSchemaInterface $schema): TableSchemaInterface {
		$schema->setColumnType('visible_elements', 'json');

		return $schema;
	}
}
