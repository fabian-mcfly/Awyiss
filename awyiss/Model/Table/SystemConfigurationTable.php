<?php declare(strict_types=1);


namespace Awyiss\Model\Table;


use Cake\Validation\Validator;


/**
 * SystemConfiguration Model
 *
 * @method \Awyiss\Model\Entity\SystemConfiguration newEmptyEntity()
 * @method \Awyiss\Model\Entity\SystemConfiguration newEntity(array $data, array $options = [])
 * @method \Awyiss\Model\Entity\SystemConfiguration[] newEntities(array $data, array $options = [])
 * @method \Awyiss\Model\Entity\SystemConfiguration get($primaryKey, $options = [])
 * @method \Awyiss\Model\Entity\SystemConfiguration findOrCreate($search, ?callable $callback = null, $options = [])
 * @method \Awyiss\Model\Entity\SystemConfiguration patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \Awyiss\Model\Entity\SystemConfiguration[] patchEntities(iterable $entities, array $data, array $options = [])
 * @method \Awyiss\Model\Entity\SystemConfiguration|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \Awyiss\Model\Entity\SystemConfiguration saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \Awyiss\Model\Entity\SystemConfiguration[]|\Cake\Datasource\ResultSetInterface|false saveMany(iterable $entities, $options = [])
 * @method \Awyiss\Model\Entity\SystemConfiguration[]|\Cake\Datasource\ResultSetInterface saveManyOrFail(iterable $entities, $options = [])
 * @method \Awyiss\Model\Entity\SystemConfiguration[]|\Cake\Datasource\ResultSetInterface|false deleteMany(iterable $entities, $options = [])
 * @method \Awyiss\Model\Entity\SystemConfiguration[]|\Cake\Datasource\ResultSetInterface deleteManyOrFail(iterable $entities, $options = [])
 * @property \Awyiss\Model\Table\LanguagesTable&\Cake\ORM\Association\BelongsTo $Languages
 */
class SystemConfigurationTable extends \Awyiss\Model\Table {
	/**
	 * Initialize method
	 *
	 * @param array $aa_config The configuration for the Table.
	 *
	 * @return void
	 */
	public function initialize (array $aa_config): void {
		parent::initialize($aa_config);

		$this->setTable('system_configuration');
		$this->setDisplayField('id');
		$this->setPrimaryKey('id');

		$this->belongsTo('Languages', [
			'foreignKey' => 'languages_shortcode',
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

		$ao_validator->scalar('scope')->maxLength('scope', 50)->notEmptyString('scope');

		$ao_validator->scalar('key')->maxLength('key', 50)->requirePresence('key', 'create')->notEmptyString('key');

		$ao_validator->scalar('value')->allowEmptyString('value');

		$ao_validator->scalar('languages_shortcode')->maxLength('languages_shortcode', 2)->allowEmptyString('languages_shortcode');

		return $ao_validator;
	}
}
