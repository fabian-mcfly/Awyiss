<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Model\Table;


use Awyiss\Awyiss;
use Awyiss\Core\LocalConfig;
use Awyiss\Model\Entity\FormElement;
use Awyiss\Model\Table\FormElementsTable;
use Awyiss\ORM\Association\BelongsTo;
use Awyiss\ORM\Association\HasMany;
use Awyiss\ORM\Association\HasOne;
use Awyiss\Test\TestSuite\TestCase;
use Awyiss\Utility\Content\ColumnSystem\AwyissColumn;
use Awyiss\Utility\Content\ColumnSystem\AwyissColumnSystem;
use Awyiss\Utility\Content\ColumnSystem\BootstrapColumnSystem;
use Awyiss\Validation\Validator;
use Cake\Datasource\FactoryLocator;


/**
 * FormElementsTable Test Case
 *
 * @see \Awyiss\Model\Table\FormElementsTable
 */
class FormElementsTableTest extends TestCase {
	/**
	 * @var \Awyiss\Model\Table\FormElementsTable
	 */
	protected FormElementsTable $formElementsTable;


	/**
	 * @inheritDoc
	 */
	protected function setUp(): void {
		parent::setUp();

		/** @noinspection PhpFieldAssignmentTypeMismatchInspection */
		$this->formElementsTable = FactoryLocator::get('Table')->get('FormElements');
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\FormElementsTable::ATTRIBUTABLE
	 */
	public function testAttributableConstant(): void {
		$this->assertFalse($this->formElementsTable::ATTRIBUTABLE);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\FormElementsTable::TABLE
	 */
	public function testTableConstant(): void {
		$this->assertEquals('form_elements', $this->formElementsTable::TABLE);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\FormElementsTable::initializeAssociations()
	 */
	public function testInitializeAssociations(): void {
		$this->assertCount(12, $this->formElementsTable->associations()->keys());

		// Test Forms association (BelongsTo)
		$this->assertTrue($this->formElementsTable->hasAssociation('Forms'));
		$formsAssociation = $this->formElementsTable->getAssociation('Forms');
		$this->assertInstanceOf(BelongsTo::class, $formsAssociation);
		$this->assertSame('formId', $formsAssociation->getForeignKey());
		$this->assertSame('INNER', $formsAssociation->getJoinType());

		// Test ParentFormElements association (BelongsTo from nest behavior)
		$this->assertTrue($this->formElementsTable->hasAssociation('ParentFormElements'));
		$parentFormElementsAssociation = $this->formElementsTable->getAssociation('ParentFormElements');
		$this->assertInstanceOf(BelongsTo::class, $parentFormElementsAssociation);
		$this->assertFalse($parentFormElementsAssociation->getCascadeCallbacks());
		$this->assertFalse($parentFormElementsAssociation->getDependent());

		// Test ChildFormElements association (HasMany from nest behavior)
		$this->assertTrue($this->formElementsTable->hasAssociation('ChildFormElements'));
		$childFormElementsAssociation = $this->formElementsTable->getAssociation('ChildFormElements');
		$this->assertInstanceOf(HasMany::class, $childFormElementsAssociation);
		$this->assertTrue($childFormElementsAssociation->getCascadeCallbacks());
		$this->assertTrue($childFormElementsAssociation->getDependent());

		// 'MediaAssignments' must also exist
		$this->assertTrue($this->formElementsTable->hasAssociation('MediaAssignments'));
		$mediaAssignmentsAssociation = $this->formElementsTable->getAssociation('MediaAssignments');
		$this->assertInstanceOf(HasMany::class, $mediaAssignmentsAssociation);
		$this->assertTrue($mediaAssignmentsAssociation->getCascadeCallbacks());
		$this->assertTrue($mediaAssignmentsAssociation->getDependent());

		// Test user tracking associations
		$this->assertTrue($this->formElementsTable->hasAssociation('CreatedByUser'));
		$createdByUserAssociation = $this->formElementsTable->getAssociation('CreatedByUser');
		$this->assertInstanceOf(BelongsTo::class, $createdByUserAssociation);
		$this->assertFalse($createdByUserAssociation->getCascadeCallbacks());
		$this->assertFalse($createdByUserAssociation->getDependent());

		$this->assertTrue($this->formElementsTable->hasAssociation('ChangedByUser'));
		$changedByUserAssociation = $this->formElementsTable->getAssociation('ChangedByUser');
		$this->assertInstanceOf(BelongsTo::class, $changedByUserAssociation);
		$this->assertFalse($changedByUserAssociation->getCascadeCallbacks());
		$this->assertFalse($changedByUserAssociation->getDependent());

		$this->assertTrue($this->formElementsTable->hasAssociation('DeletedByUser'));
		$deletedByUserAssociation = $this->formElementsTable->getAssociation('DeletedByUser');
		$this->assertInstanceOf(BelongsTo::class, $deletedByUserAssociation);
		$this->assertFalse($deletedByUserAssociation->getCascadeCallbacks());
		$this->assertFalse($deletedByUserAssociation->getDependent());

		// Test translation associations
		$this->assertTrue($this->formElementsTable->hasAssociation('FormElements_title_translation'));
		$titleTranslationAssociation = $this->formElementsTable->getAssociation('FormElements_title_translation');
		$this->assertInstanceOf(HasOne::class, $titleTranslationAssociation);
		$this->assertFalse($titleTranslationAssociation->getCascadeCallbacks());
		$this->assertFalse($titleTranslationAssociation->getDependent());

		$this->assertTrue($this->formElementsTable->hasAssociation('FormElements_titleEmail_translation'));
		$titleEmailTranslationAssociation = $this->formElementsTable->getAssociation('FormElements_titleEmail_translation');
		$this->assertInstanceOf(HasOne::class, $titleEmailTranslationAssociation);
		$this->assertFalse($titleEmailTranslationAssociation->getCascadeCallbacks());
		$this->assertFalse($titleEmailTranslationAssociation->getDependent());

		$this->assertTrue($this->formElementsTable->hasAssociation('FormElements_placeholder_translation'));
		$placeholderTranslationAssociation = $this->formElementsTable->getAssociation('FormElements_placeholder_translation');
		$this->assertInstanceOf(HasOne::class, $placeholderTranslationAssociation);
		$this->assertFalse($placeholderTranslationAssociation->getCascadeCallbacks());
		$this->assertFalse($placeholderTranslationAssociation->getDependent());

		$this->assertTrue($this->formElementsTable->hasAssociation('FormElements_text_translation'));
		$textTranslationAssociation = $this->formElementsTable->getAssociation('FormElements_text_translation');
		$this->assertInstanceOf(HasOne::class, $textTranslationAssociation);
		$this->assertFalse($textTranslationAssociation->getCascadeCallbacks());
		$this->assertFalse($textTranslationAssociation->getDependent());

		// Test I18n association
		$this->assertTrue($this->formElementsTable->hasAssociation('I18n'));
		$i18nAssociation = $this->formElementsTable->getAssociation('I18n');
		$this->assertInstanceOf(HasMany::class, $i18nAssociation);
		$this->assertFalse($i18nAssociation->getCascadeCallbacks());
		$this->assertTrue($i18nAssociation->getDependent());
		$this->assertEquals('append', $i18nAssociation->getSaveStrategy());
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\FormElementsTable::getAvailableTypes()
	 */
	public function testGetAvailableTypes(): void {
		$types = $this->formElementsTable->getAvailableTypes();

		$this->assertIsArray($types);
		$this->assertSame([
			'text',
			'textarea',
			'email',
			'url',
			'number',
			'tel',
			'date',
			'time',
			'datetime',
			'range',
			'checkbox',
			'radio',
			'select',
			'selectMultiple',
			'file',
			'hidden',
			'fieldset',
			'freeText',
			'submit',
		], $types);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\FormElementsTable::getAvailableTypes()
	 */
	public function testGetAvailableTypesTranslated(): void {
		$types = $this->formElementsTable->getAvailableTypes(true);

		$this->assertSame([
			'text' => 'FormElements::type_text',
			'textarea' => 'FormElements::type_textarea',
			'email' => 'FormElements::type_email',
			'url' => 'FormElements::type_url',
			'number' => 'FormElements::type_number',
			'tel' => 'FormElements::type_tel',
			'date' => 'FormElements::type_date',
			'time' => 'FormElements::type_time',
			'datetime' => 'FormElements::type_datetime',
			'range' => 'FormElements::type_range',
			'checkbox' => 'FormElements::type_checkbox',
			'radio' => 'FormElements::type_radio',
			'select' => 'FormElements::type_select',
			'selectMultiple' => 'FormElements::type_select_multiple',
			'file' => 'FormElements::type_file',
			'hidden' => 'FormElements::type_hidden',
			'fieldset' => 'FormElements::type_fieldset',
			'freeText' => 'FormElements::type_free_text',
			'submit' => 'FormElements::type_submit',
		], $types);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\FormElementsTable::getColumnSystemClass()
	 */
	public function testGetColumnSystemClass(): void {
		$columnSystemClass = $this->formElementsTable->getColumnSystemClass();

		$this->assertSame(AwyissColumnSystem::class, $columnSystemClass);
		$this->assertTrue(class_exists($columnSystemClass));

		// Test with different column system
		$maxDenominator = BootstrapColumnSystem::getMaxDenominator();
		LocalConfig::write('columnSystem.className', BootstrapColumnSystem::class, 'Contents');
		$formElementsTable = new FormElementsTable();

		$columnSystemClass = $formElementsTable->getColumnSystemClass();
		BootstrapColumnSystem::setMaxDenominator($maxDenominator);
		$this->assertSame(BootstrapColumnSystem::class, $columnSystemClass);
		$this->assertTrue(class_exists($columnSystemClass));
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\FormElementsTable::getColumnWidths()
	 */
	public function testGetColumnWidths(): void {
		$columnWidths = $this->formElementsTable->getColumnWidths();

		$this->assertIsArray($columnWidths);
		$this->assertCount(10, $columnWidths);
		$this->assertSame([
			'1/1',
			'1/5',
			'1/4',
			'1/3',
			'2/5',
			'1/2',
			'3/5',
			'2/3',
			'3/4',
			'4/5',
		], array_keys($columnWidths));

		// Test that all values are column width objects
		foreach ($columnWidths as $key => $value) {
			$this->assertIsString($key);
			$this->assertInstanceOf(AwyissColumn::class, $value);
		}
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\FormElementsTable::getColumnIndents()
	 */
	public function testGetColumnIndents(): void {
		$columnIndents = $this->formElementsTable->getColumnIndents();

		$this->assertIsArray($columnIndents);
		$this->assertCount(9, $columnIndents);
		$this->assertSame([
			'1/5',
			'1/4',
			'1/3',
			'2/5',
			'1/2',
			'3/5',
			'2/3',
			'3/4',
			'4/5',
		], array_keys($columnIndents));

		// Test that all values are column indent objects
		foreach ($columnIndents as $key => $value) {
			$this->assertIsString($key);
			$this->assertInstanceOf(AwyissColumn::class, $value);
		}
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\FormElementsTable::validationDefault()
	 */
	public function testValidationDefault(): void {
		$validator = new Validator();
		$result = $this->formElementsTable->validationDefault($validator);

		$this->assertInstanceOf(Validator::class, $result);
		$this->assertSame('FormElements', $result->getI18nDomain());

		// Test required fields
		$this->assertTrue($result->hasField('formId'));
		$this->assertSame('create', $result->field('formId')->isPresenceRequired());

		$this->assertTrue($result->hasField('type'));
		$this->assertSame('create', $result->field('type')->isPresenceRequired());

		// Test conditionally required fields
		$this->assertTrue($result->hasField('title'));
		$this->assertTrue($result->hasField('identifier'));

		// Test other fields
		$this->assertTrue($result->hasField('id'));
		$this->assertTrue($result->hasField('parentId'));
		$this->assertTrue($result->hasField('titleEmail'));
		$this->assertTrue($result->hasField('placeholder'));
		$this->assertTrue($result->hasField('text'));
		$this->assertTrue($result->hasField('options'));
		$this->assertTrue($result->hasField('columnWidth'));
		$this->assertTrue($result->hasField('columnIndent'));
		$this->assertTrue($result->hasField('columnLast'));
		$this->assertTrue($result->hasField('columnRtl'));
		$this->assertTrue($result->hasField('cssClass'));
		$this->assertTrue($result->hasField('required'));
		$this->assertTrue($result->hasField('systemOrder'));
		$this->assertTrue($result->hasField('active'));
		$this->assertTrue($result->hasField('deleted'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\FormElementsTable::validationDefault()
	 */
	public function testEntityValidationSuccess(): void {
		$data = [
			'formId' => 1,
			'type' => 'text',
			'identifier' => 'testField',
			'title' => 'Test Field',
			'titleEmail' => 'Email Title',
			'placeholder' => 'Enter text',
			'text' => 'Help text',
			'options' => [['key' => 'value', 'value' => 'Display']],
			'columnWidth' => '1/2',
			'columnIndent' => '1/4',
			'columnLast' => false,
			'columnRtl' => false,
			'cssClass' => 'test-class',
			'required' => true,
			'systemOrder' => 1,
			'active' => true,
			'deleted' => false,
		];

		$entity = $this->formElementsTable->newEntity($data);
		$errors = $entity->getErrors();

		$this->assertEmpty($errors, 'Valid data should not produce validation errors');
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\FormElementsTable::validationDefault()
	 */
	public function testEntityValidationMissingRequired(): void {
		$data = [
			'title' => 'Test Field',
		];

		$entity = $this->formElementsTable->newEntity($data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('formId', $errors);
		$this->assertArrayHasKey('type', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\FormElementsTable::validationDefault()
	 */
	public function testEntityValidationConditionalRequiredTitle(): void {
		// Title is required for all types except 'freeText'
		$data = [
			'formId' => 1,
			'type' => 'text',
			'identifier' => 'testField',
		];

		$entity = $this->formElementsTable->newEntity($data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('title', $errors);

		// Title not required for free_text
		$data['type'] = 'freeText';
		unset($data['identifier']); // identifier also not required for free_text

		$entity = $this->formElementsTable->newEntity($data);
		$errors = $entity->getErrors();

		$this->assertArrayNotHasKey('title', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\FormElementsTable::validationDefault()
	 */
	public function testEntityValidationConditionalRequiredIdentifier(): void {
		// Identifier is required for all types except 'freeText' and 'submit'
		$data = [
			'formId' => 1,
			'type' => 'text',
			'title' => 'Test Field',
		];

		$entity = $this->formElementsTable->newEntity($data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('identifier', $errors);

		// Identifier not required for freeText
		$data['type'] = 'freeText';
		unset($data['title']); // title also not required for freeText

		$entity = $this->formElementsTable->newEntity($data);
		$errors = $entity->getErrors();

		$this->assertArrayNotHasKey('identifier', $errors);

		// Identifier not required for submit
		$data = [
			'formId' => 1,
			'type' => 'submit',
			'title' => 'Submit Button',
		];

		$entity = $this->formElementsTable->newEntity($data);
		$errors = $entity->getErrors();

		$this->assertArrayNotHasKey('identifier', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\FormElementsTable::validationDefault()
	 */
	public function testEntityValidationInvalidTypes(): void {
		$data = [
			'id' => 'not_an_integer',
			'formId' => 'not_an_integer',
			'parentId' => 'not_an_integer',
			'type' => true,
			'identifier' => true,
			'title' => true,
			'titleEmail' => true,
			'placeholder' => true,
			'text' => true,
			'options' => 'not_an_array',
			'columnLast' => 'not_a_boolean',
			'columnRtl' => 'not_a_boolean',
			'cssClass' => true,
			'required' => 'not_a_boolean',
			'systemOrder' => 'not_an_integer',
			'active' => 'not_a_boolean',
			'deleted' => 'not_a_boolean',
		];

		$entity = $this->formElementsTable->newEntity($data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('id', $errors);
		$this->assertArrayHasKey('formId', $errors);
		$this->assertArrayHasKey('parentId', $errors);
		$this->assertArrayHasKey('type', $errors);
		$this->assertArrayHasKey('identifier', $errors);
		$this->assertArrayHasKey('title', $errors);
		$this->assertArrayHasKey('titleEmail', $errors);
		$this->assertArrayHasKey('placeholder', $errors);
		$this->assertArrayHasKey('text', $errors);
		$this->assertArrayHasKey('options', $errors);
		$this->assertArrayHasKey('columnLast', $errors);
		$this->assertArrayHasKey('columnRtl', $errors);
		$this->assertArrayHasKey('cssClass', $errors);
		$this->assertArrayHasKey('required', $errors);
		$this->assertArrayHasKey('systemOrder', $errors);
		$this->assertArrayHasKey('active', $errors);
		$this->assertArrayHasKey('deleted', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\FormElementsTable::validationDefault()
	 */
	public function testEntityValidationFieldLength(): void {
		$data = [
			'id' => 123456789123, // exceeds 11 char limit
			'formId' => 123456789123, // exceeds 11 char limit
			'parentId' => 123456789123, // exceeds 11 char limit
			'type' => str_repeat('a', 21), // exceeds 20 char limit
			'identifier' => str_repeat('b', 51), // exceeds 50 char limit
			'title' => str_repeat('c', 101), // exceeds 100 char limit
			'titleEmail' => str_repeat('d', 101), // exceeds 100 char limit
			'placeholder' => str_repeat('e', 101), // exceeds 100 char limit
			'text' => str_repeat('f', 65536), // exceeds 65535 byte limit
			'cssClass' => str_repeat('g', 256), // exceeds 255 char limit
		];

		$entity = $this->formElementsTable->newEntity($data, ['guard' => false]);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('id', $errors);
		$this->assertArrayHasKey('formId', $errors);
		$this->assertArrayHasKey('parentId', $errors);
		$this->assertArrayHasKey('type', $errors);
		$this->assertArrayHasKey('identifier', $errors);
		$this->assertArrayHasKey('title', $errors);
		$this->assertArrayHasKey('titleEmail', $errors);
		$this->assertArrayHasKey('placeholder', $errors);
		$this->assertArrayHasKey('text', $errors);
		$this->assertArrayHasKey('cssClass', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\FormElementsTable::validationDefault()
	 */
	public function testEntityValidationNotBlank(): void {
		$data = [
			'formId' => 1,
			'type' => '   ', // only whitespace
			'identifier' => '   ', // only whitespace
			'title' => '   ', // only whitespace
		];

		$entity = $this->formElementsTable->newEntity($data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('type', $errors);
		$this->assertArrayHasKey('identifier', $errors);
		$this->assertArrayHasKey('title', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\FormElementsTable::validationDefault()
	 */
	public function testEntityValidationColumnWidthInList(): void {
		// Test valid column width
		$data = [
			'formId' => 1,
			'type' => 'text',
			'identifier' => 'testField',
			'title' => 'Test Field',
			'columnWidth' => '3/5',
		];

		$entity = $this->formElementsTable->newEntity($data);
		$errors = $entity->getErrors();

		$this->assertArrayNotHasKey('columnWidth', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\FormElementsTable::validationDefault()
	 */
	public function testEntityValidationColumnWidthNotInList(): void {
		// Test invalid column width
		$data = [
			'formId' => 1,
			'type' => 'text',
			'identifier' => 'testField',
			'title' => 'Test Field',
			'columnWidth' => 'invalid_column_width',
		];

		$entity = $this->formElementsTable->newEntity($data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('columnWidth', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\FormElementsTable::validationDefault()
	 */
	public function testEntityValidationColumnIndentInList(): void {
		// Test valid column indent
		$data = [
			'formId' => 1,
			'type' => 'text',
			'identifier' => 'testField',
			'title' => 'Test Field',
			'columnIndent' => '2/5',
		];

		$entity = $this->formElementsTable->newEntity($data);
		$errors = $entity->getErrors();

		$this->assertArrayNotHasKey('columnIndent', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\FormElementsTable::validationDefault()
	 */
	public function testEntityValidationColumnIndentNotInList(): void {
		// Test invalid column indent
		$data = [
			'formId' => 1,
			'type' => 'text',
			'identifier' => 'testField',
			'title' => 'Test Field',
			'columnIndent' => 'invalid_column_indent',
		];

		$entity = $this->formElementsTable->newEntity($data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('columnIndent', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\FormElementsTable::validationDefault()
	 */
	public function testEntityValidationOptionsArrayMaxLength(): void {
		$largeOptions = array_fill(0, 10000, ['key' => str_repeat('x', 100), 'value' => str_repeat('y', 100)]);

		$data = [
			'formId' => 1,
			'type' => 'select',
			'identifier' => 'testField',
			'title' => 'Test Field',
			'options' => $largeOptions,
		];

		$entity = $this->formElementsTable->newEntity($data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('options', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\FormElementsTable::buildRules()
	 */
	public function testBuildRulesUniqueIdentifier(): void {
		// Test with existing identifier/form combination (should fail)
		$data = [
			'formId' => 1,
			'type' => 'text',
			'identifier' => 'vorname', // This identifier already exists for form 1 in fixtures
			'title' => 'Test Field',
		];

		$entity = $this->formElementsTable->newEntity($data);
		$result = $this->formElementsTable->checkRules($entity);

		$this->assertFalse($result);

		$errors = $entity->getErrors();
		$this->assertArrayHasKey('identifier', $errors);
		$this->assertArrayHasKey('identifierUnique', $errors['identifier']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\FormElementsTable::buildRules()
	 */
	public function testBuildRulesValidInputType(): void {
		// Test with valid input type
		$data = [
			'formId' => 1,
			'type' => 'text',
			'identifier' => 'uniqueField',
			'title' => 'Test Field',
		];

		$entity = $this->formElementsTable->newEntity($data);
		$result = $this->formElementsTable->checkRules($entity);

		$this->assertTrue($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\FormElementsTable::buildRules()
	 */
	public function testBuildRulesInvalidInputType(): void {
		// Test with invalid input type
		$data = [
			'formId' => 1,
			'type' => 'invalid_type',
			'identifier' => 'uniqueField',
			'title' => 'Test Field',
		];

		$entity = $this->formElementsTable->newEntity($data);
		$result = $this->formElementsTable->checkRules($entity);

		$this->assertFalse($result);

		$errors = $entity->getErrors();
		$this->assertArrayHasKey('type', $errors);
		$this->assertArrayHasKey('validInputType', $errors['type']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\FormElementsTable::buildRules()
	 */
	public function testBuildRulesValidWidthIndentCombination(): void {
		// Test valid width/indent combination
		$data = [
			'formId' => 1,
			'type' => 'text',
			'identifier' => 'uniqueField',
			'title' => 'Test Field',
			'columnWidth' => '3/5',
			'columnIndent' => '2/5',
		];

		$entity = $this->formElementsTable->newEntity($data);
		$result = $this->formElementsTable->checkRules($entity);

		$this->assertTrue($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\FormElementsTable::buildRules()
	 */
	public function testBuildRulesInvalidWidthIndentCombination(): void {
		// Test invalid width/indent combination
		$data = [
			'formId' => 1,
			'type' => 'text',
			'identifier' => 'uniqueField',
			'title' => 'Test Field',
			'columnWidth' => '3/5',
			'columnIndent' => '3/5', // Invalid combination (should not exceed 1)
		];

		$entity = $this->formElementsTable->newEntity($data);
		$result = $this->formElementsTable->checkRules($entity);

		$this->assertFalse($result);

		$errors = $entity->getErrors();
		$this->assertArrayHasKey('_general', $errors);
		$this->assertArrayHasKey('validWidthIndentCombination', $errors['_general']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\FormElementsTable::newDefaultEntity()
	 */
	public function testNewDefaultEntity(): void {
		$entity = $this->formElementsTable->newDefaultEntity();

		$this->assertInstanceOf(FormElement::class, $entity);
		$this->assertTrue($entity->isNew());

		// Check default values
		$this->assertNull($entity->formId);
		$this->assertNull($entity->parentId);
		$this->assertSame('text', $entity->type);
		$this->assertNull($entity->identifier);
		$this->assertNull($entity->title);
		$this->assertNull($entity->titleEmail);
		$this->assertNull($entity->placeholder);
		$this->assertNull($entity->text);
		$this->assertNull($entity->options);
		$this->assertSame('1/1', $entity->columnWidth);
		$this->assertNull($entity->columnIndent);
		$this->assertFalse($entity->columnLast);
		$this->assertFalse($entity->columnRtl);
		$this->assertNull($entity->cssClass);
		$this->assertFalse($entity->required);
		$this->assertSame(0, $entity->systemOrder);
		$this->assertTrue($entity->active);
		$this->assertFalse($entity->deleted);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\FormElementsTable::newDefaultEntity()
	 */
	public function testNewDefaultEntityWithData(): void {
		$additionalData = [
			'formId' => 1,
			'type' => 'text',
			'identifier' => 'customField',
			'title' => 'Custom Field',
			'titleEmail' => 'Custom Email Title',
			'placeholder' => 'Custom placeholder',
			'text' => 'Custom help text',
			'options' => [['key' => 'value', 'value' => 'Display']],
			'columnWidth' => '1/2',
			'columnIndent' => '1/4',
			'columnLast' => true,
			'columnRtl' => true,
			'cssClass' => 'custom-class',
			'required' => true,
			'systemOrder' => 5,
			'active' => false,
		];

		$entity = $this->formElementsTable->newDefaultEntity($additionalData);

		$this->assertInstanceOf(FormElement::class, $entity);
		$this->assertTrue($entity->isNew());

		// Check custom values
		$this->assertSame(1, $entity->formId);
		$this->assertSame('text', $entity->type);
		$this->assertSame('customField', $entity->identifier);
		$this->assertSame('Custom Field', $entity->title);
		$this->assertSame('Custom Email Title', $entity->titleEmail);
		$this->assertSame('Custom placeholder', $entity->placeholder);
		$this->assertSame('Custom help text', $entity->text);
		$this->assertSame([['key' => 'value', 'value' => 'Display']], $entity->options);
		$this->assertSame('1/2', $entity->columnWidth);
		$this->assertSame('1/4', $entity->columnIndent);
		$this->assertTrue($entity->columnLast);
		$this->assertTrue($entity->columnRtl);
		$this->assertSame('custom-class', $entity->cssClass);
		$this->assertTrue($entity->required);
		$this->assertSame(5, $entity->systemOrder);
		$this->assertFalse($entity->active);

		// Check that defaults are preserved
		$this->assertFalse($entity->deleted);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\FormElementsTable::$categories
	 */
	public function testCategoriesBehavior(): void {
		$this->assertTrue($this->formElementsTable->hasBehavior('Categories'));

		$config = $this->formElementsTable->getBehavior('Categories')->getConfig();

		$this->assertFalse($config['allowAggregation']);
		$this->assertTrue($config['enabled']);
		$this->assertSame('Forms', $config['associationName']);
		$this->assertSame('form', $config['identifier']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\FormElementsTable::$nest
	 */
	public function testNestBehavior(): void {
		$this->assertTrue($this->formElementsTable->hasBehavior('Nest'));

		$config = $this->formElementsTable->getBehavior('Nest')->getConfig();

		$this->assertTrue($config['enabled']);
		$this->assertSame(['formId'], $config['relatedColumns']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\FormElementsTable::$search
	 */
	public function testSearchBehavior(): void {
		$this->assertTrue($this->formElementsTable->hasBehavior('Search'));

		$config = $this->formElementsTable->getBehavior('Search')->getConfig();

		$this->assertSame(['formId'], $config['blocklistedColumns']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\FormElementsTable::$systemOrder
	 */
	public function testSystemOrderBehavior(): void {
		$this->assertTrue($this->formElementsTable->hasBehavior('SystemOrder'));

		$config = $this->formElementsTable->getBehavior('SystemOrder')->getConfig();

		$this->assertSame(['formId', 'parentId'], $config['relatedColumns']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\FormElementsTable::$translate
	 */
	public function testTranslateBehavior(): void {
		$this->assertTrue($this->formElementsTable->hasBehavior('Translate'));

		$config = $this->formElementsTable->getBehavior('Translate')->getConfig();

		$this->assertSame(Awyiss::REALM_FRONTEND, $config['realm']);

		$this->assertIsArray($config['fields']);
		$this->assertSame([
			'title',
			'titleEmail',
			'placeholder',
			'text',
		], $config['fields']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\FormElementsTable::initializeSchema()
	 */
	public function testInitializeSchemaJsonColumns(): void {
		$schema = $this->formElementsTable->getSchema();

		// Test that options column is configured as JSON type
		$this->assertSame('json', $schema->getColumnType('options'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\FormElementsTable::disableCascadeCallbacks()
	 * @noinspection PhpConditionAlreadyCheckedInspection
	 */
	public function testDisableCascadeCallbacks(): void {
		$association = $this->formElementsTable->getAssociation('ChildFormElements');
		// Ensure cascade callbacks are enabled by default
		$this->assertTrue($association->getCascadeCallbacks());
		$this->assertTrue($association->getDependent());

		// Disable cascade callbacks
		$this->formElementsTable->disableCascadeCallbacks();

		// Check if cascade callbacks are disabled
		$this->assertFalse($association->getCascadeCallbacks());
		$this->assertFalse($association->getDependent());
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\FormElementsTable::enableCascadeCallbacks()
	 * @noinspection PhpConditionAlreadyCheckedInspection
	 */
	public function testEnableCascadeCallbacks(): void {
		$association = $this->formElementsTable->getAssociation('ChildFormElements');

		// Disable cascade callbacks first
		$this->formElementsTable->disableCascadeCallbacks();

		$this->assertFalse($association->getCascadeCallbacks());
		$this->assertFalse($association->getDependent());

		// Enable cascade callbacks
		$this->formElementsTable->enableCascadeCallbacks();

		// Check if cascade callbacks are enabled
		$this->assertTrue($association->getCascadeCallbacks());
		$this->assertTrue($association->getDependent());
	}
}
