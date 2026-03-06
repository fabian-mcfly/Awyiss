<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Model\Table;


use Awyiss\Model\Enum\PageRoleEnumInterface;
use Awyiss\Model\Table\PagesTable;
use Awyiss\ORM\Association\BelongsTo;
use Awyiss\ORM\Association\HasMany;
use Awyiss\ORM\Association\HasOne;
use Awyiss\Test\TestSuite\TestCase;
use Awyiss\Validation\Validator;
use Cake\Datasource\FactoryLocator;
use Cake\Datasource\RulesChecker;
use Cake\ORM\Query\SelectQuery;
use Customer\Model\Enum\PageRole;


/**
 * PagesTable Test Case
 *
 * @see \Awyiss\Model\Table\PagesTable
 */
class PagesTableTest extends TestCase {
	/**
	 * @var \Awyiss\Model\Table\PagesTable
	 */
	protected PagesTable $pagesTable;


	/**
	 * @inheritDoc
	 */
	protected function setUp(): void {
		parent::setUp();

		/** @noinspection PhpFieldAssignmentTypeMismatchInspection */
		$this->pagesTable = FactoryLocator::get('Table')->get('Pages');
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\PagesTable::ATTRIBUTABLE
	 */
	public function testAttributableConstant(): void {
		/** @noinspection PhpUnitAssertTrueWithIncompatibleTypeArgumentInspection */
		$this->assertTrue($this->pagesTable::ATTRIBUTABLE);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\PagesTable::TABLE
	 */
	public function testTableConstant(): void {
		$this->assertEquals('pages', $this->pagesTable::TABLE);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\PagesTable::initialize()
	 */
	public function testInitializePageRoleLogic(): void {
		// Test that pageRole is properly set based on class name
		$pageRole = $this->pagesTable->getPageRole();

		/** @noinspection PhpConditionAlreadyCheckedInspection */
		$this->assertInstanceOf(PageRoleEnumInterface::class, $pageRole);
		$this->assertEquals(PageRole::Page, $pageRole);

		// Create a new PagesTable instance from the NewsTable
		$newsTable = FactoryLocator::get('Table')->get('News');
		$this->assertInstanceOf(PagesTable::class, $newsTable);

		// Verify the pageRole is still set correctly
		$newsPageRole = $newsTable->getPageRole();
		/** @noinspection PhpConditionAlreadyCheckedInspection */
		$this->assertInstanceOf(PageRoleEnumInterface::class, $newsPageRole);
		$this->assertEquals(PageRole::News, $newsPageRole);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\PagesTable::getPageRole()
	 */
	public function testGetPageRole(): void {
		$pageRole = $this->pagesTable->getPageRole();

		/** @noinspection PhpConditionAlreadyCheckedInspection */
		$this->assertInstanceOf(PageRoleEnumInterface::class, $pageRole);
		$this->assertEquals(PageRole::Page, $pageRole);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\PagesTable::initializeSchema()
	 */
	public function testInitializeSchemaPageRoleIdColumn(): void {
		$schema = $this->pagesTable->getSchema();

		// Test that page_role_id column is configured as an enum type
		$this->assertSame('enum-customer-model-enum-pagerole', $schema->getColumnType('pageRoleId'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\PagesTable::initializeAssociations()
	 */
	public function testInitializeAssociations(): void {
		$this->assertCount(18, $this->pagesTable->associations()->keys());

		$this->assertTrue($this->pagesTable->hasAssociation('AttributesPages'));
		$attributesPagesAssociation = $this->pagesTable->getAssociation('AttributesPages');
		$this->assertInstanceOf(HasOne::class, $attributesPagesAssociation);
		$this->assertTrue($attributesPagesAssociation->getCascadeCallbacks());
		$this->assertTrue($attributesPagesAssociation->getDependent());

		// Test Contents association (HasMany)
		$this->assertTrue($this->pagesTable->hasAssociation('Contents'));
		$contentsAssociation = $this->pagesTable->getAssociation('Contents');
		$this->assertInstanceOf(HasMany::class, $contentsAssociation);
		$this->assertTrue($contentsAssociation->getCascadeCallbacks());
		$this->assertTrue($contentsAssociation->getDependent());

		// Test Forms association (BelongsTo)
		$this->assertTrue($this->pagesTable->hasAssociation('Forms'));
		$formsAssociation = $this->pagesTable->getAssociation('Forms');
		$this->assertInstanceOf(BelongsTo::class, $formsAssociation);
		$this->assertFalse($formsAssociation->getCascadeCallbacks());
		$this->assertFalse($formsAssociation->getDependent());

		// 'CustomerGroupAccessSettings' must also exist
		$this->assertTrue($this->pagesTable->hasAssociation('CustomerGroupAccessSettings'));
		$customerGroupAccessSettingsAssociation = $this->pagesTable->getAssociation('CustomerGroupAccessSettings');
		$this->assertInstanceOf(HasOne::class, $customerGroupAccessSettingsAssociation);
		$this->assertTrue($customerGroupAccessSettingsAssociation->getCascadeCallbacks());
		$this->assertTrue($customerGroupAccessSettingsAssociation->getDependent());

		// 'CustomerGroupAssignments' must also exist
		$this->assertTrue($this->pagesTable->hasAssociation('CustomerGroupAssignments'));
		$customerGroupAssignmentsAssociation = $this->pagesTable->getAssociation('CustomerGroupAssignments');
		$this->assertInstanceOf(HasMany::class, $customerGroupAssignmentsAssociation);
		$this->assertTrue($customerGroupAssignmentsAssociation->getCascadeCallbacks());
		$this->assertTrue($customerGroupAssignmentsAssociation->getDependent());

		// 'MediaAssignments' must also exist
		$this->assertTrue($this->pagesTable->hasAssociation('MediaAssignments'));
		$mediaAssignmentsAssociation = $this->pagesTable->getAssociation('MediaAssignments');
		$this->assertInstanceOf(HasMany::class, $mediaAssignmentsAssociation);
		$this->assertTrue($mediaAssignmentsAssociation->getCascadeCallbacks());
		$this->assertTrue($mediaAssignmentsAssociation->getDependent());

		// Test Languages association (BelongsTo)
		$this->assertTrue($this->pagesTable->hasAssociation('Languages'));
		$languagesAssociation = $this->pagesTable->getAssociation('Languages');
		$this->assertInstanceOf(BelongsTo::class, $languagesAssociation);
		$this->assertFalse($languagesAssociation->getCascadeCallbacks());
		$this->assertFalse($languagesAssociation->getDependent());
		$this->assertEquals('shortcode', $languagesAssociation->getBindingKey());
		$this->assertEquals('languageShortcode', $languagesAssociation->getForeignKey());

		// Test PageRoles association (BelongsTo)
		$this->assertTrue($this->pagesTable->hasAssociation('PageRoles'));
		$pageRolesAssociation = $this->pagesTable->getAssociation('PageRoles');
		$this->assertInstanceOf(BelongsTo::class, $pageRolesAssociation);
		$this->assertFalse($pageRolesAssociation->getCascadeCallbacks());
		$this->assertFalse($pageRolesAssociation->getDependent());

		// Test PageTemplates association (BelongsTo)
		$this->assertTrue($this->pagesTable->hasAssociation('PageTemplates'));
		$pageTemplatesAssociation = $this->pagesTable->getAssociation('PageTemplates');
		$this->assertInstanceOf(BelongsTo::class, $pageTemplatesAssociation);
		$this->assertFalse($pageTemplatesAssociation->getCascadeCallbacks());
		$this->assertFalse($pageTemplatesAssociation->getDependent());
		$this->assertEquals(['id', 'pageRoleId'], $pageTemplatesAssociation->getBindingKey());
		$this->assertEquals(['pageTemplateId', 'pageRoleId'], $pageTemplatesAssociation->getForeignKey());

		// Test Surveys association (BelongsTo)
		$this->assertTrue($this->pagesTable->hasAssociation('Surveys'));
		$surveysAssociation = $this->pagesTable->getAssociation('Surveys');
		$this->assertInstanceOf(BelongsTo::class, $surveysAssociation);
		$this->assertFalse($surveysAssociation->getCascadeCallbacks());
		$this->assertFalse($surveysAssociation->getDependent());

		// 'ParentPages' must also exist (from parent table implementation)
		$this->assertTrue($this->pagesTable->hasAssociation('ParentPages'));
		$parentPagesAssociation = $this->pagesTable->getAssociation('ParentPages');
		$this->assertInstanceOf(BelongsTo::class, $parentPagesAssociation);
		$this->assertFalse($parentPagesAssociation->getCascadeCallbacks());
		$this->assertFalse($parentPagesAssociation->getDependent());

		// 'ChildPages' must also exist (from parent table implementation)
		$this->assertTrue($this->pagesTable->hasAssociation('ChildPages'));
		$childPagesAssociation = $this->pagesTable->getAssociation('ChildPages');
		$this->assertInstanceOf(HasMany::class, $childPagesAssociation);
		$this->assertTrue($childPagesAssociation->getCascadeCallbacks());
		$this->assertTrue($childPagesAssociation->getDependent());

		// Test UrlHistory association (HasMany)
		$this->assertTrue($this->pagesTable->hasAssociation('UrlHistory'));
		$urlHistoryAssociation = $this->pagesTable->getAssociation('UrlHistory');
		$this->assertInstanceOf(HasMany::class, $urlHistoryAssociation);
		$this->assertTrue($urlHistoryAssociation->getCascadeCallbacks());
		$this->assertTrue($urlHistoryAssociation->getDependent());
		$this->assertEquals('foreignKey', $urlHistoryAssociation->getForeignKey());

		$this->assertTrue($this->pagesTable->hasAssociation('DuplicatingPages'));
		$duplicatingPagesAssociation = $this->pagesTable->getAssociation('DuplicatingPages');
		$this->assertInstanceOf(HasMany::class, $duplicatingPagesAssociation);
		$this->assertFalse($duplicatingPagesAssociation->getCascadeCallbacks());
		$this->assertFalse($duplicatingPagesAssociation->getDependent());

		$this->assertTrue($this->pagesTable->hasAssociation('DuplicateOfPage'));
		$duplicateOfPageAssociation = $this->pagesTable->getAssociation('DuplicateOfPage');
		$this->assertInstanceOf(BelongsTo::class, $duplicateOfPageAssociation);
		$this->assertFalse($duplicateOfPageAssociation->getCascadeCallbacks());
		$this->assertFalse($duplicateOfPageAssociation->getDependent());

		// 'CreatedByUser' must also exist
		$this->assertTrue($this->pagesTable->hasAssociation('CreatedByUser'));
		$createdByUserAssociation = $this->pagesTable->getAssociation('CreatedByUser');
		$this->assertInstanceOf(BelongsTo::class, $createdByUserAssociation);
		$this->assertFalse($createdByUserAssociation->getCascadeCallbacks());
		$this->assertFalse($createdByUserAssociation->getDependent());

		// 'ChangedByUser' must also exist
		$this->assertTrue($this->pagesTable->hasAssociation('ChangedByUser'));
		$changedByUserAssociation = $this->pagesTable->getAssociation('ChangedByUser');
		$this->assertInstanceOf(BelongsTo::class, $changedByUserAssociation);
		$this->assertFalse($changedByUserAssociation->getCascadeCallbacks());
		$this->assertFalse($changedByUserAssociation->getDependent());

		// 'DeletedByUser' must also exist
		$this->assertTrue($this->pagesTable->hasAssociation('DeletedByUser'));
		$deletedByUserAssociation = $this->pagesTable->getAssociation('DeletedByUser');
		$this->assertInstanceOf(BelongsTo::class, $deletedByUserAssociation);
		$this->assertFalse($deletedByUserAssociation->getCascadeCallbacks());
		$this->assertFalse($deletedByUserAssociation->getDependent());
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\PagesTable::initializeAssociations()
	 */
	public function testInitializeAssociationsCreatesPageRoleDependentAssociations(): void {
		$newsTable = FactoryLocator::get('Table')->get('News');

		// 'ParentNews' must also exist (from parent table implementation)
		$this->assertTrue($newsTable->hasAssociation('ParentNews'));
		$parentNewsAssociation = $newsTable->getAssociation('ParentNews');
		$this->assertInstanceOf(BelongsTo::class, $parentNewsAssociation);
		$this->assertFalse($parentNewsAssociation->getCascadeCallbacks());
		$this->assertFalse($parentNewsAssociation->getDependent());

		// 'ChildNews' must also exist (from parent table implementation)
		$this->assertTrue($newsTable->hasAssociation('ChildNews'));
		$childNewsAssociation = $newsTable->getAssociation('ChildNews');
		$this->assertInstanceOf(HasMany::class, $childNewsAssociation);
		$this->assertTrue($childNewsAssociation->getCascadeCallbacks());
		$this->assertTrue($childNewsAssociation->getDependent());
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\PagesTable::findActive()
	 */
	public function testFindActive(): void {
		$query = $this->pagesTable->find('active');

		/** @noinspection PhpConditionAlreadyCheckedInspection */
		$this->assertInstanceOf(SelectQuery::class, $query);

		// Verify the specific conditions are applied
		$sql = $query->sql();
		$this->assertStringContainsString('active = :c2', $sql);
		$this->assertStringContainsString('parentsActive = :c3', $sql);

		// Verify the bound values
		$valueBinder = $query->getValueBinder();
		$bindings = $valueBinder->bindings();

		$this->assertTrue($bindings[':c2']['value']);
		$this->assertTrue($bindings[':c3']['value']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\PagesTable::validationDefault()
	 */
	public function testValidationDefault(): void {
		$validator = new Validator();
		$result = $this->pagesTable->validationDefault($validator);

		$this->assertInstanceOf(Validator::class, $result);
		$this->assertSame('Pages', $result->getI18nDomain());

		// Test required fields
		$this->assertTrue($result->hasField('languageShortcode'));
		$this->assertSame('create', $result->field('languageShortcode')->isPresenceRequired());

		$this->assertTrue($result->hasField('slug'));
		$this->assertSame('create', $result->field('slug')->isPresenceRequired());

		$this->assertTrue($result->hasField('title'));
		$this->assertSame('create', $result->field('title')->isPresenceRequired());

		$this->assertTrue($result->hasField('pageRoleId'));
		$this->assertSame('create', $result->field('pageRoleId')->isPresenceRequired());

		$this->assertTrue($result->hasField('pageTemplateId'));
		$this->assertSame('create', $result->field('pageTemplateId')->isPresenceRequired());

		// Test other fields
		$this->assertTrue($result->hasField('id'));
		$this->assertTrue($result->hasField('parentId'));
		$this->assertTrue($result->hasField('redirectLink'));
		$this->assertTrue($result->hasField('metaTitle'));
		$this->assertTrue($result->hasField('metaDescription'));
		$this->assertTrue($result->hasField('robotsIndex'));
		$this->assertTrue($result->hasField('robotsFollow'));
		$this->assertTrue($result->hasField('duplicateOf'));
		$this->assertTrue($result->hasField('formId'));
		$this->assertTrue($result->hasField('surveyId'));
		$this->assertTrue($result->hasField('systemOrder'));
		$this->assertTrue($result->hasField('active'));
		$this->assertTrue($result->hasField('parentsActive'));
		$this->assertTrue($result->hasField('deleted'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\PagesTable::validationDefault()
	 */
	public function testEntityValidationSuccess(): void {
		$data = [
			'languageShortcode' => 'de',
			'slug' => 'test-page',
			'title' => 'Test Page',
			'pageRoleId' => 1,
			'pageTemplateId' => 1,
			'active' => true,
			'parentsActive' => true,
			'deleted' => false,
		];

		$entity = $this->pagesTable->newEntity($data);
		$errors = $entity->getErrors();

		$this->assertEmpty($errors, 'Valid data should not produce validation errors');
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\PagesTable::validationDefault()
	 */
	public function testEntityValidationMissingRequired(): void {
		$data = [
			'active' => true,
		];

		$entity = $this->pagesTable->newEntity($data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('languageShortcode', $errors);
		$this->assertArrayHasKey('slug', $errors);
		$this->assertArrayHasKey('title', $errors);
		$this->assertArrayHasKey('pageRoleId', $errors);
		$this->assertArrayHasKey('pageTemplateId', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\PagesTable::validationDefault()
	 */
	public function testEntityValidationInvalidTypes(): void {
		$data = [
			'id' => 'not_an_integer',
			'parentId' => 'not_an_integer',
			'languageShortcode' => true,
			'slug' => true,
			'title' => true,
			'redirectLink' => true,
			'metaTitle' => true,
			'metaDescription' => true,
			'robotsIndex' => 'not_a_boolean',
			'robotsFollow' => 'not_a_boolean',
			'pageRoleId' => 'not_an_enum_case',
			'pageTemplateId' => 'not_an_integer',
			'duplicateOf' => 'not_an_integer',
			'formId' => 'not_an_integer',
			'surveyId' => 'not_an_integer',
			'systemOrder' => 'not_an_integer',
			'active' => 'not_a_boolean',
			'parentsActive' => 'not_a_boolean',
			'deleted' => 'not_a_boolean',
		];

		$entity = $this->pagesTable->newEntity($data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('id', $errors);
		$this->assertArrayHasKey('parentId', $errors);
		$this->assertArrayHasKey('languageShortcode', $errors);
		$this->assertArrayHasKey('slug', $errors);
		$this->assertArrayHasKey('title', $errors);
		$this->assertArrayHasKey('redirectLink', $errors);
		$this->assertArrayHasKey('metaTitle', $errors);
		$this->assertArrayHasKey('metaDescription', $errors);
		$this->assertArrayHasKey('robotsIndex', $errors);
		$this->assertArrayHasKey('robotsFollow', $errors);

		$this->assertArrayHasKey('pageRoleId', $errors);
		$this->assertArrayHasKey('enum', $errors['pageRoleId']);
		$this->assertSame('pages::error_enum', $errors['pageRoleId']['enum']);

		$this->assertArrayHasKey('pageTemplateId', $errors);
		$this->assertArrayHasKey('duplicateOf', $errors);
		$this->assertArrayHasKey('formId', $errors);
		$this->assertArrayHasKey('surveyId', $errors);
		$this->assertArrayHasKey('systemOrder', $errors);
		$this->assertArrayHasKey('active', $errors);
		$this->assertArrayHasKey('parentsActive', $errors);
		$this->assertArrayHasKey('deleted', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\PagesTable::validationDefault()
	 */
	public function testEntityValidationFieldLength(): void {
		$data = [
			'id' => 123456789123, // exceeds 11 char limit
			'parentId' => 123456789123, // exceeds 11 char limit
			'languageShortcode' => 'eng', // exceeds 2 char limit
			'slug' => 'ab', // below 3 char minimum
			'title' => str_repeat('a', 256), // exceeds 255 char limit
			'redirectLink' => str_repeat('b', 256), // exceeds 255 char limit
			'metaTitle' => str_repeat('c', 101), // exceeds 100 char limit
			'metaDescription' => str_repeat('d', 65536), // exceeds byte limit
			'pageRoleId' => 123456789123, // exceeds 11 char limit
			'pageTemplateId' => 123456789123, // exceeds 11 char limit
			'duplicateOf' => 123456789123, // exceeds 11 char limit
			'formId' => 123456789123, // exceeds 11 char limit
			'surveyId' => 123456789123, // exceeds 11 char limit
		];

		$entity = $this->pagesTable->newEntity($data, ['guard' => false]);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('id', $errors);
		$this->assertArrayHasKey('parentId', $errors);
		$this->assertArrayHasKey('languageShortcode', $errors);
		$this->assertArrayHasKey('slug', $errors);
		$this->assertArrayHasKey('title', $errors);
		$this->assertArrayHasKey('redirectLink', $errors);
		$this->assertArrayHasKey('metaTitle', $errors);
		$this->assertArrayHasKey('metaDescription', $errors);
		$this->assertArrayHasKey('pageRoleId', $errors);
		$this->assertArrayHasKey('pageTemplateId', $errors);
		$this->assertArrayHasKey('duplicateOf', $errors);
		$this->assertArrayHasKey('formId', $errors);
		$this->assertArrayHasKey('surveyId', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\PagesTable::validationDefault()
	 */
	public function testEntityValidationLanguageShortcodeExactLength(): void {
		$data = [
			'languageShortcode' => 'e', // too short
			'slug' => 'test-page',
			'title' => 'Test Page',
			'pageRoleId' => 1,
			'pageTemplateId' => 1,
		];

		$entity = $this->pagesTable->newEntity($data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('languageShortcode', $errors);
		$this->assertArrayHasKey('exactLength', $errors['languageShortcode']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\PagesTable::validationDefault()
	 */
	public function testEntityValidationRedirectLinkUrl(): void {
		$data = [
			'languageShortcode' => 'de',
			'slug' => 'test-page',
			'title' => 'Test Page',
			'pageRoleId' => 1,
			'pageTemplateId' => 1,
			'redirectLink' => 'not-a-valid-url',
		];

		$entity = $this->pagesTable->newEntity($data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('redirectLink', $errors);
		$this->assertArrayHasKey('url', $errors['redirectLink']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\PagesTable::buildRules()
	 */
	public function testBuildRulesLanguageExistsValid(): void {
		// Test with existing language
		$data = [
			'languageShortcode' => 'de',
			'slug' => 'test-page',
			'title' => 'Test Page',
			'pageRoleId' => 1,
			'pageTemplateId' => 1,
		];

		$entity = $this->pagesTable->newEntity($data);
		$result = $this->pagesTable->checkRules($entity);
		$this->assertTrue($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\PagesTable::buildRules()
	 */
	public function testBuildRulesLanguageExistsInvalid(): void {
		// Test with non-existing language
		$data = [
			'languageShortcode' => 'xx',
			'slug' => 'test-page',
			'title' => 'Test Page',
			'pageRoleId' => 1,
			'pageTemplateId' => 1,
		];

		$entity = $this->pagesTable->newEntity($data);
		$result = $this->pagesTable->checkRules($entity);
		$this->assertFalse($result);

		$errors = $entity->getErrors();
		$this->assertArrayHasKey('languageShortcode', $errors);
		$this->assertArrayHasKey('languageExists', $errors['languageShortcode']);
		$this->assertEquals('pages::error_language_exists', $errors['languageShortcode']['languageExists']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\PagesTable::buildRules()
	 */
	public function testBuildRulesValidPageRoleValid(): void {
		// Test with existing page role
		$data = [
			'languageShortcode' => 'de',
			'slug' => 'test-page',
			'title' => 'Test Page',
			'pageRoleId' => 1, // Patching entity will convert to enum
			'pageTemplateId' => 1,
		];

		$entity = $this->pagesTable->newDefaultEntity();

		$this->pagesTable->patchEntity($entity, $data);

		$this->assertSame(PageRole::Page, $entity->pageRoleId);

		$result = $this->pagesTable->checkRules($entity);

		$this->assertTrue($result);

		$entity->pageRoleId = PageRole::News;
		$entity->pageTemplateId = 3;

		$result = $this->pagesTable->checkRules($entity);

		$this->assertTrue($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\PagesTable::buildRules()
	 */
	public function testBuildRulesValidPageRoleInvalid(): void {
		// Test with non-existing page role
		$data = [
			'languageShortcode' => 'de',
			'slug' => 'test-page',
			'title' => 'Test Page',
			'pageRoleId' => 99999, // Patching entity will convert to enum but fail here
			'pageTemplateId' => 1,
		];

		$entity = $this->pagesTable->newDefaultEntity();

		$this->pagesTable->patchEntity($entity, $data);

		$this->assertSame(PageRole::Page, $entity->pageRoleId);

		$result = $this->pagesTable->checkRules($entity);

		$this->assertTrue($result);

		$entity->pageRoleId = 'invalid';  // Setting a value directly will not convert to enum

		$result = $this->pagesTable->checkRules($entity);

		$this->assertFalse($result);

		$errors = $entity->getErrors();
		$this->assertArrayHasKey('pageRoleId', $errors);
		$this->assertArrayHasKey('validPageRoleId', $errors['pageRoleId']);
		$this->assertSame('pages::error_valid_page_role_id', $errors['pageRoleId']['validPageRoleId']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\PagesTable::buildRules()
	 */
	public function testBuildRulesValidPageTemplateValid(): void {
		// Test with existing page template matching page role
		$data = [
			'languageShortcode' => 'de',
			'slug' => 'test-page',
			'title' => 'Test Page',
			'pageRoleId' => 1,
			'pageTemplateId' => 1,
		];

		$entity = $this->pagesTable->newEntity($data);
		$result = $this->pagesTable->checkRules($entity);
		$this->assertTrue($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\PagesTable::buildRules()
	 */
	public function testBuildRulesValidPageTemplateInvalid(): void {
		// Test with non-existing page template
		$data = [
			'languageShortcode' => 'de',
			'slug' => 'test-page',
			'title' => 'Test Page',
			'pageRoleId' => 1,
			'pageTemplateId' => 99999,
		];

		$entity = $this->pagesTable->newEntity($data);
		$result = $this->pagesTable->checkRules($entity);
		$this->assertFalse($result);

		$errors = $entity->getErrors();
		$this->assertArrayHasKey('pageTemplateId', $errors);
		$this->assertArrayHasKey('validPageTemplate', $errors['pageTemplateId']);
		$this->assertEquals('pages::error_valid_page_template', $errors['pageTemplateId']['validPageTemplate']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\PagesTable::buildRules()
	 */
	public function testBuildRulesValidPageTemplateInvalidForPageRole(): void {
		// Test with page template not matching page role
		$data = [
			'languageShortcode' => 'de',
			'slug' => 'test-page',
			'title' => 'Test Page',
			'pageRoleId' => 2, // Different role
			'pageTemplateId' => 1, // Template for role 1
		];

		$entity = $this->pagesTable->newEntity($data);
		$result = $this->pagesTable->checkRules($entity);
		$this->assertFalse($result);

		$errors = $entity->getErrors();
		$this->assertArrayHasKey('pageTemplateId', $errors);
		$this->assertArrayHasKey('validPageTemplate', $errors['pageTemplateId']);
		$this->assertEquals('pages::error_valid_page_template', $errors['pageTemplateId']['validPageTemplate']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\PagesTable::buildRules()
	 */
	public function testBuildRulesValidFormIdValid(): void {
		// Test with valid form ID
		$data = [
			'languageShortcode' => 'de',
			'slug' => 'test-page',
			'title' => 'Test Page',
			'pageRoleId' => 1,
			'pageTemplateId' => 1,
			'formId' => 1,
		];

		$entity = $this->pagesTable->newEntity($data);
		$result = $this->pagesTable->checkRules($entity);
		$this->assertTrue($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\PagesTable::buildRules()
	 */
	public function testBuildRulesValidFormIdNull(): void {
		// Test with null form ID (should be valid)
		$nullFormData = [
			'languageShortcode' => 'de',
			'slug' => 'test-page',
			'title' => 'Test Page',
			'pageRoleId' => 1,
			'pageTemplateId' => 1,
			'formId' => null,
		];

		$nullFormEntity = $this->pagesTable->newEntity($nullFormData);
		$nullFormResult = $this->pagesTable->checkRules($nullFormEntity);
		$this->assertTrue($nullFormResult);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\PagesTable::buildRules()
	 */
	public function testBuildRulesValidFormIdInvalid(): void {
		// Test with non-existing form ID
		$data = [
			'languageShortcode' => 'de',
			'slug' => 'test-page',
			'title' => 'Test Page',
			'pageRoleId' => 1,
			'pageTemplateId' => 1,
			'formId' => 99999,
		];

		$entity = $this->pagesTable->newEntity($data);
		$result = $this->pagesTable->checkRules($entity);
		$this->assertFalse($result);

		$errors = $entity->getErrors();
		$this->assertArrayHasKey('formId', $errors);
		$this->assertArrayHasKey('validFormId', $errors['formId']);
		$this->assertEquals('validation::error_exists_in', $errors['formId']['validFormId']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\PagesTable::buildRules()
	 */
	public function testBuildRulesValidSurveyIdValid(): void {
		// Test with valid survey ID
		$data = [
			'languageShortcode' => 'de',
			'slug' => 'test-page',
			'title' => 'Test Page',
			'pageRoleId' => 1,
			'pageTemplateId' => 1,
			'surveyId' => 1,
		];

		$entity = $this->pagesTable->newEntity($data);
		$result = $this->pagesTable->checkRules($entity);
		$this->assertTrue($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\PagesTable::buildRules()
	 */
	public function testBuildRulesValidSurveyIdNull(): void {
		// Test with null survey ID (should be valid)
		$nullSurveyData = [
			'languageShortcode' => 'de',
			'slug' => 'test-page',
			'title' => 'Test Page',
			'pageRoleId' => 1,
			'pageTemplateId' => 1,
			'surveyId' => null,
		];

		$nullSurveyEntity = $this->pagesTable->newEntity($nullSurveyData);
		$nullSurveyResult = $this->pagesTable->checkRules($nullSurveyEntity);
		$this->assertTrue($nullSurveyResult);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\PagesTable::buildRules()
	 */
	public function testBuildRulesValidSurveyIdInvalid(): void {
		// Test with non-existing survey ID
		$data = [
			'languageShortcode' => 'de',
			'slug' => 'test-page',
			'title' => 'Test Page',
			'pageRoleId' => 1,
			'pageTemplateId' => 1,
			'surveyId' => 99999,
		];

		$entity = $this->pagesTable->newEntity($data);
		$result = $this->pagesTable->checkRules($entity);
		$this->assertFalse($result);

		$errors = $entity->getErrors();
		$this->assertArrayHasKey('surveyId', $errors);
		$this->assertArrayHasKey('validSurveyId', $errors['surveyId']);
		$this->assertEquals('validation::error_exists_in', $errors['surveyId']['validSurveyId']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\PagesTable::buildRules()
	 */
	public function testBuildRulesValidDuplicateOfValid(): void {
		// Test with valid duplicate of (existing page)
		$data = [
			'languageShortcode' => 'de',
			'slug' => 'test-page',
			'title' => 'Test Page',
			'pageRoleId' => 1,
			'pageTemplateId' => 1,
			'duplicateOf' => 1,
		];

		$entity = $this->pagesTable->newEntity($data);
		$result = $this->pagesTable->checkRules($entity);
		$this->assertTrue($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\PagesTable::buildRules()
	 */
	public function testBuildRulesValidDuplicateOfNull(): void {
		// Test with empty duplicate of (should be valid)
		$emptyDuplicateData = [
			'languageShortcode' => 'de',
			'slug' => 'test-page',
			'title' => 'Test Page',
			'pageRoleId' => 1,
			'pageTemplateId' => 1,
			'duplicateOf' => null,
		];

		$emptyDuplicateEntity = $this->pagesTable->newEntity($emptyDuplicateData);
		$emptyDuplicateResult = $this->pagesTable->checkRules($emptyDuplicateEntity);
		$this->assertTrue($emptyDuplicateResult);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\PagesTable::buildRules()
	 */
	public function testBuildRulesValidDuplicateOfInvalid(): void {
		// Test with non-existing duplicate target
		$data = [
			'languageShortcode' => 'de',
			'slug' => 'test-page',
			'title' => 'Test Page',
			'pageRoleId' => 2,
			'pageTemplateId' => 1,
			'duplicateOf' => 1,
		];

		$entity = $this->pagesTable->newEntity($data);
		$result = $this->pagesTable->checkRules($entity);
		$this->assertFalse($result);

		$errors = $entity->getErrors();
		$this->assertArrayHasKey('duplicateOf', $errors);
		$this->assertArrayHasKey('validDuplicateOf', $errors['duplicateOf']);
		$this->assertEquals('pages::error_valid_duplicate_of', $errors['duplicateOf']['validDuplicateOf']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\PagesTable::buildRules()
	 */
	public function testBuildRulesValidDuplicateOfInvalidForDifferentPageRole(): void {
		// Test with non-existing duplicate target
		$data = [
			'languageShortcode' => 'de',
			'slug' => 'test-page',
			'title' => 'Test Page',
			'pageRoleId' => 1,
			'pageTemplateId' => 1,
			'duplicateOf' => 99999, // Non-existing page
		];

		$entity = $this->pagesTable->newEntity($data);
		$result = $this->pagesTable->checkRules($entity);
		$this->assertFalse($result);

		$errors = $entity->getErrors();
		$this->assertArrayHasKey('duplicateOf', $errors);
		$this->assertArrayHasKey('validDuplicateOf', $errors['duplicateOf']);
		$this->assertEquals('pages::error_valid_duplicate_of', $errors['duplicateOf']['validDuplicateOf']);
	}


	/**
	 * Prevent a page (current) from duplicating itself (target).
	 *
	 * @return void
	 * @see \Awyiss\Model\Table\PagesTable::buildRules()
	 */
	public function testBuildRulesValidDuplicateOfSelf(): void {
		$entity = $this->pagesTable->newEntity([
			'languageShortcode' => 'de',
			'slug' => 'test-page',
			'title' => 'Test Page',
			'pageRoleId' => 1,
			'pageTemplateId' => 1,
			'duplicateOf' => 123,
		]);
		$entity->set('id', 123);
		$entity->setNew(false);

		$result = $this->pagesTable->checkRules($entity);
		$this->assertFalse($result);

		$errors = $entity->getErrors();
		$this->assertArrayHasKey('duplicateOf', $errors);
		$this->assertArrayHasKey('validDuplicateOf', $errors['duplicateOf']);
		$this->assertEquals('pages::error_not_self_duplicating', $errors['duplicateOf']['validDuplicateOf']);
	}


	/**
	 * Prevent a page (current) from duplicating another one (target),
	 * if the (current) page is already duplicated by a page (third).
	 *
	 * @return void
	 * @see \Awyiss\Model\Table\PagesTable::buildRules()
	 */
	public function testBuildRulesValidDuplicateOfAlreadyDuplicated(): void {
		$entity = $this->pagesTable->newEntity([
			'languageShortcode' => 'de',
			'slug' => 'test-page',
			'title' => 'Test Page',
			'pageRoleId' => 1,
			'pageTemplateId' => 1,
			'duplicateOf' => 10,
		]);
		$entity->set('id', 32); // Page 32 is already duplicated by 33
		$entity->setNew(false);

		$result = $this->pagesTable->checkRules($entity);
		$this->assertFalse($result);

		$errors = $entity->getErrors();
		$this->assertArrayHasKey('duplicateOf', $errors);
		$this->assertArrayHasKey('validDuplicateOf', $errors['duplicateOf']);
		$this->assertEquals('pages::error_not_duplicating_duplicated', $errors['duplicateOf']['validDuplicateOf']);
	}


	/**
	 * Prevents a page (current) from duplicating another page (target),
	 * if the (target) page is already duplicating another page (third).
	 *
	 * @return void
	 * @see \Awyiss\Model\Table\PagesTable::buildRules()
	 */
	public function testBuildRulesValidDuplicateOfDuplicatingDuplicating(): void {
		$entity = $this->pagesTable->newEntity([
			'languageShortcode' => 'de',
			'slug' => 'test-page',
			'title' => 'Test Page',
			'pageRoleId' => 1,
			'pageTemplateId' => 1,
			'duplicateOf' => 33, // Page 33 is duplicating another page (32)
		]);

		$result = $this->pagesTable->checkRules($entity);
		$this->assertFalse($result);

		$errors = $entity->getErrors();
		$this->assertArrayHasKey('duplicateOf', $errors);
		$this->assertArrayHasKey('validDuplicateOf', $errors['duplicateOf']);
		$this->assertEquals('pages::error_not_duplicating_duplicating', $errors['duplicateOf']['validDuplicateOf']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\PagesTable::buildDeleteRules()
	 */
	public function testBuildDeleteRulesNoDuplicatingPages(): void {
		/** @var \Awyiss\Model\Entity\Page $page */
		$page = $this->pagesTable->get(1);

		$result = $this->pagesTable->checkRules($page, RulesChecker::DELETE);
		$this->assertFalse($result);

		$errors = $page->getErrors();
		$this->assertArrayHasKey('_general', $errors);
		$this->assertArrayHasKey('noDuplicatingPages', $errors['_general']);
		$this->assertEquals('pages::error_no_duplicating_pages', $errors['_general']['noDuplicatingPages']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\PagesTable::buildDeleteRules()
	 */
	public function testBuildDeleteRulesNoDuplicatingSubpages(): void {
		/** @var \Awyiss\Model\Entity\Page $page */
		$page = $this->pagesTable->get(2); // Page 2 has subpages that are used as duplicates

		$result = $this->pagesTable->checkRules($page, RulesChecker::DELETE);
		$this->assertFalse($result);

		$errors = $page->getErrors();
		$this->assertArrayHasKey('_general', $errors);
		$this->assertArrayHasKey('noDuplicatingPages', $errors['_general']);
		$this->assertEquals('pages::error_no_duplicating_pages', $errors['_general']['noDuplicatingPages']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\PagesTable::buildDeleteRules()
	 */
	public function testBuildDeleteRulesNoDuplicatedContents(): void {
		/** @var \Awyiss\Model\Entity\Page $page */
		$page = $this->pagesTable->get(25); // Content 32 on Page 25 gets duplicated by Content 31 on Page 29

		$result = $this->pagesTable->checkRules($page, RulesChecker::DELETE);
		$this->assertFalse($result);

		$errors = $page->getErrors();
		$this->assertArrayHasKey('_general', $errors);
		$this->assertArrayHasKey('noDuplicatedContents', $errors['_general']);
		$this->assertEquals('pages::error_no_duplicated_contents', $errors['_general']['noDuplicatedContents']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\PagesTable::buildDeleteRules()
	 * @see \Awyiss\Model\Table\PagesTable::hasDescendantsWithDifferentPageRole()
	 */
	public function testBuildDeleteRulesNoNestedChildrenWithDifferentPageRole(): void {
		/** @var \Awyiss\Model\Entity\Page $page */
		$page = $this->pagesTable->get(7); // Page with nested children of different page roles

		$result = $this->pagesTable->checkRules($page, RulesChecker::DELETE);
		$this->assertFalse($result);

		$errors = $page->getErrors();
		$this->assertArrayHasKey('_general', $errors);
		$this->assertArrayHasKey('noNestedChildrenWithDifferentPageRole', $errors['_general']);
		$this->assertEquals('pages::error_no_nested_children_with_different_page_role', $errors['_general']['noNestedChildrenWithDifferentPageRole']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\PagesTable::buildDeleteRules()
	 */
	public function testBuildDeleteRulesSuccess(): void {
		/** @var \Awyiss\Model\Entity\Page $page */
		$page = $this->pagesTable->get(5); // Page with no blocking conditions

		$result = $this->pagesTable->checkRules($page, RulesChecker::DELETE);
		$this->assertTrue($result);

		$errors = $page->getErrors();
		$this->assertEmpty($errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\PagesTable::getNestedPages()
	 */
	public function testGetNestedPages(): void {
		/** @var \Awyiss\Model\Entity\Page $entity */
		$entity = $this->pagesTable->get(1);
		$pages = $this->pagesTable->getNestedPages($entity)?->toArray();

		$this->assertNull($pages);

		/** @var \Awyiss\Model\Entity\Page $entity */
		$entity = $this->pagesTable->get(2);
		$pages = $this->pagesTable->getNestedPages($entity)?->toArray();

		$this->assertNotNull($pages);
		$this->assertCount(13, $pages);

		// Make sure children with different page roles are included as well
		$pageRoles = array_map(
			function (PageRole $pageRole): int {
				return $pageRole->value;
			},
			array_column($pages, 'pageRoleId')
		);
		$uniquePageRoles = array_unique($pageRoles);

		$this->assertNotEmpty($uniquePageRoles);
		$this->assertCount(3, $uniquePageRoles);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\PagesTable::getPossibleFieldValues()
	 * @throws \ReflectionException
	 */
	public function testGetPossibleFieldValuesFormId(): void {
		$result = $this->pagesTable->getPossibleFieldValues('formId');

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
	 * @see \Awyiss\Model\Table\PagesTable::getPossibleFieldValues()
	 * @throws \ReflectionException
	 */
	public function testGetPossibleFieldValuesDuplicateOf(): void {
		$result = $this->pagesTable->getPossibleFieldValues('duplicateOf');

		$this->assertIsArray($result);

		$this->assertSame([
			1 => 'Startseite',
			2 => 'Über uns',
			3 => '- Unternehmensgeschichte',
			4 => '- Mission und Vision',
			5 => '- Teamvorstellung',
			6 => '- Zertifikate und Auszeichnungen',
			7 => '- Aktuelles',
			8 => 'Dienstleistungen',
			9 => '- Seefracht',
			10 => '- Luftfracht',
			11 => '- Landtransport',
			12 => '- Lagerung und Logistik',
			13 => '- Zollabwicklung',
			14 => 'Flotte',
			15 => '- Übersicht der Schiffe',
			16 => '- Technische Daten',
			17 => '- Sicherheitsstandards',
			18 => '- Umweltfreundlichkeit',
			19 => 'Kundenbereich',
			20 => '- Anmeldung/Registrierung',
			22 => '- Dokumentenverwaltung',
			23 => '- Rechnungsübersicht',
			24 => 'Karriere',
			25 => '- Offene Stellen',
			26 => '- Ausbildungsprogramme',
			27 => '- Mitarbeiterbenefits',
			28 => '- Bewerbungsprozess',
			29 => 'Kontakt',
			30 => 'Impressum',
			31 => 'Datenschutzrichtlinien',
			32 => 'Fehler 404',
			33 => 'Fehler 410',
		], $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\PagesTable::getPossibleFieldValues()
	 * @throws \ReflectionException
	 */
	public function testGetPossibleFieldValuesPageTemplateId(): void {
		$result = $this->pagesTable->getPossibleFieldValues('pageTemplateId');

		$this->assertIsArray($result);

		$this->assertSame([
			1 => 'Standard',
			2 => 'Mit Seitenteaser',
			4 => 'Unused',
		], $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\PagesTable::getPossibleFieldValues()
	 * @throws \ReflectionException
	 */
	public function testGetPossibleFieldValuesPageTemplateIdForDifferentPageRole(): void {
		/** @var \Customer\Model\Table\NewsTable $newsTable */
		$newsTable = FactoryLocator::get('Table')->get('News');
		$result = $newsTable->getPossibleFieldValues('pageTemplateId');

		$this->assertIsArray($result);
		$this->assertSame([
			3 => 'Standard',
		], $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\PagesTable::getPossibleFieldValues()
	 * @throws \ReflectionException
	 */
	public function testGetPossibleFieldValuesSurveyId(): void {
		$result = $this->pagesTable->getPossibleFieldValues('surveyId');

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
	 * @see \Awyiss\Model\Table\PagesTable::$categories
	 */
	public function testCategoriesBehavior(): void {
		$this->assertTrue($this->pagesTable->hasBehavior('Categories'));

		$behavior = $this->pagesTable->getBehavior('Categories');
		$config = $behavior->getConfig();

		$this->assertSame('forCurrentLanguage', $config['finder']);
		$this->assertSame('parentId', $config['foreignKey']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\PagesTable::$nest
	 */
	public function testNestBehavior(): void {
		$this->assertTrue($this->pagesTable->hasBehavior('Nest'));

		$behavior = $this->pagesTable->getBehavior('Nest');
		$config = $behavior->getConfig();

		$this->assertTrue($config['enabled']);
		$this->assertSame(['languageShortcode', 'pageRoleId'], $config['relatedColumns']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\PagesTable::$search
	 */
	public function testSearchBehavior(): void {
		$this->assertTrue($this->pagesTable->hasBehavior('Search'));

		$behavior = $this->pagesTable->getBehavior('Search');
		$config = $behavior->getConfig();

		$this->assertSame(['languageShortcode', 'pageRoleId'], $config['blocklistedColumns']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\PagesTable::$systemOrder
	 */
	public function testSystemOrderBehavior(): void {
		$this->assertTrue($this->pagesTable->hasBehavior('SystemOrder'));

		$behavior = $this->pagesTable->getBehavior('SystemOrder');
		$config = $behavior->getConfig();

		$this->assertSame(['languageShortcode', 'pageRoleId', 'parentId'], $config['relatedColumns']);
	}
}
