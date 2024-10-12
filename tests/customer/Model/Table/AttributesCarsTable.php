<?php declare(strict_types=1);


namespace Customer\Model\Table;


use Awyiss\Model\Table;
use Awyiss\ORM\RulesChecker;
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
	public const ATTRIBUTABLE = false;
	/**
	 * @inheritDoc
	 */
	public const TABLE = 'attributes_cars';


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
	 * Returns the default validator object.
	 *
	 * @param \Cake\Validation\Validator $validator The validator that can be modified to
	 * add some rules to it.
	 * @return \Cake\Validation\Validator
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
	 * @param \Cake\ORM\RulesChecker $rules The rules object to be modified.
	 * @param \Awyiss\ORM\RulesChecker|\Cake\ORM\RulesChecker $rules The rules object to be modified.
	 */
	public function buildRules(RulesChecker|BaseRulesChecker $rules): RulesChecker {
		$rules->add($rules->existsIn(['carId'], 'Cars'), 'validCarId', ['errorField' => 'carId']);

		return $rules;
	}
}
