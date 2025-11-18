<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Model\Table;


use Awyiss\Middleware\DesignMiddleware;
use Awyiss\Model\Entity\Content;
use Awyiss\Model\Entity\Page;
use Awyiss\Model\Table\ContentsTable;
use Awyiss\ORM\Association\BelongsTo;
use Awyiss\ORM\Association\HasMany;
use Awyiss\ORM\Association\HasOne;
use Awyiss\Routing\Router;
use Awyiss\Test\TestSuite\TestCase;
use Awyiss\Utility\Content\AwyissColumn;
use Awyiss\Utility\Content\AwyissColumnSystem;
use Awyiss\Validation\Validator;
use Cake\Datasource\FactoryLocator;
use Cake\Datasource\Locator\LocatorInterface;
use Cake\Datasource\RulesChecker;
use Cake\ORM\Query\SelectQuery;
use Customer\Model\Enum\PageRole;


/**
 * ContentsTable Test Case
 *
 * @see \Awyiss\Model\Table\ContentsTable
 */
class ContentsTableTest extends TestCase {
	/**
	 * @var \Awyiss\Model\Table\ContentsTable
	 */
	protected ContentsTable $contentsTable;
	/**
	 * @var \Cake\Datasource\Locator\LocatorInterface
	 */
	protected LocatorInterface $tableLocator;


