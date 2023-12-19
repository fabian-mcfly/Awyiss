<?php declare(strict_types=1);


namespace FoobarCustomer\Model\Table;


use Awyiss\Model\Table;
use Cake\ORM\RulesChecker;
use Cake\Validation\Validator;


/**
 * AttributesContents Model
 *
 * @property \Awyiss\Model\Table\ContentsTable&\Cake\ORM\Association\BelongsTo $Contents
 *
 * @method \FoobarCustomer\Model\Entity\AttributesContent newEmptyEntity()
 * @method \FoobarCustomer\Model\Entity\AttributesContent newEntity(array $data, array $options = [])
 * @method \FoobarCustomer\Model\Entity\AttributesContent[] newEntities(array $data, array $options = [])
 * @method \FoobarCustomer\Model\Entity\AttributesContent get($primaryKey, $options = [])
 * @method \FoobarCustomer\Model\Entity\AttributesContent findOrCreate($search, ?callable $callback = null, $options = [])
 * @method \FoobarCustomer\Model\Entity\AttributesContent patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \FoobarCustomer\Model\Entity\AttributesContent[] patchEntities(iterable $entities, array $data, array $options = [])
 * @method \FoobarCustomer\Model\Entity\AttributesContent|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \FoobarCustomer\Model\Entity\AttributesContent saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \FoobarCustomer\Model\Entity\AttributesContent[]|\Cake\Datasource\ResultSetInterface|false saveMany(iterable $entities, $options = [])
 * @method \FoobarCustomer\Model\Entity\AttributesContent[]|\Cake\Datasource\ResultSetInterface saveManyOrFail(iterable $entities, $options = [])
 * @method \FoobarCustomer\Model\Entity\AttributesContent[]|\Cake\Datasource\ResultSetInterface|false deleteMany(iterable $entities, $options = [])
 * @method \FoobarCustomer\Model\Entity\AttributesContent[]|\Cake\Datasource\ResultSetInterface deleteManyOrFail(iterable $entities, $options = [])
 */
class AttributesContentsTable extends Table {
	/**
	* @inheritDoc
	*/
	public const ATTRIBUTABLE = FALSE;
	/**
	* @inheritDoc
	*/
	public const TABLE = 'attributes_contents';


	/**
	 * @inheritDoc
	 */
	public function initialize (array $aa_config): void {
		parent::initialize($aa_config);

		$this->setTable(static::TABLE);
		$this->setDisplayField('id');
		$this->setPrimaryKey('id');

		$this->belongsTo('Contents', [
            'foreignKey' => 'content_id',
            'joinType' => 'INNER',
        ]);
	}


	/**
	 * @inheritDoc
	 *
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function validationDefault (Validator $ao_validator): Validator {
		$ao_validator
			->integer('content_id')
			->notEmptyString('content_id');

		$ao_validator
			->scalar('background_color')
			->maxLength('background_color', 30)
			->requirePresence('background_color', 'create')
			->notEmptyString('background_color');

		$ao_validator
			->isArray('jason_test')
			->requirePresence('jason_test', 'create')
			->notEmptyArray('jason_test');

		return $ao_validator;
	}


	/**
	 * @inheritDoc
	 *
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function buildRules (RulesChecker $ao_rules): RulesChecker {
		$ao_rules->add($ao_rules->existsIn('content_id', 'Contents'), ['errorField' => 'content_id']);

		return $ao_rules;
	}
}
