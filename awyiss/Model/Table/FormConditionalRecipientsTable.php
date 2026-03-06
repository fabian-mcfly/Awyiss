<?php declare(strict_types=1);


namespace Awyiss\Model\Table;


use Awyiss\Core\App;
use Awyiss\Model\Table;
use Awyiss\ORM\RulesChecker;
use Cake\Database\Schema\TableSchemaInterface;
use Cake\Database\Type\EnumType;
use Cake\ORM\RulesChecker as BaseRulesChecker;
use Cake\Validation\Validator;


/**
 * FormConditionalRecipients Model
 *
 * @property \Awyiss\Model\Table\FormsTable&\Awyiss\ORM\Association\BelongsTo $Forms
 * @method \Awyiss\Model\Entity\FormConditionalRecipient newDefaultEntity(array $additionalData = [], array $options = [])
 * @noinspection PhpFullyQualifiedNameUsageInspection
 */
class FormConditionalRecipientsTable extends Table {
	/**
	 * @inheritDoc
	 */
	public const bool ATTRIBUTABLE = false;
	/**
	 * @inheritDoc
	 */
	public const string TABLE = 'form_conditional_recipients';


	/**
	 * @inheritDoc
	 */
	protected array $systemOrder = [
		'relatedColumns' => ['formId'],
	];


	/**
	 * @inheritDoc
	 */
	public function initializeAssociations(): void {
		$this->belongsTo('Forms', [
			'foreignKey' => 'formId',
			'joinType' => 'INNER',
		]);
	}


	/**
	 * @inheritDoc
	 * @noinspection DuplicatedCode
	 */
	public function validationDefault(Validator $validator): Validator {
		parent::validationDefault($validator);


		$validator->requirePresence([
			'type',
			'operator',
			'value',
			'recipient',
		], 'create');


		$validator->notEmptyString('formId');
		$validator->add('formId', [
			'isInteger' => ['rule' => 'isInteger'],
			'maxLength' => ['rule' => ['maxLength', 11]],
		]);


		$validator->notEmptyString('type');
		$validator->add('type', [
			'isScalar' => ['rule' => 'isScalar'],
			'notBoolean' => ['rule' => 'notBoolean'],
			'inList' => ['rule' => ['inList', ['currentPage', 'elementIdentifier']]],
			'notBlank' => ['rule' => 'notBlank'],
		]);


		$validator->notEmptyString('field');
		$validator->add('field', [
			'isScalar' => ['rule' => 'isScalar'],
			'notBoolean' => ['rule' => 'notBoolean'],
			'notBlank' => ['rule' => 'notBlank'],
			'maxLength' => ['rule' => ['maxLength', 50]],
		]);


		$validator->notEmptyString('operator');
		/** @var class-string<\Awyiss\Model\Enum\ComparisonOperator> $comparisonOperatorEnumClass */
		$comparisonOperatorEnumClass = App::className('ComparisonOperator', 'Model/Enum');
		$validator->add('operator', [
			'enum' => ['rule' => ['enum', $comparisonOperatorEnumClass]],
		]);


		$validator->allowEmptyString('value');
		$validator->add('value', [
			'isScalar' => ['rule' => 'isScalar'],
			'notBoolean' => ['rule' => 'notBoolean'],
			'maxLength' => ['rule' => ['maxLength', 255]],
			'notBlank' => ['rule' => 'notBlank'],
		]);


		$validator->notEmptyString('recipient');
		$validator->add('recipient', [
			'isScalar' => ['rule' => 'isScalar'],
			'notBoolean' => ['rule' => 'notBoolean'],
			'maxLength' => ['rule' => ['maxLength', 255]],
			'notBlank' => ['rule' => 'notBlank'],
			'email' => ['rule' => 'email'],
		]);


		$validator->add('systemOrder', [
			'isInteger' => ['rule' => 'isInteger'],
			'maxLength' => ['rule' => ['maxLength', 11]],
		]);


		return $validator;
	}


	/**
	 * Returns a RulesChecker object after modifying the one that was supplied.
	 *
	 * @param \Awyiss\ORM\RulesChecker|\Cake\ORM\RulesChecker $rules The rules object to be modified.
	 * @return \Awyiss\ORM\RulesChecker
	 */
	public function buildRules(RulesChecker|BaseRulesChecker $rules): RulesChecker {
		$rules->add(
			$rules->existsIn('formId', 'Forms'),
			'formExists',
			[
				'errorField' => 'formId',
				'message' => __df($this->getI18nDomain(), 'Validation', 'error_form_exists'),
			]
		);

		return $rules;
	}


	/**
	 * @inheritDoc
	 */
	protected function initializeSchema(TableSchemaInterface $schema): void {
		parent::initializeSchema($schema);

		/** @var class-string<\Awyiss\Model\Enum\ComparisonOperator> $comparisonOperatorEnumClass */
		$comparisonOperatorEnumClass = App::className('ComparisonOperator', 'Model/Enum');

		$schema->setColumnType('operator', EnumType::from($comparisonOperatorEnumClass));
	}
}
