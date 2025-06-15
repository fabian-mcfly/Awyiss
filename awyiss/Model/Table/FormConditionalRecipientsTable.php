<?php declare(strict_types=1);


namespace Awyiss\Model\Table;


use Awyiss\Model\Enum\ComparisonOperator;
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
	public const ATTRIBUTABLE = false;
	/**
	 * @inheritDoc
	 */
	public const TABLE = 'form_conditional_recipients';


	/**
	 * @inheritDoc
	 */
	protected array $systemOrder = [
		'relatedColumns' => ['form_id'],
	];


	/**
	 * @inheritDoc
	 */
	public function initializeAssociations(): void {
		$this->belongsTo('Forms', [
			'foreignKey' => 'form_id',
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
			'inList' => ['rule' => ['inList', ['current_page', 'element_identifier']]],
			'notBlank' => ['rule' => 'notBlank'],
		]);


		$validator->notEmptyString('field');
		$validator->add('field', [
			'isScalar' => ['rule' => 'isScalar'],
			'notBlank' => ['rule' => 'notBlank'],
			'maxLength' => ['rule' => ['maxLength', 50]],
		]);


		$validator->notEmptyString('operator');
		$validator->enum('operator', ComparisonOperator::class);


		$validator->allowEmptyString('value');
		$validator->add('value', [
			'isScalar' => ['rule' => 'isScalar'],
			'maxLength' => ['rule' => ['maxLength', 255]],
		]);


		$validator->notEmptyString('recipient');
		$validator->add('recipient', [
			'maxLength' => ['rule' => ['maxLength', 255]],
		]);


		$validator->add('systemOrder', [
			'isInteger' => ['rule' => 'isInteger'],
		]);


		return $validator;
	}


	/**
	 * Returns a RulesChecker object after modifying the one that was supplied.
	 *
	 * @param BaseRulesChecker $rules The rules object to be modified.
	 * @param \Awyiss\ORM\RulesChecker|BaseRulesChecker $rules The rules object to be modified.
	 */
	public function buildRules(RulesChecker|BaseRulesChecker $rules): RulesChecker {
		$rules->add(
			$rules->existsIn('formId', 'Forms'),
			'formExists',
			[
				'errorField' => 'formId',
				'message' => __df($this->getI18nDomain(), 'validation', 'error_form_exists'),
			]
		);

		return $rules;
	}


	/**
	 * @inheritDoc
	 */
	protected function initializeSchema(TableSchemaInterface $schema): void {
		parent::initializeSchema($schema);

		$schema->setColumnType('operator', EnumType::from(ComparisonOperator::class));
	}
}
