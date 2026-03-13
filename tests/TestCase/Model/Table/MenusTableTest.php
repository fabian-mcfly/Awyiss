<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Model\Table;


use Awyiss\Model\Entity\Menu;
use Awyiss\Model\Table\MenusTable;
use Awyiss\ORM\Association\BelongsTo;
use Awyiss\ORM\Association\HasMany;
use Awyiss\ORM\Association\HasOne;
use Awyiss\Test\TestSuite\TestCase;
use Awyiss\Validation\Validator;
use Cake\Datasource\FactoryLocator;


/**
 * MenusTable Test Case
 *
 * @see \Awyiss\Model\Table\MenusTable
 */
class MenusTableTest extends TestCase {
	/**
	 * @var \Awyiss\Model\Table\MenusTable
	 */
	protected MenusTable $menusTable;


	/**
	 * @inheritDoc
	 */
	protected function setUp(): void {
		parent::setUp();

		/** @noinspection PhpFieldAssignmentTypeMismatchInspection */
		$this->menusTable = FactoryLocator::get('Table')->get('Menus');
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\MenusTable::ATTRIBUTABLE
	 */
	public function testAttributableConstant(): void {
		$this->assertFalse($this->menusTable::ATTRIBUTABLE);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\MenusTable::TABLE
	 */
	public function testTableConstant(): void {
		$this->assertEquals('menus', $this->menusTable::TABLE);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\MenusTable::initializeAssociations()
	 */
	public function testInitializeAssociations(): void {
		$this->assertCount(9, $this->menusTable->associations()->keys());

		// Test MenuEntries association (HasMany)
		$this->assertTrue($this->menusTable->hasAssociation('MenuEntries'));
		$menuEntriesAssociation = $this->menusTable->getAssociation('MenuEntries');
		$this->assertInstanceOf(HasMany::class, $menuEntriesAssociation);
		$this->assertTrue($menuEntriesAssociation->getCascadeCallbacks());
		$this->assertTrue($menuEntriesAssociation->getDependent());
		$this->assertEquals('menuId', $menuEntriesAssociation->getForeignKey());
		$this->assertEquals('forCurrentLanguage', $menuEntriesAssociation->getFinder());

		// 'CustomerGroupAccessSettings' must also exist
		$this->assertTrue($this->menusTable->hasAssociation('CustomerGroupAccessSettings'));
		$customerGroupAccessSettingsAssociation = $this->menusTable->getAssociation('CustomerGroupAccessSettings');
		$this->assertInstanceOf(HasOne::class, $customerGroupAccessSettingsAssociation);
		$this->assertTrue($customerGroupAccessSettingsAssociation->getCascadeCallbacks());
		$this->assertTrue($customerGroupAccessSettingsAssociation->getDependent());

		// 'CustomerGroupAssignments' must also exist
		$this->assertTrue($this->menusTable->hasAssociation('CustomerGroupAssignments'));
		$customerGroupAssignmentsAssociation = $this->menusTable->getAssociation('CustomerGroupAssignments');
		$this->assertInstanceOf(HasMany::class, $customerGroupAssignmentsAssociation);
		$this->assertTrue($customerGroupAssignmentsAssociation->getCascadeCallbacks());
		$this->assertTrue($customerGroupAssignmentsAssociation->getDependent());

		// 'MediaAssignments' must also exist
		$this->assertTrue($this->menusTable->hasAssociation('MediaAssignments'));
		$mediaAssignmentsAssociation = $this->menusTable->getAssociation('MediaAssignments');
		$this->assertInstanceOf(HasMany::class, $mediaAssignmentsAssociation);
		$this->assertTrue($mediaAssignmentsAssociation->getCascadeCallbacks());
		$this->assertTrue($mediaAssignmentsAssociation->getDependent());

		// 'CreatedByUser' must also exist
		$this->assertTrue($this->menusTable->hasAssociation('CreatedByUser'));
		$createdByUserAssociation = $this->menusTable->getAssociation('CreatedByUser');
		$this->assertInstanceOf(BelongsTo::class, $createdByUserAssociation);
		$this->assertFalse($createdByUserAssociation->getCascadeCallbacks());
		$this->assertFalse($createdByUserAssociation->getDependent());

		// 'ChangedByUser' must also exist
		$this->assertTrue($this->menusTable->hasAssociation('ChangedByUser'));
		$changedByUserAssociation = $this->menusTable->getAssociation('ChangedByUser');
		$this->assertInstanceOf(BelongsTo::class, $changedByUserAssociation);
		$this->assertFalse($changedByUserAssociation->getCascadeCallbacks());
		$this->assertFalse($changedByUserAssociation->getDependent());

		// 'DeletedByUser' must also exist
		$this->assertTrue($this->menusTable->hasAssociation('DeletedByUser'));
		$deletedByUserAssociation = $this->menusTable->getAssociation('DeletedByUser');
		$this->assertInstanceOf(BelongsTo::class, $deletedByUserAssociation);
		$this->assertFalse($deletedByUserAssociation->getCascadeCallbacks());
		$this->assertFalse($deletedByUserAssociation->getDependent());

		// 'Menus_title_translation' must also exist
		$this->assertTrue($this->menusTable->hasAssociation('Menus_title_translation'));
		$titleTranslationAssociation = $this->menusTable->getAssociation('Menus_title_translation');
		$this->assertInstanceOf(HasOne::class, $titleTranslationAssociation);
		$this->assertFalse($titleTranslationAssociation->getCascadeCallbacks());
		$this->assertFalse($titleTranslationAssociation->getDependent());

		// 'I18n' must also exist
		$this->assertTrue($this->menusTable->hasAssociation('I18n'));
		$i18nAssociation = $this->menusTable->getAssociation('I18n');
		$this->assertInstanceOf(HasMany::class, $i18nAssociation);
		$this->assertFalse($i18nAssociation->getCascadeCallbacks());
		$this->assertTrue($i18nAssociation->getDependent());
		$this->assertEquals('append', $i18nAssociation->getSaveStrategy());
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\MenusTable::validationDefault()
	 */
	public function testValidationDefault(): void {
		$validator = new Validator();
		$result = $this->menusTable->validationDefault($validator);

		$this->assertInstanceOf(Validator::class, $result);
		$this->assertSame('Menus', $result->getI18nDomain());

		// Test required fields
		$this->assertTrue($result->hasField('title'));
		$this->assertSame('create', $result->field('title')->isPresenceRequired());

		$this->assertTrue($result->hasField('identifier'));
		$this->assertSame('create', $result->field('identifier')->isPresenceRequired());

		// Test other fields
		$this->assertTrue($result->hasField('id'));
		$this->assertTrue($result->hasField('active'));
		$this->assertTrue($result->hasField('deleted'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\MenusTable::validationDefault()
	 */
	public function testEntityValidationSuccess(): void {
		$data = [
			'title' => 'Test Menu',
			'identifier' => 'testMenu',
			'active' => true,
			'deleted' => false,
		];

		$entity = $this->menusTable->newEntity($data);
		$errors = $entity->getErrors();

		$this->assertEmpty($errors, 'Valid data should not produce validation errors');
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\MenusTable::validationDefault()
	 */
	public function testEntityValidationMissingRequired(): void {
		$data = [
			'active' => true,
		];

		$entity = $this->menusTable->newEntity($data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('title', $errors);
		$this->assertArrayHasKey('identifier', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\MenusTable::validationDefault()
	 */
	public function testEntityValidationInvalidTypes(): void {
		$data = [
			'id' => 'not_an_integer',
			'title' => true,
			'identifier' => true,
			'active' => 'not_a_boolean',
			'deleted' => 'not_a_boolean',
		];

		$entity = $this->menusTable->newEntity($data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('id', $errors);
		$this->assertArrayHasKey('title', $errors);
		$this->assertArrayHasKey('identifier', $errors);
		$this->assertArrayHasKey('active', $errors);
		$this->assertArrayHasKey('deleted', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\MenusTable::validationDefault()
	 */
	public function testEntityValidationFieldLength(): void {
		$data = [
			'id' => 123456789123, // exceeds 11 char limit
			'title' => str_repeat('a', 51), // exceeds 50 char limit
			'identifier' => str_repeat('b', 51), // exceeds 50 char limit
		];

		$entity = $this->menusTable->newEntity($data, ['guard' => false]);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('id', $errors);
		$this->assertArrayHasKey('title', $errors);
		$this->assertArrayHasKey('identifier', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\MenusTable::validationDefault()
	 */
	public function testEntityValidationNotBlank(): void {
		$data = [
			'title' => '   ', // only whitespace
			'identifier' => '   ', // only whitespace
		];

		$entity = $this->menusTable->newEntity($data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('title', $errors);
		$this->assertArrayHasKey('identifier', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\MenusTable::buildRules()
	 */
	public function testBuildRulesIdentifierUnique(): void {
		// Test with unique identifier (should pass)
		$data = [
			'title' => 'Test Menu',
			'identifier' => 'uniqueTestMenu',
		];

		$entity = $this->menusTable->newEntity($data);
		$result = $this->menusTable->checkRules($entity);
		$this->assertTrue($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\MenusTable::buildRules()
	 */
	public function testBuildRulesIdentifierNotUnique(): void {
		// Try to create another menu with the same identifier
		$entity = $this->menusTable->newEntity([
			'title' => 'Second Menu',
			'identifier' => 'main',
		]);

		$result = $this->menusTable->checkRules($entity);
		$this->assertFalse($result);

		$errors = $entity->getErrors();
		$this->assertArrayHasKey('identifier', $errors);
		$this->assertArrayHasKey('identifierUnique', $errors['identifier']);
		$this->assertEquals('Menus::error_identifier_unique', $errors['identifier']['identifierUnique']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\MenusTable::newDefaultEntity()
	 */
	public function testNewDefaultEntity(): void {
		/** @var \Awyiss\Model\Entity\Menu $entity */
		$entity = $this->menusTable->newDefaultEntity();

		$this->assertInstanceOf(Menu::class, $entity);
		$this->assertTrue($entity->isNew());

		// Check default values
		$this->assertTrue($entity->active);
		$this->assertFalse($entity->deleted);
		$this->assertNull($entity->title);
		$this->assertNull($entity->identifier);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\MenusTable::newDefaultEntity()
	 */
	public function testNewDefaultEntityWithData(): void {
		$additionalData = [
			'title' => 'Custom Menu',
			'identifier' => 'customMenu',
			'active' => false,
		];

		/** @var \Awyiss\Model\Entity\Menu $entity */
		$entity = $this->menusTable->newDefaultEntity($additionalData);

		$this->assertInstanceOf(Menu::class, $entity);
		$this->assertTrue($entity->isNew());

		// Check custom values
		$this->assertSame('Custom Menu', $entity->title);
		$this->assertSame('customMenu', $entity->identifier);
		$this->assertFalse($entity->active);
		$this->assertFalse($entity->deleted); // Should remain default
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\MenusTable::$translate
	 */
	public function testTranslateBehavior(): void {
		$this->assertTrue($this->menusTable->hasBehavior('Translate'));

		$config = $this->menusTable->getBehavior('Translate')->getConfig();

		$this->assertIsArray($config['fields']);
		$this->assertSame(['title'], $config['fields']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\MenusTable::delete()
	 */
	public function testDeleteCascadeMenuEntries(): void {
		$menusTable = $this->menusTable;
		$menuEntriesTable = $this->fetchTable('MenuEntries');

		/** @var \Awyiss\Model\Entity\Menu $menu */
		$menu = $menusTable->get(1);
		$menu->identifier = 'mainCopy';
		$result = $menusTable->save($menu, ['asCopy' => true, 'audit' => ['skip' => true]]);
		$this->assertNotFalse($result);

		$this->assertCount(35, $menuEntriesTable->find()->where(['menuId' => 1])->all());
		$this->assertCount(35, $menuEntriesTable->find()->where(['menuId' => $menu->id])->all());

		$menusTable->delete($menu, ['audit' => ['skip' => true]]);

		$this->assertCount(35, $menuEntriesTable->find()->where(['menuId' => 1])->all());
		$this->assertCount(0, $menuEntriesTable->find()->where(['menuId' => $menu->id])->all());
		$this->assertCount(35, $menuEntriesTable->find('deleted')->where(['menuId' => $menu->id])->all());
	}
}
