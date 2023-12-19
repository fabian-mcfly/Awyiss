<?php declare(strict_types=1);


namespace Awyiss\Model\Table;


use Cake\ORM\RulesChecker;
use Cake\Validation\Validator;


/**
 * Languages Model
 *
 * @method \Awyiss\Model\Entity\Language newDefaultEntity()
 * @method \Awyiss\Model\Entity\Language newEmptyEntity()
 * @method \Awyiss\Model\Entity\Language newEntity(array $data, array $options = [])
 * @method \Awyiss\Model\Entity\Language[] newEntities(array $data, array $options = [])
 * @method \Awyiss\Model\Entity\Language get($primaryKey, $options = [])
 * @method \Awyiss\Model\Entity\Language findOrCreate($search, ?callable $callback = NULL, $options = [])
 * @method \Awyiss\Model\Entity\Language patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \Awyiss\Model\Entity\Language[] patchEntities(iterable $entities, array $data, array $options = [])
 * @method \Awyiss\Model\Entity\Language|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \Awyiss\Model\Entity\Language saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \Awyiss\Model\Entity\Language[]|\Cake\Datasource\ResultSetInterface|false saveMany(iterable $entities, $options = [])
 * @method \Awyiss\Model\Entity\Language[]|\Cake\Datasource\ResultSetInterface saveManyOrFail(iterable $entities, $options = [])
 * @method \Awyiss\Model\Entity\Language[]|\Cake\Datasource\ResultSetInterface|false deleteMany(iterable $entities, $options = [])
 * @method \Awyiss\Model\Entity\Language[]|\Cake\Datasource\ResultSetInterface deleteManyOrFail(iterable $entities, $options = [])
 * @property \Awyiss\Model\Table\ConfigurationTable&\Cake\ORM\Association\HasMany $Configuration
 */
class LanguagesTable extends \Awyiss\Model\Table {
	protected array $_defaultConfig = [
		'systemOrder' => [
			'relatedColumns' => ['type'],
		],
	];


	/**
	 * Initialize method
	 *
	 * @param array $aa_config The configuration for the Table.
	 *
	 * @return void
	 */
	public function initialize (array $aa_config): void {
		parent::initialize($aa_config);

		$this->setTable('languages');
		$this->setDisplayField('title');
		$this->setPrimaryKey('id');

		$this->hasMany('Configuration')
			->setBindingKey('shortcode')
			->setForeignKey('languages_shortcode')
			->setSaveStrategy('replace')
			->setDependent(FALSE);
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

		$ao_validator->scalar('shortcode')
			->minLength('shortcode', 2, __('validation::shortcode_incorrect_length'))
			->maxLength('shortcode', 2, __('validation::shortcode_incorrect_length'))
			->requirePresence('shortcode')
			->notEmptyString('shortcode');

		$ao_validator->scalar('title')->maxLength('title', 32)->requirePresence('title')->notEmptyString('title');

		$ao_validator->scalar('timezone')->maxLength('timezone', 32)->requirePresence('timezone')->notEmptyString('timezone');

		$ao_validator->scalar('locale')->maxLength('locale', 20)->requirePresence('locale')->notEmptyString('locale');

		$ao_validator->scalar('type')->notEmptyString('type');

		$ao_validator->boolean('active')->notEmptyString('active');

		$ao_validator->boolean('deleted')->notEmptyString('deleted');

		$ao_validator->integer('system_order')->requirePresence('system_order')->notEmptyString('system_order');

		return $ao_validator;
	}


	public function buildRules (RulesChecker $rules): RulesChecker {
		$rules->add($rules->isUnique(['shortcode', 'type'], __('validation::shortcode_not_unique')));

		return $rules;
	}
}
