<?php declare(strict_types=1);


namespace Customer\Model\Table;


use Awyiss\Model\Table;
use Awyiss\ORM\RulesChecker;
use Cake\Database\Schema\TableSchemaInterface;
use Cake\ORM\RulesChecker as BaseRulesChecker;
use Cake\Validation\Validator;


/**
 * AttributesCars Model
 *
 * @method \FoobarCustomer\Model\Entity\AttributesCar newDefaultEntity(array $additionalData = [], array $options = [])
 */
class AttributesCarsTable extends Table {
	/**
	 * @inheritDoc
	 */
	public const bool ATTRIBUTABLE = false;
	/**
	 * @inheritDoc
	 */
	public const string TABLE = 'attributes_cars';


	/**
	 * @inheritDoc
	 */
	public function initialize(array $config): void {
		parent::initialize($config);

		$this->belongsTo('Cars', [
			'foreignKey' => 'carId',
			'joinType' => 'INNER',
		]);
	}


	/**
	 * @inheritDoc
	 * @noinspection DuplicatedCode
	 */
	public function validationDefault(Validator $validator): Validator {
		parent::validationDefault($validator);

		$validator
			->integer('carId')
			->notEmptyString('carId');

		$validator
			->scalar('freeText')
			->allowEmptyString('freeText');

		$validator
			->scalar('dropdownSelect')
			->maxLength('dropdownSelect', 50)
			->requirePresence('dropdownSelect', 'create')
			->notEmptyString('dropdownSelect');

		return $validator;
	}


	/**
	 * Returns a RulesChecker object after modifying the one that was supplied.
	 *
	 * @param \Awyiss\ORM\RulesChecker|\Cake\ORM\RulesChecker $rules The rules object to be modified.
	 * @return \Awyiss\ORM\RulesChecker
	 */
	public function buildRules(RulesChecker|BaseRulesChecker $rules): RulesChecker {
		$rules->add($rules->existsIn(['carId'], 'Cars'), 'validCarId', ['errorField' => 'carId']);

		return $rules;
	}


	/**
	 * @inheritDoc
	 */
	protected function initializeSchema(TableSchemaInterface $schema): void {
		parent::initializeSchema($schema);

		$schema->setColumnType('inputList', 'json');
		$schema->setColumnType('inputKeyValueList', 'json');
	}
}
