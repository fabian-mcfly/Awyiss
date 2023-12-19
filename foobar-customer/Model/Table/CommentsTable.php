<?php declare(strict_types=1);


namespace FoobarCustomer\Model\Table;


use Awyiss\Model\Table;
use Awyiss\ORM\RulesChecker;
use Cake\Validation\Validator;


/**
 * Comments Model
 *
 * @method \FoobarCustomer\Model\Entity\Comment newEmptyEntity()
 * @method \FoobarCustomer\Model\Entity\Comment newEntity(array $data, array $options = [])
 * @method \FoobarCustomer\Model\Entity\Comment[] newEntities(array $data, array $options = [])
 * @method \FoobarCustomer\Model\Entity\Comment get($primaryKey, $options = [])
 * @method \FoobarCustomer\Model\Entity\Comment findOrCreate($search, ?callable $callback = null, $options = [])
 * @method \FoobarCustomer\Model\Entity\Comment patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \FoobarCustomer\Model\Entity\Comment[] patchEntities(iterable $entities, array $data, array $options = [])
 * @method \FoobarCustomer\Model\Entity\Comment|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \FoobarCustomer\Model\Entity\Comment saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \FoobarCustomer\Model\Entity\Comment[]|\Cake\Datasource\ResultSetInterface|false saveMany(iterable $entities, $options = [])
 * @method \FoobarCustomer\Model\Entity\Comment[]|\Cake\Datasource\ResultSetInterface saveManyOrFail(iterable $entities, $options = [])
 * @method \FoobarCustomer\Model\Entity\Comment[]|\Cake\Datasource\ResultSetInterface|false deleteMany(iterable $entities, $options = [])
 * @method \FoobarCustomer\Model\Entity\Comment[]|\Cake\Datasource\ResultSetInterface deleteManyOrFail(iterable $entities, $options = [])
 */
class CommentsTable extends Table {
	/**
	* @inheritDoc
	*/
	public const ATTRIBUTABLE = FALSE;
	/**
	* @inheritDoc
	*/
	public const TABLE = 'comments';


	/**
	 * @inheritDoc
	 */
	public function initialize (array $aa_config): void {
		parent::initialize($aa_config);	}


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
			->integer('articleId')
			->requirePresence('articleId', 'create')
			->notEmptyString('articleId');

		$ao_validator
			->integer('parentId')
			->allowEmptyString('parentId');

		$ao_validator
			->scalar('text')
			->requirePresence('text', 'create')
			->notEmptyString('text');

		$ao_validator
			->boolean('active')
			->requirePresence('active', 'create')
			->notEmptyString('active');

		return $ao_validator;
	}
}
