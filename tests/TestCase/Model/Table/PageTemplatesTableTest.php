<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Model\Table;


use Awyiss\Annotation\MediaElementAssignable;
use Awyiss\Model\Entity\PageTemplate;
use Awyiss\Model\Table\PageTemplatesTable;
use Awyiss\ORM\Association\BelongsTo;
use Awyiss\ORM\Association\BelongsToMany;
use Awyiss\ORM\Association\HasMany;
use Awyiss\ORM\Association\HasOne;
use Awyiss\Test\TestSuite\TestCase;
use Awyiss\Validation\Validator;
use Cake\Database\Expression\AggregateExpression;
use Cake\Datasource\FactoryLocator;
use Cake\Datasource\RulesChecker;
use Cake\ORM\Query\SelectQuery;
use Customer\Model\Enum\PageRole;
use ReflectionClass;


/**
 * PageTemplatesTable Test Case
 *
 * @see \Awyiss\Model\Table\PageTemplatesTable
 */
class PageTemplatesTableTest extends TestCase {
	/**
	 * @var \Awyiss\Model\Table\PageTemplatesTable
	 */
	protected PageTemplatesTable $pageTemplatesTable;


	/**
	 * @inheritDoc
	 */
	protected function setUp(): void {
		parent::setUp();

		/** @noinspection PhpFieldAssignmentTypeMismatchInspection */
		$this->pageTemplatesTable = FactoryLocator::get('Table')->get('PageTemplates');
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\PageTemplatesTable::ATTRIBUTABLE
	 */
	public function testAttributableConstant(): void {
		/** @noinspection PhpUnitAssertTrueWithIncompatibleTypeArgumentInspection */
		$this->assertTrue($this->pageTemplatesTable::ATTRIBUTABLE);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\PageTemplatesTable::TABLE
	 */
	public function testTableConstant(): void {
		$this->assertEquals('page_templates', $this->pageTemplatesTable::TABLE);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\PageTemplatesTable
	 */
	public function testMediaElementAssignableAttribute(): void {
		$reflection = new ReflectionClass(PageTemplatesTable::class);
		$attributes = $reflection->getAttributes(MediaElementAssignable::class);

		$this->assertCount(1, $attributes);

		$attribute = $attributes[0];
		$this->assertSame(MediaElementAssignable::class, $attribute->getName());

		$instance = $attribute->newInstance();
		$this->assertInstanceOf(MediaElementAssignable::class, $instance);
		$this->assertSame(MediaElementAssignable::ENTITY_LEVEL, $instance->level);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\PageTemplatesTable::initializeAssociations()
	 */
	public function testInitializeAssociations(): void {
		$this->assertCount(11, $this->pageTemplatesTable->associations()->keys());

		// Test ContentAreas association (BelongsToMany)
		$this->assertTrue($this->pageTemplatesTable->hasAssociation('ContentAreas'));
		$contentAreasAssociation = $this->pageTemplatesTable->getAssociation('ContentAreas');
		$this->assertInstanceOf(BelongsToMany::class, $contentAreasAssociation);
		$this->assertFalse($contentAreasAssociation->getCascadeCallbacks());
		$this->assertTrue($contentAreasAssociation->getDependent());
		$this->assertEquals('PageTemplateContentAreas', $contentAreasAssociation->getThrough());

		// Test ContentTemplateContentAreas association (HasMany)
		$this->assertTrue($this->pageTemplatesTable->hasAssociation('ContentTemplateContentAreas'));
		$contentTemplateContentAreasAssociation = $this->pageTemplatesTable->getAssociation('ContentTemplateContentAreas');
		$this->assertInstanceOf(HasMany::class, $contentTemplateContentAreasAssociation);
		$this->assertFalse($contentTemplateContentAreasAssociation->getCascadeCallbacks());
		$this->assertFalse($contentTemplateContentAreasAssociation->getDependent());
		$this->assertEquals('replace', $contentTemplateContentAreasAssociation->getSaveStrategy());

		// Test PageRoles association (BelongsTo)
		$this->assertTrue($this->pageTemplatesTable->hasAssociation('PageRoles'));
		$pageRolesAssociation = $this->pageTemplatesTable->getAssociation('PageRoles');
		$this->assertInstanceOf(BelongsTo::class, $pageRolesAssociation);
		$this->assertFalse($pageRolesAssociation->getCascadeCallbacks());
		$this->assertFalse($pageRolesAssociation->getDependent());

		// Test Pages association (HasMany)
		$this->assertTrue($this->pageTemplatesTable->hasAssociation('Pages'));
		$pagesAssociation = $this->pageTemplatesTable->getAssociation('Pages');
		$this->assertInstanceOf(HasMany::class, $pagesAssociation);
		$this->assertFalse($pagesAssociation->getCascadeCallbacks());
		$this->assertFalse($pagesAssociation->getDependent());

		// Test Pages association finder configuration
		$this->assertSame(['all' => ['skipPageRoleCheck' => true]], $pagesAssociation->getFinder());

		// 'CreatedByUser' must also exist
		$this->assertTrue($this->pageTemplatesTable->hasAssociation('CreatedByUser'));
		$createdByUserAssociation = $this->pageTemplatesTable->getAssociation('CreatedByUser');
		$this->assertInstanceOf(BelongsTo::class, $createdByUserAssociation);
		$this->assertFalse($createdByUserAssociation->getCascadeCallbacks());
		$this->assertFalse($createdByUserAssociation->getDependent());

		// 'ChangedByUser' must also exist
		$this->assertTrue($this->pageTemplatesTable->hasAssociation('ChangedByUser'));
		$changedByUserAssociation = $this->pageTemplatesTable->getAssociation('ChangedByUser');
		$this->assertInstanceOf(BelongsTo::class, $changedByUserAssociation);
		$this->assertFalse($changedByUserAssociation->getCascadeCallbacks());
		$this->assertFalse($changedByUserAssociation->getDependent());

		// 'DeletedByUser' must also exist
		$this->assertTrue($this->pageTemplatesTable->hasAssociation('DeletedByUser'));
		$deletedByUserAssociation = $this->pageTemplatesTable->getAssociation('DeletedByUser');
		$this->assertInstanceOf(BelongsTo::class, $deletedByUserAssociation);
		$this->assertFalse($deletedByUserAssociation->getCascadeCallbacks());
		$this->assertFalse($deletedByUserAssociation->getDependent());

		// 'MediaAssignments' must also exist
		$this->assertTrue($this->pageTemplatesTable->hasAssociation('MediaAssignments'));
		$mediaAssignmentsAssociation = $this->pageTemplatesTable->getAssociation('MediaAssignments');
		$this->assertInstanceOf(HasMany::class, $mediaAssignmentsAssociation);
		$this->assertTrue($mediaAssignmentsAssociation->getCascadeCallbacks());
		$this->assertTrue($mediaAssignmentsAssociation->getDependent());

		// 'MediaElementAssignments' must also exist
		$this->assertTrue($this->pageTemplatesTable->hasAssociation('MediaElementAssignments'));
		$mediaElementAssignmentsAssociation = $this->pageTemplatesTable->getAssociation('MediaElementAssignments');
		$this->assertInstanceOf(HasMany::class, $mediaElementAssignmentsAssociation);
		$this->assertTrue($mediaElementAssignmentsAssociation->getCascadeCallbacks());
		$this->assertTrue($mediaElementAssignmentsAssociation->getDependent());

		// 'PageTemplates_title_translation' must also exist
		$this->assertTrue($this->pageTemplatesTable->hasAssociation('PageTemplates_title_translation'));
		$titleTranslationAssociation = $this->pageTemplatesTable->getAssociation('PageTemplates_title_translation');
		$this->assertInstanceOf(HasOne::class, $titleTranslationAssociation);
		$this->assertFalse($titleTranslationAssociation->getCascadeCallbacks());
		$this->assertFalse($titleTranslationAssociation->getDependent());

		// 'I18n' must also exist
		$this->assertTrue($this->pageTemplatesTable->hasAssociation('I18n'));
		$i18nAssociation = $this->pageTemplatesTable->getAssociation('I18n');
		$this->assertInstanceOf(HasMany::class, $i18nAssociation);
		$this->assertFalse($i18nAssociation->getCascadeCallbacks());
		$this->assertTrue($i18nAssociation->getDependent());
		$this->assertEquals('append', $i18nAssociation->getSaveStrategy());
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\PageTemplatesTable::findWithUsages()
	 */
	public function testFindWithUsages(): void {
		$query = $this->pageTemplatesTable->find('withUsages');

		/** @noinspection PhpConditionAlreadyCheckedInspection */
		$this->assertInstanceOf(SelectQuery::class, $query);

		// Test that the query includes the expected fields
		$select = $query->clause('select');
		$this->assertContains('usedForPages', array_keys($select));
		$this->assertInstanceOf(AggregateExpression::class, $select['usedForPages']);

		// Test that the query includes group by
		$this->assertTrue($query->isAutoFieldsEnabled());
		$this->assertSame(['PageTemplates.id'], $query->clause('group'));

		// Test that the query has a left join with Pages
		$matching = $query->getEagerLoader()->getMatching();
		$this->assertArrayHasKey('Pages', $matching);
		$this->assertArrayHasKey('queryBuilder', $matching['Pages']);
		/** @var \Cake\ORM\Query\SelectQuery $query */
		$query = $matching['Pages']['queryBuilder']($query);

		$this->assertSame(['attributes' => ['skip' => true]], $query->getOptions());
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\PageTemplatesTable::validationDefault()
	 */
	public function testValidationDefault(): void {
		$validator = new Validator();
		$result = $this->pageTemplatesTable->validationDefault($validator);

		$this->assertInstanceOf(Validator::class, $result);
		$this->assertSame('PageTemplates', $result->getI18nDomain());

		// Test required fields
		$this->assertTrue($result->hasField('pageRoleId'));
		$this->assertSame('create', $result->field('pageRoleId')->isPresenceRequired());

		$this->assertTrue($result->hasField('title'));
		$this->assertSame('create', $result->field('title')->isPresenceRequired());

		$this->assertTrue($result->hasField('fileName'));
		$this->assertSame('create', $result->field('fileName')->isPresenceRequired());

		// Test other fields
		$this->assertTrue($result->hasField('id'));
		$this->assertTrue($result->hasField('systemOrder'));
		$this->assertTrue($result->hasField('active'));
		$this->assertTrue($result->hasField('deleted'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\PageTemplatesTable::validationDefault()
	 */
	public function testEntityValidationSuccess(): void {
		$data = [
			'pageRoleId' => 1,
			'title' => 'Test Page Template',
			'fileName' => 'test_page_template',
			'systemOrder' => 1,
			'active' => true,
			'deleted' => false,
		];

		$entity = $this->pageTemplatesTable->newEntity($data);
		$errors = $entity->getErrors();

		$this->assertEmpty($errors, 'Valid data should not produce validation errors');
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\PageTemplatesTable::validationDefault()
	 */
	public function testEntityValidationMissingRequired(): void {
		$data = [
			'systemOrder' => 1,
		];

		$entity = $this->pageTemplatesTable->newEntity($data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('pageRoleId', $errors);
		$this->assertArrayHasKey('title', $errors);
		$this->assertArrayHasKey('fileName', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\PageTemplatesTable::validationDefault()
	 */
	public function testEntityValidationInvalidTypes(): void {
		$data = [
			'id' => 'not_an_integer',
			'pageRoleId' => 'not_an_integer',
			'title' => true,
			'fileName' => true,
			'systemOrder' => 'not_an_integer',
			'active' => 'not_a_boolean',
			'deleted' => 'not_a_boolean',
		];

		$entity = $this->pageTemplatesTable->newEntity($data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('id', $errors);

		$this->assertArrayHasKey('pageRoleId', $errors);
		$this->assertArrayHasKey('enum', $errors['pageRoleId']);
		$this->assertSame('page_templates::error_enum', $errors['pageRoleId']['enum']);

		$this->assertArrayHasKey('title', $errors);
		$this->assertArrayHasKey('fileName', $errors);
		$this->assertArrayHasKey('systemOrder', $errors);
		$this->assertArrayHasKey('active', $errors);
		$this->assertArrayHasKey('deleted', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\PageTemplatesTable::validationDefault()
	 */
	public function testEntityValidationFieldLength(): void {
		$data = [
			'id' => 123456789123, // exceeds 11 char limit
			'pageRoleId' => 123456789123, // exceeds 11 char limit
			'title' => str_repeat('a', 101), // exceeds 100 char limit
			'fileName' => str_repeat('b', 101), // exceeds 100 char limit
		];

		$entity = $this->pageTemplatesTable->newEntity($data, ['guard' => false]);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('id', $errors);
		$this->assertArrayHasKey('pageRoleId', $errors);
		$this->assertArrayHasKey('title', $errors);
		$this->assertArrayHasKey('fileName', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\PageTemplatesTable::validationDefault()
	 */
	public function testEntityValidationBlankFields(): void {
		$data = [
			'pageRoleId' => 1,
			'title' => '   ', // Only whitespace
			'fileName' => '   ', // Only whitespace
		];

		$entity = $this->pageTemplatesTable->newEntity($data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('title', $errors);
		$this->assertArrayHasKey('notBlank', $errors['title']);

		$this->assertArrayHasKey('fileName', $errors);
		$this->assertArrayHasKey('notBlank', $errors['fileName']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\PageTemplatesTable::validationDefault()
	 */
	public function testEntityValidationFileNameAscii(): void {
		$data = [
			'pageRoleId' => 1,
			'title' => 'Test Template',
			'fileName' => 'tëst_tëmplätë', // Non-ASCII characters
		];

		$entity = $this->pageTemplatesTable->newEntity($data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('fileName', $errors);
		$this->assertArrayHasKey('ascii', $errors['fileName']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\PageTemplatesTable::buildRules()
	 */
	public function testBuildRulesFileNameUnique(): void {
		/** @var \Awyiss\Model\Entity\PageTemplate $entity1 */
		$entity1 = $this->pageTemplatesTable->get(1);

		// Try to create second entity with same fileName
		$entity2 = unserialize(serialize($entity1));
		$entity2->unset('id'); // Clear ID to create a new entity
		$entity2->setNew(true);

		$saved2 = $this->pageTemplatesTable->checkRules($entity2);
		$this->assertFalse($saved2, 'Second entity should fail due to duplicate fileName');

		$errors = $entity2->getErrors();
		$this->assertArrayHasKey('fileName', $errors);
		$this->assertArrayHasKey('fileNameUnique', $errors['fileName']);
		$this->assertEquals('page_templates::error_file_name_unique', $errors['fileName']['fileNameUnique']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\SurveyQuestionsTable::buildRules()
	 */
	public function testBuildRulesValidPageRoleId(): void {
		$data = [
			'pageRoleId' => 1, // Patching entity will convert to enum
			'title' => 'Test Template',
			'fileName' => 'test_template',
		];

		$entity = $this->pageTemplatesTable->newDefaultEntity();

		$this->pageTemplatesTable->patchEntity($entity, $data);

		$this->assertSame(PageRole::Page, $entity->pageRoleId);

		$result = $this->pageTemplatesTable->checkRules($entity);

		$this->assertTrue($result);

		$entity->pageRoleId = PageRole::Newscategory;

		$result = $this->pageTemplatesTable->checkRules($entity);

		$this->assertTrue($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\SurveyQuestionsTable::buildRules()
	 */
	public function testBuildRulesInvalidPageRoleId(): void {
		$data = [
			'pageRoleId' => 'invalid_role', // Patching entity will convert to enum but fail here
			'title' => 'Test Template',
			'fileName' => 'test_template',
		];

		$entity = $this->pageTemplatesTable->newDefaultEntity();

		$this->pageTemplatesTable->patchEntity($entity, $data);

		$this->assertNull($entity->pageRoleId);

		$result = $this->pageTemplatesTable->checkRules($entity);

		$this->assertFalse($result);

		$entity->pageRoleId = 'invalid';  // Setting a value directly will not convert to enum

		$result = $this->pageTemplatesTable->checkRules($entity);

		$this->assertFalse($result);

		$errors = $entity->getErrors();
		$this->assertArrayHasKey('pageRoleId', $errors);
		$this->assertArrayHasKey('validPageRoleId', $errors['pageRoleId']);
		$this->assertSame('page_templates::error_valid_page_role_id', $errors['pageRoleId']['validPageRoleId']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\PageTemplatesTable::buildRules()
	 */
	public function testBuildRulesNoLinkedPageTemplatesUpdateWithSameValue(): void {
		/** @var \Awyiss\Model\Entity\PageTemplate $entity */
		$entity = $this->pageTemplatesTable->get(1);

		// Try to change pageRoleId when there are linked pages
		$entity->pageRoleId = 1;

		$this->assertTrue($entity->isDirty('pageRoleId'));

		$result = $this->pageTemplatesTable->checkRules($entity, RulesChecker::UPDATE);

		$this->assertFalse($result);

		$errors = $entity->getErrors();

		$this->assertArrayHasKey('pageRoleId', $errors);
		$this->assertArrayHasKey('_isNotLinkedTo', $errors['pageRoleId']);
		$this->assertEquals('page_templates::error_no_linked_pages', $errors['pageRoleId']['_isNotLinkedTo']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\PageTemplatesTable::buildRules()
	 */
	public function testBuildRulesNoLinkedPageTemplatesUpdate(): void {
		/** @var \Awyiss\Model\Entity\PageTemplate $entity */
		$entity = $this->pageTemplatesTable->get(1);

		// Try to change pageRoleId when there are linked pages
		$entity->pageRoleId = 2;

		$result = $this->pageTemplatesTable->checkRules($entity, RulesChecker::UPDATE);

		$this->assertFalse($result);

		$errors = $entity->getErrors();

		$this->assertArrayHasKey('pageRoleId', $errors);
		$this->assertArrayHasKey('_isNotLinkedTo', $errors['pageRoleId']);
		$this->assertEquals('page_templates::error_no_linked_pages', $errors['pageRoleId']['_isNotLinkedTo']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\PageTemplatesTable::buildRules()
	 */
	public function testBuildRulesNoLinkedPageTemplatesUpdateWithCopy(): void {
		/** @var \Awyiss\Model\Entity\PageTemplate $entity */
		$entity = $this->pageTemplatesTable->get(1);

		// Try to change pageRoleId with isCopy option
		$entity->pageRoleId = 2;

		$result = $this->pageTemplatesTable->checkRules($entity, RulesChecker::CREATE, ['isCopy' => true]);

		$this->assertTrue($result, 'Entity should pass validation when isCopy is true');
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\PageTemplatesTable::buildRules()
	 */
	public function testBuildRulesNoLinkedPages(): void {
		/** @var \Awyiss\Model\Entity\PageTemplate $entity */
		$entity = $this->pageTemplatesTable->get(1);

		$saved = $this->pageTemplatesTable->checkRules($entity, RulesChecker::DELETE);
		$this->assertFalse($saved);

		$errors = $entity->getErrors();
		$this->assertArrayHasKey('_general', $errors);
		$this->assertArrayHasKey('noLinkedPages', $errors['_general']);
		$this->assertEquals('page_templates::error_no_linked_pages', $errors['_general']['noLinkedPages']);

		/** @var \Awyiss\Model\Entity\PageTemplate $entity */
		$entity = $this->pageTemplatesTable->get(4);

		$saved = $this->pageTemplatesTable->checkRules($entity, RulesChecker::DELETE);
		$this->assertTrue($saved);

		$errors = $entity->getErrors();
		$this->assertEmpty($errors, 'Entity with no linked pages should not have errors');
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\PageTemplatesTable::newDefaultEntity()
	 */
	public function testNewDefaultEntity(): void {
		$entity = $this->pageTemplatesTable->newDefaultEntity();

		$this->assertInstanceOf(PageTemplate::class, $entity);
		$this->assertTrue($entity->isNew());

		// Test default values
		$this->assertTrue($entity->active);
		$this->assertFalse($entity->deleted);
		$this->assertSame(0, $entity->systemOrder);
		$this->assertNull($entity->pageRoleId);
		$this->assertNull($entity->title);
		$this->assertNull($entity->fileName);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\PageTemplatesTable::newDefaultEntity()
	 */
	public function testNewDefaultEntityWithData(): void {
		$additionalData = [
			'pageRoleId' => 2,
			'title' => 'Custom Page Template',
			'fileName' => 'custom_page_template',
			'systemOrder' => 5,
			'active' => false,
			'deleted' => true,
		];

		$entity = $this->pageTemplatesTable->newDefaultEntity($additionalData);

		$this->assertInstanceOf(PageTemplate::class, $entity);
		$this->assertTrue($entity->isNew());

		$this->assertSame(PageRole::Newscategory, $entity->pageRoleId);
		$this->assertSame('Custom Page Template', $entity->title);
		$this->assertSame('custom_page_template', $entity->fileName);
		$this->assertSame(5, $entity->systemOrder);
		$this->assertFalse($entity->active);
		$this->assertTrue($entity->deleted);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\PageTemplatesTable::initializeSchema()
	 */
	public function testInitializeSchemaPageRoleIdColumn(): void {
		$schema = $this->pageTemplatesTable->getSchema();

		// Test that page_role_id column is configured as an enum type
		$this->assertSame('enum-customer-model-enum-pagerole', $schema->getColumnType('pageRoleId'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\PageTemplatesTable::$categories
	 */
	public function testCategoriesBehavior(): void {
		$this->assertTrue($this->pageTemplatesTable->hasBehavior('Categories'));

		$config = $this->pageTemplatesTable->getBehavior('Categories')->getConfig();

		$this->assertTrue($config['allowAggregation']);
		$this->assertFalse($config['allowUnassigned']);
		$this->assertEquals('PageRoles', $config['associationName']);
		$this->assertTrue($config['enabled']);
		$this->assertEquals('pageRole', $config['identifier']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\PageTemplatesTable::$systemOrder
	 */
	public function testSystemOrderBehavior(): void {
		$this->assertTrue($this->pageTemplatesTable->hasBehavior('SystemOrder'));

		$config = $this->pageTemplatesTable->getBehavior('SystemOrder')->getConfig();

		$this->assertArrayHasKey('relatedColumns', $config);
		$this->assertEquals(['pageRoleId'], $config['relatedColumns']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\PageTemplatesTable::$translate
	 */
	public function testTranslateBehavior(): void {
		$this->assertTrue($this->pageTemplatesTable->hasBehavior('Translate'));

		$config = $this->pageTemplatesTable->getBehavior('Translate')->getConfig();

		$this->assertIsArray($config['fields']);
		$this->assertSame(['title'], $config['fields']);
	}
}
