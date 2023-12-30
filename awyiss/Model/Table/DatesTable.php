<?php declare(strict_types=1);


namespace Awyiss\Model\Table;


use Awyiss\Model\Table;
use Cake\Validation\Validator;


/**
 * Dates Model
 */
class DatesTable extends Table {
	/**
	 * @inheritDoc
	 */
	public const ATTRIBUTABLE = false;
	/**
	 * @inheritDoc
	 */
	public const TABLE = 'dates';


	/**
	 * @inheritDoc
	 */
	protected array $audit = [
		'enabled' => false,
	];


	/**
	 * Returns the default validator object.
	 *
	 * @param \Cake\Validation\Validator $ao_validator The validator that can be modified to add some rules to it.
	 * @return \Cake\Validation\Validator
	 */
	public function validationDefault(Validator $ao_validator): Validator {
		parent::validationDefault($ao_validator);

		$ao_validator->scalar('scope')->maxLength('scope', 50)->requirePresence('scope', 'create')->notEmptyString('scope');

		$ao_validator->integer('foreignId')->notEmptyString('foreignId');

		$ao_validator->scalar('type')->maxLength('type', 20)->requirePresence('type', 'create')->notEmptyString('type');

		$ao_validator->dateTime('datetime')->allowEmptyDateTime('datetime');

		$ao_validator->dateTime('date')->allowEmptyDate('date');

		$ao_validator->dateTime('time')->allowEmptyTime('time');


		return $ao_validator;
	}
}