	/**
	 * @inheritDoc
	 * @throws \Exception
	 */
	protected function setUp(): void {
		parent::setUp();

		/** @noinspection PhpFieldAssignmentTypeMismatchInspection */
		$this->contentsTable = FactoryLocator::get('Table')->get('Contents');
		$this->contentsTable->forPageRole(PageRole::Page);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\ContentsTable::ATTRIBUTABLE
	 */
	public function testAttributableConstant(): void {
		/** @noinspection PhpUnitAssertTrueWithIncompatibleTypeArgumentInspection */
		$this->assertTrue($this->contentsTable::ATTRIBUTABLE);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\ContentsTable::TABLE
	 */
	public function testTableConstant(): void {
		$this->assertEquals('contents', $this->contentsTable::TABLE);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\ContentsTable::initializeAssociations()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testInitializeAssociations(): void {
		$this->assertCount(14, $this->contentsTable->associations()->keys());

		$this->assertTrue($this->contentsTable->hasAssociation('AttributesContents'));
		$attributesAssociation = $this->contentsTable->getAssociation('AttributesContents');
		$this->assertInstanceOf(HasOne::class, $attributesAssociation);
		$this->assertTrue($attributesAssociation->getCascadeCallbacks());
		$this->assertTrue($attributesAssociation->getDependent());

		// Test ContentAreas association (BelongsTo with INNER join)
		$this->assertTrue($this->contentsTable->hasAssociation('ContentAreas'));
		$contentAreasAssociation = $this->contentsTable->getAssociation('ContentAreas');
		$this->assertInstanceOf(BelongsTo::class, $contentAreasAssociation);
		$this->assertEquals('INNER', $contentAreasAssociation->getJoinType());
		$this->assertFalse($contentAreasAssociation->getCascadeCallbacks());
		$this->assertFalse($contentAreasAssociation->getDependent());

		// Test ContentTemplates association (BelongsTo)
		$this->assertTrue($this->contentsTable->hasAssociation('ContentTemplates'));
		$contentTemplatesAssociation = $this->contentsTable->getAssociation('ContentTemplates');
		$this->assertInstanceOf(BelongsTo::class, $contentTemplatesAssociation);
		$this->assertFalse($contentTemplatesAssociation->getCascadeCallbacks());
		$this->assertFalse($contentTemplatesAssociation->getDependent());

		// Test DuplicatingContents association (HasMany)
		$this->assertTrue($this->contentsTable->hasAssociation('DuplicatingContents'));
		$duplicatingContentsAssociation = $this->contentsTable->getAssociation('DuplicatingContents');
		$this->assertInstanceOf(HasMany::class, $duplicatingContentsAssociation);
		$this->assertEquals('duplicate_of', $duplicatingContentsAssociation->getBindingKey());
		$this->assertEquals('id', $duplicatingContentsAssociation->getForeignKey());
		$this->assertEquals('Contents', $duplicatingContentsAssociation->getClassName());

		// Test DuplicateOfContents association (BelongsTo)
		$this->assertTrue($this->contentsTable->hasAssociation('DuplicateOfContents'));
		$duplicateOfContentsAssociation = $this->contentsTable->getAssociation('DuplicateOfContents');
		$this->assertInstanceOf(BelongsTo::class, $duplicateOfContentsAssociation);
		$this->assertEquals('id', $duplicateOfContentsAssociation->getBindingKey());
		$this->assertEquals('duplicate_of', $duplicateOfContentsAssociation->getForeignKey());
		$this->assertEquals('Contents', $duplicateOfContentsAssociation->getClassName());

		// Test Forms association (BelongsTo)
		$this->assertTrue($this->contentsTable->hasAssociation('Forms'));
		$formsAssociation = $this->contentsTable->getAssociation('Forms');
		$this->assertInstanceOf(BelongsTo::class, $formsAssociation);
		$this->assertFalse($formsAssociation->getCascadeCallbacks());
		$this->assertFalse($formsAssociation->getDependent());

		// Test Surveys association (BelongsTo)
		$this->assertTrue($this->contentsTable->hasAssociation('Surveys'));
		$surveysAssociation = $this->contentsTable->getAssociation('Surveys');
		$this->assertInstanceOf(BelongsTo::class, $surveysAssociation);
		$this->assertFalse($surveysAssociation->getCascadeCallbacks());
		$this->assertFalse($surveysAssociation->getDependent());

		// Test Pages association (BelongsTo, set up by forPageRole)
		$this->assertTrue($this->contentsTable->hasAssociation('Pages'));
		$pagesAssociation = $this->contentsTable->getAssociation('Pages');
		$this->assertInstanceOf(BelongsTo::class, $pagesAssociation);
		$this->assertEquals('id', $pagesAssociation->getBindingKey());
		$this->assertEquals('page_id', $pagesAssociation->getForeignKey());
		$this->assertEquals('forCurrentLanguage', $pagesAssociation->getFinder());
		$this->assertEquals('page', $pagesAssociation->getProperty());

		// 'ParentContents' must also exist (from parent table implementation)
		$this->assertTrue($this->contentsTable->hasAssociation('ParentContents'));
		$parentContentsAssociation = $this->contentsTable->getAssociation('ParentContents');
		$this->assertInstanceOf(BelongsTo::class, $parentContentsAssociation);
		$this->assertFalse($parentContentsAssociation->getCascadeCallbacks());
		$this->assertFalse($parentContentsAssociation->getDependent());

		// 'ChildContents' must also exist (from parent table implementation)
		$this->assertTrue($this->contentsTable->hasAssociation('ChildContents'));
		$childContentsAssociation = $this->contentsTable->getAssociation('ChildContents');
		$this->assertInstanceOf(HasMany::class, $childContentsAssociation);
		$this->assertTrue($childContentsAssociation->getCascadeCallbacks());
		$this->assertTrue($childContentsAssociation->getDependent());

		// 'MediaAssignments' must also exist
		$this->assertTrue($this->contentsTable->hasAssociation('MediaAssignments'));
		$mediaAssignmentsAssociation = $this->contentsTable->getAssociation('MediaAssignments');
		$this->assertInstanceOf(HasMany::class, $mediaAssignmentsAssociation);
		$this->assertTrue($mediaAssignmentsAssociation->getCascadeCallbacks());
		$this->assertTrue($mediaAssignmentsAssociation->getDependent());

		// 'CreatedByUser' must also exist
		$this->assertTrue($this->contentsTable->hasAssociation('CreatedByUser'));
		$createdByUserAssociation = $this->contentsTable->getAssociation('CreatedByUser');
		$this->assertInstanceOf(BelongsTo::class, $createdByUserAssociation);
		$this->assertFalse($createdByUserAssociation->getCascadeCallbacks());
		$this->assertFalse($createdByUserAssociation->getDependent());

		// 'ChangedByUser' must also exist
		$this->assertTrue($this->contentsTable->hasAssociation('ChangedByUser'));
		$changedByUserAssociation = $this->contentsTable->getAssociation('ChangedByUser');
		$this->assertInstanceOf(BelongsTo::class, $changedByUserAssociation);
		$this->assertFalse($changedByUserAssociation->getCascadeCallbacks());
		$this->assertFalse($changedByUserAssociation->getDependent());

		// 'DeletedByUser' must also exist
		$this->assertTrue($this->contentsTable->hasAssociation('DeletedByUser'));
		$deletedByUserAssociation = $this->contentsTable->getAssociation('DeletedByUser');
		$this->assertInstanceOf(BelongsTo::class, $deletedByUserAssociation);
		$this->assertFalse($deletedByUserAssociation->getCascadeCallbacks());
		$this->assertFalse($deletedByUserAssociation->getDependent());
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\ContentsTable::getColumnSystemClass()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testGetColumnSystemClass(): void {
		$columnSystemClass = $this->contentsTable->getColumnSystemClass();

		$this->assertSame(AwyissColumnSystem::class, $columnSystemClass);
		$this->assertTrue(class_exists($columnSystemClass));
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\ContentsTable::getColumnWidths()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testGetColumnWidths(): void {
		$columnWidths = $this->contentsTable->getColumnWidths();

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
	 * @see \Awyiss\Model\Table\ContentsTable::getColumnIndents()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testGetColumnIndents(): void {
		$columnIndents = $this->contentsTable->getColumnIndents();

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
	 * @see \Awyiss\Model\Table\ContentsTable::findLatestForPages()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testFindLatestForPages(): void {
		$query = $this->contentsTable->find('all');
		$query = $this->contentsTable->findLatestForPages($query);

		/** @noinspection PhpConditionAlreadyCheckedInspection */
		$this->assertInstanceOf(SelectQuery::class, $query);

		// Verify the specific fields are selected
		//$this->assertEquals(['page_id', 'id', 'changed_on', 'created_on'], $query->clause('select'));

		// Verify ordering and grouping
		$sql = $query->sql();
		$this->assertStringContainsString('GROUP BY Contents.page_id', $sql);
		$this->assertStringContainsString('ORDER BY Contents.changed_on DESC, Contents.created_on DESC', $sql);

		$result = $query->all();

		$this->assertCount(8, $result);

		$first = $result->first();

		$this->assertSame(22, $first->id);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\ContentsTable::getAllowedKeyForDuplicating()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testGetAllowedKeyForDuplicating(): void {
		$allowedKeys = $this->contentsTable->getAllowedKeyForDuplicating();

		$this->assertIsArray($allowedKeys);
		$this->assertEquals([
			'active',
			'pageId',
			'contentAreaId',
			'parentId',
			'columnWidth',
			'columnIndent',
			'columnLast',
			'columnRtl',
			'duplicateOf',
			'systemOrder',
		], $allowedKeys);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\ContentsTable::forPageRole()
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testForPageRole(): void {
		/** @var \Awyiss\Model\Table\ContentsTable $forNewsTable */
		$forNewsTable = FactoryLocator::get('Table')->get('Contents');

		// Test that the initial state is correct
		$this->assertEquals('pages', $forNewsTable->getForScope());

		$forNewsTable->forPageRole(PageRole::News);

		// Test that the page role is set correctly
		$this->assertEquals(PageRole::News, $forNewsTable->getPageRole());

		// Test that the News association was created
		$this->assertTrue($forNewsTable->hasAssociation('News'));
		$newsAssociation = $forNewsTable->getAssociation('News');
		$this->assertInstanceOf(BelongsTo::class, $newsAssociation);
		$this->assertEquals('id', $newsAssociation->getBindingKey());
		$this->assertEquals('page_id', $newsAssociation->getForeignKey());
		$this->assertEquals('forCurrentLanguage', $newsAssociation->getFinder());
		$this->assertEquals('page', $newsAssociation->getProperty());

		// Test that the scope is set correctly
		$this->assertEquals('news', $forNewsTable->getForScope());

		$pageRole = $this->contentsTable->getPageRole();

		$this->assertEquals(PageRole::News, $pageRole);

		/** @var \Awyiss\Model\Table\ContentsTable $childContentsAssociation */
		$childContentsAssociation = $forNewsTable->getAssociation('ChildContents');
		$this->assertEquals('news', $childContentsAssociation->getForScope());

		/** @var \Awyiss\Model\Table\ContentsTable $parentContentsAssociation */
		$parentContentsAssociation = $forNewsTable->getAssociation('ParentContents');
		$this->assertEquals('news', $parentContentsAssociation->getForScope());
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\ContentsTable::getForScope()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testGetForScope(): void {
		$this->assertEquals('pages', $this->contentsTable->getForScope());
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\ContentsTable::getPageRole()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testGetPageRole(): void {
		$pageRole = $this->contentsTable->getPageRole();

		$this->assertEquals(PageRole::Page, $pageRole);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\ContentsTable::getPage()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testGetPage(): void {
		$page = $this->contentsTable->getPage(1);

		/** @noinspection PhpConditionAlreadyCheckedInspection */
		$this->assertInstanceOf(Page::class, $page);
		$this->assertEquals(1, $page->id);
		$this->assertNotNull($page->pageTemplate);
		$this->assertNotNull($page->pageTemplate->contentAreas);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\ContentsTable::validationDefault()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testValidationDefault(): void {
		$validator = new Validator();
		$result = $this->contentsTable->validationDefault($validator);

		$this->assertInstanceOf(Validator::class, $result);
		$this->assertSame('contents', $result->getI18nDomain());

		// Test required fields
		$this->assertTrue($result->hasField('pageId'));
		$this->assertSame('create', $result->field('pageId')->isPresenceRequired());

		$this->assertTrue($result->hasField('contentAreaId'));
		$this->assertSame('create', $result->field('contentAreaId')->isPresenceRequired());

		$this->assertTrue($result->hasField('contentTemplateId'));
		$this->assertSame('create', $result->field('contentTemplateId')->isPresenceRequired());

		// Test other fields
		$this->assertTrue($result->hasField('id'));
		$this->assertTrue($result->hasField('parentId'));
		$this->assertTrue($result->hasField('title'));
		$this->assertTrue($result->hasField('subtitle'));
		$this->assertTrue($result->hasField('text'));
		$this->assertTrue($result->hasField('link'));
		$this->assertTrue($result->hasField('columnWidth'));
		$this->assertTrue($result->hasField('columnIndent'));
		$this->assertTrue($result->hasField('columnLast'));
		$this->assertTrue($result->hasField('columnRtl'));
		$this->assertTrue($result->hasField('cssClass'));
		$this->assertTrue($result->hasField('duplicateOf'));
		$this->assertTrue($result->hasField('data'));
		$this->assertTrue($result->hasField('formId'));
		$this->assertTrue($result->hasField('surveyId'));
		$this->assertTrue($result->hasField('systemOrder'));
		$this->assertTrue($result->hasField('active'));
		$this->assertTrue($result->hasField('deleted'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\ContentsTable::validationDefault()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testEntityValidationSuccess(): void {
		$data = [
			'pageId' => 1,
			'contentAreaId' => 1,
			'contentTemplateId' => 1,
			'title' => 'Test Content',
			'subtitle' => 'Test Subtitle',
			'text' => 'Test text content',
			'link' => 'https://example.com',
			'cssClass' => 'test-class',
			'data' => ['key' => 'value'],
			'systemOrder' => 1,
			'active' => true,
			'deleted' => false,
		];

		$entity = $this->contentsTable->newEntity($data);
		$errors = $entity->getErrors();

		$this->assertEmpty($errors, 'Valid data should not produce validation errors');
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\ContentsTable::validationDefault()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testEntityValidationMissingRequired(): void {
		$data = [
			'title' => 'Test Content',
		];

		$entity = $this->contentsTable->newEntity($data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('pageId', $errors);
		$this->assertArrayHasKey('contentAreaId', $errors);
		$this->assertArrayHasKey('contentTemplateId', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\ContentsTable::validationDefault()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testEntityValidationInvalidTypes(): void {
		$data = [
			'id' => 'not_an_integer',
			'pageId' => 'not_an_integer',
			'parentId' => 'not_an_integer',
			'title' => true,
			'subtitle' => true,
			'text' => true,
			'link' => true,
			'contentAreaId' => 'not_an_integer',
			'contentTemplateId' => 'not_an_integer',
			'columnLast' => 'not_a_boolean',
			'columnRtl' => 'not_a_boolean',
			'cssClass' => true,
			'duplicateOf' => 'not_an_integer',
			'data' => 'not_an_array',
			'formId' => 'not_an_integer',
			'surveyId' => 'not_an_integer',
			'systemOrder' => 'not_an_integer',
			'active' => 'not_a_boolean',
			'deleted' => 'not_a_boolean',
		];

		$entity = $this->contentsTable->newEntity($data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('id', $errors);
		$this->assertArrayHasKey('pageId', $errors);
		$this->assertArrayHasKey('parentId', $errors);
		$this->assertArrayHasKey('title', $errors);
		$this->assertArrayHasKey('subtitle', $errors);
		$this->assertArrayHasKey('text', $errors);
		$this->assertArrayHasKey('link', $errors);
		$this->assertArrayHasKey('contentAreaId', $errors);
		$this->assertArrayHasKey('contentTemplateId', $errors);
		$this->assertArrayHasKey('columnLast', $errors);
		$this->assertArrayHasKey('columnRtl', $errors);
		$this->assertArrayHasKey('cssClass', $errors);
		$this->assertArrayHasKey('duplicateOf', $errors);
		$this->assertArrayHasKey('data', $errors);
		$this->assertArrayHasKey('formId', $errors);
		$this->assertArrayHasKey('surveyId', $errors);
		$this->assertArrayHasKey('systemOrder', $errors);
		$this->assertArrayHasKey('active', $errors);
		$this->assertArrayHasKey('deleted', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\ContentsTable::validationDefault()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testEntityValidationFieldLength(): void {
		$data = [
			'id' => 123456789123, // exceeds 11 char limit
			'pageId' => 123456789123, // exceeds 11 char limit
			'parentId' => 123456789123, // exceeds 11 char limit
			'title' => str_repeat('a', 256), // exceeds 255 char limit
			'subtitle' => str_repeat('b', 256), // exceeds 255 char limit
			'text' => str_repeat('c', 65536), // exceeds 65535 byte limit
			'link' => str_repeat('d', 256), // exceeds 255 char limit
			'contentAreaId' => 123456789123, // exceeds 11 char limit
			'contentTemplateId' => 123456789123, // exceeds 11 char limit
			'cssClass' => str_repeat('e', 256), // exceeds 255 char limit
			'duplicateOf' => 123456789123, // exceeds 11 char limit
			'formId' => 123456789123, // exceeds 11 char limit
			'surveyId' => 123456789123, // exceeds 11 char limit
		];

		$entity = $this->contentsTable->newEntity($data, ['guard' => false]);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('id', $errors);
		$this->assertArrayHasKey('pageId', $errors);
		$this->assertArrayHasKey('parentId', $errors);
		$this->assertArrayHasKey('title', $errors);
		$this->assertArrayHasKey('subtitle', $errors);
		$this->assertArrayHasKey('text', $errors);
		$this->assertArrayHasKey('link', $errors);
		$this->assertArrayHasKey('contentAreaId', $errors);
		$this->assertArrayHasKey('contentTemplateId', $errors);
		$this->assertArrayHasKey('cssClass', $errors);
		$this->assertArrayHasKey('duplicateOf', $errors);
		$this->assertArrayHasKey('formId', $errors);
		$this->assertArrayHasKey('surveyId', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\ContentsTable::validationDefault()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testEntityValidationColumnWidthInList(): void {
		// Test valid column width
		$data = [
			'pageId' => 1,
			'contentAreaId' => 1,
			'contentTemplateId' => 1,
			'columnWidth' => '3/5',
		];

		$entity = $this->contentsTable->newEntity($data);
		$errors = $entity->getErrors();

		$this->assertArrayNotHasKey('columnWidth', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\ContentsTable::validationDefault()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testEntityValidationColumnWidthNotInList(): void {
		// Test invalid column width
		$data = [
			'pageId' => 1,
			'contentAreaId' => 1,
			'contentTemplateId' => 1,
			'columnWidth' => 'invalid_column_width',
		];

		$entity = $this->contentsTable->newEntity($data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('columnWidth', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\ContentsTable::validationDefault()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testEntityValidationColumnIndentInList(): void {
		// Test valid column indent
		$data = [
			'pageId' => 1,
			'contentAreaId' => 1,
			'contentTemplateId' => 1,
			'columnIndent' => '2/5',
		];

		$entity = $this->contentsTable->newEntity($data);
		$errors = $entity->getErrors();

		$this->assertArrayNotHasKey('columnIndent', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\ContentsTable::validationDefault()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testEntityValidationColumnIndentNotInList(): void {
		// Test invalid column indent
		$data = [
			'pageId' => 1,
			'contentAreaId' => 1,
			'contentTemplateId' => 1,
			'columnIndent' => 'invalid_column_indent',
		];

		$entity = $this->contentsTable->newEntity($data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('columnIndent', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\ContentsTable::validationDefault()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testEntityValidationDataArrayMaxLength(): void {
		$largeData = array_fill(0, 10000, str_repeat('x', 100)); // Create very large array

		$data = [
			'pageId' => 1,
			'contentAreaId' => 1,
			'contentTemplateId' => 1,
			'data' => $largeData,
		];

		$entity = $this->contentsTable->newEntity($data, ['guard' => false]);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('data', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\ContentsTable::buildRules()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testBuildRulesValidContentArea(): void {
		// Test with valid page, content template, and content area combination
		$data = [
			'pageId' => 1,
			'contentAreaId' => 1,
			'contentTemplateId' => 1,
			'systemOrder' => 1,
		];

		$entity = $this->contentsTable->newEntity($data);
		$result = $this->contentsTable->checkRules($entity);

		$this->assertTrue($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\ContentsTable::buildRules()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testBuildRulesInvalidPageId(): void {
		// Test with non-existing page
		$data = [
			'pageId' => 99999,
			'contentAreaId' => 1,
			'contentTemplateId' => 1,
			'systemOrder' => 1,
		];

		$entity = $this->contentsTable->newEntity($data);
		$result = $this->contentsTable->checkRules($entity);

		$this->assertFalse($result);

		$errors = $entity->getErrors();
		$this->assertArrayHasKey('pageId', $errors);
		$this->assertArrayHasKey('validPageId', $errors['pageId']);
		$this->assertEquals('contents::error_valid_page_id', $errors['pageId']['validPageId']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\ContentsTable::buildRules()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testBuildRulesInvalidContentTemplateId(): void {
		// Test with non-existing content template
		$data = [
			'pageId' => 1,
			'contentAreaId' => 1,
			'contentTemplateId' => 99999,
			'systemOrder' => 1,
		];

		$entity = $this->contentsTable->newEntity($data);
		$result = $this->contentsTable->checkRules($entity);

		$this->assertFalse($result);

		$errors = $entity->getErrors();
		$this->assertArrayHasKey('contentTemplateId', $errors);
		$this->assertArrayHasKey('validContentTemplateId', $errors['contentTemplateId']);
		$this->assertEquals('contents::error_valid_content_template_id', $errors['contentTemplateId']['validContentTemplateId']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\ContentsTable::buildRules()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testBuildRulesInvalidContentAreaId(): void {
		// Test with content area not assigned to content template
		$data = [
			'pageId' => 1,
			'contentAreaId' => 2, // Area not assigned to template 1
			'contentTemplateId' => 1,
			'systemOrder' => 1,
		];

		$entity = $this->contentsTable->newEntity($data);
		$result = $this->contentsTable->checkRules($entity);

		$this->assertFalse($result);

		$errors = $entity->getErrors();
		$this->assertArrayHasKey('contentAreaId', $errors);
		$this->assertArrayHasKey('validContentAreaId', $errors['contentAreaId']);
		$this->assertEquals('contents::error_valid_content_area_id', $errors['contentAreaId']['validContentAreaId']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\ContentsTable::buildRules()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testBuildRulesValidFormId(): void {
		// Test with existing form
		$data = [
			'pageId' => 1,
			'contentAreaId' => 1,
			'contentTemplateId' => 2,
			'formId' => 1,
			'systemOrder' => 1,
		];

		$entity = $this->contentsTable->newEntity($data);
		$result = $this->contentsTable->checkRules($entity);

		$this->assertTrue($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\ContentsTable::buildRules()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testBuildRulesNullFormId(): void {
		// Test with null form (should be allowed)
		$data = [
			'pageId' => 1,
			'contentAreaId' => 1,
			'contentTemplateId' => 2,
			'formId' => null,
			'systemOrder' => 1,
		];

		$entity = $this->contentsTable->newEntity($data);
		$result = $this->contentsTable->checkRules($entity);

		$this->assertTrue($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\ContentsTable::buildRules()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testBuildRulesInvalidFormId(): void {
		// Test with non-existing form
		$data = [
			'pageId' => 1,
			'contentAreaId' => 1,
			'contentTemplateId' => 2,
			'formId' => 99999,
			'systemOrder' => 1,
		];

		$entity = $this->contentsTable->newEntity($data);
		$result = $this->contentsTable->checkRules($entity);

		$this->assertFalse($result);

		$errors = $entity->getErrors();
		$this->assertArrayHasKey('formId', $errors);
		$this->assertArrayHasKey('validFormId', $errors['formId']);
		$this->assertEquals('validation::error_exists_in', $errors['formId']['validFormId']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\ContentsTable::buildRules()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testBuildRulesValidSurveyId(): void {
		// Test with existing survey
		$data = [
			'pageId' => 1,
			'contentAreaId' => 1,
			'contentTemplateId' => 2,
			'surveyId' => 1,
			'systemOrder' => 1,
		];

		$entity = $this->contentsTable->newEntity($data);
		$result = $this->contentsTable->checkRules($entity);

		$this->assertTrue($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\ContentsTable::buildRules()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testBuildRulesNullSurveyId(): void {
		// Test with null survey (should be allowed)
		$data = [
			'pageId' => 1,
			'contentAreaId' => 1,
			'contentTemplateId' => 2,
			'surveyId' => null,
			'systemOrder' => 1,
		];

		$entity = $this->contentsTable->newEntity($data);
		$result = $this->contentsTable->checkRules($entity);

		$this->assertTrue($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\ContentsTable::buildRules()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testBuildRulesInvalidSurveyId(): void {
		// Test with non-existing survey
		$data = [
			'pageId' => 1,
			'contentAreaId' => 1,
			'contentTemplateId' => 2,
			'surveyId' => 99999,
			'systemOrder' => 1,
		];

		$entity = $this->contentsTable->newEntity($data);
		$result = $this->contentsTable->checkRules($entity);

		$this->assertFalse($result);

		$errors = $entity->getErrors();
		$this->assertArrayHasKey('surveyId', $errors);
		$this->assertArrayHasKey('validSurveyId', $errors['surveyId']);
		$this->assertEquals('validation::error_exists_in', $errors['surveyId']['validSurveyId']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\ContentsTable::buildRules()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testBuildRulesValidWidthIndentCombination(): void {
		// Test valid width/indent combination
		$data = [
			'pageId' => 1,
			'contentAreaId' => 1,
			'contentTemplateId' => 1,
			'columnWidth' => '3/5',
			'columnIndent' => '2/5',
			'systemOrder' => 1,
		];

		$entity = $this->contentsTable->newEntity($data);
		$result = $this->contentsTable->checkRules($entity);

		$this->assertTrue($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\ContentsTable::buildRules()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testBuildRulesInvalidWidthIndentCombination(): void {
		// Test invalid width/indent combination
		$data = [
			'pageId' => 1,
			'contentAreaId' => 1,
			'contentTemplateId' => 1,
			'columnWidth' => '3/5',
			'columnIndent' => '3/5', // Invalid combination (should not exceed 1)
			'systemOrder' => 1,
		];

		$entity = $this->contentsTable->newEntity($data);
		$result = $this->contentsTable->checkRules($entity);

		$this->assertFalse($result);

		$errors = $entity->getErrors();
		$this->assertArrayHasKey('_general', $errors);
		$this->assertArrayHasKey('validWidthIndentCombination', $errors['_general']);
		$this->assertEquals('contents::error_valid_width_indent_combination', $errors['_general']['validWidthIndentCombination']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\ContentsTable::buildRules()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testBuildRulesValidDuplicateOf(): void {
		// Test with valid duplicate of (existing content on different page)
		$data = [
			'pageId' => 7,
			'contentAreaId' => 1,
			'contentTemplateId' => 1,
			'duplicateOf' => 1, // Content 1 is on page 1
			'systemOrder' => 1,
		];

		$entity = $this->contentsTable->newEntity($data);
		$result = $this->contentsTable->checkRules($entity);

		$this->assertTrue($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\ContentsTable::buildRules()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testBuildRulesInvalidDuplicateOfNonExisting(): void {
		// Test with non-existing duplicate target
		$data = [
			'pageId' => 1,
			'contentAreaId' => 1,
			'contentTemplateId' => 1,
			'duplicateOf' => 99999, // Non-existing content
			'systemOrder' => 1,
		];

		$entity = $this->contentsTable->newEntity($data);
		$result = $this->contentsTable->checkRules($entity);

		$this->assertFalse($result);

		$errors = $entity->getErrors();
		$this->assertArrayHasKey('duplicateOf', $errors);
		$this->assertArrayHasKey('validDuplicateOf', $errors['duplicateOf']);
		$this->assertEquals('contents::error_valid_duplicate_of', $errors['duplicateOf']['validDuplicateOf']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\ContentsTable::buildRules()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testBuildRulesInvalidDuplicateOfSamePage(): void {
		// Test with duplicate target on same page
		$data = [
			'pageId' => 1,
			'contentAreaId' => 1,
			'contentTemplateId' => 1,
			'duplicateOf' => 2, // Content 2 is also on page 1
			'systemOrder' => 1,
		];

		$entity = $this->contentsTable->newEntity($data);
		$result = $this->contentsTable->checkRules($entity);

		$this->assertFalse($result);

		$errors = $entity->getErrors();
		$this->assertArrayHasKey('duplicateOf', $errors);
		$this->assertArrayHasKey('validDuplicateOf', $errors['duplicateOf']);
		$this->assertEquals('contents::error_duplicate_not_on_same_page', $errors['duplicateOf']['validDuplicateOf']);
	}


	/**
	 * Prevent a content (current) from duplicating itself (target).
	 *
	 * @return void
	 * @see \Awyiss\Model\Table\ContentsTable::buildRules()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testBuildRulesInvalidDuplicateOfSelf(): void {
		$entity = $this->contentsTable->newEntity([
			'pageId' => 1,
			'contentAreaId' => 1,
			'contentTemplateId' => 1,
			'duplicateOf' => 123,
			'systemOrder' => 1,
		]);
		$entity->set('id', 123);
		$entity->setNew(false);

		$result = $this->contentsTable->checkRules($entity);
		$this->assertFalse($result);

		$errors = $entity->getErrors();
		$this->assertArrayHasKey('duplicateOf', $errors);
		$this->assertArrayHasKey('validDuplicateOf', $errors['duplicateOf']);
		$this->assertEquals('contents::error_not_self_duplicating', $errors['duplicateOf']['validDuplicateOf']);
	}


	/**
	 * Prevent a content (current) from duplicating another one (target),
	 * if the (current) content is already duplicated by a content (third).
	 *
	 * @return void
	 * @see \Awyiss\Model\Table\ContentsTable::buildRules()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testBuildRulesInvalidDuplicateOfAlreadyDuplicated(): void {
		$entity = $this->contentsTable->newEntity([
			'pageId' => 7,
			'contentAreaId' => 1,
			'contentTemplateId' => 1,
			'duplicateOf' => 10,
			'systemOrder' => 1,
		]);
		$entity->set('id', 32); // Content 32 is already duplicated by content 31
		$entity->setNew(false);

		$result = $this->contentsTable->checkRules($entity);
		$this->assertFalse($result);

		$errors = $entity->getErrors();
		$this->assertArrayHasKey('duplicateOf', $errors);
		$this->assertArrayHasKey('validDuplicateOf', $errors['duplicateOf']);
		$this->assertEquals('contents::error_not_duplicating_duplicated', $errors['duplicateOf']['validDuplicateOf']);
	}


	/**
	 * Prevents a content (current) from duplicating another content (target),
	 * if the (target) content is already duplicating another content (third).
	 *
	 * @return void
	 * @see \Awyiss\Model\Table\ContentsTable::buildRules()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testBuildRulesInvalidDuplicateOfDuplicatingContent(): void {
		$entity = $this->contentsTable->newEntity([
			'pageId' => 7,
			'contentAreaId' => 1,
			'contentTemplateId' => 1,
			'duplicateOf' => 31, // Content 31 is duplicating content 32
			'systemOrder' => 1,
		]);

		$result = $this->contentsTable->checkRules($entity);
		$this->assertFalse($result);

		$errors = $entity->getErrors();
		$this->assertArrayHasKey('duplicateOf', $errors);
		$this->assertArrayHasKey('validDuplicateOf', $errors['duplicateOf']);
		$this->assertEquals('contents::error_not_duplicating_duplicating', $errors['duplicateOf']['validDuplicateOf']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\ContentsTable::buildRules()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testBuildDeleteRulesNoDuplicatingContents(): void {
		/** @var \Awyiss\Model\Entity\Content $content */
		$content = $this->contentsTable->get(32); // Content 32 is duplicated by content 31

		$result = $this->contentsTable->checkRules($content, RulesChecker::DELETE);
		$this->assertFalse($result);

		$errors = $content->getErrors();
		$this->assertArrayHasKey('_general', $errors);
		$this->assertArrayHasKey('noDuplicatingContents', $errors['_general']);
		$this->assertEquals('contents::error_no_duplicating_contents', $errors['_general']['noDuplicatingContents']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\ContentsTable::buildRules()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testBuildDeleteRulesSuccess(): void {
		/** @var \Awyiss\Model\Entity\Content $content */
		$content = $this->contentsTable->get(15); // Content with no blocking conditions

		$result = $this->contentsTable->checkRules($content, RulesChecker::DELETE);
		$this->assertTrue($result);

		$errors = $content->getErrors();
		$this->assertEmpty($errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\ContentsTable::newDefaultEntity()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testNewDefaultEntity(): void {
		$entity = $this->contentsTable->newDefaultEntity();

		$this->assertInstanceOf(Content::class, $entity);
		$this->assertTrue($entity->isNew());

		$this->assertNull($entity->pageId);
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
		$this->assertNull($entity->duplicateOf);
		$this->assertNull($entity->data);
		$this->assertNull($entity->formId);
		$this->assertNull($entity->surveyId);
		$this->assertTrue($entity->active);
		$this->assertFalse($entity->deleted);
		$this->assertSame(0, $entity->systemOrder);
		$this->assertNull($entity->contentAreaId);
		$this->assertNull($entity->contentTemplateId);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\ContentsTable::newDefaultEntity()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testNewDefaultEntityWithData(): void {
		$additionalData = [
			'active' => false,
			'pageId' => 1,
			'contentAreaId' => 1,
			'contentTemplateId' => 1,
			'title' => 'Custom Content',
			'subtitle' => 'Custom Subtitle',
			'text' => 'Custom text',
			'link' => 'https://custom.com',
			'systemOrder' => 5,
			'data' => [
				'custom_key' => 'custom_value',
			],
		];

		$entity = $this->contentsTable->newDefaultEntity($additionalData);

		$this->assertInstanceOf(Content::class, $entity);
		$this->assertTrue($entity->isNew());

		$this->assertFalse($entity->active);
		$this->assertFalse($entity->deleted);

		$this->assertSame(5, $entity->systemOrder);
		$this->assertSame(1, $entity->pageId);
		$this->assertSame(1, $entity->contentAreaId);
		$this->assertSame(1, $entity->contentTemplateId);
		$this->assertSame('Custom Content', $entity->title);
		$this->assertSame('Custom Subtitle', $entity->subtitle);
		$this->assertSame('Custom text', $entity->text);
		$this->assertSame('https://custom.com', $entity->link);

		$this->assertSame(['custom_key' => 'custom_value'], $entity->data);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\ContentsTable::disableCascadeCallbacks()
	 * @see \Awyiss\Model\Table\ContentsTable::enableCascadeCallbacks()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testCascadeCallbacksToggle(): void {
		// Test disabling cascade callbacks
		$this->contentsTable->disableCascadeCallbacks();
		$childContentsAssociation = $this->contentsTable->getAssociation('ChildContents');
		$this->assertFalse($childContentsAssociation->getDependent());
		$this->assertFalse($childContentsAssociation->getCascadeCallbacks());

		// Test enabling cascade callbacks
		$this->contentsTable->enableCascadeCallbacks();
		$childContentsAssociation = $this->contentsTable->getAssociation('ChildContents');
		$this->assertTrue($childContentsAssociation->getDependent());
		$this->assertTrue($childContentsAssociation->getCascadeCallbacks());
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\ContentsTable::$categories
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testCategoriesBehavior(): void {
		$this->assertTrue($this->contentsTable->hasBehavior('Categories'));

		$behavior = $this->contentsTable->getBehavior('Categories');
		$config = $behavior->getConfig();

		$this->assertFalse($config['allowAggregation']);
		$this->assertTrue($config['enabled']);
		$this->assertSame('pageId', $config['field']);
		$this->assertSame('page', $config['identifier']);
		$this->assertSame('Pages', $config['associationName']); // Set by forPageRole
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\ContentsTable::$nest
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testNestBehavior(): void {
		$this->assertTrue($this->contentsTable->hasBehavior('Nest'));

		$behavior = $this->contentsTable->getBehavior('Nest');
		$config = $behavior->getConfig();

		$this->assertTrue($config['enabled']);
		$this->assertSame(['pageId', 'contentAreaId'], $config['relatedColumns']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\ContentsTable::$search
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testSearchBehavior(): void {
		$this->assertTrue($this->contentsTable->hasBehavior('Search'));

		$behavior = $this->contentsTable->getBehavior('Search');
		$config = $behavior->getConfig();

		$this->assertSame(['page_id'], $config['blocklistedColumns']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\ContentsTable::$systemOrder
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testSystemOrderBehavior(): void {
		$this->assertTrue($this->contentsTable->hasBehavior('SystemOrder'));

		$behavior = $this->contentsTable->getBehavior('SystemOrder');
		$config = $behavior->getConfig();

		$this->assertSame(['pageId', 'contentAreaId', 'parentId'], $config['relatedColumns']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\ContentsTable::initializeSchema()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testInitializeSchemaDataColumn(): void {
		$schema = $this->contentsTable->getSchema();
		// Test that data column is configured as JSON type
		$this->assertSame('json', $schema->getColumnType('data'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\ContentsTable::getPossibleFieldValues()
	 * @throws \ReflectionException
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testGetPossibleFieldValuesFormId(): void {
		$result = $this->contentsTable->getPossibleFieldValues('form_id');

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
	 * @see \Awyiss\Model\Table\ContentsTable::getPossibleFieldValues()
	 * @throws \ReflectionException
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testGetPossibleFieldValuesDuplicateOf(): void {
		$result = $this->contentsTable->getPossibleFieldValues('duplicate_of');

		$this->assertIsArray($result);
		$this->assertSame([
			1 => 'Template: Inhaltsblock',
			9 => '- (h1) H1-Überschrift',
			10 => '- Testuntertitel3 mit Verlinkung',
			12 => '- - Template: Standard',
			11 => '- (h3) Beatae culpa ex molestiae nobis nulla quidem rem, voluptas',
			13 => '- - Preisgruppe 1 Preisgruppe 2     Montag - Freitag 99,- 82,-   Samstag & Sonntag 123,- 118,.   ',
			14 => '- Lorem ipsum dolor sit amet, consectetur adipisicing elit. Aliquid, animi commodi cum dolor enim et e...',
			15 => '- - Template: Standard',
			25 => 'Template: Inhaltsblock',
			26 => '- (h1) Aktuelle Meldungen',
			27 => 'Template: Inhaltsblock',
			28 => '- (h1) Dit war nüscht, wa?',
			33 => 'Template: Inhaltsblock',
			32 => '- (h1) Titel H1',
			34 => '- - BLABLABLAAAAAAAAAAAAAAA',
			35 => 'Template: Inhaltsblock',
			36 => '- (h1) Historie',
			43 => 'Zwischenüberschrift (h2) mit integriertem Zeilenumbruch. In Farbe.',
			50 => 'Template: Standard',
			52 => '- Atjkjque doloribus enim harum incidunt laudantium quos repellat…',
			53 => '- Testuntertitel3 mit Verlinkung',
			54 => '- - logo-awyiss.svg',
			55 => '- Beatae culpa ex molestiae nobis nulla quidem rem, voluptas',
			56 => '- - Preisgruppe 1',
			51 => '- Lorem ipsum dolor sit amet, consectetur adipisicing elit. Aliquid, animi commodi cum dolor enim et e...',
			57 => '- - logo-awyiss2.jpg',
			2 => 'contents::inactive logo-awyiss.jpg',
			8 => '- Module: test',
			30 => 'Template: Inhaltsblock',
			31 => '- contents::duplicate_of: (h1) Titel H1 (ID: 32)',
			37 => 'Template: Inhaltsblock',
			38 => '- contents::inactive (h2) Bildergalerie',
			39 => '- - logo-awyiss.jpg',
			40 => '- - Template: Standard',
			41 => '- - Template: Standard',
			42 => '- - Template: Standard',
			44 => 'logo-awyiss.jpg',
			3 => 'Template: Inhaltsblock',
			16 => '- contents::inactive (h2) Aktuelle Meldungen',
			45 => 'Content with inline img tagbetween two paragraphs',
			48 => '- Nested content',
			47 => '- - Nested content',
			4 => 'Template: Inhaltsblock',
			17 => '- Template: Standard',
			46 => 'contents::survey_id: Dummy Survey',
			5 => 'Template: Inhaltsblock',
			18 => '- Zwischenüberschrift (h2) mit integriertem Zeilenumbruch. In Farbe.',
			20 => '- Template: Standard',
			19 => '- (h3) Testtitel',
			6 => 'Template: Inhaltsblock',
			21 => '- (h3) Block #1',
			22 => '- (h3) Block #2',
			23 => '- (h3) Block #3',
			7 => 'Template: Inhaltsblock',
			24 => '- Spezielle Headline (h2),',
		], $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\ContentsTable::getPossibleFieldValues()
	 * @throws \ReflectionException
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testGetPossibleFieldValuesContentTemplateId(): void {
		$result = $this->contentsTable->getPossibleFieldValues('content_template_id');

		$this->assertIsArray($result);
		$this->assertSame([
			1 => 'Standard',
			2 => 'Inhaltsblock',
		], $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\ContentsTable::getPossibleFieldValues()
	 * @throws \ReflectionException
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testGetPossibleFieldValuesSurveyId(): void {
		$result = $this->contentsTable->getPossibleFieldValues('survey_id');

		$this->assertIsArray($result);

		$this->assertSame([
			1 => 'Dummy Survey',
			2 => 'surveys::inactive Dummy Survey (Inactive)',
			3 => 'Dummy Survey (Inline Image)',
			4 => 'Dummy Survey (Survey Results)',
		], $result);
	}


	/**
	 * Test valid SCSS compilation
	 *
	 * @return void
	 * @see \Awyiss\Model\Table\ContentsTable::buildRules()
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testBuildRulesValidScss(): void {
		// Mock DesignMiddleware with design variables
		$designMiddlewareMock = $this->createMock(DesignMiddleware::class);
		$designMiddlewareMock->method('getDesignVariables')->willReturn([]);

		$request = Router::getRequest();
		$request = $request->withAttribute('design', $designMiddlewareMock);
		Router::setRequest($request);

		// Test with valid SCSS
		$data = [
			'pageId' => 1,
			'contentAreaId' => 1,
			'contentTemplateId' => 1,
			'systemOrder' => 1,
			'css' => 'background-color: #ff0000; color: #ffffff;',
		];

		$entity = $this->contentsTable->newEntity($data);
		$result = $this->contentsTable->checkRules($entity);

		$this->assertTrue($result);
		$errors = $entity->getErrors();
		$this->assertArrayNotHasKey('css', $errors);
	}


	/**
	 * Test invalid SCSS compilation
	 *
	 * @return void
	 * @see \Awyiss\Model\Table\ContentsTable::buildRules()
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testBuildRulesInvalidScss(): void {
		// Mock DesignMiddleware with design variables
		$designMiddlewareMock = $this->createMock(DesignMiddleware::class);
		$designMiddlewareMock->method('getDesignVariables')->willReturn([]);

		$request = Router::getRequest();
		$request = $request->withAttribute('design', $designMiddlewareMock);
		Router::setRequest($request);

		// Test with invalid SCSS (unclosed brace)
		$data = [
			'pageId' => 1,
			'contentAreaId' => 1,
			'contentTemplateId' => 1,
			'systemOrder' => 1,
			'css' => 'background-color: #ff0000; &:hover { color: #ffffff;',
		];

		$entity = $this->contentsTable->newEntity($data);
		$result = $this->contentsTable->checkRules($entity);

		$this->assertFalse($result);
		$errors = $entity->getErrors();
		$this->assertArrayHasKey('css', $errors);
		$this->assertArrayHasKey('validCss', $errors['css']);
	}


	/**
	 * Test SCSS with known variables (from design settings)
	 *
	 * @return void
	 * @see \Awyiss\Model\Table\ContentsTable::buildRules()
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testBuildRulesScssWithKnownVariables(): void {
		// Mock DesignMiddleware with design variables
		$designMiddlewareMock = $this->createMock(DesignMiddleware::class);
		$designMiddlewareMock->method('getDesignVariables')->willReturn([
			'primaryColor' => '#ff0000',
			'secondaryColor' => '#00ff00',
			'fontSize' => '16px',
		]);

		$request = Router::getRequest();
		$request = $request->withAttribute('design', $designMiddlewareMock);
		Router::setRequest($request);

		// Test with SCSS using known variables
		$data = [
			'pageId' => 1,
			'contentAreaId' => 1,
			'contentTemplateId' => 1,
			'systemOrder' => 1,
			'css' => 'background-color: $primaryColor; color: $secondaryColor; font-size: $fontSize;',
		];

		$entity = $this->contentsTable->newEntity($data);
		$result = $this->contentsTable->checkRules($entity);

		$this->assertTrue($result);
		$errors = $entity->getErrors();
		$this->assertArrayNotHasKey('css', $errors);
	}


	/**
	 * Test SCSS with unknown variables
	 *
	 * @return void
	 * @see \Awyiss\Model\Table\ContentsTable::buildRules()
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testBuildRulesScssWithUnknownVariables(): void {
		// Mock DesignMiddleware with design variables
		$designMiddlewareMock = $this->createMock(DesignMiddleware::class);
		$designMiddlewareMock->method('getDesignVariables')->willReturn([
			'primaryColor' => '#ff0000',
		]);

		$request = Router::getRequest();
		$request = $request->withAttribute('design', $designMiddlewareMock);
		Router::setRequest($request);

		// Test with SCSS using unknown variable
		$data = [
			'pageId' => 1,
			'contentAreaId' => 1,
			'contentTemplateId' => 1,
			'systemOrder' => 1,
			'css' => 'background-color: $unknownVariable;',
		];

		$entity = $this->contentsTable->newEntity($data);
		$result = $this->contentsTable->checkRules($entity);

		$this->assertFalse($result);
		$errors = $entity->getErrors();
		$this->assertArrayHasKey('css', $errors);
		$this->assertArrayHasKey('validCss', $errors['css']);
	}


	/**
	 * Test that @import is rejected in CSS
	 *
	 * @return void
	 * @see \Awyiss\Model\Table\ContentsTable::buildRules()
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testBuildRulesCssWithImport(): void {
		// Mock DesignMiddleware with design variables
		$designMiddlewareMock = $this->createMock(DesignMiddleware::class);
		$designMiddlewareMock->method('getDesignVariables')->willReturn([]);

		$request = Router::getRequest();
		$request = $request->withAttribute('design', $designMiddlewareMock);
		Router::setRequest($request);

		// Test with @import statement
		$data = [
			'pageId' => 1,
			'contentAreaId' => 1,
			'contentTemplateId' => 1,
			'systemOrder' => 1,
			'css' => '@import "some-file.css"; background-color: #ff0000;',
		];

		$entity = $this->contentsTable->newEntity($data);
		$result = $this->contentsTable->checkRules($entity);

		$this->assertFalse($result);
		$errors = $entity->getErrors();
		$this->assertArrayHasKey('css', $errors);
		$this->assertArrayHasKey('validCss', $errors['css']);
	}


	/**
	 * Test that valid SCSS with nested selectors is accepted
	 *
	 * @return void
	 * @see \Awyiss\Model\Table\ContentsTable::buildRules()
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testBuildRulesValidNestedScss(): void {
		// Mock DesignMiddleware with design variables
		$designMiddlewareMock = $this->createMock(DesignMiddleware::class);
		$designMiddlewareMock->method('getDesignVariables')->willReturn([]);

		$request = Router::getRequest();
		$request = $request->withAttribute('design', $designMiddlewareMock);
		Router::setRequest($request);

		// Test with valid nested SCSS
		$data = [
			'pageId' => 1,
			'contentAreaId' => 1,
			'contentTemplateId' => 1,
			'systemOrder' => 1,
			'css' => 'background-color: #ff0000; &:hover { background-color: #00ff00; }',
		];

		$entity = $this->contentsTable->newEntity($data);
		$result = $this->contentsTable->checkRules($entity);

		$this->assertTrue($result);
		$errors = $entity->getErrors();
		$this->assertArrayNotHasKey('css', $errors);
	}


	/**
	 * Test that empty CSS is valid
	 *
	 * @return void
	 * @see \Awyiss\Model\Table\ContentsTable::buildRules()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testBuildRulesEmptyCss(): void {
		// Test with empty CSS
		$data = [
			'pageId' => 1,
			'contentAreaId' => 1,
			'contentTemplateId' => 1,
			'systemOrder' => 1,
			'css' => '',
		];

		$entity = $this->contentsTable->newEntity($data);
		$result = $this->contentsTable->checkRules($entity);

		$this->assertTrue($result);
		$errors = $entity->getErrors();
		$this->assertArrayNotHasKey('css', $errors);
	}


	/**
	 * Test that null CSS is valid
	 *
	 * @return void
	 * @see \Awyiss\Model\Table\ContentsTable::buildRules()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testBuildRulesNullCss(): void {
		// Test with null CSS
		$data = [
			'pageId' => 1,
			'contentAreaId' => 1,
			'contentTemplateId' => 1,
			'systemOrder' => 1,
			'css' => null,
		];

		$entity = $this->contentsTable->newEntity($data);
		$result = $this->contentsTable->checkRules($entity);

		$this->assertTrue($result);
		$errors = $entity->getErrors();
		$this->assertArrayNotHasKey('css', $errors);
	}
}
