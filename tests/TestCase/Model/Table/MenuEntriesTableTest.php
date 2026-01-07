<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Model\Table;


use Awyiss\Awyiss;
use Awyiss\Model\Entity\MenuEntry;
use Awyiss\Model\Table\MenuEntriesTable;
use Awyiss\ORM\Association\BelongsTo;
use Awyiss\ORM\Association\HasMany;
use Awyiss\ORM\Association\HasOne;
use Awyiss\Test\TestSuite\TestCase;
use Awyiss\Validation\Validator;
use Cake\Datasource\FactoryLocator;


/**
 * MenuEntriesTable Test Case
 *
 * @see \Awyiss\Model\Table\MenuEntriesTable
 */
class MenuEntriesTableTest extends TestCase {
	/**
	 * @var \Awyiss\Model\Table\MenuEntriesTable
	 */
	protected MenuEntriesTable $menuEntriesTable;


	/**
	 * @inheritDoc
	 */
	protected function setUp(): void {
		parent::setUp();

		/** @noinspection PhpFieldAssignmentTypeMismatchInspection */
		$this->menuEntriesTable = FactoryLocator::get('Table')->get('MenuEntries');
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\MenuEntriesTable::ATTRIBUTABLE
	 */
	public function testAttributableConstant(): void {
		/** @noinspection PhpUnitAssertTrueWithIncompatibleTypeArgumentInspection */
		$this->assertTrue($this->menuEntriesTable::ATTRIBUTABLE);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\MenuEntriesTable::TABLE
	 */
	public function testTableConstant(): void {
		$this->assertEquals('menu_entries', $this->menuEntriesTable::TABLE);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\MenuEntriesTable::initializeAssociations()
	 */
	public function testInitializeAssociations(): void {
		$this->assertCount(10, $this->menuEntriesTable->associations()->keys());

		// Test Menus association (BelongsTo)
		$this->assertTrue($this->menuEntriesTable->hasAssociation('Menus'));
		$menusAssociation = $this->menuEntriesTable->getAssociation('Menus');
		$this->assertInstanceOf(BelongsTo::class, $menusAssociation);
		$this->assertFalse($menusAssociation->getCascadeCallbacks());
		$this->assertFalse($menusAssociation->getDependent());

		// Test Languages association (BelongsTo)
		$this->assertTrue($this->menuEntriesTable->hasAssociation('Languages'));
		$languagesAssociation = $this->menuEntriesTable->getAssociation('Languages');
		$this->assertInstanceOf(BelongsTo::class, $languagesAssociation);
		$this->assertFalse($languagesAssociation->getCascadeCallbacks());
		$this->assertFalse($languagesAssociation->getDependent());
		$this->assertEquals('shortcode', $languagesAssociation->getBindingKey());
		$this->assertEquals('language_shortcode', $languagesAssociation->getForeignKey());

		// Check the condition for Languages association
		$conditions = $languagesAssociation->getConditions();
		$this->assertArrayHasKey('realm', $conditions);
		$this->assertEquals(Awyiss::REALM_FRONTEND, $conditions['realm']);

		// 'CustomerGroupAccessSettings' must also exist
		$this->assertTrue($this->menuEntriesTable->hasAssociation('CustomerGroupAccessSettings'));
		$customerGroupAccessSettingsAssociation = $this->menuEntriesTable->getAssociation('CustomerGroupAccessSettings');
		$this->assertInstanceOf(HasOne::class, $customerGroupAccessSettingsAssociation);
		$this->assertTrue($customerGroupAccessSettingsAssociation->getCascadeCallbacks());
		$this->assertTrue($customerGroupAccessSettingsAssociation->getDependent());

		// 'CustomerGroupAssignments' must also exist
		$this->assertTrue($this->menuEntriesTable->hasAssociation('CustomerGroupAssignments'));
		$customerGroupAssignmentsAssociation = $this->menuEntriesTable->getAssociation('CustomerGroupAssignments');
		$this->assertInstanceOf(HasMany::class, $customerGroupAssignmentsAssociation);
		$this->assertTrue($customerGroupAssignmentsAssociation->getCascadeCallbacks());
		$this->assertTrue($customerGroupAssignmentsAssociation->getDependent());

		// 'MediaAssignments' must also exist
		$this->assertTrue($this->menuEntriesTable->hasAssociation('MediaAssignments'));
		$mediaAssignmentsAssociation = $this->menuEntriesTable->getAssociation('MediaAssignments');
		$this->assertInstanceOf(HasMany::class, $mediaAssignmentsAssociation);
		$this->assertTrue($mediaAssignmentsAssociation->getCascadeCallbacks());
		$this->assertTrue($mediaAssignmentsAssociation->getDependent());

		// 'CreatedByUser' must also exist
		$this->assertTrue($this->menuEntriesTable->hasAssociation('CreatedByUser'));
		$createdByUserAssociation = $this->menuEntriesTable->getAssociation('CreatedByUser');
		$this->assertInstanceOf(BelongsTo::class, $createdByUserAssociation);
		$this->assertFalse($createdByUserAssociation->getCascadeCallbacks());
		$this->assertFalse($createdByUserAssociation->getDependent());

		// 'ChangedByUser' must also exist
		$this->assertTrue($this->menuEntriesTable->hasAssociation('ChangedByUser'));
		$changedByUserAssociation = $this->menuEntriesTable->getAssociation('ChangedByUser');
		$this->assertInstanceOf(BelongsTo::class, $changedByUserAssociation);
		$this->assertFalse($changedByUserAssociation->getCascadeCallbacks());
		$this->assertFalse($changedByUserAssociation->getDependent());

		// 'DeletedByUser' must also exist
		$this->assertTrue($this->menuEntriesTable->hasAssociation('DeletedByUser'));
		$deletedByUserAssociation = $this->menuEntriesTable->getAssociation('DeletedByUser');
		$this->assertInstanceOf(BelongsTo::class, $deletedByUserAssociation);
		$this->assertFalse($deletedByUserAssociation->getCascadeCallbacks());
		$this->assertFalse($deletedByUserAssociation->getDependent());

		// 'ParentMenuEntries' must also exist (from parent table implementation)
		$this->assertTrue($this->menuEntriesTable->hasAssociation('ParentMenuEntries'));
		$parentMenuEntriesAssociation = $this->menuEntriesTable->getAssociation('ParentMenuEntries');
		$this->assertInstanceOf(BelongsTo::class, $parentMenuEntriesAssociation);
		$this->assertFalse($parentMenuEntriesAssociation->getCascadeCallbacks());
		$this->assertFalse($parentMenuEntriesAssociation->getDependent());

		// 'ChildMenuEntries' must also exist (from parent table implementation)
		$this->assertTrue($this->menuEntriesTable->hasAssociation('ChildMenuEntries'));
		$childMenuEntriesAssociation = $this->menuEntriesTable->getAssociation('ChildMenuEntries');
		$this->assertInstanceOf(HasMany::class, $childMenuEntriesAssociation);
		$this->assertTrue($childMenuEntriesAssociation->getCascadeCallbacks());
		$this->assertTrue($childMenuEntriesAssociation->getDependent());
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\MenuEntriesTable::validationDefault()
	 */
	public function testValidationDefault(): void {
		$validator = new Validator();
		$result = $this->menuEntriesTable->validationDefault($validator);

		$this->assertInstanceOf(Validator::class, $result);
		$this->assertSame('menu_entries', $result->getI18nDomain());

		// Test required fields
		$this->assertTrue($result->hasField('menuId'));
		$this->assertSame('create', $result->field('menuId')->isPresenceRequired());

		$this->assertTrue($result->hasField('languageShortcode'));
		$this->assertSame('create', $result->field('languageShortcode')->isPresenceRequired());

		$this->assertTrue($result->hasField('title'));
		$this->assertSame('create', $result->field('title')->isPresenceRequired());

		// Test other fields
		$this->assertTrue($result->hasField('id'));
		$this->assertTrue($result->hasField('parentId'));
		$this->assertTrue($result->hasField('link'));
		$this->assertTrue($result->hasField('systemOrder'));
		$this->assertTrue($result->hasField('active'));
		$this->assertTrue($result->hasField('deleted'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\MenuEntriesTable::validationDefault()
	 */
	public function testEntityValidationSuccess(): void {
		$data = [
			'menuId' => 1,
			'languageShortcode' => 'de',
			'title' => 'Test Menu Entry',
			'link' => '/test-page',
			'systemOrder' => 1,
			'active' => true,
			'deleted' => false,
		];

		$entity = $this->menuEntriesTable->newEntity($data);
		$errors = $entity->getErrors();

		$this->assertEmpty($errors, 'Valid data should not produce validation errors');
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\MenuEntriesTable::validationDefault()
	 */
	public function testEntityValidationMissingRequired(): void {
		$data = [
			'active' => true,
		];

		$entity = $this->menuEntriesTable->newEntity($data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('menuId', $errors);
		$this->assertArrayHasKey('languageShortcode', $errors);
		$this->assertArrayHasKey('title', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\MenuEntriesTable::validationDefault()
	 */
	public function testEntityValidationInvalidTypes(): void {
		$data = [
			'id' => 'not_an_integer',
			'menuId' => 'not_an_integer',
			'parentId' => 'not_an_integer',
			'languageShortcode' => true,
			'title' => true,
			'link' => true,
			'systemOrder' => 'not_an_integer',
			'active' => 'not_a_boolean',
			'deleted' => 'not_a_boolean',
		];

		$entity = $this->menuEntriesTable->newEntity($data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('id', $errors);
		$this->assertArrayHasKey('menuId', $errors);
		$this->assertArrayHasKey('parentId', $errors);
		$this->assertArrayHasKey('languageShortcode', $errors);
		$this->assertArrayHasKey('title', $errors);
		$this->assertArrayHasKey('link', $errors);
		$this->assertArrayHasKey('systemOrder', $errors);
		$this->assertArrayHasKey('active', $errors);
		$this->assertArrayHasKey('deleted', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\MenuEntriesTable::validationDefault()
	 */
	public function testEntityValidationFieldLength(): void {
		$data = [
			'id' => 123456789123, // exceeds 11 char limit
			'menuId' => 123456789123, // exceeds 11 char limit
			'parentId' => 123456789123, // exceeds 11 char limit
			'languageShortcode' => 'eng', // exceeds 2 char limit (assuming same as pages)
			'title' => str_repeat('a', 256), // exceeds 255 char limit
			'link' => str_repeat('b', 256), // exceeds 255 char limit
		];

		$entity = $this->menuEntriesTable->newEntity($data, ['guard' => false]);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('id', $errors);
		$this->assertArrayHasKey('menuId', $errors);
		$this->assertArrayHasKey('parentId', $errors);
		$this->assertArrayHasKey('languageShortcode', $errors);
		$this->assertArrayHasKey('title', $errors);
		$this->assertArrayHasKey('link', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\MenuEntriesTable::validationDefault()
	 */
	public function testEntityValidationNotBlank(): void {
		$data = [
			'menuId' => 1,
			'languageShortcode' => 'de',
			'title' => '   ', // only whitespace
		];

		$entity = $this->menuEntriesTable->newEntity($data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('title', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\MenuEntriesTable::validationDefault()
	 */
	public function testEntityValidationLanguageShortcodeExactLength(): void {
		$data = [
			'menuId' => 1,
			'languageShortcode' => 'e', // too short
			'title' => 'Test Menu Entry',
		];

		$entity = $this->menuEntriesTable->newEntity($data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('languageShortcode', $errors);
		$this->assertArrayHasKey('exactLength', $errors['languageShortcode']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\MenuEntriesTable::buildRules()
	 */
	public function testBuildRulesValidMenuId(): void {
		// Test with existing menu
		$data = [
			'menuId' => 1,
			'languageShortcode' => 'de',
			'title' => 'Test Menu Entry',
		];

		$entity = $this->menuEntriesTable->newEntity($data);
		$result = $this->menuEntriesTable->checkRules($entity);
		$this->assertTrue($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\MenuEntriesTable::buildRules()
	 */
	public function testBuildRulesInvalidMenuId(): void {
		// Test with non-existing menu
		$data = [
			'menuId' => 99999,
			'languageShortcode' => 'de',
			'title' => 'Test Menu Entry',
		];

		$entity = $this->menuEntriesTable->newEntity($data);
		$result = $this->menuEntriesTable->checkRules($entity);
		$this->assertFalse($result);

		$errors = $entity->getErrors();
		$this->assertArrayHasKey('menuId', $errors);
		$this->assertArrayHasKey('validMenuId', $errors['menuId']);
		// Error message comes from the nest behavior
		$this->assertEquals('menu_entries::error_valid_menu_id', $errors['menuId']['validMenuId']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\MenuEntriesTable::buildRules()
	 */
	public function testBuildRulesValidLanguageShortcode(): void {
		// Test with existing language
		$data = [
			'menuId' => 1,
			'languageShortcode' => 'de',
			'title' => 'Test Menu Entry',
		];

		$entity = $this->menuEntriesTable->newEntity($data);
		$result = $this->menuEntriesTable->checkRules($entity);
		$this->assertTrue($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\MenuEntriesTable::buildRules()
	 */
	public function testBuildRulesInvalidLanguageShortcode(): void {
		// Test with non-existing language
		$data = [
			'menuId' => 1,
			'languageShortcode' => 'xx',
			'title' => 'Test Menu Entry',
		];

		$entity = $this->menuEntriesTable->newEntity($data);
		$result = $this->menuEntriesTable->checkRules($entity);
		$this->assertFalse($result);

		$errors = $entity->getErrors();
		$this->assertArrayHasKey('languageShortcode', $errors);
		$this->assertArrayHasKey('languageExists', $errors['languageShortcode']);
		$this->assertEquals('menu_entries::error_language_exists', $errors['languageShortcode']['languageExists']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\MenuEntriesTable::buildRules()
	 */
	public function testBuildRulesValidParentId(): void {
		// Test with existing parent menu entry
		$data = [
			'menuId' => 2,
			'languageShortcode' => 'de',
			'title' => 'Test Child Menu Entry',
			'parentId' => 1, // Existing menu entry
		];

		$entity = $this->menuEntriesTable->newEntity($data);
		$result = $this->menuEntriesTable->checkRules($entity);
		$this->assertTrue($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\MenuEntriesTable::buildRules()
	 */
	public function testBuildRulesNullParentId(): void {
		// Test with null parent (should be valid)
		$data = [
			'menuId' => 2,
			'languageShortcode' => 'de',
			'title' => 'Test Root Menu Entry',
			'parentId' => null,
		];

		$entity = $this->menuEntriesTable->newEntity($data);
		$result = $this->menuEntriesTable->checkRules($entity);
		$this->assertTrue($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\MenuEntriesTable::buildRules()
	 */
	public function testBuildRulesInvalidParentId(): void {
		// Test with non-existing parent menu entry
		$data = [
			'menuId' => 2,
			'languageShortcode' => 'de',
			'title' => 'Test Child Menu Entry',
			'parentId' => 99999, // Non-existing menu entry
		];

		$entity = $this->menuEntriesTable->newEntity($data);
		$result = $this->menuEntriesTable->checkRules($entity);
		$this->assertFalse($result);

		$errors = $entity->getErrors();
		$this->assertArrayHasKey('parentId', $errors);
		$this->assertArrayHasKey('validParentId', $errors['parentId']);
		$this->assertEquals('menu_entries::error_valid_parent_id', $errors['parentId']['validParentId']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\MenuEntriesTable::newDefaultEntity()
	 */
	public function testNewDefaultEntity(): void {
		/** @var \Awyiss\Model\Entity\MenuEntry $entity */
		$entity = $this->menuEntriesTable->newDefaultEntity();

		$this->assertInstanceOf(MenuEntry::class, $entity);
		$this->assertTrue($entity->isNew());

		// Check default values
		$this->assertNull($entity->menuId);
		$this->assertNull($entity->parentId);
		$this->assertNull($entity->languageShortcode);
		$this->assertNull($entity->title);
		$this->assertSame('', $entity->link);
		$this->assertSame(0, $entity->systemOrder);
		$this->assertTrue($entity->active);
		$this->assertFalse($entity->deleted);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\MenuEntriesTable::newDefaultEntity()
	 */
	public function testNewDefaultEntityWithData(): void {
		$additionalData = [
			'menuId' => 1,
			'languageShortcode' => 'de',
			'title' => 'Custom Menu Entry',
			'link' => '/custom-page',
			'systemOrder' => 5,
			'active' => false,
		];

		/** @var \Awyiss\Model\Entity\MenuEntry $entity */
		$entity = $this->menuEntriesTable->newDefaultEntity($additionalData);

		$this->assertInstanceOf(MenuEntry::class, $entity);
		$this->assertTrue($entity->isNew());

		// Check custom values
		$this->assertSame(1, $entity->menuId);
		$this->assertSame('de', $entity->languageShortcode);
		$this->assertSame('Custom Menu Entry', $entity->title);
		$this->assertSame('/custom-page', $entity->link);
		$this->assertSame(5, $entity->systemOrder);
		$this->assertFalse($entity->active);
		$this->assertFalse($entity->deleted);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\MenuEntriesTable::$categories
	 */
	public function testCategoriesBehavior(): void {
		$this->assertTrue($this->menuEntriesTable->hasBehavior('Categories'));

		$config = $this->menuEntriesTable->getBehavior('Categories')->getConfig();

		$this->assertfalse($config['allowAggregation']);
		$this->assertfalse($config['allowUnassigned']);
		$this->assertSame('menu_id', $config['foreignKey']);
		$this->assertSame('menu', $config['identifier']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\MenuEntriesTable::$nest
	 */
	public function testNestBehavior(): void {
		$this->assertTrue($this->menuEntriesTable->hasBehavior('Nest'));

		$config = $this->menuEntriesTable->getBehavior('Nest')->getConfig();

		$this->assertTrue($config['enabled']);
		$this->assertSame(['languageShortcode', 'menuId'], $config['relatedColumns']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\MenuEntriesTable::$systemOrder
	 */
	public function testSystemOrderBehavior(): void {
		$this->assertTrue($this->menuEntriesTable->hasBehavior('SystemOrder'));

		$config = $this->menuEntriesTable->getBehavior('SystemOrder')->getConfig();

		$this->assertSame(['languageShortcode', 'menuId', 'parentId'], $config['relatedColumns']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\MenuEntriesTable::disableCascadeCallbacks()
	 */
	public function testDisableCascadeCallbacks(): void {
		$association = $this->menuEntriesTable->getAssociation('ChildMenuEntries');
		// Ensure cascade callbacks are enabled by default
		$this->assertTrue($association->getCascadeCallbacks());
		$this->assertTrue($association->getDependent());

		// Disable cascade callbacks
		$this->menuEntriesTable->disableCascadeCallbacks();

		// Check if cascade callbacks are disabled
		/** @noinspection PhpConditionAlreadyCheckedInspection */
		$this->assertFalse($association->getCascadeCallbacks());
		/** @noinspection PhpConditionAlreadyCheckedInspection */
		$this->assertFalse($association->getDependent());
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\MenuEntriesTable::enableCascadeCallbacks()
	 */
	public function testEnableCascadeCallbacks(): void {
		$association = $this->menuEntriesTable->getAssociation('ChildMenuEntries');

		// Disable cascade callbacks
		$this->menuEntriesTable->disableCascadeCallbacks();

		$this->assertFalse($association->getCascadeCallbacks());
		$this->assertFalse($association->getDependent());

		// Enable cascade callbacks
		$this->menuEntriesTable->enableCascadeCallbacks();

		// Check if cascade callbacks are enabled
		/** @noinspection PhpConditionAlreadyCheckedInspection */
		$this->assertTrue($association->getCascadeCallbacks());
		/** @noinspection PhpConditionAlreadyCheckedInspection */
		$this->assertTrue($association->getDependent());
	}
}
