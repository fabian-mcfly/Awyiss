<?php declare(strict_types=1);


namespace FoobarCustomer\Model\Table;


use Awyiss\Model\Table;
use Awyiss\ORM\RulesChecker;
use Cake\Validation\Validator;


/**
 * AttributesContents Model
 *
 * @property \Awyiss\Model\Table\ContentsTable&\Cake\ORM\Association\BelongsTo $Contents
 *
 * @method \FoobarCustomer\Model\Entity\AttributesContent newEmptyEntity()
 * @method \FoobarCustomer\Model\Entity\AttributesContent newEntity(array $data, array $options = [])
 * @method array<\FoobarCustomer\Model\Entity\AttributesContent> newEntities(array $data, array $options = [])
 * @method \FoobarCustomer\Model\Entity\AttributesContent get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \FoobarCustomer\Model\Entity\AttributesContent findOrCreate($search, ?callable $callback = null, array $options = [])
 * @method \FoobarCustomer\Model\Entity\AttributesContent patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method array<\FoobarCustomer\Model\Entity\AttributesContent> patchEntities(iterable $entities, array $data, array $options = [])
 * @method \FoobarCustomer\Model\Entity\AttributesContent|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \FoobarCustomer\Model\Entity\AttributesContent saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method iterable<\FoobarCustomer\Model\Entity\AttributesContent>|\Cake\Datasource\ResultSetInterface<\FoobarCustomer\Model\Entity\AttributesContent>|false saveMany(iterable $entities, array $options = [])
 * @method iterable<\FoobarCustomer\Model\Entity\AttributesContent>|\Cake\Datasource\ResultSetInterface<\FoobarCustomer\Model\Entity\AttributesContent> saveManyOrFail(iterable $entities, array $options = [])
 * @method iterable<\FoobarCustomer\Model\Entity\AttributesContent>|\Cake\Datasource\ResultSetInterface<\FoobarCustomer\Model\Entity\AttributesContent>|false deleteMany(iterable $entities, array $options = [])
 * @method iterable<\FoobarCustomer\Model\Entity\AttributesContent>|\Cake\Datasource\ResultSetInterface<\FoobarCustomer\Model\Entity\AttributesContent> deleteManyOrFail(iterable $entities, array $options = [])
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
	protected array $_defaultConfig = [
		'authorize' => [
			'scope' => ['contents'],
		],
	];


	/**
	 * @inheritDoc
	 */
	public function initialize (array $aa_config): void {
		parent::initialize($aa_config);

		$this->belongsTo('Contents', [
			'foreignKey' => 'contentId',
			'joinType' => 'INNER',
		]);
	}


	/**
	 * Returns the default validator object.
	 *
	 * @param \Cake\Validation\Validator $ao_validator The validator that can be modified to
	 * add some rules to it.
	 *
	 * @return \Cake\Validation\Validator
	 *
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function validationDefault (Validator $ao_validator): Validator {
		parent::validationDefault($ao_validator);

		$ao_validator
			->integer('contentId')
			->notEmptyString('contentId');

		$ao_validator
			->scalar('backgroundColor')
			->maxLength('backgroundColor', 50)
			->allowEmptyString('backgroundColor');

		$ao_validator
			->scalar('alter2')
			->maxLength('alter2', 255)
			->allowEmptyString('alter2');

		return $ao_validator;
	}


	/**
	 * Returns a RulesChecker object after modifying the one that was supplied.
	 *
	 * @param \Cake\ORM\RulesChecker $ao_rules The rules object to be modified.
	 *
	 * @param \Awyiss\ORM\RulesChecker|\Cake\ORM\RulesChecker $ao_rules The rules object to be modified.
	 *
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function buildRules (RulesChecker|\Cake\ORM\RulesChecker $ao_rules): RulesChecker {
		$ao_rules->add($ao_rules->existsIn('contentId', 'Contents'), ['errorField' => 'contentId']);

		return $ao_rules;
	}
}
