<?php declare(strict_types=1);


namespace Awyiss\Model\Table;


use Awyiss\Model\Behavior\Date\DateType;
use Awyiss\Model\Table;
use Awyiss\ORM\RulesChecker;
use Cake\Database\Schema\TableSchemaInterface;
use Cake\Database\Type\EnumType;
use Cake\Validation\Validator;


/**
 * Dates Model
 */
class DatesTable extends Table {
	/**
	 * @inheritDoc
	 */
	public const ATTRIBUTABLE = FALSE;
	/**
	 * @inheritDoc
	 */
	public const TABLE = 'dates';


	/**
	 * @inheritDoc
	 */
	protected array $_defaultConfig = [
		'audit' => [
			'enabled' => FALSE,
		],
		'authorize' => [
			'enabled' => FALSE,
		],
	];


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

		$ao_validator->scalar('scope')->maxLength('scope', 50)->requirePresence('scope', 'create')->notEmptyString('scope');

		$ao_validator->integer('parentId')->notEmptyString('parentId');

		$ao_validator->scalar('type')->maxLength('type', 20)->requirePresence('type', 'create')->notEmptyString('type');

		$ao_validator->dateTime('value')->allowEmptyDateTime('value');

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
		$ao_rules->add($ao_rules->existsIn('parentId', 'ParentDates'), ['errorField' => 'parentId']);

		return $ao_rules;
	}


	public function initializeSchema (TableSchemaInterface $ao_schema): void {
		parent::initializeSchema($ao_schema);

		$ao_schema->setColumnType('type', EnumType::from(DateType::class));
	}
}
