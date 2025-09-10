<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Model\Table;


use Awyiss\Awyiss;
use Awyiss\Core\LocalConfig;
use Awyiss\Model\Entity\Widget;
use Awyiss\Model\Table\WidgetsTable;
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
use Customer\Model\Entity\AttributesWidget;


/**
 * WidgetsTable Test Case
 *
 * @see \Awyiss\Model\Table\WidgetsTable
 */
class WidgetsTableTest extends TestCase {
	/**
	 * @var \Awyiss\Model\Table\WidgetsTable
	 */
	protected WidgetsTable $widgetsTable;


	/**
	 * @inheritDoc
	 */
	protected function setUp(): void {
		parent::setUp();

		/** @noinspection PhpFieldAssignmentTypeMismatchInspection */
		$this->widgetsTable = FactoryLocator::get('Table')->get('Widgets');
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\UsergroupsUsersTable::ATTRIBUTABLE
	 */
	public function testAttributableConstant(): void {
		/** @noinspection PhpUnitAssertTrueWithIncompatibleTypeArgumentInspection */
		$this->assertTrue($this->widgetsTable::ATTRIBUTABLE);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\WidgetsTable::TABLE
	 */
	public function testTableConstant(): void {
		$this->assertEquals('widgets', $this->widgetsTable::TABLE);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\WidgetsTable::initializeAssociations()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testInitializeAssociations(): void {
		$this->assertCount(15, $this->widgetsTable->associations()->keys());

		$this->assertTrue($this->widgetsTable->hasAssociation('AttributesWidgets'));
		$attributesWidgetsAssociation = $this->widgetsTable->getAssociation('AttributesWidgets');
		$this->assertInstanceOf(HasOne::class, $attributesWidgetsAssociation);
		$this->assertTrue($attributesWidgetsAssociation->getCascadeCallbacks());
		$this->assertTrue($attributesWidgetsAssociation->getDependent());

		$this->assertTrue($this->widgetsTable->hasAssociation('Forms'));
		$formsAssociation = $this->widgetsTable->getAssociation('Forms');
		$this->assertInstanceOf(BelongsTo::class, $formsAssociation);
		$this->assertFalse($formsAssociation->getCascadeCallbacks());
		$this->assertFalse($formsAssociation->getDependent());

		// 'MediaAssignments' must also exist
		$this->assertTrue($this->widgetsTable->hasAssociation('MediaAssignments'));
		$mediaAssignmentsAssociation = $this->widgetsTable->getAssociation('MediaAssignments');
		$this->assertInstanceOf(HasMany::class, $mediaAssignmentsAssociation);
		$this->assertTrue($mediaAssignmentsAssociation->getCascadeCallbacks());
		$this->assertTrue($mediaAssignmentsAssociation->getDependent());

		$this->assertTrue($this->widgetsTable->hasAssociation('Surveys'));
		$surveysAssociation = $this->widgetsTable->getAssociation('Surveys');
		$this->assertInstanceOf(BelongsTo::class, $surveysAssociation);
		$this->assertFalse($surveysAssociation->getCascadeCallbacks());
		$this->assertFalse($surveysAssociation->getDependent());

		$this->assertTrue($this->widgetsTable->hasAssociation('WidgetTemplates'));
		$widgetTemplatesAssociation = $this->widgetsTable->getAssociation('WidgetTemplates');
		$this->assertInstanceOf(BelongsTo::class, $widgetTemplatesAssociation);
		$this->assertFalse($widgetTemplatesAssociation->getCascadeCallbacks());
		$this->assertFalse($widgetTemplatesAssociation->getDependent());

		// 'ParentWidgets' must also exist (from parent table implementation)
		$this->assertTrue($this->widgetsTable->hasAssociation('ParentWidgets'));
		$parentWidgetsAssociation = $this->widgetsTable->getAssociation('ParentWidgets');
		$this->assertInstanceOf(BelongsTo::class, $parentWidgetsAssociation);
		$this->assertFalse($parentWidgetsAssociation->getCascadeCallbacks());
		$this->assertFalse($parentWidgetsAssociation->getDependent());

		// 'ChildWidgets' must also exist (from parent table implementation)
		$this->assertTrue($this->widgetsTable->hasAssociation('ChildWidgets'));
		$childWidgetsAssociation = $this->widgetsTable->getAssociation('ChildWidgets');
		$this->assertInstanceOf(HasMany::class, $childWidgetsAssociation);
		$this->assertTrue($childWidgetsAssociation->getCascadeCallbacks());
		$this->assertTrue($childWidgetsAssociation->getDependent());

		// 'CreatedByUser' must also exist
		$this->assertTrue($this->widgetsTable->hasAssociation('CreatedByUser'));
		$createdByUserAssociation = $this->widgetsTable->getAssociation('CreatedByUser');
		$this->assertInstanceOf(BelongsTo::class, $createdByUserAssociation);
		$this->assertFalse($createdByUserAssociation->getCascadeCallbacks());
		$this->assertFalse($createdByUserAssociation->getDependent());

		// 'ChangedByUser' must also exist
		$this->assertTrue($this->widgetsTable->hasAssociation('ChangedByUser'));
		$changedByUserAssociation = $this->widgetsTable->getAssociation('ChangedByUser');
		$this->assertInstanceOf(BelongsTo::class, $changedByUserAssociation);
		$this->assertFalse($changedByUserAssociation->getCascadeCallbacks());
		$this->assertFalse($changedByUserAssociation->getDependent());

		// 'DeletedByUser' must also exist
		$this->assertTrue($this->widgetsTable->hasAssociation('DeletedByUser'));
		$deletedByUserAssociation = $this->widgetsTable->getAssociation('DeletedByUser');
		$this->assertInstanceOf(BelongsTo::class, $deletedByUserAssociation);
		$this->assertFalse($deletedByUserAssociation->getCascadeCallbacks());
		$this->assertFalse($deletedByUserAssociation->getDependent());

		// 'Widgets_title_translation' must also exist
		$this->assertTrue($this->widgetsTable->hasAssociation('Widgets_title_translation'));
		$titleTranslationAssociation = $this->widgetsTable->getAssociation('Widgets_title_translation');
		$this->assertInstanceOf(HasOne::class, $titleTranslationAssociation);
		$this->assertFalse($titleTranslationAssociation->getCascadeCallbacks());
		$this->assertFalse($titleTranslationAssociation->getDependent());

		// 'Widgets_subtitle_translation' must also exist
		$this->assertTrue($this->widgetsTable->hasAssociation('Widgets_subtitle_translation'));
		$subtitleTranslationAssociation = $this->widgetsTable->getAssociation('Widgets_subtitle_translation');
		$this->assertInstanceOf(HasOne::class, $subtitleTranslationAssociation);
		$this->assertFalse($subtitleTranslationAssociation->getCascadeCallbacks());
		$this->assertFalse($subtitleTranslationAssociation->getDependent());

		// 'Widgets_text_translation' must also exist
		$this->assertTrue($this->widgetsTable->hasAssociation('Widgets_text_translation'));
		$textTranslationAssociation = $this->widgetsTable->getAssociation('Widgets_text_translation');
		$this->assertInstanceOf(HasOne::class, $textTranslationAssociation);
		$this->assertFalse($textTranslationAssociation->getCascadeCallbacks());
		$this->assertFalse($textTranslationAssociation->getDependent());

		// 'Widgets_link_translation' must also exist
		$this->assertTrue($this->widgetsTable->hasAssociation('Widgets_link_translation'));
		$linkTranslationAssociation = $this->widgetsTable->getAssociation('Widgets_link_translation');
		$this->assertInstanceOf(HasOne::class, $linkTranslationAssociation);
		$this->assertFalse($linkTranslationAssociation->getCascadeCallbacks());
		$this->assertFalse($linkTranslationAssociation->getDependent());

		// 'I18n' must also exist
		$this->assertTrue($this->widgetsTable->hasAssociation('I18n'));
		$i18nAssociation = $this->widgetsTable->getAssociation('I18n');
		$this->assertInstanceOf(HasMany::class, $i18nAssociation);
		$this->assertFalse($i18nAssociation->getCascadeCallbacks());
		$this->assertTrue($i18nAssociation->getDependent());
		$this->assertEquals('append', $i18nAssociation->getSaveStrategy());
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\WidgetsTable::getColumnSystemClass()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testGetColumnSystemClass(): void {
		$columnSystemClass = $this->widgetsTable->getColumnSystemClass();

		$this->assertSame(AwyissColumnSystem::class, $columnSystemClass);
		$this->assertTrue(class_exists($columnSystemClass));

		$maxDenominator = BootstrapColumnSystem::getMaxDenominator();
		LocalConfig::write('columnSystem.className', BootstrapColumnSystem::class, 'Contents');
		$widgetsTable = new WidgetsTable();

		$columnSystemClass = $widgetsTable->getColumnSystemClass();
		BootstrapColumnSystem::setMaxDenominator($maxDenominator);
		$this->assertSame(BootstrapColumnSystem::class, $columnSystemClass);
		$this->assertTrue(class_exists($columnSystemClass));
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\WidgetsTable::getColumnWidths()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testGetColumnWidths(): void {
		$columnWidths = $this->widgetsTable->getColumnWidths();

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
	 * @see \Awyiss\Model\Table\WidgetsTable::getColumnIndents()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testGetColumnIndents(): void {
		$columnIndents = $this->widgetsTable->getColumnIndents();

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
	 * @see \Awyiss\Model\Table\WidgetsTable::validationDefault()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testValidationDefault(): void {
		$validator = new Validator();
		$result = $this->widgetsTable->validationDefault($validator);

		$this->assertInstanceOf(Validator::class, $result);
		$this->assertSame('widgets', $result->getI18nDomain());

		// Test required fields
		$this->assertTrue($result->hasField('identifier'));
		$this->assertSame('create', $result->field('identifier')->isPresenceRequired());

		$this->assertTrue($result->hasField('widgetTemplateId'));
		$this->assertSame('create', $result->field('widgetTemplateId')->isPresenceRequired());

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
	 * @see \Awyiss\Model\Table\WidgetsTable::validationDefault()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testEntityValidationSuccess(): void {
		$data = [
			'identifier' => 'test_widget',
			'widgetTemplateId' => 1,
			'title' => 'Test Widget',
			'subtitle' => 'Test Subtitle',
			'text' => 'Test text content',
			'link' => 'https://example.com',
			'cssClass' => 'test-class',
			'data' => ['key' => 'value'],
			'systemOrder' => 1,
			'active' => true,
			'deleted' => false,
		];

		$entity = $this->widgetsTable->newDefaultEntity($data);
		$errors = $entity->getErrors();

		$this->assertEmpty($errors, 'Valid data should not produce validation errors');
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\WidgetsTable::validationDefault()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testEntityValidationMissingRequired(): void {
		$data = [
			'title' => 'Test Widget',
		];

		$entity = $this->widgetsTable->newDefaultEntity();
		$this->widgetsTable->patchEntity($entity, $data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('identifier', $errors);
		$this->assertArrayHasKey('widgetTemplateId', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\WidgetsTable::validationDefault()
	 * @noinspection PhpVariableNamingConventionInspection
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
			'widgetTemplateId' => 'not_an_integer',
			'cssClass' => true,
			'data' => 'not_an_array',
			'formId' => 'not_an_integer',
			'surveyId' => 'not_an_integer',
			'systemOrder' => 'not_an_integer',
			'active' => 'not_a_boolean',
			'deleted' => 'not_a_boolean',
		];

		$entity = $this->widgetsTable->newDefaultEntity();
		$this->widgetsTable->patchEntity($entity, $data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('id', $errors);
		$this->assertArrayHasKey('identifier', $errors);
		$this->assertArrayHasKey('parentId', $errors);
		$this->assertArrayHasKey('title', $errors);
		$this->assertArrayHasKey('subtitle', $errors);
		$this->assertArrayHasKey('text', $errors);
		$this->assertArrayHasKey('link', $errors);
		$this->assertArrayHasKey('widgetTemplateId', $errors);
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
	 * @see \Awyiss\Model\Table\WidgetsTable::validationDefault()
	 * @noinspection PhpVariableNamingConventionInspection
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
			'widgetTemplateId' => 123456789123, // exceeds 11 char limit
			'cssClass' => str_repeat('f', 256), // exceeds 255 char limit
			'formId' => 123456789123, // exceeds 11 char limit
			'surveyId' => 123456789123, // exceeds 11 char limit
		];

		$entity = $this->widgetsTable->newDefaultEntity();
		$this->widgetsTable->patchEntity($entity, $data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('id', $errors);
		$this->assertArrayHasKey('identifier', $errors);
		$this->assertArrayHasKey('parentId', $errors);
		$this->assertArrayHasKey('title', $errors);
		$this->assertArrayHasKey('subtitle', $errors);
		$this->assertArrayHasKey('text', $errors);
		$this->assertArrayHasKey('link', $errors);
		$this->assertArrayHasKey('widgetTemplateId', $errors);
		$this->assertArrayHasKey('cssClass', $errors);
		$this->assertArrayHasKey('formId', $errors);
		$this->assertArrayHasKey('surveyId', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\WidgetsTable::validationDefault()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testEntityValidationColumnWidthInList(): void {
		// Test valid column width
		$data = [
			'identifier' => 'test_widget',
			'widgetTemplateId' => 1,
			'columnWidth' => '3/5',
		];

		$entity = $this->widgetsTable->newDefaultEntity();
		$this->widgetsTable->patchEntity($entity, $data);
		$errors = $entity->getErrors();

		$this->assertArrayNotHasKey('columnWidth', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\WidgetsTable::validationDefault()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testEntityValidationColumnWidthNotInList(): void {
		// Test invalid column width
		$data = [
			'identifier' => 'test_widget',
			'widgetTemplateId' => 1,
			'columnWidth' => 'invalid_column_width',
		];

		$entity = $this->widgetsTable->newDefaultEntity();
		$this->widgetsTable->patchEntity($entity, $data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('columnWidth', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\WidgetsTable::validationDefault()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testEntityValidationColumnIndentInList(): void {
		// Test valid column indent
		$data = [
			'identifier' => 'test_widget',
			'widgetTemplateId' => 1,
			'columnIndent' => '2/5',
		];

		$entity = $this->widgetsTable->newDefaultEntity();
		$this->widgetsTable->patchEntity($entity, $data);
		$errors = $entity->getErrors();

		$this->assertArrayNotHasKey('columnIndent', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\WidgetsTable::validationDefault()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testEntityValidationColumnIndentNotInList(): void {
		// Test invalid column indent
		$data = [
			'identifier' => 'test_widget',
			'widgetTemplateId' => 1,
			'columnIndent' => 'invalid_column_indent',
		];

		$entity = $this->widgetsTable->newDefaultEntity();
		$this->widgetsTable->patchEntity($entity, $data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('columnIndent', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\WidgetsTable::validationDefault()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testEntityValidationDataArrayMaxLength(): void {
		$largeData = array_fill(0, 10000, str_repeat('x', 100)); // Create very large array

		$data = [
			'identifier' => 'test_widget',
			'widgetTemplateId' => 1,
			'data' => $largeData,
		];

		$entity = $this->widgetsTable->newDefaultEntity();
		$this->widgetsTable->patchEntity($entity, $data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('data', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\WidgetsTable::buildRules()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testBuildRulesValidWidgetTemplate(): void {
		// Test with existing widget template
		$data = [
			'identifier' => 'test_widget',
			'widgetTemplateId' => 1,
			'systemOrder' => 1,
			'attributes' => [
				'free_text' => 'This is a valid free text attribute',
			],
		];

		$entity = $this->widgetsTable->newDefaultEntity();
		$this->widgetsTable->patchEntity($entity, $data);
		$result = $this->widgetsTable->checkRules($entity);

		$this->assertTrue($result);
	}

	/**
	 * @return void
	 * @see \Awyiss\Model\Table\WidgetsTable::buildRules()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testBuildRulesInvalidWidgetTemplate(): void {
		// Test with non-existing widget template
		$data = [
			'identifier' => 'test_widget',
			'widgetTemplateId' => 99999,
			'systemOrder' => 1,
			'attributes' => [
				'free_text' => 'This is a valid free text attribute',
			],
		];

		$entity = $this->widgetsTable->newDefaultEntity();
		$this->widgetsTable->patchEntity($entity, $data);
		$result = $this->widgetsTable->checkRules($entity);

		$this->assertFalse($result);

		$errors = $entity->getErrors();
		$this->assertArrayHasKey('widgetTemplateId', $errors);
		$this->assertArrayHasKey(0, $errors['widgetTemplateId']);
		$this->assertSame('widgets::error_valid_widget_template_id', $errors['widgetTemplateId'][0]);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\WidgetsTable::buildRules()
	 * @see \Awyiss\Model\Table\WidgetsTable::validateInputFields()
	 * @see \Awyiss\Model\Table\WidgetsTable::validateAssignedElements()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testBuildRulesValidAssignedElements(): void {
		// Template 1 has required elements: widget_template_id, identifier, system_order
		// Template 1 has required attribute: attributes.free_text (from custom seed)

		$data = [
			'identifier' => 'test_widget',
			'widgetTemplateId' => 1,
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
				'free_text' => 'This is allowed',
			],
		];

		$entity = $this->widgetsTable->newDefaultEntity();
		$this->widgetsTable->patchEntity($entity, $data);
		$result = $this->widgetsTable->checkRules($entity);

		$this->assertTrue($result);
		$this->assertEmpty($entity->getErrors());
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\WidgetsTable::buildRules()
	 * @see \Awyiss\Model\Table\WidgetsTable::validateInputFields()
	 * @see \Awyiss\Model\Table\WidgetsTable::validateAssignedElements()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testBuildRulesInvalidAssignedElements(): void {
		// Template 1 has required elements: widget_template_id, identifier, system_order
		// Template 1 has required attribute: attributes.free_text (from custom seed)

		$data = [
			'widgetTemplateId' => 1, // Required, otherwise the validation will not even run
			// Missing required 'identifier'
			// Missing required 'system_order'
			'text' => 'Some content', // This is assigned but optional
		];

		$entity = $this->widgetsTable->newDefaultEntity();
		unset($entity->systemOrder);
		$this->widgetsTable->patchEntity($entity, $data);

		$result = $this->widgetsTable->checkRules($entity);
		$this->assertFalse($result, 'Missing required assigned elements should fail validation');

		$errors = $entity->getErrors();

		$this->assertArrayHasKey('identifier', $errors);
		$this->assertSame('widgets::error_required', $errors['identifier']['_required']);

		$this->assertArrayHasKey('systemOrder', $errors);
		$this->assertSame('widgets::error_required', $errors['systemOrder']['_required']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\WidgetsTable::buildRules()
	 * @see \Awyiss\Model\Table\WidgetsTable::validateInputFields()
	 * @see \Awyiss\Model\Table\WidgetsTable::validateUnassignedElements()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testBuildRulesValidUnassignedElements(): void {
		// Unassigned elements should be empty/default: title, title_tag, subtitle, subtitle_tag, link, form_id, survey_id
		// Special defaults: column_last=false, column_rtl=false, column_width=default first key

		$data = [
			'identifier' => 'test_widget',
			'widgetTemplateId' => 1,
			'systemOrder' => 1,
			'attributes' => [
				'free_text' => 'This is a valid free text attribute',
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

		$entity = $this->widgetsTable->newDefaultEntity();
		$this->widgetsTable->patchEntity($entity, $data);

		$result = $this->widgetsTable->checkRules($entity);
		$this->assertTrue($result, 'Valid unassigned elements (empty/default) should pass validation');
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\WidgetsTable::buildRules()
	 * @see \Awyiss\Model\Table\WidgetsTable::validateInputFields()
	 * @see \Awyiss\Model\Table\WidgetsTable::validateUnassignedElements()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testBuildRulesInvalidUnassignedElements(): void {
		// Unassigned elements should be empty/default: title, title_tag, subtitle, subtitle_tag, link, form_id, survey_id
		// Special defaults: column_last=false, column_rtl=false, column_width=default first key

		$data = [
			'identifier' => 'test_widget',
			'widgetTemplateId' => 1,
			'systemOrder' => 1,
			'attributes' => [
				'free_text' => 'This is a valid free text attribute',
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

		$entity = $this->widgetsTable->newDefaultEntity();
		$this->widgetsTable->patchEntity($entity, $data);

		$result = $this->widgetsTable->checkRules($entity);
		$this->assertFalse($result, 'Unassigned elements with values should fail validation');

		$errors = $entity->getErrors();

		$this->assertArrayHasKey('title', $errors);
		$this->assertSame('widgets::error_is_empty', $errors['title']['isEmpty']);

		$this->assertArrayHasKey('subtitle', $errors);
		$this->assertSame('widgets::error_is_empty', $errors['subtitle']['isEmpty']);

		$this->assertArrayHasKey('link', $errors);
		$this->assertSame('widgets::error_is_empty', $errors['link']['isEmpty']);

		$this->assertArrayHasKey('formId', $errors);
		$this->assertSame('widgets::error_is_empty', $errors['formId']['isEmpty']);

		$this->assertArrayHasKey('surveyId', $errors);
		$this->assertSame('widgets::error_is_empty', $errors['surveyId']['isEmpty']);

		$this->assertArrayHasKey('columnLast', $errors);
		$this->assertSame('widgets::error_equal_to', $errors['columnLast']['equalTo']);

		$this->assertArrayHasKey('columnRtl', $errors);
		$this->assertSame('widgets::error_equal_to', $errors['columnRtl']['equalTo']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\WidgetsTable::buildRules()
	 * @see \Awyiss\Model\Table\WidgetsTable::validateInputFields()
	 * @see \Awyiss\Model\Table\WidgetsTable::validateUnassignedAttributes()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testBuildRulesValidUnassignedAttributes(): void {
		// Template 1 has assigned attribute: free_text
		// Any other attributes should be empty if they are dirty

		$data = [
			'identifier' => 'test_widget',
			'widgetTemplateId' => 1,
			'systemOrder' => 1,
			'attributes' => [
				'free_text' => 'This is a valid free text attribute',
				'teaser' => null, // This is unassigned and should be empty
			],
		];

		$entity = $this->widgetsTable->newDefaultEntity();
		$this->widgetsTable->patchEntity($entity, $data);

		// Make the attribute dirty to trigger validation
		$entity->attributes->setDirty('free_text');
		$entity->attributes->setDirty('teaser');

		$result = $this->widgetsTable->checkRules($entity);
		$this->assertTrue($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\WidgetsTable::buildRules()
	 * @see \Awyiss\Model\Table\WidgetsTable::validateInputFields()
	 * @see \Awyiss\Model\Table\WidgetsTable::validateUnassignedAttributes()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testBuildRulesInvalidUnassignedAttributes(): void {
		// Template 1 has assigned attribute: free_text
		// Any other attributes with values should fail if they are dirty

		$data = [
			'identifier' => 'test_widget',
			'widgetTemplateId' => 2,
			'systemOrder' => 1,
			'attributes' => [
				'free_text' => 'This is allowed',
				'teaser' => 'This is not allowed',
			],
		];

		$entity = $this->widgetsTable->newDefaultEntity();
		$this->widgetsTable->patchEntity($entity, $data);

		// Make the attributes dirty to trigger validation
		$entity->attributes->setDirty('free_text');
		$entity->attributes->setDirty('teaser');

		$result = $this->widgetsTable->checkRules($entity);

		$this->assertFalse($result);

		$errors = $entity->getErrors();

		$this->assertArrayHasKey('attributes', $errors);
		$this->assertArrayHasKey('teaser', $errors['attributes']);
		$this->assertSame('widgets::error_is_empty', $errors['attributes']['teaser']['isEmpty']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\WidgetsTable::buildRules()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testBuildRulesValidFormId(): void {
		// Test with existing form
		$data = [
			'identifier' => 'test_widget',
			'widgetTemplateId' => 2,
			'systemOrder' => 1,
			'formId' => 1,
			'surveyId' => 1,
			'attributes' => [
				'free_text' => 'This is a valid free text attribute',
			],
		];

		$entity = $this->widgetsTable->newDefaultEntity();
		$this->widgetsTable->patchEntity($entity, $data);

		$result = $this->widgetsTable->checkRules($entity);
		$this->assertTrue($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\WidgetsTable::buildRules()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testBuildRulesNullFormId(): void {
		// Test with null form (should be allowed)
		$data = [
			'identifier' => 'test_widget',
			'widgetTemplateId' => 2,
			'systemOrder' => 1,
			'formId' => null,
			'surveyId' => 1,
			'attributes' => [
				'free_text' => 'This is a valid free text attribute',
			],
		];

		$entity = $this->widgetsTable->newDefaultEntity();
		$this->widgetsTable->patchEntity($entity, $data);

		$result = $this->widgetsTable->checkRules($entity);
		$this->assertTrue($result);
	}

	/**
	 * @return void
	 * @see \Awyiss\Model\Table\WidgetsTable::buildRules()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testBuildRulesInvvalidFormId(): void {
		// Test with non-existing form
		$data = [
			'identifier' => 'test_widget',
			'widgetTemplateId' => 2,
			'systemOrder' => 1,
			'formId' => 99999,
			'surveyId' => 1,
			'attributes' => [
				'free_text' => 'This is a valid free text attribute',
			],
		];

		$entity = $this->widgetsTable->newDefaultEntity();
		$this->widgetsTable->patchEntity($entity, $data);

		$result = $this->widgetsTable->checkRules($entity);
		$this->assertFalse($result);

		$errors = $entity->getErrors();

		$this->assertArrayHasKey('formId', $errors);
		$this->assertArrayHasKey('validFormId', $errors['formId']);
		$this->assertSame('validation::error_exists_in', $errors['formId']['validFormId']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\WidgetsTable::buildRules()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testBuildRulesValidSurveyId(): void {
		// Test with existing survey
		$data = [
			'identifier' => 'test_widget',
			'widgetTemplateId' => 2,
			'systemOrder' => 1,
			'formId' => null,
			'surveyId' => 1,
			'attributes' => [
				'free_text' => 'This is a valid free text attribute',
			],
		];

		$entity = $this->widgetsTable->newDefaultEntity();
		$this->widgetsTable->patchEntity($entity, $data);

		$result = $this->widgetsTable->checkRules($entity);
		$this->assertTrue($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\WidgetsTable::buildRules()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testBuildRulesNullSurveyId(): void {
		// Test with null survey (should be allowed)
		$data = [
			'identifier' => 'test_widget',
			'widgetTemplateId' => 2,
			'systemOrder' => 1,
			'formId' => null,
			'surveyId' => null,
			'attributes' => [
				'free_text' => 'This is a valid free text attribute',
			],
		];

		$entity = $this->widgetsTable->newDefaultEntity();
		$this->widgetsTable->patchEntity($entity, $data);

		$result = $this->widgetsTable->checkRules($entity);
		$this->assertTrue($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\WidgetsTable::buildRules()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testBuildRulesInvalidSurveyId(): void {
		// Test with non-existing survey
		$data = [
			'identifier' => 'test_widget',
			'widgetTemplateId' => 2,
			'systemOrder' => 1,
			'formId' => null,
			'surveyId' => 99999,
			'attributes' => [
				'free_text' => 'This is a valid free text attribute',
			],
		];

		$entity = $this->widgetsTable->newDefaultEntity();
		$this->widgetsTable->patchEntity($entity, $data);

		$result = $this->widgetsTable->checkRules($entity);
		$this->assertFalse($result);

		$errors = $entity->getErrors();

		$this->assertArrayHasKey('surveyId', $errors);
		$this->assertArrayHasKey('validSurveyId', $errors['surveyId']);
		$this->assertSame('validation::error_exists_in', $errors['surveyId']['validSurveyId']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\WidgetsTable::buildRules()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testBuildRulesValidWidthIndentCombination(): void {
		// Test valid width/indent combination
		$data = [
			'identifier' => 'test_widget',
			'widgetTemplateId' => 1,
			'systemOrder' => 1,
			'columnWidth' => '3/5',
			'columnIndent' => '2/5',
			'attributes' => [
				'free_text' => 'This is a valid free text attribute',
			],
		];

		$entity = $this->widgetsTable->newDefaultEntity();
		$this->widgetsTable->patchEntity($entity, $data);

		$result = $this->widgetsTable->checkRules($entity);
		$this->assertTrue($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\WidgetsTable::buildRules()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testBuildRulesInvalidWidthIndentCombination(): void {
		// Test invalid width/indent combination
		$data = [
			'identifier' => 'test_widget',
			'widgetTemplateId' => 1,
			'systemOrder' => 1,
			'columnWidth' => '3/5',
			'columnIndent' => '3/5', // Invalid combination (should not exceed 1)
			'attributes' => [
				'free_text' => 'This is a valid free text attribute',
			],
		];

		$entity = $this->widgetsTable->newDefaultEntity();
		$this->widgetsTable->patchEntity($entity, $data);

		$result = $this->widgetsTable->checkRules($entity);
		$this->assertFalse($result);

		$errors = $entity->getErrors();

		$this->assertArrayHasKey('_general', $errors);
		$this->assertArrayHasKey('validWidthIndentCombination', $errors['_general']);
		$this->assertSame('widgets::error_valid_width_indent_combination', $errors['_general']['validWidthIndentCombination']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\WidgetsTable::nestedByIdentifier()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testNestedByIdentifier(): void {
		$query = $this->widgetsTable->find('all');
		$result = $this->widgetsTable->nestedByIdentifier($query);

		/** @noinspection PhpConditionAlreadyCheckedInspection */
		$this->assertInstanceOf(CollectionInterface::class, $result);

		$result = $result->toArray();
		$this->assertSame([
			'dummy_row_overflow',
			'dummy_nested',
			'dummy_multi_row',
			'dummy_single_row',
			'dummy_narrow',
			'inline_img',
			'custom_template',
			'double_inline_img',
			'with_survey',
		], array_keys($result));

		$this->assertCount(4, $result['dummy_row_overflow']);
		$this->assertCount(4, $result['dummy_nested']);
		$this->assertCount(5, $result['dummy_multi_row']);
		$this->assertCount(2, $result['dummy_single_row']);
		$this->assertCount(2, $result['dummy_narrow']);
		$this->assertCount(1, $result['inline_img']);
		$this->assertCount(3, $result['custom_template']);
		$this->assertCount(1, $result['double_inline_img']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\WidgetsTable::newDefaultEntity()
	 * @noinspection PhpVariableNamingConventionInspection
	 * @noinspection PhpPossiblePolymorphicInvocationInspection
	 */
	public function testNewDefaultEntity(): void {
		/** @var \Awyiss\Model\Entity\Widget $entity */
		$entity = $this->widgetsTable->newDefaultEntity();

		$this->assertInstanceOf(Widget::class, $entity);
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
		$this->assertNull($entity->widgetTemplateId);

		$this->assertInstanceOf(AttributesWidget::class, $entity->attributes);

		$this->assertNull($entity->attributes->teaser);
		$this->assertNull($entity->attributes->freeText);
		$this->assertNull($entity->attributes->widgetId);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\WidgetsTable::newDefaultEntity()
	 * @noinspection PhpVariableNamingConventionInspection
	 * @noinspection PhpPossiblePolymorphicInvocationInspection
	 */
	public function testNewDefaultEntityWithData(): void {
		$additionalData = [
			'active' => false,
			'identifier' => 'custom_widget',
			'widgetTemplateId' => 1,
			'title' => 'Custom Widget',
			'subtitle' => 'Custom Subtitle',
			'text' => 'Custom text',
			'link' => 'https://custom.com',
			'systemOrder' => 5,
			'attributes' => [
				'free_text' => 'This is a custom free text attribute',
			],
			'data' => [
				'custom_key' => 'custom_value',
			],
		];

		$entity = $this->widgetsTable->newDefaultEntity($additionalData);

		$this->assertInstanceOf(Widget::class, $entity);
		$this->assertTrue($entity->isNew());

		$this->assertFalse($entity->active);
		$this->assertFalse($entity->deleted);

		$this->assertSame(5, $entity->systemOrder);
		$this->assertSame('custom_widget', $entity->identifier);
		$this->assertSame(1, $entity->widgetTemplateId);
		$this->assertSame('Custom Widget', $entity->title);
		$this->assertSame('Custom Subtitle', $entity->subtitle);
		$this->assertSame('Custom text', $entity->text);
		$this->assertSame('https://custom.com', $entity->link);

		$this->assertSame(['custom_key' => 'custom_value'], $entity->data);

		$this->assertInstanceOf(AttributesWidget::class, $entity->attributes);

		$this->assertSame('This is a custom free text attribute', $entity->attributes->freeText);
		$this->assertNull($entity->attributes->teaser);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\WidgetsTable::$nest
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testNestBehavior(): void {
		$this->assertTrue($this->widgetsTable->hasBehavior('Nest'));

		$config = $this->widgetsTable->getBehavior('Nest')->getConfig();

		$this->assertTrue($config['enabled']);
		$this->assertSame(['identifier'], $config['relatedColumns']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\WidgetsTable::$systemOrder
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testSystemOrderBehavior(): void {
		$this->assertTrue($this->widgetsTable->hasBehavior('SystemOrder'));

		$config = $this->widgetsTable->getBehavior('SystemOrder')->getConfig();

		$this->assertSame(['identifier', 'parentId'], $config['relatedColumns']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\WidgetsTable::$translate
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testTranslateBehavior(): void {
		$this->assertTrue($this->widgetsTable->hasBehavior('Translate'));

		$config = $this->widgetsTable->getBehavior('Translate')->getConfig();

		$this->assertSame(Awyiss::REALM_FRONTEND, $config['realm']);

		$this->assertIsArray($config['fields']);
		$this->assertSame(['title', 'subtitle', 'text', 'link'], $config['fields']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\WidgetsTable::initializeSchema()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testInitializeSchemaDataColumn(): void {
		$schema = $this->widgetsTable->getSchema();
		// Test that data column is configured as JSON type
		$this->assertSame('json', $schema->getColumnType('data'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\WidgetsTable::getPossibleFieldValues()
	 * @throws \ReflectionException
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testGetPossibleFieldValuesFormId(): void {
		$result = $this->widgetsTable->getPossibleFieldValues('form_id');

		$this->assertIsArray($result);
		$this->assertSame([
			1 => 'Kontaktformular',
			2 => 'Kontaktformular2',
			3 => 'forms::inactive Kontaktformular3',
			4 => 'Kontaktformular4',
			5 => 'Kontaktformular5',
		], $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\WidgetsTable::getPossibleFieldValues()
	 * @throws \ReflectionException
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testGetPossibleFieldValuesSurveyId(): void {
		$result = $this->widgetsTable->getPossibleFieldValues('survey_id');

		$this->assertIsArray($result);

		$this->assertSame([
			1 => 'Dummy Survey',
			2 => 'surveys::inactive Dummy Survey (Inactive)',
			3 => 'Dummy Survey (Inline Image)',
			4 => 'Dummy Survey (Survey Results)',
		], $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\WidgetsTable::getPossibleFieldValues()
	 * @throws \ReflectionException
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testGetPossibleFieldValuesWidgetTemplateId(): void {
		$result = $this->widgetsTable->getPossibleFieldValues('widget_template_id');

		$this->assertIsArray($result);

		$this->assertSame([
			1 => 'Standard',
			2 => 'Dummy',
		], $result);
	}
}
