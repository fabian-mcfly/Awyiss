<?php declare(strict_types=1);


namespace Awyiss\Model\Table;


use Cake\ORM\RulesChecker;
use Cake\Validation\Validator;


/**
 * Configuration Model
 *
 * @method \Awyiss\Model\Entity\Configuration newDefaultEntity()
 * @method \Awyiss\Model\Entity\Configuration newEmptyEntity()
 * @method \Awyiss\Model\Entity\Configuration newEntity(array $data, array $options = [])
 * @method \Awyiss\Model\Entity\Configuration[] newEntities(array $data, array $options = [])
 * @method \Awyiss\Model\Entity\Configuration get($primaryKey, $options = [])
 * @method \Awyiss\Model\Entity\Configuration findOrCreate($search, ?callable $callback = NULL, $options = [])
 * @method \Awyiss\Model\Entity\Configuration patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \Awyiss\Model\Entity\Configuration[] patchEntities(iterable $entities, array $data, array $options = [])
 * @method \Awyiss\Model\Entity\Configuration|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \Awyiss\Model\Entity\Configuration saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \Awyiss\Model\Entity\Configuration[]|\Cake\Datasource\ResultSetInterface|false saveMany(iterable $entities, $options = [])
 * @method \Awyiss\Model\Entity\Configuration[]|\Cake\Datasource\ResultSetInterface saveManyOrFail(iterable $entities, $options = [])
 * @method \Awyiss\Model\Entity\Configuration[]|\Cake\Datasource\ResultSetInterface|false deleteMany(iterable $entities, $options = [])
 * @method \Awyiss\Model\Entity\Configuration[]|\Cake\Datasource\ResultSetInterface deleteManyOrFail(iterable $entities, $options = [])
 */
class ConfigurationTable extends \Awyiss\Model\Table {
	/**
	 * Initialize method
	 *
	 * @param array $aa_config The configuration for the Table.
	 *
	 * @return void
	 */
	public function initialize (array $aa_config): void {
		parent::initialize($aa_config);

		$this->setTable('configuration');
		$this->setDisplayField('id');
		$this->setPrimaryKey('id');

		$this->belongsTo('Languages', [
			'bindingKey' => 'shortcode',
			'foreignKey' => 'languages_shortcode',
			'joinType' => 'LEFT',
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

		$la_configScopes = \Awyiss\ConfigOptions\ConfigOptionsProvider::getConfigurationFiles();
		$ao_validator->scalar('scope')->inList('scope', array_keys($la_configScopes))->maxLength('scope', 50)->notEmptyString('scope');

		$ao_validator->scalar('name')->maxLength('name', 255)->requirePresence('name', 'create')->notEmptyString('name');

		$ao_validator->scalar('value')->maxLength('value', 255)->allowEmptyString('value');

		$ao_validator->allowEmptyString('languages_shortcode');

		return $ao_validator;
	}


	/**
	 * Returns a rules checker object that will be used for validating
	 * application integrity.
	 *
	 * @param \Cake\ORM\RulesChecker $ao_rules The rules object to be modified.
	 *
	 * @return \Cake\ORM\RulesChecker
	 *
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function buildRules (RulesChecker $ao_rules): RulesChecker {
		$ao_rules->add($ao_rules->isUnique(['name', 'languages_shortcode']), ['errorField' => 'name']);

		$ao_rules->add($ao_rules->existsIn('languages_shortcode', 'Languages'), ['errorField' => 'languages_shortcode']);

		return $ao_rules;
	}
}
