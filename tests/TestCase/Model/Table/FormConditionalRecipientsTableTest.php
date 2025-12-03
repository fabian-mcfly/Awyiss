<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Model\Table;


use Awyiss\Model\Entity\FormConditionalRecipient;
use Awyiss\Model\Enum\ComparisonOperator;
use Awyiss\Model\Table\FormConditionalRecipientsTable;
use Awyiss\ORM\Association\BelongsTo;
use Awyiss\ORM\Association\HasMany;
use Awyiss\Test\TestSuite\TestCase;
use Awyiss\Validation\Validator;
use Cake\Datasource\FactoryLocator;


/**
 * FormConditionalRecipientsTable Test Case
 *
 * @see \Awyiss\Model\Table\FormConditionalRecipientsTable
 */
class FormConditionalRecipientsTableTest extends TestCase {
	/**
	 * @var \Awyiss\Model\Table\FormConditionalRecipientsTable
	 */
	protected FormConditionalRecipientsTable $formConditionalRecipientsTable;


	/**
	 * @inheritDoc
	 */
	protected function setUp(): void {
		parent::setUp();

		/** @noinspection PhpFieldAssignmentTypeMismatchInspection */
		$this->formConditionalRecipientsTable = FactoryLocator::get('Table')->get('FormConditionalRecipients');
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\FormConditionalRecipientsTable::ATTRIBUTABLE
	 */
	public function testAttributableConstant(): void {
		$this->assertFalse($this->formConditionalRecipientsTable::ATTRIBUTABLE);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\FormConditionalRecipientsTable::TABLE
	 */
	public function testTableConstant(): void {
		$this->assertEquals('form_conditional_recipients', $this->formConditionalRecipientsTable::TABLE);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\FormConditionalRecipientsTable::initializeAssociations()
	 */
	public function testInitializeAssociations(): void {
		$this->assertCount(5, $this->formConditionalRecipientsTable->associations()->keys());

		// Test Forms association (BelongsTo)
		$this->assertTrue($this->formConditionalRecipientsTable->hasAssociation('Forms'));
		$formsAssociation = $this->formConditionalRecipientsTable->getAssociation('Forms');
		$this->assertInstanceOf(BelongsTo::class, $formsAssociation);
		$this->assertSame('form_id', $formsAssociation->getForeignKey());
		$this->assertSame('INNER', $formsAssociation->getJoinType());

		// 'MediaAssignments' must also exist
		$this->assertTrue($this->formConditionalRecipientsTable->hasAssociation('MediaAssignments'));
		$mediaAssignmentsAssociation = $this->formConditionalRecipientsTable->getAssociation('MediaAssignments');
		$this->assertInstanceOf(HasMany::class, $mediaAssignmentsAssociation);
		$this->assertTrue($mediaAssignmentsAssociation->getCascadeCallbacks());
		$this->assertTrue($mediaAssignmentsAssociation->getDependent());

		// Test user tracking associations
		$this->assertTrue($this->formConditionalRecipientsTable->hasAssociation('CreatedByUser'));
		$createdByUserAssociation = $this->formConditionalRecipientsTable->getAssociation('CreatedByUser');
		$this->assertInstanceOf(BelongsTo::class, $createdByUserAssociation);
		$this->assertFalse($createdByUserAssociation->getCascadeCallbacks());
		$this->assertFalse($createdByUserAssociation->getDependent());

		$this->assertTrue($this->formConditionalRecipientsTable->hasAssociation('ChangedByUser'));
		$changedByUserAssociation = $this->formConditionalRecipientsTable->getAssociation('ChangedByUser');
		$this->assertInstanceOf(BelongsTo::class, $changedByUserAssociation);
		$this->assertFalse($changedByUserAssociation->getCascadeCallbacks());
		$this->assertFalse($changedByUserAssociation->getDependent());

		$this->assertTrue($this->formConditionalRecipientsTable->hasAssociation('DeletedByUser'));
		$deletedByUserAssociation = $this->formConditionalRecipientsTable->getAssociation('DeletedByUser');
		$this->assertInstanceOf(BelongsTo::class, $deletedByUserAssociation);
		$this->assertFalse($deletedByUserAssociation->getCascadeCallbacks());
		$this->assertFalse($deletedByUserAssociation->getDependent());
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\FormConditionalRecipientsTable::validationDefault()
	 */
	public function testValidationDefault(): void {
		$validator = new Validator();
		$result = $this->formConditionalRecipientsTable->validationDefault($validator);

		$this->assertInstanceOf(Validator::class, $result);
		$this->assertSame('form_conditional_recipients', $result->getI18nDomain());

		// Test required fields
		$this->assertTrue($result->hasField('type'));
		$this->assertSame('create', $result->field('type')->isPresenceRequired());

		$this->assertTrue($result->hasField('operator'));
		$this->assertSame('create', $result->field('operator')->isPresenceRequired());

		$this->assertTrue($result->hasField('value'));
		$this->assertSame('create', $result->field('value')->isPresenceRequired());

		$this->assertTrue($result->hasField('recipient'));
		$this->assertSame('create', $result->field('recipient')->isPresenceRequired());

		// Test other fields
		$this->assertTrue($result->hasField('formId'));
		$this->assertTrue($result->hasField('field'));
		$this->assertTrue($result->hasField('systemOrder'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\FormConditionalRecipientsTable::validationDefault()
	 */
	public function testEntityValidationSuccess(): void {
		$data = [
			'formId' => 1,
			'type' => 'element_identifier',
			'field' => 'email',
			'operator' => ComparisonOperator::Equal,
			'value' => 'test@example.com',
			'recipient' => 'recipient@example.com',
			'systemOrder' => 1,
		];

		$entity = $this->formConditionalRecipientsTable->newEntity($data);
		$errors = $entity->getErrors();

		$this->assertEmpty($errors, 'Valid data should not produce validation errors');
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\FormConditionalRecipientsTable::validationDefault()
	 */
	public function testEntityValidationMissingRequired(): void {
		$data = [
			'formId' => 1,
		];

		$entity = $this->formConditionalRecipientsTable->newEntity($data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('type', $errors);
		$this->assertArrayHasKey('operator', $errors);
		$this->assertArrayHasKey('value', $errors);
		$this->assertArrayHasKey('recipient', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\FormConditionalRecipientsTable::validationDefault()
	 */
	public function testEntityValidationInvalidTypes(): void {
		$data = [
			'formId' => 'not_an_integer',
			'type' => true,
			'field' => true,
			'operator' => 'invalid_operator',
			'value' => true,
			'recipient' => 123,
			'systemOrder' => 'not_an_integer',
		];

		$entity = $this->formConditionalRecipientsTable->newEntity($data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('formId', $errors);
		$this->assertArrayHasKey('type', $errors);
		$this->assertArrayHasKey('field', $errors);
		$this->assertArrayHasKey('operator', $errors);
		$this->assertArrayHasKey('value', $errors);
		$this->assertArrayHasKey('recipient', $errors);
		$this->assertArrayHasKey('systemOrder', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\FormConditionalRecipientsTable::validationDefault()
	 */
	public function testEntityValidationFieldLength(): void {
		$data = [
			'formId' => 123456789123, // exceeds 11 char limit
			'field' => str_repeat('a', 51), // exceeds 50 char limit
			'value' => str_repeat('b', 256), // exceeds 255 char limit
			'recipient' => str_repeat('c', 256), // exceeds 255 char limit
		];

		$entity = $this->formConditionalRecipientsTable->newEntity($data, ['guard' => false]);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('formId', $errors);
		$this->assertArrayHasKey('field', $errors);
		$this->assertArrayHasKey('value', $errors);
		$this->assertArrayHasKey('recipient', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\FormConditionalRecipientsTable::validationDefault()
	 */
	public function testEntityValidationNotBlank(): void {
		$data = [
			'formId' => 1,
			'type' => '   ', // only whitespace
			'field' => '   ', // only whitespace
			'operator' => ComparisonOperator::Equal,
			'value' => '', // empty string is allowed
			'recipient' => '   ', // only whitespace
		];

		$entity = $this->formConditionalRecipientsTable->newEntity($data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('type', $errors);
		$this->assertArrayHasKey('field', $errors);
		$this->assertArrayHasKey('recipient', $errors);
		$this->assertArrayNotHasKey('value', $errors); // value allows empty string
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\FormConditionalRecipientsTable::validationDefault()
	 */
	public function testEntityValidationTypeInList(): void {
		// Test valid types
		$validTypes = ['current_page', 'element_identifier'];

		foreach ($validTypes as $type) {
			$data = [
				'formId' => 1,
				'type' => $type,
				'field' => 'test_field',
				'operator' => ComparisonOperator::Equal,
				'value' => 'test_value',
				'recipient' => 'test@example.com',
			];

			$entity = $this->formConditionalRecipientsTable->newEntity($data);
			$errors = $entity->getErrors();

			$this->assertArrayNotHasKey('type', $errors);
		}

		// Test invalid type
		$data = [
			'formId' => 1,
			'type' => 'invalid_type',
			'field' => 'test_field',
			'operator' => ComparisonOperator::Equal,
			'value' => 'test_value',
			'recipient' => 'test@example.com',
		];

		$entity = $this->formConditionalRecipientsTable->newEntity($data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('type', $errors);
		$this->assertArrayHasKey('inList', $errors['type']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\FormConditionalRecipientsTable::validationDefault()
	 */
	public function testEntityValidationOperatorEnum(): void {
		// Test valid operators
		$validOperators = [
			ComparisonOperator::Equal,
			ComparisonOperator::NotEqual,
			ComparisonOperator::Contains,
			ComparisonOperator::NotContains,
			ComparisonOperator::StartsWith,
			ComparisonOperator::NotStartsWith,
			ComparisonOperator::EndsWith,
			ComparisonOperator::NotEndsWith,
			ComparisonOperator::In,
			ComparisonOperator::NotIn,
			ComparisonOperator::LessThan,
			ComparisonOperator::LessThanOrEqual,
			ComparisonOperator::GreaterThan,
			ComparisonOperator::GreaterThanOrEqual,
			ComparisonOperator::Between,
			ComparisonOperator::NotBetween,
			ComparisonOperator::LengthEqual,
			ComparisonOperator::LengthNotEqual,
			ComparisonOperator::ShorterThan,
			ComparisonOperator::ShorterThanOrEqual,
			ComparisonOperator::LongerThan,
			ComparisonOperator::LongerThanOrEqual,
			ComparisonOperator::Regexp,
		];

		foreach ($validOperators as $operator) {
			$data = [
				'formId' => 1,
				'type' => 'element_identifier',
				'field' => 'test_field',
				'operator' => $operator,
				'value' => 'test_value',
				'recipient' => 'test@example.com',
			];

			$entity = $this->formConditionalRecipientsTable->newEntity($data);
			$errors = $entity->getErrors();

			$this->assertArrayNotHasKey('operator', $errors);
		}
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\FormConditionalRecipientsTable::validationDefault()
	 */
	public function testEntityValidationAllowEmptyValue(): void {
		// Test that value field allows empty string
		$data = [
			'formId' => 1,
			'type' => 'element_identifier',
			'field' => 'test_field',
			'operator' => ComparisonOperator::Equal,
			'value' => '', // empty string should be allowed
			'recipient' => 'test@example.com',
		];

		$entity = $this->formConditionalRecipientsTable->newEntity($data);
		$errors = $entity->getErrors();

		$this->assertArrayNotHasKey('value', $errors, 'Empty value should be allowed');

		// Test with null value
		$data['value'] = null;
		$entity = $this->formConditionalRecipientsTable->newEntity($data);
		$errors = $entity->getErrors();

		$this->assertArrayNotHasKey('value', $errors, 'Null value should be allowed');
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\FormConditionalRecipientsTable::buildRules()
	 */
	public function testBuildRulesValidForm(): void {
		// Test with existing form
		$data = [
			'formId' => 1,
			'type' => 'element_identifier',
			'field' => 'test_field',
			'operator' => ComparisonOperator::Equal,
			'value' => 'test_value',
			'recipient' => 'test@example.com',
		];

		$entity = $this->formConditionalRecipientsTable->newEntity($data);
		$result = $this->formConditionalRecipientsTable->checkRules($entity);

		$this->assertTrue($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\FormConditionalRecipientsTable::buildRules()
	 */
	public function testBuildRulesInvalidForm(): void {
		// Test with non-existing form
		$data = [
			'formId' => 99999,
			'type' => 'element_identifier',
			'field' => 'test_field',
			'operator' => ComparisonOperator::Equal,
			'value' => 'test_value',
			'recipient' => 'test@example.com',
		];

		$entity = $this->formConditionalRecipientsTable->newEntity($data);
		$result = $this->formConditionalRecipientsTable->checkRules($entity);

		$this->assertFalse($result);

		$errors = $entity->getErrors();
		$this->assertArrayHasKey('formId', $errors);
		$this->assertArrayHasKey('formExists', $errors['formId']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\FormConditionalRecipientsTable::newDefaultEntity()
	 */
	public function testNewDefaultEntity(): void {
		$entity = $this->formConditionalRecipientsTable->newDefaultEntity();

		$this->assertInstanceOf(FormConditionalRecipient::class, $entity);
		$this->assertTrue($entity->isNew());

		// Check default values
		$this->assertNull($entity->formId);
		$this->assertNull($entity->type);
		$this->assertNull($entity->field);
		$this->assertNull($entity->operator);
		$this->assertNull($entity->value);
		$this->assertNull($entity->recipient);
		$this->assertSame(0, $entity->systemOrder);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\FormConditionalRecipientsTable::newDefaultEntity()
	 */
	public function testNewDefaultEntityWithData(): void {
		$additionalData = [
			'formId' => 1,
			'type' => 'element_identifier',
			'field' => 'custom_field',
			'operator' => ComparisonOperator::Contains,
			'value' => 'custom_value',
			'recipient' => 'custom@example.com',
			'systemOrder' => 5,
		];

		$entity = $this->formConditionalRecipientsTable->newDefaultEntity($additionalData);

		$this->assertInstanceOf(FormConditionalRecipient::class, $entity);
		$this->assertTrue($entity->isNew());

		// Check custom values
		$this->assertSame(1, $entity->formId);
		$this->assertSame('element_identifier', $entity->type);
		$this->assertSame('custom_field', $entity->field);
		$this->assertSame(ComparisonOperator::Contains, $entity->operator);
		$this->assertSame('custom_value', $entity->value);
		$this->assertSame('custom@example.com', $entity->recipient);
		$this->assertSame(5, $entity->systemOrder);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\FormConditionalRecipientsTable::$systemOrder
	 */
	public function testSystemOrderBehavior(): void {
		$this->assertTrue($this->formConditionalRecipientsTable->hasBehavior('SystemOrder'));

		$config = $this->formConditionalRecipientsTable->getBehavior('SystemOrder')->getConfig();

		$this->assertSame(['formId'], $config['relatedColumns']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\FormConditionalRecipientsTable::initializeSchema()
	 */
	public function testInitializeSchemaEnumColumn(): void {
		$schema = $this->formConditionalRecipientsTable->getSchema();

		// Test that operator column is configured as enum type
		$this->assertSame('enum-awyiss-model-enum-comparisonoperator', $schema->getColumnType('operator'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\FormConditionalRecipientsTable::validationDefault()
	 */
	public function testEntityValidationCurrentPageType(): void {
		// Test current_page type
		$data = [
			'formId' => 1,
			'type' => 'current_page',
			'field' => 'page_id',
			'operator' => ComparisonOperator::Equal,
			'value' => '123',
			'recipient' => 'page@example.com',
		];

		$entity = $this->formConditionalRecipientsTable->newEntity($data);
		$errors = $entity->getErrors();

		$this->assertEmpty($errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\FormConditionalRecipientsTable::validationDefault()
	 */
	public function testEntityValidationElementIdentifierType(): void {
		// Test element_identifier type
		$data = [
			'formId' => 1,
			'type' => 'element_identifier',
			'field' => 'email_field',
			'operator' => ComparisonOperator::NotEqual,
			'value' => 'unwanted@example.com',
			'recipient' => 'special@example.com',
		];

		$entity = $this->formConditionalRecipientsTable->newEntity($data);
		$errors = $entity->getErrors();

		$this->assertEmpty($errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\FormConditionalRecipientsTable::validationDefault()
	 */
	public function testEntityValidationComplexOperators(): void {
		// Test complex operators with different values
		$testCases = [
			[
				'operator' => ComparisonOperator::Between,
				'value' => '10,20',
				'description' => 'Between operator with range',
			],
			[
				'operator' => ComparisonOperator::In,
				'value' => 'value1,value2,value3',
				'description' => 'In operator with multiple values',
			],
			[
				'operator' => ComparisonOperator::Regexp,
				'value' => '/^[a-z]+$/',
				'description' => 'Regexp operator with pattern',
			],
			[
				'operator' => ComparisonOperator::LengthEqual,
				'value' => '10',
				'description' => 'Length equal operator with numeric value',
			],
		];

		foreach ($testCases as $testCase) {
			$data = [
				'formId' => 1,
				'type' => 'element_identifier',
				'field' => 'test_field',
				'operator' => $testCase['operator'],
				'value' => $testCase['value'],
				'recipient' => 'test@example.com',
			];

			$entity = $this->formConditionalRecipientsTable->newEntity($data);
			$errors = $entity->getErrors();

			$this->assertEmpty($errors);
		}
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\FormConditionalRecipientsTable::validationDefault()
	 */
	public function testEntityValidationRecipientEmailFormat(): void {
		// Test various email formats for recipient
		$validEmails = [
			'simple@example.com',
			'test.email@domain.co.uk',
			'user+tag@example.org',
			'complex.email-address@sub.domain.com',
		];

		foreach ($validEmails as $email) {
			$data = [
				'formId' => 1,
				'type' => 'element_identifier',
				'field' => 'test_field',
				'operator' => ComparisonOperator::Equal,
				'value' => 'test_value',
				'recipient' => $email,
			];

			$entity = $this->formConditionalRecipientsTable->newEntity($data);
			$errors = $entity->getErrors();

			$this->assertEmpty($errors);
		}
	}
}
