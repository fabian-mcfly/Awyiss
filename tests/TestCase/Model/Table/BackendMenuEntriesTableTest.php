<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Model\Table;


use Awyiss\Awyiss;
use Awyiss\Middleware\LocaleMiddleware;
use Awyiss\Model\Entity\BackendMenuEntry;
use Awyiss\Model\Table\BackendMenuEntriesTable;
use Awyiss\ORM\Association\BelongsTo;
use Awyiss\ORM\Association\HasMany;
use Awyiss\ORM\Association\HasOne;
use Awyiss\Routing\Router;
use Awyiss\Test\TestSuite\TestCase;
use Awyiss\Validation\Validator;
use Cake\Datasource\FactoryLocator;
use Cake\Http\ServerRequest;
use Cake\TestSuite\IntegrationTestTrait;


/**
 * BackendMenuEntriesTable Test Case
 *
 * @see \Awyiss\Model\Table\BackendMenuEntriesTable
 */
class BackendMenuEntriesTableTest extends TestCase {
	use IntegrationTestTrait;


	/**
	 * @var \Awyiss\Model\Table\BackendMenuEntriesTable
	 */
	protected BackendMenuEntriesTable $backendMenuEntriesTable;


	/**
	 * @inheritDoc
	 */
	protected function setUp(): void {
		parent::setUp();

		/** @noinspection PhpFieldAssignmentTypeMismatchInspection */
		$this->backendMenuEntriesTable = FactoryLocator::get('Table')->get('BackendMenuEntries');

		$this->configApplication(Awyiss::class, []);

		Awyiss::setRealm(Awyiss::REALM_BACKEND);
		LocaleMiddleware::setRealm(Awyiss::REALM_BACKEND);
		Awyiss::loadConfiguration('xy', 'yx');

		$request = new ServerRequest([
			'url' => '/backend/xy/some-controller/the-action',
			'params' => [
				'lang' => 'xy',
				'controller' => 'SomeController',
				'action' => 'theAction',
				'_name' => 'Backend',
				'prefix' => 'Backend',
				'parts' => [],
				'pass' => [],
				'plugin' => null,
			],
		]);
		Router::setRequest($request);

		$this->loadRoutes();
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\BackendMenuEntriesTable::ATTRIBUTABLE
	 */
	public function testAttributableConstant(): void {
		$this->assertFalse($this->backendMenuEntriesTable::ATTRIBUTABLE);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\BackendMenuEntriesTable::TABLE
	 */
	public function testTableConstant(): void {
		$this->assertEquals('backend_menu_entries', $this->backendMenuEntriesTable::TABLE);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\BackendMenuEntriesTable::initializeAssociations()
	 */
	public function testInitializeAssociations(): void {
		$this->assertCount(8, $this->backendMenuEntriesTable->associations()->keys());

		// 'MediaAssignments' must also exist
		$this->assertTrue($this->backendMenuEntriesTable->hasAssociation('MediaAssignments'));
		$mediaAssignmentsAssociation = $this->backendMenuEntriesTable->getAssociation('MediaAssignments');
		$this->assertInstanceOf(HasMany::class, $mediaAssignmentsAssociation);
		$this->assertTrue($mediaAssignmentsAssociation->getCascadeCallbacks());
		$this->assertTrue($mediaAssignmentsAssociation->getDependent());

		// 'CreatedByUser' must exist
		$this->assertTrue($this->backendMenuEntriesTable->hasAssociation('CreatedByUser'));
		$createdByUserAssociation = $this->backendMenuEntriesTable->getAssociation('CreatedByUser');
		$this->assertInstanceOf(BelongsTo::class, $createdByUserAssociation);
		$this->assertFalse($createdByUserAssociation->getCascadeCallbacks());
		$this->assertFalse($createdByUserAssociation->getDependent());

		// 'ChangedByUser' must exist
		$this->assertTrue($this->backendMenuEntriesTable->hasAssociation('ChangedByUser'));
		$changedByUserAssociation = $this->backendMenuEntriesTable->getAssociation('ChangedByUser');
		$this->assertInstanceOf(BelongsTo::class, $changedByUserAssociation);
		$this->assertFalse($changedByUserAssociation->getCascadeCallbacks());
		$this->assertFalse($changedByUserAssociation->getDependent());

		// 'DeletedByUser' must exist
		$this->assertTrue($this->backendMenuEntriesTable->hasAssociation('DeletedByUser'));
		$deletedByUserAssociation = $this->backendMenuEntriesTable->getAssociation('DeletedByUser');
		$this->assertInstanceOf(BelongsTo::class, $deletedByUserAssociation);
		$this->assertFalse($deletedByUserAssociation->getCascadeCallbacks());
		$this->assertFalse($deletedByUserAssociation->getDependent());

		// 'ParentBackendMenuEntries' must exist (from nest behavior)
		$this->assertTrue($this->backendMenuEntriesTable->hasAssociation('ParentBackendMenuEntries'));
		$parentBackendMenuEntriesAssociation = $this->backendMenuEntriesTable->getAssociation('ParentBackendMenuEntries');
		$this->assertInstanceOf(BelongsTo::class, $parentBackendMenuEntriesAssociation);
		$this->assertFalse($parentBackendMenuEntriesAssociation->getCascadeCallbacks());
		$this->assertFalse($parentBackendMenuEntriesAssociation->getDependent());

		// 'ChildBackendMenuEntries' must exist (from nest behavior)
		$this->assertTrue($this->backendMenuEntriesTable->hasAssociation('ChildBackendMenuEntries'));
		$childBackendMenuEntriesAssociation = $this->backendMenuEntriesTable->getAssociation('ChildBackendMenuEntries');
		$this->assertInstanceOf(HasMany::class, $childBackendMenuEntriesAssociation);
		$this->assertTrue($childBackendMenuEntriesAssociation->getCascadeCallbacks());
		$this->assertTrue($childBackendMenuEntriesAssociation->getDependent());

		// Test translation associations
		$this->assertTrue($this->backendMenuEntriesTable->hasAssociation('BackendMenuEntries_title_translation'));
		$titleTranslationAssociation = $this->backendMenuEntriesTable->getAssociation('BackendMenuEntries_title_translation');
		$this->assertInstanceOf(HasOne::class, $titleTranslationAssociation);
		$this->assertFalse($titleTranslationAssociation->getCascadeCallbacks());
		$this->assertFalse($titleTranslationAssociation->getDependent());

		$this->assertTrue($this->backendMenuEntriesTable->hasAssociation('I18n'));
		$i18nAssociation = $this->backendMenuEntriesTable->getAssociation('I18n');
		$this->assertInstanceOf(HasMany::class, $i18nAssociation);
		$this->assertFalse($i18nAssociation->getCascadeCallbacks());
		$this->assertTrue($i18nAssociation->getDependent());
		$this->assertEquals('append', $i18nAssociation->getSaveStrategy());
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\BackendMenuEntriesTable::validationDefault()
	 */
	public function testValidationDefault(): void {
		$validator = new Validator();
		$result = $this->backendMenuEntriesTable->validationDefault($validator);

		$this->assertInstanceOf(Validator::class, $result);
		$this->assertSame('backend_menu_entries', $result->getI18nDomain());

		// Test required fields
		$this->assertTrue($result->hasField('title'));
		$this->assertSame('create', $result->field('title')->isPresenceRequired());

		// Test other fields
		$this->assertTrue($result->hasField('id'));
		$this->assertTrue($result->hasField('parentId'));
		$this->assertTrue($result->hasField('insertAfterId'));
		$this->assertTrue($result->hasField('link'));
		$this->assertTrue($result->hasField('external'));
		$this->assertTrue($result->hasField('systemOrder'));
		$this->assertTrue($result->hasField('active'));
		$this->assertTrue($result->hasField('deleted'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\BackendMenuEntriesTable::validationDefault()
	 */
	public function testEntityValidationSuccess(): void {
		$data = [
			'title' => 'Test Backend Menu Entry',
			'link' => 'TestController::overview',
			'access' => [
				'scope' => 'test',
				'identifier' => 'read',
			],
			'external' => false,
			'systemOrder' => 1,
			'active' => true,
			'deleted' => false,
		];

		$entity = $this->backendMenuEntriesTable->newEntity($data);
		$errors = $entity->getErrors();

		$this->assertEmpty($errors, 'Valid data should not produce validation errors');
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\BackendMenuEntriesTable::validationDefault()
	 */
	public function testEntityValidationMissingRequired(): void {
		$data = [
			'link' => 'TestController::overview',
			'active' => true,
		];

		$entity = $this->backendMenuEntriesTable->newEntity($data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('title', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\BackendMenuEntriesTable::validationDefault()
	 */
	public function testEntityValidationInvalidTypes(): void {
		$data = [
			'id' => 'not_an_integer',
			'parentId' => true,
			'insertAfterId' => true,
			'title' => true,
			'link' => true,
			'external' => 'not_a_boolean',
			'systemOrder' => 'not_an_integer',
			'active' => 'not_a_boolean',
			'deleted' => 'not_a_boolean',
		];

		$entity = $this->backendMenuEntriesTable->newEntity($data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('id', $errors);
		$this->assertArrayHasKey('parentId', $errors);
		$this->assertArrayHasKey('insertAfterId', $errors);
		$this->assertArrayHasKey('title', $errors);
		$this->assertArrayHasKey('link', $errors);
		$this->assertArrayHasKey('external', $errors);
		$this->assertArrayHasKey('systemOrder', $errors);
		$this->assertArrayHasKey('active', $errors);
		$this->assertArrayHasKey('deleted', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\BackendMenuEntriesTable::validationDefault()
	 */
	public function testEntityValidationFieldLength(): void {
		$data = [
			'id' => 123456789123, // exceeds 11 char limit
			'parentId' => str_repeat('a', 51), // exceeds 50 char limit
			'insertAfterId' => str_repeat('b', 51), // exceeds 50 char limit
			'title' => str_repeat('c', 101), // exceeds 100 char limit
			'link' => str_repeat('d', 256), // exceeds 255 char limit
		];

		$entity = $this->backendMenuEntriesTable->newEntity($data, ['guard' => false]);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('id', $errors);
		$this->assertArrayHasKey('parentId', $errors);
		$this->assertArrayHasKey('insertAfterId', $errors);
		$this->assertArrayHasKey('title', $errors);
		$this->assertArrayHasKey('link', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\BackendMenuEntriesTable::validationDefault()
	 */
	public function testEntityValidationNotBlank(): void {
		$data = [
			'title' => '   ', // only whitespace
			'link' => '   ', // only whitespace
		];

		$entity = $this->backendMenuEntriesTable->newEntity($data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('title', $errors);
		$this->assertArrayHasKey('link', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\BackendMenuEntriesTable::validationDefault()
	 */
	public function testEntityValidationAllowEmptyParentId(): void {
		$data = [
			'title' => 'Test Menu Entry',
			'link' => 'TestController::overview',
			'parentId' => null,
		];

		$entity = $this->backendMenuEntriesTable->newEntity($data);
		$errors = $entity->getErrors();

		$this->assertArrayNotHasKey('parentId', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\BackendMenuEntriesTable::validationDefault()
	 */
	public function testEntityValidationAllowEmptyInsertAfterId(): void {
		$data = [
			'title' => 'Test Menu Entry',
			'link' => 'TestController::overview',
			'insertAfterId' => null,
		];

		$entity = $this->backendMenuEntriesTable->newEntity($data);
		$errors = $entity->getErrors();

		$this->assertArrayNotHasKey('insertAfterId', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\BackendMenuEntriesTable::buildRules()
	 */
	public function testBuildRulesNullParentId(): void {
		// Test with null parent (should be valid)
		$data = [
			'title' => 'Test Root Menu Entry',
			'link' => 'TestController::overview',
			'parentId' => null,
		];

		$entity = $this->backendMenuEntriesTable->newEntity($data);
		$result = $this->backendMenuEntriesTable->checkRules($entity);
		$this->assertTrue($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\BackendMenuEntriesTable::buildRules()
	 */
	public function testBuildRulesValidParentIdNumeric(): void {
		// Test with existing parent menu entry (numeric)
		$data = [
			'title' => 'Test Child Menu Entry',
			'link' => 'TestController::overview',
			'parentId' => 1, // Existing menu entry from seed
		];

		$entity = $this->backendMenuEntriesTable->newEntity($data);
		$result = $this->backendMenuEntriesTable->checkRules($entity);
		$this->assertTrue($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\BackendMenuEntriesTable::buildRules()
	 */
	public function testBuildRulesValidParentIdString(): void {
		// Test with existing parent menu entry (string identifier)
		$data = [
			'title' => 'Test Child Menu Entry',
			'link' => 'TestController::overview',
			'parentId' => 'media', // Existing menu entry from system menu
		];

		$entity = $this->backendMenuEntriesTable->newEntity($data);
		$result = $this->backendMenuEntriesTable->checkRules($entity);
		$this->assertTrue($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\BackendMenuEntriesTable::buildRules()
	 */
	public function testBuildRulesInvalidParentIdNumeric(): void {
		// Test with non-existing parent menu entry (numeric)
		$data = [
			'title' => 'Test Child Menu Entry',
			'link' => 'TestController::overview',
			'parentId' => 99999, // Non-existing menu entry
		];

		$entity = $this->backendMenuEntriesTable->newEntity($data);
		$result = $this->backendMenuEntriesTable->checkRules($entity);
		$this->assertFalse($result);

		$errors = $entity->getErrors();
		$this->assertArrayHasKey('parentId', $errors);
		$this->assertArrayHasKey('_existsIn', $errors['parentId']);
		$this->assertEquals('validation::error_exists_in', $errors['parentId']['_existsIn']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\BackendMenuEntriesTable::buildRules()
	 */
	public function testBuildRulesInvalidParentIdString(): void {
		// Test with non-existing parent menu entry (string identifier)
		$data = [
			'title' => 'Test Child Menu Entry',
			'link' => 'TestController::overview',
			'parentId' => 'non_existing_menu_item', // Non-existing menu entry
		];

		$entity = $this->backendMenuEntriesTable->newEntity($data);
		$result = $this->backendMenuEntriesTable->checkRules($entity);
		$this->assertFalse($result);

		$errors = $entity->getErrors();
		$this->assertArrayHasKey('parentId', $errors);
		$this->assertArrayHasKey('validParentId', $errors['parentId']);
		$this->assertEquals('backend_menu_entries::error_valid_parent_id', $errors['parentId']['validParentId']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\BackendMenuEntriesTable::createEntries()
	 */
	public function testCreateEntries(): void {
		// Create a mock entity with title
		/** @var \Awyiss\Model\Entity\BackendMenuEntry $mockEntity */
		$mockEntity = $this->backendMenuEntriesTable->newEntity([
			'title' => 'Test Widget',
		]);

		// Test creating entries
		/** @noinspection PhpRedundantOptionalArgumentInspection */
		$this->backendMenuEntriesTable->createEntries($mockEntity, 'TestController', 'test_scope', 'pages');

		// Verify that entries were created
		$entries = $this->backendMenuEntriesTable->find()->where(['title' => 'Test Widget'])->contain(['ChildBackendMenuEntries'])->first();

		$this->backendMenuEntriesTable->deleteAll([
			'id >' => 5,
		]);

		$this->assertNotNull($entries);
		$this->assertEquals('Test Widget', $entries->title);
		$this->assertEquals('pages', $entries->insertAfterId);
		$this->assertEquals('TestController::overview', $entries->link);
		$this->assertNotNull($entries->access);
		$this->assertEquals('test_scope', $entries->access['scope']);
		$this->assertEquals('read', $entries->access['identifier']);

		// Check child entries
		$this->assertCount(3, $entries->childBackendMenuEntries);

		$children = $entries->childBackendMenuEntries;
		$this->assertEquals('generic_datatables::menu_overview', $children[0]->title);
		$this->assertEquals('TestController::overview', $children[0]->link);
		$this->assertEquals(1, $children[0]->systemOrder);

		$this->assertEquals('generic_datatables::menu_add', $children[1]->title);
		$this->assertEquals('TestController::add', $children[1]->link);
		$this->assertEquals(2, $children[1]->systemOrder);

		$this->assertEquals('generic_datatables::menu_configure', $children[2]->title);
		$this->assertEquals('Configuration::overview::scope:test_scope', $children[2]->link);
		$this->assertEquals(3, $children[2]->systemOrder);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\BackendMenuEntriesTable::newDefaultEntity()
	 */
	public function testNewDefaultEntity(): void {
		/** @var \Awyiss\Model\Entity\BackendMenuEntry $entity */
		$entity = $this->backendMenuEntriesTable->newDefaultEntity();

		$this->assertInstanceOf(BackendMenuEntry::class, $entity);
		$this->assertTrue($entity->isNew());

		// Check default values
		$this->assertNull($entity->parentId);
		$this->assertNull($entity->insertAfterId);
		$this->assertSame('', $entity->title);
		$this->assertNull($entity->link);
		$this->assertNull($entity->access);
		$this->assertFalse($entity->external);
		$this->assertSame(0, $entity->systemOrder);
		$this->assertTrue($entity->active);
		$this->assertFalse($entity->deleted);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\BackendMenuEntriesTable::newDefaultEntity()
	 */
	public function testNewDefaultEntityWithData(): void {
		$additionalData = [
			'title' => 'Custom Menu Entry',
			'link' => 'CustomController::overview',
			'parentId' => 'media',
			'access' => [
				'scope' => 'custom',
				'identifier' => 'read',
			],
			'external' => true,
			'systemOrder' => 5,
			'active' => false,
		];

		/** @var \Awyiss\Model\Entity\BackendMenuEntry $entity */
		$entity = $this->backendMenuEntriesTable->newDefaultEntity($additionalData);

		$this->assertInstanceOf(BackendMenuEntry::class, $entity);
		$this->assertTrue($entity->isNew());

		// Check custom values
		$this->assertSame('Custom Menu Entry', $entity->title);
		$this->assertSame('CustomController::overview', $entity->link);
		$this->assertSame('media', $entity->parentId);
		$this->assertSame(['scope' => 'custom', 'identifier' => 'read'], $entity->access);
		$this->assertTrue($entity->external);
		$this->assertSame(5, $entity->systemOrder);
		$this->assertFalse($entity->active);
		$this->assertFalse($entity->deleted);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\BackendMenuEntriesTable::$nest
	 */
	public function testNestBehavior(): void {
		$this->assertTrue($this->backendMenuEntriesTable->hasBehavior('Nest'));

		$config = $this->backendMenuEntriesTable->getBehavior('Nest')->getConfig();

		$this->assertFalse($config['buildRules']);
		$this->assertTrue($config['enabled']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\BackendMenuEntriesTable::$systemOrder
	 */
	public function testSystemOrderBehavior(): void {
		$this->assertTrue($this->backendMenuEntriesTable->hasBehavior('SystemOrder'));

		$config = $this->backendMenuEntriesTable->getBehavior('SystemOrder')->getConfig();

		$this->assertSame(['parentId', 'insertAfterId'], $config['relatedColumns']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\BackendMenuEntriesTable::$translate
	 */
	public function testTranslateBehavior(): void {
		$this->assertTrue($this->backendMenuEntriesTable->hasBehavior('Translate'));

		$config = $this->backendMenuEntriesTable->getBehavior('Translate')->getConfig();

		// Auto-realm (no specific realm set)
		$this->assertNull($config['realm'] ?? null);

		$this->assertIsArray($config['fields']);
		$this->assertSame(['title'], $config['fields']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\BackendMenuEntriesTable::initializeSchema()
	 */
	public function testInitializeSchemaAccessColumn(): void {
		$schema = $this->backendMenuEntriesTable->getSchema();
		// Test that access column is configured as JSON type
		$this->assertSame('json', $schema->getColumnType('access'));
	}
}
