<?php declare(strict_types=1);


namespace FoobarCustomer\Model\Table;


use Awyiss\Model\Table;
use Awyiss\ORM\RulesChecker;
use Cake\Validation\Validator;


/**
 * AttributesPages Model
 *
 * @property \Awyiss\Model\Table\PagesTable&\Cake\ORM\Association\BelongsTo $Pages
 *
 * @method \FoobarCustomer\Model\Entity\AttributesPage newEmptyEntity()
 * @method \FoobarCustomer\Model\Entity\AttributesPage newEntity(array $data, array $options = [])
 * @method array<\FoobarCustomer\Model\Entity\AttributesPage> newEntities(array $data, array $options = [])
 * @method \FoobarCustomer\Model\Entity\AttributesPage get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \FoobarCustomer\Model\Entity\AttributesPage findOrCreate($search, ?callable $callback = null, array $options = [])
 * @method \FoobarCustomer\Model\Entity\AttributesPage patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method array<\FoobarCustomer\Model\Entity\AttributesPage> patchEntities(iterable $entities, array $data, array $options = [])
 * @method \FoobarCustomer\Model\Entity\AttributesPage|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \FoobarCustomer\Model\Entity\AttributesPage saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method iterable<\FoobarCustomer\Model\Entity\AttributesPage>|\Cake\Datasource\ResultSetInterface<\FoobarCustomer\Model\Entity\AttributesPage>|false saveMany(iterable $entities, array $options = [])
 * @method iterable<\FoobarCustomer\Model\Entity\AttributesPage>|\Cake\Datasource\ResultSetInterface<\FoobarCustomer\Model\Entity\AttributesPage> saveManyOrFail(iterable $entities, array $options = [])
 * @method iterable<\FoobarCustomer\Model\Entity\AttributesPage>|\Cake\Datasource\ResultSetInterface<\FoobarCustomer\Model\Entity\AttributesPage>|false deleteMany(iterable $entities, array $options = [])
 * @method iterable<\FoobarCustomer\Model\Entity\AttributesPage>|\Cake\Datasource\ResultSetInterface<\FoobarCustomer\Model\Entity\AttributesPage> deleteManyOrFail(iterable $entities, array $options = [])
 */
class AttributesPagesTable extends Table {
	/**
	 * @inheritDoc
	 */
	public const ATTRIBUTABLE = FALSE;
	/**
	 * @inheritDoc
	 */
	public const TABLE = 'attributes_pages';


	/**
	 * @inheritDoc
	 */
	public function initialize (array $aa_config): void {
		parent::initialize($aa_config);

		$this->belongsTo('Pages', [
			'foreignKey' => 'pageId',
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
			->integer('pageId')
			->notEmptyString('pageId');

		$ao_validator
			->dateTime('testdate')
			->allowEmptyDateTime('testdate');

		$ao_validator
			->dateTime('testdate2')
			->allowEmptyDateTime('testdate2');

		$ao_validator
			->date('onlydate')
			->allowEmptyDate('onlydate');

		$ao_validator
			->time('onlytime')
			->allowEmptyTime('onlytime');

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
		$ao_rules->add($ao_rules->existsIn('pageId', 'Pages'), ['errorField' => 'pageId']);

		return $ao_rules;
	}
}
