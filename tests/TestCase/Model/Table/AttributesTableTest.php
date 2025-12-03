<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Model\Table;


use Awyiss\Model\Entity\Attribute;
use Awyiss\Model\Table\AttributesTable;
use Awyiss\ORM\Association\BelongsTo;
use Awyiss\ORM\Association\HasMany;
use Awyiss\ORM\Association\HasOne;
use Awyiss\Test\TestSuite\TestCase;
use Awyiss\Utility\Content\ColumnInterface;
use Awyiss\Validation\Validator;
use Cake\Datasource\FactoryLocator;


/**
 * AttributesTable Test Case
 *
 * @see \Awyiss\Model\Table\AttributesTable
 */
class AttributesTableTest extends TestCase {
	/**
	 * @var \Awyiss\Model\Table\AttributesTable
	 */
	protected AttributesTable $attributesTable;


	/**
	 * @inheritDoc
	 */
	protected function setUp(): void {
		parent::setUp();

		/** @noinspection PhpFieldAssignmentTypeMismatchInspection */
		$this->attributesTable = FactoryLocator::get('Table')->get('Attributes');
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\AttributesTable::ATTRIBUTABLE
	 */
	public function testAttributableConstant(): void {
		$this->assertFalse($this->attributesTable::ATTRIBUTABLE);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\AttributesTable::TABLE
	 */
	public function testTableConstant(): void {
		$this->assertEquals('attributes', $this->attributesTable::TABLE);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\AttributesTable::TYPE_PATTERN
	 */
	public function testTypePatternConstant(): void {
		/** @var string $pattern */
		$pattern = $this->attributesTable::TYPE_PATTERN;
		$this->assertEquals('/^(\w*)(?:\((\d+(?:,\d+)*)+\)+)?$/', $pattern);

		// Test valid type patterns
		$validTypes = [
			'varchar(255)',
			'int(11)',
			'decimal(10,2)',
			'tinyint',
			'text',
			'float(7,4)',
		];

		foreach ($validTypes as $type) {
			$this->assertEquals(1, preg_match($pattern, $type));
		}

		// Test invalid type patterns
		$invalidTypes = [
			'varchar()',
			'int(a)',
			'decimal(10,)',
			'varchar(255',
			'invalid-type',
		];

		foreach ($invalidTypes as $type) {
			$this->assertEquals(0, preg_match($pattern, $type));
		}
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\AttributesTable::initializeAssociations()
	 */
	public function testInitializeAssociations(): void {
		$this->assertCount(6, $this->attributesTable->associations()->keys());

		// 'MediaAssignments' must also exist
		$this->assertTrue($this->attributesTable->hasAssociation('MediaAssignments'));
		$mediaAssignmentsAssociation = $this->attributesTable->getAssociation('MediaAssignments');
		$this->assertInstanceOf(HasMany::class, $mediaAssignmentsAssociation);
		$this->assertTrue($mediaAssignmentsAssociation->getCascadeCallbacks());
		$this->assertTrue($mediaAssignmentsAssociation->getDependent());

		// 'CreatedByUser' must exist
		$this->assertTrue($this->attributesTable->hasAssociation('CreatedByUser'));
		$createdByUserAssociation = $this->attributesTable->getAssociation('CreatedByUser');
		$this->assertInstanceOf(BelongsTo::class, $createdByUserAssociation);
		$this->assertFalse($createdByUserAssociation->getCascadeCallbacks());
		$this->assertFalse($createdByUserAssociation->getDependent());

		// 'ChangedByUser' must exist
		$this->assertTrue($this->attributesTable->hasAssociation('ChangedByUser'));
		$changedByUserAssociation = $this->attributesTable->getAssociation('ChangedByUser');
		$this->assertInstanceOf(BelongsTo::class, $changedByUserAssociation);
		$this->assertFalse($changedByUserAssociation->getCascadeCallbacks());
		$this->assertFalse($changedByUserAssociation->getDependent());

		// 'DeletedByUser' must exist
		$this->assertTrue($this->attributesTable->hasAssociation('DeletedByUser'));
		$deletedByUserAssociation = $this->attributesTable->getAssociation('DeletedByUser');
		$this->assertInstanceOf(BelongsTo::class, $deletedByUserAssociation);
		$this->assertFalse($deletedByUserAssociation->getCascadeCallbacks());
		$this->assertFalse($deletedByUserAssociation->getDependent());

		// 'Attributes_title_translation' must also exist
		$this->assertTrue($this->attributesTable->hasAssociation('Attributes_title_translation'));
		$titleTranslationAssociation = $this->attributesTable->getAssociation('Attributes_title_translation');
		$this->assertInstanceOf(HasOne::class, $titleTranslationAssociation);
		$this->assertFalse($titleTranslationAssociation->getCascadeCallbacks());
		$this->assertFalse($titleTranslationAssociation->getDependent());

		// 'I18n' must exist (from Translate behavior)
		$this->assertTrue($this->attributesTable->hasAssociation('I18n'));
		$i18nAssociation = $this->attributesTable->getAssociation('I18n');
		$this->assertInstanceOf(HasMany::class, $i18nAssociation);
		$this->assertFalse($i18nAssociation->getCascadeCallbacks());
		$this->assertTrue($i18nAssociation->getDependent());
		$this->assertEquals('append', $i18nAssociation->getSaveStrategy());
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\AttributesTable::validationDefault()
	 */
	public function testValidationDefault(): void {
		$validator = new Validator();
		$result = $this->attributesTable->validationDefault($validator);

		$this->assertInstanceOf(Validator::class, $result);
		$this->assertSame('attributes', $result->getI18nDomain());

		// Test required fields
		$this->assertTrue($result->hasField('scope'));
		$this->assertSame('create', $result->field('scope')->isPresenceRequired());

		$this->assertTrue($result->hasField('title'));
		$this->assertSame('create', $result->field('title')->isPresenceRequired());

		$this->assertTrue($result->hasField('identifier'));
		$this->assertSame('create', $result->field('identifier')->isPresenceRequired());

		// Test other fields
		$this->assertTrue($result->hasField('id'));
		$this->assertTrue($result->hasField('type'));
		$this->assertTrue($result->hasField('hasIndex'));
		$this->assertTrue($result->hasField('fieldset'));
		$this->assertTrue($result->hasField('inputType'));
		$this->assertTrue($result->hasField('defaultValue'));
		$this->assertTrue($result->hasField('required'));
		$this->assertTrue($result->hasField('translatable'));
		$this->assertTrue($result->hasField('columnSpan'));
		$this->assertTrue($result->hasField('systemOrder'));
		$this->assertTrue($result->hasField('active'));
		$this->assertTrue($result->hasField('deleted'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\AttributesTable::validationDefault()
	 */
	public function testEntityValidationSuccess(): void {
		$data = [
			'scope' => 'pages',
			'title' => 'Test Attribute',
			'identifier' => 'test_attribute',
			'type' => 'varchar(255)',
			'hasIndex' => false,
			'fieldset' => 'general',
			'inputType' => 'text',
			'defaultValue' => 'default_value',
			'required' => false,
			'translatable' => false,
			'columnSpan' => '8/12',
			'systemOrder' => 1,
			'active' => true,
			'deleted' => false,
		];

		$entity = $this->attributesTable->newEntity($data);
		$errors = $entity->getErrors();

		$this->assertEmpty($errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\AttributesTable::validationDefault()
	 */
	public function testEntityValidationMissingRequired(): void {
		$data = [
			'type' => 'varchar(255)',
			'active' => true,
		];

		$entity = $this->attributesTable->newEntity($data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('scope', $errors);
		$this->assertArrayHasKey('title', $errors);
		$this->assertArrayHasKey('identifier', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\AttributesTable::validationDefault()
	 */
	public function testEntityValidationInvalidTypes(): void {
		$data = [
			'id' => 'not_an_integer',
			'scope' => true,
			'title' => true,
			'identifier' => true,
			'type' => true,
			'hasIndex' => 'not_a_boolean',
			'fieldset' => true,
			'inputType' => true,
			'defaultValue' => true,
			'required' => 'not_a_boolean',
			'translatable' => 'not_a_boolean',
			'systemOrder' => 'not_an_integer',
			'active' => 'not_a_boolean',
			'deleted' => 'not_a_boolean',
		];

		$entity = $this->attributesTable->newEntity($data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('id', $errors);
		$this->assertArrayHasKey('scope', $errors);
		$this->assertArrayHasKey('title', $errors);
		$this->assertArrayHasKey('identifier', $errors);
		$this->assertArrayHasKey('type', $errors);
		$this->assertArrayHasKey('hasIndex', $errors);
		$this->assertArrayHasKey('fieldset', $errors);
		$this->assertArrayHasKey('inputType', $errors);
		$this->assertArrayHasKey('defaultValue', $errors);
		$this->assertArrayHasKey('required', $errors);
		$this->assertArrayHasKey('translatable', $errors);
		$this->assertArrayHasKey('systemOrder', $errors);
		$this->assertArrayHasKey('active', $errors);
		$this->assertArrayHasKey('deleted', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\AttributesTable::validationDefault()
	 */
	public function testEntityValidationFieldLength(): void {
		$data = [
			'id' => 123456789123, // exceeds 11 char limit
			'scope' => str_repeat('a', 51), // exceeds 50 char limit
			'title' => str_repeat('b', 101), // exceeds 100 char limit
			'identifier' => str_repeat('c', 51), // exceeds 50 char limit
			'type' => str_repeat('d', 21), // exceeds 20 char limit
			'fieldset' => str_repeat('e', 21), // exceeds 20 char limit
			'inputType' => str_repeat('f', 31), // exceeds 30 char limit
			'defaultValue' => str_repeat('g', 101), // exceeds 100 char limit
		];

		$entity = $this->attributesTable->newEntity($data, ['guard' => false]);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('id', $errors);
		$this->assertArrayHasKey('scope', $errors);
		$this->assertArrayHasKey('title', $errors);
		$this->assertArrayHasKey('identifier', $errors);
		$this->assertArrayHasKey('type', $errors);
		$this->assertArrayHasKey('fieldset', $errors);
		$this->assertArrayHasKey('inputType', $errors);
		$this->assertArrayHasKey('defaultValue', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\AttributesTable::validationDefault()
	 */
	public function testEntityValidationNotBlank(): void {
		$data = [
			'scope' => '   ', // only whitespace
			'title' => '   ', // only whitespace
			'identifier' => '   ', // only whitespace
			'type' => '   ', // only whitespace
			'fieldset' => '   ', // only whitespace
			'inputType' => '   ', // only whitespace
		];

		$entity = $this->attributesTable->newEntity($data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('scope', $errors);
		$this->assertArrayHasKey('title', $errors);
		$this->assertArrayHasKey('identifier', $errors);
		$this->assertArrayHasKey('type', $errors);
		$this->assertArrayHasKey('fieldset', $errors);
		$this->assertArrayHasKey('inputType', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\AttributesTable::validationDefault()
	 */
	public function testEntityValidationTypeRegexValid(): void {
		// Test valid type formats
		$types = [
			'varchar(255)',
			'int(11)',
			'decimal(10,2)',
			'tinyint',
			'text',
		];

		foreach ($types as $type) {
			$data = [
				'scope' => 'pages',
				'title' => 'Test Attribute',
				'identifier' => 'test_attribute',
				'type' => $type,
			];

			$entity = $this->attributesTable->newEntity($data);
			$errors = $entity->getErrors();

			$this->assertArrayNotHasKey('type', $errors);
		}
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\AttributesTable::validationDefault()
	 */
	public function testEntityValidationTypeRegexInvalid(): void {
		// Test invalid type format
		$data = [
			'scope' => 'pages',
			'title' => 'Test Attribute',
			'identifier' => 'test_attribute',
			'type' => 'invalid-type-format',
		];

		$entity = $this->attributesTable->newEntity($data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('type', $errors);
		$this->assertArrayHasKey('typeRegex', $errors['type']);
		$this->assertEquals('attributes::error_type_regex', $errors['type']['typeRegex']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\AttributesTable::validationDefault()
	 */
	public function testEntityValidationColumnSpanValid(): void {
		$columnSpans = $this->attributesTable->getColumnSpans();

		// Test valid column span
		$span = array_keys($columnSpans)[0];
		$data = [
			'scope' => 'pages',
			'title' => 'Test Attribute',
			'identifier' => 'test_attribute',
			'columnSpan' => $span,
		];

		$entity = $this->attributesTable->newEntity($data);
		$errors = $entity->getErrors();

		$this->assertArrayNotHasKey('columnSpan', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\AttributesTable::validationDefault()
	 */
	public function testEntityValidationColumnSpanInvalid(): void {
		// Test invalid column span
		$data = [
			'scope' => 'pages',
			'title' => 'Test Attribute',
			'identifier' => 'test_attribute',
			'columnSpan' => 'invalid_column_span',
		];

		$entity = $this->attributesTable->newEntity($data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('columnSpan', $errors);
		$this->assertArrayHasKey('inList', $errors['columnSpan']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\AttributesTable::buildRules()
	 */
	public function testBuildRulesValidIdentifier(): void {
		// Test with valid identifier that doesn't conflict with table columns or reserved words
		$data = [
			'scope' => 'widgets',
			'title' => 'Test Attribute',
			'identifier' => 'custom_field',
			'fieldset' => 'general',
			'inputType' => 'text',
		];

		$entity = $this->attributesTable->newEntity($data);
		$result = $this->attributesTable->checkRules($entity);
		$this->assertTrue($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\AttributesTable::buildRules()
	 */
	public function testBuildRulesReservedIdentifier(): void {
		// Test with MySQL reserved word
		$data = [
			'scope' => 'widgets',
			'title' => 'Test Attribute',
			'identifier' => 'select', // MySQL reserved word
			'fieldset' => 'general',
			'inputType' => 'text',
		];

		$entity = $this->attributesTable->newEntity($data);
		$result = $this->attributesTable->checkRules($entity);
		$this->assertFalse($result);

		$errors = $entity->getErrors();
		$this->assertArrayHasKey('identifier', $errors);
		$this->assertArrayHasKey('validIdentifier', $errors['identifier']);
		$this->assertEquals('attributes::error_reserved_identifier', $errors['identifier']['validIdentifier']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\AttributesTable::buildRules()
	 */
	public function testBuildRulesExistingTableColumn(): void {
		// Test with identifier that matches existing table column
		$data = [
			'scope' => 'pages',
			'title' => 'Test Attribute',
			'identifier' => 'title', // This column exists in pages table
			'fieldset' => 'general',
			'inputType' => 'text',
		];

		$entity = $this->attributesTable->newEntity($data);
		$result = $this->attributesTable->checkRules($entity);
		$this->assertFalse($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\AttributesTable::buildRules()
	 */
	public function testBuildRulesIdentifierUniqueForScopeValid(): void {
		// Create first attribute
		$data = [
			'scope' => 'contents',
			'title' => 'Test Attribute 1',
			'identifier' => 'unique_identifier',
			'fieldset' => 'general',
			'inputType' => 'text',
		];

		$entity = $this->attributesTable->newEntity($data);
		$result = $this->attributesTable->checkRules($entity);

		$this->assertTrue($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\AttributesTable::buildRules()
	 */
	public function testBuildRulesIdentifierValidForDifferentscope(): void {
		/** @var \Awyiss\Model\Entity\Attribute $entity */
		$entity = $this->attributesTable->get(1);
		$entity->unset('id');
		$entity->scope = 'widgets';
		$entity->setNew(true);

		$result = $this->attributesTable->checkRules($entity);
		$this->assertTrue($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\AttributesTable::buildRules()
	 */
	public function testBuildRulesIdentifierUniqueForScopeInvalid(): void {
		$entity = $this->attributesTable->get(1);
		$entity->unset('id');
		$entity->setNew(true);
		$result = $this->attributesTable->checkRules($entity);
		$this->assertFalse($result);

		$errors = $entity->getErrors();
		$this->assertArrayHasKey('identifier', $errors);
		$this->assertArrayHasKey('identifierUniqueForScope', $errors['identifier']);
		$this->assertEquals('attributes::error_identifier_unique_for_scope', $errors['identifier']['identifierUniqueForScope']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\AttributesTable::buildRules()
	 */
	public function testBuildRulesValidFieldset(): void {
		$availableFieldsets = $this->attributesTable->getAvailableFieldsets();

		// Test with valid fieldset
		$data = [
			'scope' => 'pages',
			'title' => 'Test Attribute',
			'identifier' => 'test_fieldset',
			'fieldset' => $availableFieldsets[0],
			'inputType' => 'text',
		];

		$entity = $this->attributesTable->newEntity($data);
		$result = $this->attributesTable->checkRules($entity);

		$this->assertTrue($result);

		// Test with special scopes (contents, widgets) - should always be valid
		$specialScopes = ['contents', 'widgets'];
		foreach ($specialScopes as $scope) {
			$data = [
				'scope' => $scope,
				'title' => 'Test Attribute',
				'identifier' => 'test_special_' . $scope,
				'fieldset' => 'any_fieldset', // Any fieldset should be valid for special scopes
				'inputType' => 'text',
			];

			$entity = $this->attributesTable->newEntity($data);
			$result = $this->attributesTable->checkRules($entity);

			$this->assertTrue($result);
		}
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\AttributesTable::buildRules()
	 */
	public function testBuildRulesInvalidFieldset(): void {
		// Test with invalid fieldset for non-special scope
		$data = [
			'scope' => 'pages',
			'title' => 'Test Attribute',
			'identifier' => 'test_invalid_fieldset',
			'fieldset' => 'invalid_fieldset',
			'inputType' => 'text',
		];

		$entity = $this->attributesTable->newEntity($data);
		$result = $this->attributesTable->checkRules($entity);
		$this->assertFalse($result);

		$errors = $entity->getErrors();
		$this->assertArrayHasKey('fieldset', $errors);
		$this->assertArrayHasKey('validFieldset', $errors['fieldset']);
		$this->assertEquals('attributes::error_valid_fieldset', $errors['fieldset']['validFieldset']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\AttributesTable::buildRules()
	 */
	public function testBuildRulesValidInputType(): void {
		$availableInputTypes = $this->attributesTable->getAvailableInputTypes();

		// Test with valid input type
		$data = [
			'scope' => 'pages',
			'title' => 'Test Attribute',
			'identifier' => 'test_input_type',
			'fieldset' => 'general',
			'inputType' => $availableInputTypes[0],
		];

		$entity = $this->attributesTable->newEntity($data);
		$result = $this->attributesTable->checkRules($entity);
		$this->assertTrue($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\AttributesTable::buildRules()
	 */
	public function testBuildRulesInvalidInputType(): void {
		// Test with invalid input type
		$data = [
			'scope' => 'pages',
			'title' => 'Test Attribute',
			'identifier' => 'test_invalid_input_type',
			'fieldset' => 'general',
			'inputType' => 'invalid_input_type',
		];

		$entity = $this->attributesTable->newEntity($data);
		$result = $this->attributesTable->checkRules($entity);
		$this->assertFalse($result);

		$errors = $entity->getErrors();
		$this->assertArrayHasKey('inputType', $errors);
		$this->assertArrayHasKey('validInputType', $errors['inputType']);
		$this->assertEquals('attributes::error_valid_input_type', $errors['inputType']['validInputType']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\AttributesTable::buildCategories()
	 */
	public function testBuildCategories(): void {
		$categories = $this->attributesTable->buildCategories();

		$this->assertIsArray($categories);
		$this->assertSame([
			'employers' => 'Arbeitgeber',
			'cars' => 'Autos',
			'content_templates' => 'content_templates::menu_title',
			'contents' => 'contents::menu_title',
			'dummy_users' => 'dummy_users::menu_title',
			'languages' => 'languages::menu_title',
			'media_folders' => 'media_folders::menu_title',
			'media' => 'media::menu_title',
			'menu_entries' => 'menu_entries::menu_title',
			'employees' => 'Mitarbeiter',
			'news' => 'News',
			'newscategories' => 'Newskategorie',
			'products' => 'page_roles::inactive Produkt',
			'page_roles' => 'page_roles::menu_title',
			'page_templates' => 'page_templates::menu_title',
			'pages' => 'pages::menu_title',
			'survey_answers' => 'survey_answers::menu_title',
			'survey_questions' => 'survey_questions::menu_title',
			'surveys' => 'surveys::menu_title',
			'url_history' => 'url_history::menu_title',
			'urls_not_found' => 'urls_not_found::menu_title',
			'usergroups' => 'usergroups::menu_title',
			'users' => 'users::menu_title',
			'widget_templates' => 'widget_templates::menu_title',
			'widgets' => 'widgets::menu_title',
		], $categories);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\AttributesTable::getAvailableFieldsets()
	 */
	public function testGetAvailableFieldsets(): void {
		$fieldsets = $this->attributesTable->getAvailableFieldsets();

		$this->assertIsArray($fieldsets);
		$this->assertSame([
			'presentation',
			'conditions',
			'general',
			'content',
			'media',
			'attributes',
		], $fieldsets);

		// Test with specific scope parameter
		$fieldsetsWithScope = $this->attributesTable->getAvailableFieldsets('pages');
		$this->assertEquals($fieldsets, $fieldsetsWithScope);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\AttributesTable::getAvailableInputTypes()
	 */
	public function testGetAvailableInputTypes(): void {
		$inputTypes = $this->attributesTable->getAvailableInputTypes();

		$this->assertIsArray($inputTypes);
		$this->assertSame([
			'text',
			'color',
			'date',
			'datetime',
			'time',
			'checkbox',
			'multicheckbox',
			'select',
			'select_multiple',
			'input_list',
			'input_key_value_list',
			'textarea',
			'texteditor',
			'password',
			'hidden',
		], $inputTypes);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\AttributesTable::getAvailableScopes()
	 */
	public function testGetAvailableScopes(): void {
		$scopes = $this->attributesTable->getAvailableScopes();

		$this->assertIsArray($scopes);
		$this->assertSame([
			'cars',
			'content_templates',
			'contents',
			'dummy_users',
			'employees',
			'employers',
			'languages',
			'media',
			'media_folders',
			'menu_entries',
			'news',
			'newscategories',
			'page_roles',
			'page_templates',
			'pages',
			'products',
			'survey_answers',
			'survey_questions',
			'surveys',
			'url_history',
			'urls_not_found',
			'usergroups',
			'users',
			'widget_templates',
			'widgets',
		], array_keys($scopes));
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\AttributesTable::getColumnSpans()
	 */
	public function testGetColumnSpans(): void {
		$columnSpans = $this->attributesTable->getColumnSpans();

		$this->assertIsArray($columnSpans);
		$this->assertSame([
			'12/12',
			'1/12',
			'2/12',
			'3/12',
			'4/12',
			'5/12',
			'6/12',
			'7/12',
			'8/12',
			'9/12',
			'10/12',
			'11/12',
		], array_keys($columnSpans));

		foreach ($columnSpans as $label => $span) {
			$this->assertIsString($label);
			$this->assertInstanceOf(ColumnInterface::class, $span);
		}
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\AttributesTable::newDefaultEntity()
	 */
	public function testNewDefaultEntity(): void {
		/** @var \Awyiss\Model\Entity\Attribute $entity */
		$entity = $this->attributesTable->newDefaultEntity();

		$this->assertInstanceOf(Attribute::class, $entity);
		$this->assertTrue($entity->isNew());

		// Check default values
		$this->assertNull($entity->scope);
		$this->assertNull($entity->title);
		$this->assertNull($entity->identifier);
		$this->assertNull($entity->defaultValue);
		$this->assertSame('varchar(255)', $entity->type);
		$this->assertFalse($entity->hasIndex);
		$this->assertSame('text', $entity->inputType);
		$this->assertFalse($entity->required);
		$this->assertFalse($entity->translatable);
		$this->assertSame(0, $entity->systemOrder);
		$this->assertTrue($entity->active);
		$this->assertFalse($entity->deleted);

		// Check that fieldset has default value from available fieldsets
		$availableFieldsets = $this->attributesTable->getAvailableFieldsets();
		$this->assertSame($availableFieldsets[0], $entity->fieldset);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\AttributesTable::newDefaultEntity()
	 */
	public function testNewDefaultEntityWithData(): void {
		$additionalData = [
			'scope' => 'custom_scope',
			'title' => 'Custom Attribute',
			'identifier' => 'custom_attribute',
			'type' => 'varchar(100)',
			'hasIndex' => true,
			'fieldset' => 'content',
			'inputType' => 'textarea',
			'defaultValue' => 'custom_default',
			'required' => true,
			'translatable' => true,
			'columnSpan' => 'col-md-12',
			'systemOrder' => 5,
			'active' => false,
		];

		/** @var \Awyiss\Model\Entity\Attribute $entity */
		$entity = $this->attributesTable->newDefaultEntity($additionalData);

		$this->assertInstanceOf(Attribute::class, $entity);
		$this->assertTrue($entity->isNew());

		// Check custom values
		$this->assertSame('custom_scope', $entity->scope);
		$this->assertSame('Custom Attribute', $entity->title);
		$this->assertSame('custom_attribute', $entity->identifier);
		$this->assertSame('varchar(100)', $entity->type);
		$this->assertTrue($entity->hasIndex);
		$this->assertSame('content', $entity->fieldset);
		$this->assertSame('textarea', $entity->inputType);
		$this->assertSame('custom_default', $entity->defaultValue);
		$this->assertTrue($entity->required);
		$this->assertTrue($entity->translatable);
		$this->assertSame('col-md-12', $entity->columnSpan);
		$this->assertSame(5, $entity->systemOrder);
		$this->assertFalse($entity->active);
		$this->assertFalse($entity->deleted);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\AttributesTable::$categories
	 */
	public function testCategoriesBehavior(): void {
		$this->assertTrue($this->attributesTable->hasBehavior('Categories'));

		$config = $this->attributesTable->getBehavior('Categories')->getConfig();

		$this->assertFalse($config['allowAggregation']);
		$this->assertFalse($config['allowUnassigned']);
		$this->assertTrue($config['enabled']);
		$this->assertSame('scope', $config['identifier']);
		$this->assertFalse($config['useDatasource']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\AttributesTable::$search
	 */
	public function testSearchBehavior(): void {
		$this->assertTrue($this->attributesTable->hasBehavior('Search'));

		$config = $this->attributesTable->getBehavior('Search')->getConfig();

		$this->assertSame(['scope'], $config['blocklistedColumns']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\AttributesTable::$systemOrder
	 */
	public function testSystemOrderBehavior(): void {
		$this->assertTrue($this->attributesTable->hasBehavior('SystemOrder'));

		$config = $this->attributesTable->getBehavior('SystemOrder')->getConfig();

		$this->assertSame(['scope', 'fieldset'], $config['relatedColumns']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\AttributesTable::$translate
	 */
	public function testTranslateBehavior(): void {
		$this->assertTrue($this->attributesTable->hasBehavior('Translate'));

		$config = $this->attributesTable->getBehavior('Translate')->getConfig();

		// Auto-realm (no specific realm set)
		$this->assertNull($config['realm'] ?? null);

		$this->assertIsArray($config['fields']);
		$this->assertSame(['title'], $config['fields']);
	}
}
