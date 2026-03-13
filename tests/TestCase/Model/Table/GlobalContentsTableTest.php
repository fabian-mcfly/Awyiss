<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Model\Table;


use Awyiss\Awyiss;
use Awyiss\Core\LocalConfig;
use Awyiss\Model\Entity\GlobalContent;
use Awyiss\Model\Table\GlobalContentsTable;
use Awyiss\ORM\Association\BelongsTo;
use Awyiss\ORM\Association\HasMany;
use Awyiss\ORM\Association\HasOne;
use Awyiss\Test\TestSuite\TestCase;
use Awyiss\Utility\Content\AwyissColumn;
use Awyiss\Utility\Content\AwyissColumnSystem;
use Awyiss\Utility\Content\BootstrapColumnSystem;
use Awyiss\Validation\Validator;
use Cake\Collection\CollectionInterface;
use Cake\Datasource\FactoryLocator;
use Customer\Model\Entity\AttributesGlobalContent;


/**
 * GlobalContentsTable Test Case
 *
 * @see \Awyiss\Model\Table\GlobalContentsTable
 */
class GlobalContentsTableTest extends TestCase {
	/**
	 * @var \Awyiss\Model\Table\GlobalContentsTable
	 */
	protected GlobalContentsTable $globalContentsTable;


	/**
	 * @inheritDoc
	 */
	protected function setUp(): void {
		parent::setUp();

		/** @noinspection PhpFieldAssignmentTypeMismatchInspection */
		$this->globalContentsTable = FactoryLocator::get('Table')->get('GlobalContents');
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\UsergroupsUsersTable::ATTRIBUTABLE
	 */
	public function testAttributableConstant(): void {
		/** @noinspection PhpUnitAssertTrueWithIncompatibleTypeArgumentInspection */
		$this->assertTrue($this->globalContentsTable::ATTRIBUTABLE);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\GlobalContentsTable::TABLE
	 */
	public function testTableConstant(): void {
		$this->assertEquals('global_contents', $this->globalContentsTable::TABLE);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\GlobalContentsTable::initializeAssociations()
	 */
	public function testInitializeAssociations(): void {
		$this->assertCount(17, $this->globalContentsTable->associations()->keys());

		$this->assertTrue($this->globalContentsTable->hasAssociation('AttributesGlobalContents'));
		$attributesGlobalContentsAssociation = $this->globalContentsTable->getAssociation('AttributesGlobalContents');
		$this->assertInstanceOf(HasOne::class, $attributesGlobalContentsAssociation);
		$this->assertTrue($attributesGlobalContentsAssociation->getCascadeCallbacks());
		$this->assertTrue($attributesGlobalContentsAssociation->getDependent());

		$this->assertTrue($this->globalContentsTable->hasAssociation('Forms'));
		$formsAssociation = $this->globalContentsTable->getAssociation('Forms');
		$this->assertInstanceOf(BelongsTo::class, $formsAssociation);
		$this->assertFalse($formsAssociation->getCascadeCallbacks());
		$this->assertFalse($formsAssociation->getDependent());

		// 'CustomerGroupAccessSettings' must also exist
		$this->assertTrue($this->globalContentsTable->hasAssociation('CustomerGroupAccessSettings'));
		$customerGroupAccessSettingsAssociation = $this->globalContentsTable->getAssociation('CustomerGroupAccessSettings');
		$this->assertInstanceOf(HasOne::class, $customerGroupAccessSettingsAssociation);
		$this->assertTrue($customerGroupAccessSettingsAssociation->getCascadeCallbacks());
		$this->assertTrue($customerGroupAccessSettingsAssociation->getDependent());

		// 'CustomerGroupAssignments' must also exist
		$this->assertTrue($this->globalContentsTable->hasAssociation('CustomerGroupAssignments'));
		$customerGroupAssignmentsAssociation = $this->globalContentsTable->getAssociation('CustomerGroupAssignments');
		$this->assertInstanceOf(HasMany::class, $customerGroupAssignmentsAssociation);
		$this->assertTrue($customerGroupAssignmentsAssociation->getCascadeCallbacks());
		$this->assertTrue($customerGroupAssignmentsAssociation->getDependent());

		// 'MediaAssignments' must also exist
		$this->assertTrue($this->globalContentsTable->hasAssociation('MediaAssignments'));
		$mediaAssignmentsAssociation = $this->globalContentsTable->getAssociation('MediaAssignments');
		$this->assertInstanceOf(HasMany::class, $mediaAssignmentsAssociation);
		$this->assertTrue($mediaAssignmentsAssociation->getCascadeCallbacks());
		$this->assertTrue($mediaAssignmentsAssociation->getDependent());

		$this->assertTrue($this->globalContentsTable->hasAssociation('Surveys'));
		$surveysAssociation = $this->globalContentsTable->getAssociation('Surveys');
		$this->assertInstanceOf(BelongsTo::class, $surveysAssociation);
		$this->assertFalse($surveysAssociation->getCascadeCallbacks());
		$this->assertFalse($surveysAssociation->getDependent());

		$this->assertTrue($this->globalContentsTable->hasAssociation('GlobalContentTemplates'));
		$globalContentsTemplatesAssociation = $this->globalContentsTable->getAssociation('GlobalContentTemplates');
		$this->assertInstanceOf(BelongsTo::class, $globalContentsTemplatesAssociation);
		$this->assertFalse($globalContentsTemplatesAssociation->getCascadeCallbacks());
		$this->assertFalse($globalContentsTemplatesAssociation->getDependent());

		// 'ParentGlobalContents' must also exist (from parent table implementation)
		$this->assertTrue($this->globalContentsTable->hasAssociation('ParentGlobalContents'));
		$parentGlobalContentsAssociation = $this->globalContentsTable->getAssociation('ParentGlobalContents');
		$this->assertInstanceOf(BelongsTo::class, $parentGlobalContentsAssociation);
		$this->assertFalse($parentGlobalContentsAssociation->getCascadeCallbacks());
		$this->assertFalse($parentGlobalContentsAssociation->getDependent());

		// 'ChildGlobalContents' must also exist (from parent table implementation)
		$this->assertTrue($this->globalContentsTable->hasAssociation('ChildGlobalContents'));
		$childGlobalContentsAssociation = $this->globalContentsTable->getAssociation('ChildGlobalContents');
		$this->assertInstanceOf(HasMany::class, $childGlobalContentsAssociation);
		$this->assertTrue($childGlobalContentsAssociation->getCascadeCallbacks());
		$this->assertTrue($childGlobalContentsAssociation->getDependent());

		// 'CreatedByUser' must also exist
		$this->assertTrue($this->globalContentsTable->hasAssociation('CreatedByUser'));
		$createdByUserAssociation = $this->globalContentsTable->getAssociation('CreatedByUser');
		$this->assertInstanceOf(BelongsTo::class, $createdByUserAssociation);
		$this->assertFalse($createdByUserAssociation->getCascadeCallbacks());
		$this->assertFalse($createdByUserAssociation->getDependent());

		// 'ChangedByUser' must also exist
		$this->assertTrue($this->globalContentsTable->hasAssociation('ChangedByUser'));
		$changedByUserAssociation = $this->globalContentsTable->getAssociation('ChangedByUser');
		$this->assertInstanceOf(BelongsTo::class, $changedByUserAssociation);
		$this->assertFalse($changedByUserAssociation->getCascadeCallbacks());
		$this->assertFalse($changedByUserAssociation->getDependent());

		// 'DeletedByUser' must also exist
		$this->assertTrue($this->globalContentsTable->hasAssociation('DeletedByUser'));
		$deletedByUserAssociation = $this->globalContentsTable->getAssociation('DeletedByUser');
		$this->assertInstanceOf(BelongsTo::class, $deletedByUserAssociation);
		$this->assertFalse($deletedByUserAssociation->getCascadeCallbacks());
		$this->assertFalse($deletedByUserAssociation->getDependent());

		// 'GlobalContents_title_translation' must also exist
		$this->assertTrue($this->globalContentsTable->hasAssociation('GlobalContents_title_translation'));
		$titleTranslationAssociation = $this->globalContentsTable->getAssociation('GlobalContents_title_translation');
		$this->assertInstanceOf(HasOne::class, $titleTranslationAssociation);
		$this->assertFalse($titleTranslationAssociation->getCascadeCallbacks());
		$this->assertFalse($titleTranslationAssociation->getDependent());

		// 'GlobalContents_subtitle_translation' must also exist
		$this->assertTrue($this->globalContentsTable->hasAssociation('GlobalContents_subtitle_translation'));
		$subtitleTranslationAssociation = $this->globalContentsTable->getAssociation('GlobalContents_subtitle_translation');
		$this->assertInstanceOf(HasOne::class, $subtitleTranslationAssociation);
		$this->assertFalse($subtitleTranslationAssociation->getCascadeCallbacks());
		$this->assertFalse($subtitleTranslationAssociation->getDependent());

		// 'GlobalContents_text_translation' must also exist
		$this->assertTrue($this->globalContentsTable->hasAssociation('GlobalContents_text_translation'));
		$textTranslationAssociation = $this->globalContentsTable->getAssociation('GlobalContents_text_translation');
		$this->assertInstanceOf(HasOne::class, $textTranslationAssociation);
		$this->assertFalse($textTranslationAssociation->getCascadeCallbacks());
		$this->assertFalse($textTranslationAssociation->getDependent());

		// 'GlobalContents_link_translation' must also exist
		$this->assertTrue($this->globalContentsTable->hasAssociation('GlobalContents_link_translation'));
		$linkTranslationAssociation = $this->globalContentsTable->getAssociation('GlobalContents_link_translation');
		$this->assertInstanceOf(HasOne::class, $linkTranslationAssociation);
		$this->assertFalse($linkTranslationAssociation->getCascadeCallbacks());
		$this->assertFalse($linkTranslationAssociation->getDependent());

		// 'I18n' must also exist
		$this->assertTrue($this->globalContentsTable->hasAssociation('I18n'));
		$i18nAssociation = $this->globalContentsTable->getAssociation('I18n');
		$this->assertInstanceOf(HasMany::class, $i18nAssociation);
		$this->assertFalse($i18nAssociation->getCascadeCallbacks());
		$this->assertTrue($i18nAssociation->getDependent());
		$this->assertEquals('append', $i18nAssociation->getSaveStrategy());
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\GlobalContentsTable::getColumnSystemClass()
	 */
	public function testGetColumnSystemClass(): void {
		$columnSystemClass = $this->globalContentsTable->getColumnSystemClass();

		$this->assertSame(AwyissColumnSystem::class, $columnSystemClass);
		$this->assertTrue(class_exists($columnSystemClass));

		$maxDenominator = BootstrapColumnSystem::getMaxDenominator();
		LocalConfig::write('columnSystem.className', BootstrapColumnSystem::class, 'Contents');
		$globalContentsTable = new GlobalContentsTable();

		$columnSystemClass = $globalContentsTable->getColumnSystemClass();
		BootstrapColumnSystem::setMaxDenominator($maxDenominator);
		$this->assertSame(BootstrapColumnSystem::class, $columnSystemClass);
		$this->assertTrue(class_exists($columnSystemClass));
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\GlobalContentsTable::getColumnWidths()
	 */
	public function testGetColumnWidths(): void {
		$columnWidths = $this->globalContentsTable->getColumnWidths();

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
	 * @see \Awyiss\Model\Table\GlobalContentsTable::getColumnIndents()
	 */
	public function testGetColumnIndents(): void {
		$columnIndents = $this->globalContentsTable->getColumnIndents();

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
	 * @see \Awyiss\Model\Table\GlobalContentsTable::validationDefault()
	 */
	public function testValidationDefault(): void {
		$validator = new Validator();
		$result = $this->globalContentsTable->validationDefault($validator);

		$this->assertInstanceOf(Validator::class, $result);
		$this->assertSame('GlobalContents', $result->getI18nDomain());

		// Test required fields
		$this->assertTrue($result->hasField('identifier'));
		$this->assertSame('create', $result->field('identifier')->isPresenceRequired());

		$this->assertTrue($result->hasField('globalContentTemplateId'));
		$this->assertSame('create', $result->field('globalContentTemplateId')->isPresenceRequired());

		// Test other fields
		$this->assertTrue($result->hasField('id'));
		$this->assertTrue($result->hasField('parentId'));
		$this->assertTrue($result->hasField('title'));
		$this->assertTrue($result->hasField('subtitle'));
		$this->assertTrue($result->hasField('text'));
		$this->assertTrue($result->hasField('link'));
		$this->assertTrue($result->hasField('columnWidth'));
		$this->assertTrue($result->hasField('columnIndent'));
		$this->assertTrue($result->hasField('cssClass'));
		$this->assertTrue($result->hasField('data'));
		$this->assertTrue($result->hasField('formId'));
		$this->assertTrue($result->hasField('surveyId'));
		$this->assertTrue($result->hasField('systemOrder'));
		$this->assertTrue($result->hasField('active'));
		$this->assertTrue($result->hasField('deleted'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\GlobalContentsTable::validationDefault()
	 */
	public function testEntityValidationSuccess(): void {
		$data = [
			'identifier' => 'testGlobalContent',
			'globalContentTemplateId' => 1,
			'title' => 'Test GlobalContent',
			'subtitle' => 'Test Subtitle',
			'text' => 'Test text content',
			'link' => 'https://example.com',
			'cssClass' => 'test-class',
			'data' => ['key' => 'value'],
			'systemOrder' => 1,
			'active' => true,
			'deleted' => false,
		];

		$entity = $this->globalContentsTable->newDefaultEntity($data);
		$errors = $entity->getErrors();

		$this->assertEmpty($errors, 'Valid data should not produce validation errors');
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\GlobalContentsTable::validationDefault()
	 */
	public function testEntityValidationMissingRequired(): void {
		$data = [
			'title' => 'Test GlobalContent',
		];

		$entity = $this->globalContentsTable->newDefaultEntity();
		$this->globalContentsTable->patchEntity($entity, $data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('identifier', $errors);
		$this->assertArrayHasKey('globalContentTemplateId', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\GlobalContentsTable::validationDefault()
	 */
	public function testEntityValidationInvalidTypes(): void {
		$data = [
			'id' => 'not_an_integer',
			'identifier' => true,
			'parentId' => 'not_an_integer',
			'title' => true,
			'subtitle' => true,
			'text' => true,
			'link' => true,
			'globalContentTemplateId' => 'not_an_integer',
			'cssClass' => true,
			'data' => 'not_an_array',
			'formId' => 'not_an_integer',
			'surveyId' => 'not_an_integer',
			'systemOrder' => 'not_an_integer',
			'active' => 'not_a_boolean',
			'deleted' => 'not_a_boolean',
		];

		$entity = $this->globalContentsTable->newDefaultEntity();
		$this->globalContentsTable->patchEntity($entity, $data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('id', $errors);
		$this->assertArrayHasKey('identifier', $errors);
		$this->assertArrayHasKey('parentId', $errors);
		$this->assertArrayHasKey('title', $errors);
		$this->assertArrayHasKey('subtitle', $errors);
		$this->assertArrayHasKey('text', $errors);
		$this->assertArrayHasKey('link', $errors);
		$this->assertArrayHasKey('globalContentTemplateId', $errors);
		$this->assertArrayHasKey('cssClass', $errors);
		$this->assertArrayHasKey('data', $errors);
		$this->assertArrayHasKey('formId', $errors);
		$this->assertArrayHasKey('surveyId', $errors);
		$this->assertArrayHasKey('systemOrder', $errors);
		$this->assertArrayHasKey('active', $errors);
		$this->assertArrayHasKey('deleted', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\GlobalContentsTable::validationDefault()
	 */
	public function testEntityValidationFieldLength(): void {
		$data = [
			'id' => 123456789123, // exceeds 11 char limit
			'identifier' => str_repeat('a', 51), // exceeds 50 char limit
			'parentId' => 123456789123, // exceeds 11 char limit
			'title' => str_repeat('b', 256), // exceeds 255 char limit
			'subtitle' => str_repeat('c', 256), // exceeds 255 char limit
			'text' => str_repeat('d', 65536), // exceeds 65535 byte limit
			'link' => str_repeat('e', 256), // exceeds 255 char limit
			'globalContentTemplateId' => 123456789123, // exceeds 11 char limit
			'cssClass' => str_repeat('f', 256), // exceeds 255 char limit
			'formId' => 123456789123, // exceeds 11 char limit
			'surveyId' => 123456789123, // exceeds 11 char limit
		];

		$entity = $this->globalContentsTable->newDefaultEntity();
		$this->globalContentsTable->patchEntity($entity, $data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('id', $errors);
		$this->assertArrayHasKey('identifier', $errors);
		$this->assertArrayHasKey('parentId', $errors);
		$this->assertArrayHasKey('title', $errors);
		$this->assertArrayHasKey('subtitle', $errors);
		$this->assertArrayHasKey('text', $errors);
		$this->assertArrayHasKey('link', $errors);
		$this->assertArrayHasKey('globalContentTemplateId', $errors);
		$this->assertArrayHasKey('cssClass', $errors);
		$this->assertArrayHasKey('formId', $errors);
		$this->assertArrayHasKey('surveyId', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\GlobalContentsTable::validationDefault()
	 */
	public function testEntityValidationColumnWidthInList(): void {
		// Test valid column width
		$data = [
			'identifier' => 'testGlobalContent',
			'globalContentTemplateId' => 1,
			'columnWidth' => '3/5',
		];

		$entity = $this->globalContentsTable->newDefaultEntity();
		$this->globalContentsTable->patchEntity($entity, $data);
		$errors = $entity->getErrors();

		$this->assertArrayNotHasKey('columnWidth', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\GlobalContentsTable::validationDefault()
	 */
	public function testEntityValidationColumnWidthNotInList(): void {
		// Test invalid column width
		$data = [
			'identifier' => 'testGlobalContent',
			'globalContentTemplateId' => 1,
			'columnWidth' => 'invalid_column_width',
		];

		$entity = $this->globalContentsTable->newDefaultEntity();
		$this->globalContentsTable->patchEntity($entity, $data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('columnWidth', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\GlobalContentsTable::validationDefault()
	 */
	public function testEntityValidationColumnIndentInList(): void {
		// Test valid column indent
		$data = [
			'identifier' => 'testGlobalContent',
			'globalContentTemplateId' => 1,
			'columnIndent' => '2/5',
		];

		$entity = $this->globalContentsTable->newDefaultEntity();
		$this->globalContentsTable->patchEntity($entity, $data);
		$errors = $entity->getErrors();

		$this->assertArrayNotHasKey('columnIndent', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\GlobalContentsTable::validationDefault()
	 */
	public function testEntityValidationColumnIndentNotInList(): void {
		// Test invalid column indent
		$data = [
			'identifier' => 'testGlobalContent',
			'globalContentTemplateId' => 1,
			'columnIndent' => 'invalid_column_indent',
		];

		$entity = $this->globalContentsTable->newDefaultEntity();
		$this->globalContentsTable->patchEntity($entity, $data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('columnIndent', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\GlobalContentsTable::validationDefault()
	 */
	public function testEntityValidationDataArrayMaxLength(): void {
		$largeData = array_fill(0, 10000, str_repeat('x', 100)); // Create very large array

		$data = [
			'identifier' => 'testGlobalContent',
			'globalContentTemplateId' => 1,
			'data' => $largeData,
		];

		$entity = $this->globalContentsTable->newDefaultEntity();
		$this->globalContentsTable->patchEntity($entity, $data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('data', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\GlobalContentsTable::buildRules()
	 */
	public function testBuildRulesValidGlobalContentTemplate(): void {
		// Test with existing global content template
		$data = [
			'identifier' => 'testGlobalContent',
			'globalContentTemplateId' => 1,
			'systemOrder' => 1,
			'attributes' => [
				'freeText' => 'This is a valid free text attribute',
			],
		];

		$entity = $this->globalContentsTable->newDefaultEntity();
		$this->globalContentsTable->patchEntity($entity, $data);
		$result = $this->globalContentsTable->checkRules($entity);

		$this->assertTrue($result);
	}

	/**
	 * @return void
	 * @see \Awyiss\Model\Table\GlobalContentsTable::buildRules()
	 */
	public function testBuildRulesInvalidGlobalContentTemplate(): void {
		// Test with non-existing global content template
		$data = [
			'identifier' => 'testGlobalContent',
			'globalContentTemplateId' => 99999,
			'systemOrder' => 1,
			'attributes' => [
				'freeText' => 'This is a valid free text attribute',
			],
		];

		$entity = $this->globalContentsTable->newDefaultEntity();
		$this->globalContentsTable->patchEntity($entity, $data);
		$result = $this->globalContentsTable->checkRules($entity);

		$this->assertFalse($result);

		$errors = $entity->getErrors();
		$this->assertArrayHasKey('globalContentTemplateId', $errors);
		$this->assertArrayHasKey(0, $errors['globalContentTemplateId']);
		$this->assertSame('GlobalContents::error_valid_global_content_template_id', $errors['globalContentTemplateId'][0]);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\GlobalContentsTable::buildRules()
	 * @see \Awyiss\Model\Table\GlobalContentsTable::validateInputFields()
	 * @see \Awyiss\Model\Table\GlobalContentsTable::validateAssignedElements()
	 */
	public function testBuildRulesValidAssignedElements(): void {
		// Template 1 has required elements: globalContentTemplateId, identifier, systemOrder
		// Template 1 has required attribute: attributes.freeText (from custom seed)

		$data = [
			'identifier' => 'testGlobalContent',
			'globalContentTemplateId' => 1,
			'systemOrder' => 1,
			// Optional assigned elements can be provided
			'active' => true,
			'parentId' => null,
			'cssClass' => 'test-class',
			'columnWidth' => '1/2',
			'columnIndent' => '1/4',
			'text' => 'Some text content',
			// Attributes - free_text is assigned to template 1 but not required
			'attributes' => [
				'freeText' => 'This is allowed',
			],
		];

		$entity = $this->globalContentsTable->newDefaultEntity();
		$this->globalContentsTable->patchEntity($entity, $data);
		$result = $this->globalContentsTable->checkRules($entity);

		$this->assertTrue($result);
		$this->assertEmpty($entity->getErrors());
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\GlobalContentsTable::buildRules()
	 * @see \Awyiss\Model\Table\GlobalContentsTable::validateInputFields()
	 * @see \Awyiss\Model\Table\GlobalContentsTable::validateAssignedElements()
	 */
	public function testBuildRulesInvalidAssignedElements(): void {
		// Template 1 has required elements: globalContentTemplateId, identifier, systemOrder
		// Template 1 has required attribute: attributes.freeText (from custom seed)

		$data = [
			'globalContentTemplateId' => 1, // Required, otherwise the validation will not even run
			// Missing required 'identifier'
			// Missing required 'systemOrder'
			'text' => 'Some content', // This is assigned but optional
		];

		$entity = $this->globalContentsTable->newDefaultEntity();
		unset($entity->systemOrder);
		$this->globalContentsTable->patchEntity($entity, $data);

		$result = $this->globalContentsTable->checkRules($entity);
		$this->assertFalse($result, 'Missing required assigned elements should fail validation');

		$errors = $entity->getErrors();

		$this->assertArrayHasKey('identifier', $errors);
		$this->assertSame('GlobalContents::error_required', $errors['identifier']['_required']);

		$this->assertArrayHasKey('systemOrder', $errors);
		$this->assertSame('GlobalContents::error_required', $errors['systemOrder']['_required']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\GlobalContentsTable::buildRules()
	 * @see \Awyiss\Model\Table\GlobalContentsTable::validateInputFields()
	 * @see \Awyiss\Model\Table\GlobalContentsTable::validateUnassignedElements()
	 */
	public function testBuildRulesValidUnassignedElements(): void {
		// Unassigned elements should be empty/default: title, title_tag, subtitle, subtitle_tag, link, form_id, survey_id
		// Special defaults: column_last=false, column_rtl=false, column_width=default first key

		$data = [
			'identifier' => 'testGlobalContent',
			'globalContentTemplateId' => 1,
			'systemOrder' => 1,
			'attributes' => [
				'freeText' => 'This is a valid free text attribute',
			],
			// Unassigned elements should be empty/null/default
			'title' => null,
			'titleTag' => null,
			'subtitle' => null,
			'subtitleTag' => null,
			'link' => null,
			'formId' => null,
			'surveyId' => null,
			// Special default values for column properties
			'columnLast' => false,
			'columnRtl' => false,
		];

		$entity = $this->globalContentsTable->newDefaultEntity();
		$this->globalContentsTable->patchEntity($entity, $data);

		$result = $this->globalContentsTable->checkRules($entity);
		$this->assertTrue($result, 'Valid unassigned elements (empty/default) should pass validation');
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\GlobalContentsTable::buildRules()
	 * @see \Awyiss\Model\Table\GlobalContentsTable::validateInputFields()
	 * @see \Awyiss\Model\Table\GlobalContentsTable::validateUnassignedElements()
	 */
	public function testBuildRulesInvalidUnassignedElements(): void {
		// Unassigned elements should be empty/default: title, title_tag, subtitle, subtitle_tag, link, form_id, survey_id
		// Special defaults: column_last=false, column_rtl=false, column_width=default first key

		$data = [
			'identifier' => 'testGlobalContent',
			'globalContentTemplateId' => 1,
			'systemOrder' => 1,
			'attributes' => [
				'freeText' => 'This is a valid free text attribute',
			],
			// Unassigned elements should be empty/null/default
			'title' => 'Title',
			'titleTag' => 'h3',
			'subtitle' => 'Subtitle',
			'subtitleTag' => 'h5',
			'link' => 'https://example.com',
			'formId' => 11,
			'surveyId' => 12,
			// Should be false
			'columnLast' => true,
			'columnRtl' => true,
		];

		$entity = $this->globalContentsTable->newDefaultEntity();
		$this->globalContentsTable->patchEntity($entity, $data);

		$result = $this->globalContentsTable->checkRules($entity);
		$this->assertFalse($result, 'Unassigned elements with values should fail validation');

		$errors = $entity->getErrors();

		$this->assertArrayHasKey('title', $errors);
		$this->assertSame('GlobalContents::error_is_empty', $errors['title']['isEmpty']);

		$this->assertArrayHasKey('subtitle', $errors);
		$this->assertSame('GlobalContents::error_is_empty', $errors['subtitle']['isEmpty']);

		$this->assertArrayHasKey('link', $errors);
		$this->assertSame('GlobalContents::error_is_empty', $errors['link']['isEmpty']);

		$this->assertArrayHasKey('formId', $errors);
		$this->assertSame('GlobalContents::error_is_empty', $errors['formId']['isEmpty']);

		$this->assertArrayHasKey('surveyId', $errors);
		$this->assertSame('GlobalContents::error_is_empty', $errors['surveyId']['isEmpty']);

		$this->assertArrayHasKey('columnLast', $errors);
		$this->assertSame('GlobalContents::error_equal_to', $errors['columnLast']['equalTo']);

		$this->assertArrayHasKey('columnRtl', $errors);
		$this->assertSame('GlobalContents::error_equal_to', $errors['columnRtl']['equalTo']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\GlobalContentsTable::buildRules()
	 * @see \Awyiss\Model\Table\GlobalContentsTable::validateInputFields()
	 * @see \Awyiss\Model\Table\GlobalContentsTable::validateUnassignedAttributes()
	 */
	public function testBuildRulesValidUnassignedAttributes(): void {
		// Template 1 has assigned attribute: free_text
		// Any other attributes should be empty if they are dirty

		$data = [
			'identifier' => 'testGlobalContent',
			'globalContentTemplateId' => 1,
			'systemOrder' => 1,
			'attributes' => [
				'freeText' => 'This is a valid free text attribute',
				'teaser' => null, // This is unassigned and should be empty
			],
		];

		$entity = $this->globalContentsTable->newDefaultEntity();
		$this->globalContentsTable->patchEntity($entity, $data);

		// Make the attribute dirty to trigger validation
		$entity->attributes->setDirty('freeText');
		$entity->attributes->setDirty('teaser');

		$result = $this->globalContentsTable->checkRules($entity);
		$this->assertTrue($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\GlobalContentsTable::buildRules()
	 * @see \Awyiss\Model\Table\GlobalContentsTable::validateInputFields()
	 * @see \Awyiss\Model\Table\GlobalContentsTable::validateUnassignedAttributes()
	 */
	public function testBuildRulesInvalidUnassignedAttributes(): void {
		// Template 1 has assigned attribute: free_text
		// Any other attributes with values should fail if they are dirty

		$data = [
			'identifier' => 'testGlobalContent',
			'globalContentTemplateId' => 2,
			'systemOrder' => 1,
			'attributes' => [
				'freeText' => 'This is allowed',
				'teaser' => 'This is not allowed',
			],
		];

		$entity = $this->globalContentsTable->newDefaultEntity();
		$this->globalContentsTable->patchEntity($entity, $data);

		// Make the attributes dirty to trigger validation
		$entity->attributes->setDirty('freeText');
		$entity->attributes->setDirty('teaser');

		$result = $this->globalContentsTable->checkRules($entity);

		$this->assertFalse($result);

		$errors = $entity->getErrors();

		$this->assertArrayHasKey('attributes', $errors);
		$this->assertArrayHasKey('teaser', $errors['attributes']);
		$this->assertSame('GlobalContents::error_is_empty', $errors['attributes']['teaser']['isEmpty']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\GlobalContentsTable::buildRules()
	 */
	public function testBuildRulesValidFormId(): void {
		// Test with existing form
		$data = [
			'identifier' => 'testGlobalContent',
			'globalContentTemplateId' => 2,
			'systemOrder' => 1,
			'formId' => 1,
			'surveyId' => 1,
			'attributes' => [
				'freeText' => 'This is a valid free text attribute',
			],
		];

		$entity = $this->globalContentsTable->newDefaultEntity();
		$this->globalContentsTable->patchEntity($entity, $data);

		$result = $this->globalContentsTable->checkRules($entity);
		$this->assertTrue($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\GlobalContentsTable::buildRules()
	 */
	public function testBuildRulesNullFormId(): void {
		// Test with null form (should be allowed)
		$data = [
			'identifier' => 'testGlobalContent',
			'globalContentTemplateId' => 2,
			'systemOrder' => 1,
			'formId' => null,
			'surveyId' => 1,
			'attributes' => [
				'freeText' => 'This is a valid free text attribute',
			],
		];

		$entity = $this->globalContentsTable->newDefaultEntity();
		$this->globalContentsTable->patchEntity($entity, $data);

		$result = $this->globalContentsTable->checkRules($entity);
		$this->assertTrue($result);
	}

	/**
	 * @return void
	 * @see \Awyiss\Model\Table\GlobalContentsTable::buildRules()
	 */
	public function testBuildRulesInvvalidFormId(): void {
		// Test with non-existing form
		$data = [
			'identifier' => 'testGlobalContent',
			'globalContentTemplateId' => 2,
			'systemOrder' => 1,
			'formId' => 99999,
			'surveyId' => 1,
			'attributes' => [
				'freeText' => 'This is a valid free text attribute',
			],
		];

		$entity = $this->globalContentsTable->newDefaultEntity();
		$this->globalContentsTable->patchEntity($entity, $data);

		$result = $this->globalContentsTable->checkRules($entity);
		$this->assertFalse($result);

		$errors = $entity->getErrors();

		$this->assertArrayHasKey('formId', $errors);
		$this->assertArrayHasKey('validFormId', $errors['formId']);
		$this->assertSame('Validation::error_exists_in', $errors['formId']['validFormId']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\GlobalContentsTable::buildRules()
	 */
	public function testBuildRulesValidSurveyId(): void {
		// Test with existing survey
		$data = [
			'identifier' => 'testGlobalContent',
			'globalContentTemplateId' => 2,
			'systemOrder' => 1,
			'formId' => null,
			'surveyId' => 1,
			'attributes' => [
				'freeText' => 'This is a valid free text attribute',
			],
		];

		$entity = $this->globalContentsTable->newDefaultEntity();
		$this->globalContentsTable->patchEntity($entity, $data);

		$result = $this->globalContentsTable->checkRules($entity);
		$this->assertTrue($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\GlobalContentsTable::buildRules()
	 */
	public function testBuildRulesNullSurveyId(): void {
		// Test with null survey (should be allowed)
		$data = [
			'identifier' => 'testGlobalContent',
			'globalContentTemplateId' => 2,
			'systemOrder' => 1,
			'formId' => null,
			'surveyId' => null,
			'attributes' => [
				'freeText' => 'This is a valid free text attribute',
			],
		];

		$entity = $this->globalContentsTable->newDefaultEntity();
		$this->globalContentsTable->patchEntity($entity, $data);

		$result = $this->globalContentsTable->checkRules($entity);
		$this->assertTrue($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\GlobalContentsTable::buildRules()
	 */
	public function testBuildRulesInvalidSurveyId(): void {
		// Test with non-existing survey
		$data = [
			'identifier' => 'testGlobalContent',
			'globalContentTemplateId' => 2,
			'systemOrder' => 1,
			'formId' => null,
			'surveyId' => 99999,
			'attributes' => [
				'freeText' => 'This is a valid free text attribute',
			],
		];

		$entity = $this->globalContentsTable->newDefaultEntity();
		$this->globalContentsTable->patchEntity($entity, $data);

		$result = $this->globalContentsTable->checkRules($entity);
		$this->assertFalse($result);

		$errors = $entity->getErrors();

		$this->assertArrayHasKey('surveyId', $errors);
		$this->assertArrayHasKey('validSurveyId', $errors['surveyId']);
		$this->assertSame('Validation::error_exists_in', $errors['surveyId']['validSurveyId']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\GlobalContentsTable::buildRules()
	 */
	public function testBuildRulesValidWidthIndentCombination(): void {
		// Test valid width/indent combination
		$data = [
			'identifier' => 'testGlobalContent',
			'globalContentTemplateId' => 1,
			'systemOrder' => 1,
			'columnWidth' => '3/5',
			'columnIndent' => '2/5',
			'attributes' => [
				'freeText' => 'This is a valid free text attribute',
			],
		];

		$entity = $this->globalContentsTable->newDefaultEntity();
		$this->globalContentsTable->patchEntity($entity, $data);

		$result = $this->globalContentsTable->checkRules($entity);
		$this->assertTrue($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\GlobalContentsTable::buildRules()
	 */
	public function testBuildRulesInvalidWidthIndentCombination(): void {
		// Test invalid width/indent combination
		$data = [
			'identifier' => 'testGlobalContent',
			'globalContentTemplateId' => 1,
			'systemOrder' => 1,
			'columnWidth' => '3/5',
			'columnIndent' => '3/5', // Invalid combination (should not exceed 1)
			'attributes' => [
				'freeText' => 'This is a valid free text attribute',
			],
		];

		$entity = $this->globalContentsTable->newDefaultEntity();
		$this->globalContentsTable->patchEntity($entity, $data);

		$result = $this->globalContentsTable->checkRules($entity);
		$this->assertFalse($result);

		$errors = $entity->getErrors();

		$this->assertArrayHasKey('_general', $errors);
		$this->assertArrayHasKey('validWidthIndentCombination', $errors['_general']);
		$this->assertSame('GlobalContents::error_valid_width_indent_combination', $errors['_general']['validWidthIndentCombination']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\GlobalContentsTable::nestedByIdentifier()
	 */
	public function testNestedByIdentifier(): void {
		$query = $this->globalContentsTable->find('all');
		$result = $this->globalContentsTable->nestedByIdentifier($query);

		/** @noinspection PhpConditionAlreadyCheckedInspection */
		$this->assertInstanceOf(CollectionInterface::class, $result);

		$result = $result->toArray();
		$this->assertSame([
			'dummyRowOverflow',
			'dummyNested',
			'dummyMultiRow',
			'dummySingleRow',
			'dummyNarrow',
			'inlineImg',
			'customTemplate',
			'doubleInlineImg',
			'withSurvey',
		], array_keys($result));

		$this->assertCount(4, $result['dummyRowOverflow']);
		$this->assertCount(4, $result['dummyNested']);
		$this->assertCount(5, $result['dummyMultiRow']);
		$this->assertCount(2, $result['dummySingleRow']);
		$this->assertCount(2, $result['dummyNarrow']);
		$this->assertCount(1, $result['inlineImg']);
		$this->assertCount(3, $result['customTemplate']);
		$this->assertCount(1, $result['doubleInlineImg']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\GlobalContentsTable::newDefaultEntity()
	 * @noinspection PhpPossiblePolymorphicInvocationInspection
	 */
	public function testNewDefaultEntity(): void {
		/** @var \Awyiss\Model\Entity\GlobalContent $entity */
		$entity = $this->globalContentsTable->newDefaultEntity();

		$this->assertInstanceOf(GlobalContent::class, $entity);
		$this->assertTrue($entity->isNew());

		$this->assertNull($entity->parentId);
		$this->assertNull($entity->title);
		$this->assertNull($entity->subtitle);
		$this->assertNull($entity->text);
		$this->assertNull($entity->link);
		$this->assertSame('1/1', $entity->columnWidth);
		$this->assertNull($entity->columnIndent);
		$this->assertFalse($entity->columnLast);
		$this->assertFalse($entity->columnRtl);
		$this->assertNull($entity->cssClass);
		$this->assertNull($entity->data);
		$this->assertNull($entity->formId);
		$this->assertNull($entity->surveyId);
		$this->assertTrue($entity->active);
		$this->assertFalse($entity->deleted);
		$this->assertSame(0, $entity->systemOrder);
		$this->assertNull($entity->identifier);
		$this->assertNull($entity->globalContentTemplateId);

		$this->assertInstanceOf(AttributesGlobalContent::class, $entity->attributes);

		$this->assertNull($entity->attributes->teaser);
		$this->assertNull($entity->attributes->freeText);
		$this->assertNull($entity->attributes->globalContentId);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\GlobalContentsTable::newDefaultEntity()
	 * @noinspection PhpPossiblePolymorphicInvocationInspection
	 */
	public function testNewDefaultEntityWithData(): void {
		$additionalData = [
			'active' => false,
			'identifier' => 'customGlobalContent',
			'globalContentTemplateId' => 1,
			'title' => 'Custom GlobalContent',
			'subtitle' => 'Custom Subtitle',
			'text' => 'Custom text',
			'link' => 'https://custom.com',
			'systemOrder' => 5,
			'attributes' => [
				'freeText' => 'This is a custom free text attribute',
			],
			'data' => [
				'customKey' => 'custom_value',
			],
		];

		$entity = $this->globalContentsTable->newDefaultEntity($additionalData);

		$this->assertInstanceOf(GlobalContent::class, $entity);
		$this->assertTrue($entity->isNew());

		$this->assertFalse($entity->active);
		$this->assertFalse($entity->deleted);

		$this->assertSame(5, $entity->systemOrder);
		$this->assertSame('customGlobalContent', $entity->identifier);
		$this->assertSame(1, $entity->globalContentTemplateId);
		$this->assertSame('Custom GlobalContent', $entity->title);
		$this->assertSame('Custom Subtitle', $entity->subtitle);
		$this->assertSame('Custom text', $entity->text);
		$this->assertSame('https://custom.com', $entity->link);

		$this->assertSame(['customKey' => 'custom_value'], $entity->data);

		$this->assertInstanceOf(AttributesGlobalContent::class, $entity->attributes);

		$this->assertSame('This is a custom free text attribute', $entity->attributes->freeText);
		$this->assertNull($entity->attributes->teaser);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\GlobalContentsTable::$nest
	 */
	public function testNestBehavior(): void {
		$this->assertTrue($this->globalContentsTable->hasBehavior('Nest'));

		$config = $this->globalContentsTable->getBehavior('Nest')->getConfig();

		$this->assertTrue($config['enabled']);
		$this->assertSame(['identifier'], $config['relatedColumns']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\GlobalContentsTable::$systemOrder
	 */
	public function testSystemOrderBehavior(): void {
		$this->assertTrue($this->globalContentsTable->hasBehavior('SystemOrder'));

		$config = $this->globalContentsTable->getBehavior('SystemOrder')->getConfig();

		$this->assertSame(['identifier', 'parentId'], $config['relatedColumns']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\GlobalContentsTable::$translate
	 */
	public function testTranslateBehavior(): void {
		$this->assertTrue($this->globalContentsTable->hasBehavior('Translate'));

		$config = $this->globalContentsTable->getBehavior('Translate')->getConfig();

		$this->assertSame(Awyiss::REALM_FRONTEND, $config['realm']);

		$this->assertIsArray($config['fields']);
		$this->assertSame(['title', 'subtitle', 'text', 'link'], $config['fields']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\GlobalContentsTable::initializeSchema()
	 */
	public function testInitializeSchemaDataColumn(): void {
		$schema = $this->globalContentsTable->getSchema();
		// Test that data column is configured as JSON type
		$this->assertSame('json', $schema->getColumnType('data'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\GlobalContentsTable::getPossibleFieldValues()
	 * @throws \ReflectionException
	 */
	public function testGetPossibleFieldValuesFormId(): void {
		$result = $this->globalContentsTable->getPossibleFieldValues('formId');

		$this->assertIsArray($result);
		$this->assertSame([
			1 => 'Kontaktformular',
			2 => 'Kontaktformular2',
			3 => 'Forms::inactive Kontaktformular3',
			4 => 'Kontaktformular4',
			5 => 'Kontaktformular5',
		], $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\GlobalContentsTable::getPossibleFieldValues()
	 * @throws \ReflectionException
	 */
	public function testGetPossibleFieldValuesSurveyId(): void {
		$result = $this->globalContentsTable->getPossibleFieldValues('surveyId');

		$this->assertIsArray($result);

		$this->assertSame([
			1 => 'Dummy Survey',
			2 => 'Surveys::inactive Dummy Survey (Inactive)',
			3 => 'Dummy Survey (Inline Image)',
			4 => 'Dummy Survey (Survey Results)',
		], $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\GlobalContentsTable::getPossibleFieldValues()
	 * @throws \ReflectionException
	 */
	public function testGetPossibleFieldValuesGlobalContentTemplateId(): void {
		$result = $this->globalContentsTable->getPossibleFieldValues('globalContentTemplateId');

		$this->assertIsArray($result);

		$this->assertSame([
			1 => 'Standard',
			2 => 'Dummy',
		], $result);
	}
}
