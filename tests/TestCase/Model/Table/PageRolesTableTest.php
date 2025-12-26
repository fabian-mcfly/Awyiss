<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Model\Table;


use Awyiss\Model\Entity\PageRole;
use Awyiss\Model\Table\PageRolesTable;
use Awyiss\ORM\Association\BelongsTo;
use Awyiss\ORM\Association\HasMany;
use Awyiss\ORM\Association\HasOne;
use Awyiss\Test\TestSuite\TestCase;
use Awyiss\Validation\Validator;
use Cake\Collection\CollectionInterface;
use Cake\Datasource\FactoryLocator;
use Cake\Datasource\RulesChecker;


/**
 * PageRolesTable Test Case
 *
 * @see \Awyiss\Model\Table\PageRolesTable
 */
class PageRolesTableTest extends TestCase {
	/**
	 * @var \Awyiss\Model\Table\PageRolesTable
	 */
	protected PageRolesTable $pageRolesTable;


	/**
	 * @inheritDoc
	 */
	protected function setUp(): void {
		parent::setUp();

		/** @noinspection PhpFieldAssignmentTypeMismatchInspection */
		$this->pageRolesTable = FactoryLocator::get('Table')->get('PageRoles');
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\PageRolesTable::ATTRIBUTABLE
	 */
	public function testAttributableConstant(): void {
		/** @noinspection PhpUnitAssertTrueWithIncompatibleTypeArgumentInspection */
		$this->assertTrue($this->pageRolesTable::ATTRIBUTABLE);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\PageRolesTable::TABLE
	 */
	public function testTableConstant(): void {
		$this->assertEquals('page_roles', $this->pageRolesTable::TABLE);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\PageRolesTable::initializeAssociations()
	 */
	public function testInitializeAssociations(): void {
		$this->assertCount(10, $this->pageRolesTable->associations()->keys());

		// Test PageTemplates association (HasOne)
		$this->assertTrue($this->pageRolesTable->hasAssociation('PageTemplates'));
		$pageTemplatesAssociation = $this->pageRolesTable->getAssociation('PageTemplates');
		$this->assertInstanceOf(HasOne::class, $pageTemplatesAssociation);
		$this->assertFalse($pageTemplatesAssociation->getCascadeCallbacks());
		$this->assertFalse($pageTemplatesAssociation->getDependent());

		// Test Pages association (HasMany)
		$this->assertTrue($this->pageRolesTable->hasAssociation('Pages'));
		$pagesAssociation = $this->pageRolesTable->getAssociation('Pages');
		$this->assertInstanceOf(HasMany::class, $pagesAssociation);
		$this->assertFalse($pagesAssociation->getCascadeCallbacks());
		$this->assertFalse($pagesAssociation->getDependent());

		// Verify the finder configuration for Pages association
		$finderOptions = $pagesAssociation->getFinder();
		$this->assertArrayHasKey('all', $finderOptions);
		$this->assertArrayHasKey('skipPageRoleCheck', $finderOptions['all']);
		$this->assertTrue($finderOptions['all']['skipPageRoleCheck']);

		// 'CustomerGroupAccessSettings' must also exist
		$this->assertTrue($this->pageRolesTable->hasAssociation('CustomerGroupAccessSettings'));
		$customerGroupAccessSettingsAssociation = $this->pageRolesTable->getAssociation('CustomerGroupAccessSettings');
		$this->assertInstanceOf(HasOne::class, $customerGroupAccessSettingsAssociation);
		$this->assertTrue($customerGroupAccessSettingsAssociation->getCascadeCallbacks());
		$this->assertTrue($customerGroupAccessSettingsAssociation->getDependent());

		// 'CustomerGroupAssignments' must also exist
		$this->assertTrue($this->pageRolesTable->hasAssociation('CustomerGroupAssignments'));
		$customerGroupAssignmentsAssociation = $this->pageRolesTable->getAssociation('CustomerGroupAssignments');
		$this->assertInstanceOf(HasMany::class, $customerGroupAssignmentsAssociation);
		$this->assertTrue($customerGroupAssignmentsAssociation->getCascadeCallbacks());
		$this->assertTrue($customerGroupAssignmentsAssociation->getDependent());

		// 'MediaAssignments' must also exist
		$this->assertTrue($this->pageRolesTable->hasAssociation('MediaAssignments'));
		$mediaAssignmentsAssociation = $this->pageRolesTable->getAssociation('MediaAssignments');
		$this->assertInstanceOf(HasMany::class, $mediaAssignmentsAssociation);
		$this->assertTrue($mediaAssignmentsAssociation->getCascadeCallbacks());
		$this->assertTrue($mediaAssignmentsAssociation->getDependent());
		// 'CreatedByUser' must also exist
		$this->assertTrue($this->pageRolesTable->hasAssociation('CreatedByUser'));
		$createdByUserAssociation = $this->pageRolesTable->getAssociation('CreatedByUser');
		$this->assertInstanceOf(BelongsTo::class, $createdByUserAssociation);
		$this->assertFalse($createdByUserAssociation->getCascadeCallbacks());
		$this->assertFalse($createdByUserAssociation->getDependent());

		// 'ChangedByUser' must also exist
		$this->assertTrue($this->pageRolesTable->hasAssociation('ChangedByUser'));
		$changedByUserAssociation = $this->pageRolesTable->getAssociation('ChangedByUser');
		$this->assertInstanceOf(BelongsTo::class, $changedByUserAssociation);
		$this->assertFalse($changedByUserAssociation->getCascadeCallbacks());
		$this->assertFalse($changedByUserAssociation->getDependent());

		// 'DeletedByUser' must also exist
		$this->assertTrue($this->pageRolesTable->hasAssociation('DeletedByUser'));
		$deletedByUserAssociation = $this->pageRolesTable->getAssociation('DeletedByUser');
		$this->assertInstanceOf(BelongsTo::class, $deletedByUserAssociation);
		$this->assertFalse($deletedByUserAssociation->getCascadeCallbacks());
		$this->assertFalse($deletedByUserAssociation->getDependent());

		// 'PageRoles_title_translation' must also exist
		$this->assertTrue($this->pageRolesTable->hasAssociation('PageRoles_title_translation'));
		$titleTranslationAssociation = $this->pageRolesTable->getAssociation('PageRoles_title_translation');
		$this->assertInstanceOf(HasOne::class, $titleTranslationAssociation);
		$this->assertFalse($titleTranslationAssociation->getCascadeCallbacks());
		$this->assertFalse($titleTranslationAssociation->getDependent());

		// 'I18n' must also exist
		$this->assertTrue($this->pageRolesTable->hasAssociation('I18n'));
		$i18nAssociation = $this->pageRolesTable->getAssociation('I18n');
		$this->assertInstanceOf(HasMany::class, $i18nAssociation);
		$this->assertFalse($i18nAssociation->getCascadeCallbacks());
		$this->assertTrue($i18nAssociation->getDependent());
		$this->assertEquals('append', $i18nAssociation->getSaveStrategy());
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\PageRolesTable::findAllAndCache()
	 */
	public function testFindAllAndCache(): void {
		$result = $this->pageRolesTable->findAllAndCache();

		/** @noinspection PhpConditionAlreadyCheckedInspection */
		$this->assertInstanceOf(CollectionInterface::class, $result);

		$pageRoles = $result->toArray();
		$pageRoles = array_column($pageRoles, 'identifier', 'id');

		$this->assertSame([
			1 => 'page',
			2 => 'newscategory',
			3 => 'news',
			4 => 'product',
		], $pageRoles);

		// Test that subsequent calls return the same cached result
		$secondResult = $this->pageRolesTable->findAllAndCache();
		$this->assertNotSame($secondResult, $result);
		/** @noinspection PhpConditionAlreadyCheckedInspection */
		$this->assertInstanceOf(CollectionInterface::class, $secondResult);

		$this->assertSame($result->toArray(), $secondResult->toArray());
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\PageRolesTable::validationDefault()
	 */
	public function testValidationDefault(): void {
		$validator = new Validator();
		$result = $this->pageRolesTable->validationDefault($validator);

		$this->assertInstanceOf(Validator::class, $result);
		$this->assertSame('page_roles', $result->getI18nDomain());

		// Test required fields
		$this->assertTrue($result->hasField('title'));
		$this->assertSame('create', $result->field('title')->isPresenceRequired());

		$this->assertTrue($result->hasField('identifier'));
		$this->assertSame('create', $result->field('identifier')->isPresenceRequired());

		// Test other fields
		$this->assertTrue($result->hasField('id'));
		$this->assertTrue($result->hasField('includeInLinklist'));
		$this->assertTrue($result->hasField('systemOrder'));
		$this->assertTrue($result->hasField('active'));
		$this->assertTrue($result->hasField('deleted'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\PageRolesTable::validationDefault()
	 */
	public function testEntityValidationSuccess(): void {
		$data = [
			'title' => 'Test Page Role',
			'identifier' => 'test_role',
			'includeInLinklist' => true,
			'systemOrder' => 10,
			'active' => true,
			'deleted' => false,
		];

		$entity = $this->pageRolesTable->newEntity($data);
		$errors = $entity->getErrors();

		$this->assertEmpty($errors, 'Valid data should not produce validation errors');
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\PageRolesTable::validationDefault()
	 */
	public function testEntityValidationMissingRequired(): void {
		$data = [
			'active' => true,
		];

		$entity = $this->pageRolesTable->newEntity($data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('title', $errors);
		$this->assertArrayHasKey('identifier', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\PageRolesTable::validationDefault()
	 */
	public function testEntityValidationInvalidTypes(): void {
		$data = [
			'id' => 'not_an_integer',
			'title' => true,
			'identifier' => true,
			'includeInLinklist' => 'not_a_boolean',
			'systemOrder' => 'not_an_integer',
			'active' => 'not_a_boolean',
			'deleted' => 'not_a_boolean',
		];

		$entity = $this->pageRolesTable->newEntity($data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('id', $errors);
		$this->assertArrayHasKey('title', $errors);
		$this->assertArrayHasKey('identifier', $errors);
		$this->assertArrayHasKey('includeInLinklist', $errors);
		$this->assertArrayHasKey('systemOrder', $errors);
		$this->assertArrayHasKey('active', $errors);
		$this->assertArrayHasKey('deleted', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\PageRolesTable::validationDefault()
	 */
	public function testEntityValidationFieldLength(): void {
		$data = [
			'id' => 123456789123, // exceeds 11 char limit
			'title' => str_repeat('a', 51), // exceeds 50 char limit
			'identifier' => str_repeat('b', 51), // exceeds 50 char limit
		];

		$entity = $this->pageRolesTable->newEntity($data, ['guard' => false]);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('id', $errors);
		$this->assertArrayHasKey('title', $errors);
		$this->assertArrayHasKey('identifier', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\PageRolesTable::validationDefault()
	 */
	public function testEntityValidationEmptyStrings(): void {
		$data = [
			'title' => '',
			'identifier' => '',
		];

		$entity = $this->pageRolesTable->newEntity($data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('title', $errors);
		$this->assertArrayHasKey('identifier', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\PageRolesTable::buildRules()
	 */
	public function testBuildRulesValidIdentifierNew(): void {
		// Test with a valid new identifier
		$data = [
			'title' => 'Test Page Role',
			'identifier' => 'valid_test_role',
			'includeInLinklist' => true,
			'systemOrder' => 10,
			'active' => true,
			'deleted' => false,
		];

		$entity = $this->pageRolesTable->newEntity($data);
		$result = $this->pageRolesTable->checkRules($entity);
		$this->assertTrue($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\PageRolesTable::buildRules()
	 */
	public function testBuildRulesInvalidIdentifierBlocklisted(): void {
		// Test with blocklisted identifier
		$data = [
			'title' => 'Test Page Role',
			'identifier' => 'cell', // blocklisted identifier
			'includeInLinklist' => true,
			'systemOrder' => 10,
			'active' => true,
			'deleted' => false,
		];

		$entity = $this->pageRolesTable->newEntity($data);
		$result = $this->pageRolesTable->checkRules($entity);
		$this->assertFalse($result);

		$errors = $entity->getErrors();
		$this->assertArrayHasKey('identifier', $errors);
		$this->assertArrayHasKey('validIdentifier', $errors['identifier']);
		$this->assertEquals('page_roles::error_identifier_allowed', $errors['identifier']['validIdentifier']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\PageRolesTable::buildRules()
	 */
	public function testBuildRulesInvalidIdentifierAttributesPrefix(): void {
		// Test with attributes_ prefix (blocklisted)
		$data = [
			'title' => 'Test Page Role',
			'identifier' => 'attributes_test', // starts with attributes_
			'includeInLinklist' => true,
			'systemOrder' => 10,
			'active' => true,
			'deleted' => false,
		];

		$entity = $this->pageRolesTable->newEntity($data);
		$result = $this->pageRolesTable->checkRules($entity);
		$this->assertFalse($result);

		$errors = $entity->getErrors();
		$this->assertArrayHasKey('identifier', $errors);
		$this->assertArrayHasKey('validIdentifier', $errors['identifier']);
		$this->assertEquals('page_roles::error_identifier_allowed', $errors['identifier']['validIdentifier']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\PageRolesTable::buildRules()
	 */
	public function testBuildRulesInvalidIdentifierDuplicate(): void {
		// Test with existing identifier
		$data = [
			'title' => 'Test Page Role',
			'identifier' => 'news', // existing identifier
			'includeInLinklist' => true,
			'systemOrder' => 10,
			'active' => true,
			'deleted' => false,
		];

		$entity = $this->pageRolesTable->newEntity($data);
		$result = $this->pageRolesTable->checkRules($entity);
		$this->assertFalse($result);

		$errors = $entity->getErrors();
		$this->assertArrayHasKey('identifier', $errors);
		$this->assertArrayHasKey('validIdentifier', $errors['identifier']);
		$this->assertEquals('page_roles::error_identifier_unique', $errors['identifier']['validIdentifier']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\PageRolesTable::buildRules()
	 */
	public function testBuildRulesIdentifierUnchangedForExisting(): void {
		// Test that identifier can't be changed for existing entities
		/** @var \Awyiss\Model\Entity\PageRole $pageRole */
		$pageRole = $this->pageRolesTable->get(1);

		$pageRole->identifier = 'new_identifier';
		$result = $this->pageRolesTable->checkRules($pageRole);
		$this->assertFalse($result);

		$errors = $pageRole->getErrors();
		$this->assertArrayHasKey('identifier', $errors);
		$this->assertArrayHasKey('validIdentifier', $errors['identifier']);
		$this->assertEquals('page_roles::error_identifier_unchanged', $errors['identifier']['validIdentifier']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\PageRolesTable::buildRules()
	 */
	public function testBuildRulesIdentifierUnchangedAllowedForCopy(): void {
		// Test that identifier can be changed when copying
		/** @var \Awyiss\Model\Entity\PageRole $pageRole */
		$pageRole = $this->pageRolesTable->get(1);

		$pageRole->identifier = 'new_identifier';
		$result = $this->pageRolesTable->checkRules($pageRole, RulesChecker::UPDATE, ['isCopy' => true]);
		$this->assertTrue($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\PageRolesTable::buildRules()
	 */
	public function testBuildDeleteRulesNotPageRolePageDeletion(): void {
		// Test that 'page' role cannot be deleted
		/** @var \Awyiss\Model\Entity\PageRole $pageRole */
		$pageRole = $this->pageRolesTable->get(1);
		$this->assertNotNull($pageRole);

		$result = $this->pageRolesTable->checkRules($pageRole, RulesChecker::DELETE);
		$this->assertFalse($result);

		$errors = $pageRole->getErrors();
		$this->assertArrayHasKey('_general', $errors);
		$this->assertArrayHasKey('notPageRolePageDeletion', $errors['_general']);
		$this->assertEquals('page_roles::error_not_page_role_page_deletion', $errors['_general']['notPageRolePageDeletion']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\PageRolesTable::buildRules()
	 */
	public function testBuildDeleteRulesNoLinkedPageTemplates(): void {
		// Test that page role with linked page templates cannot be deleted
		/** @var \Awyiss\Model\Entity\PageRole $pageRole */
		$pageRole = $this->pageRolesTable->get(3); // This should have linked page templates

		$result = $this->pageRolesTable->checkRules($pageRole, RulesChecker::DELETE);
		$this->assertFalse($result);

		$errors = $pageRole->getErrors();
		$this->assertArrayHasKey('_general', $errors);
		$this->assertArrayHasKey('noLinkedPageTemplates', $errors['_general']);
		$this->assertEquals('page_roles::error_no_linked_page_templates', $errors['_general']['noLinkedPageTemplates']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\PageRolesTable::buildRules()
	 */
	public function testBuildDeleteRulesSuccessForValidPageRole(): void {
		/** @var \Awyiss\Model\Entity\PageRole $pageRole */
		$pageRole = $this->pageRolesTable->get(4);

		// Test deletion rules for this new entity
		$result = $this->pageRolesTable->checkRules($pageRole, RulesChecker::DELETE);
		$this->assertTrue($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\PageRolesTable::newDefaultEntity()
	 */
	public function testNewDefaultEntity(): void {
		$entity = $this->pageRolesTable->newDefaultEntity();

		$this->assertInstanceOf(PageRole::class, $entity);
		$this->assertTrue($entity->isNew());

		$this->assertEmpty($entity->title);
		$this->assertEmpty($entity->identifier);
		$this->assertTrue($entity->includeInLinklist);
		$this->assertEquals(0, $entity->systemOrder);
		$this->assertTrue($entity->active);
		$this->assertFalse($entity->deleted);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\PageRolesTable::newDefaultEntity()
	 */
	public function testNewDefaultEntityWithData(): void {
		$additionalData = [
			'title' => 'Test Role',
			'identifier' => 'test_role',
			'includeInLinklist' => false,
			'systemOrder' => 5,
			'active' => false,
		];

		$entity = $this->pageRolesTable->newDefaultEntity($additionalData);

		$this->assertInstanceOf(PageRole::class, $entity);
		$this->assertTrue($entity->isNew());
		$this->assertEquals('Test Role', $entity->title);
		$this->assertEquals('test_role', $entity->identifier);
		$this->assertFalse($entity->includeInLinklist);
		$this->assertEquals(5, $entity->systemOrder);
		$this->assertFalse($entity->active);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\UsergroupsTable::$translate
	 */
	public function testTranslateBehavior(): void {
		$this->assertTrue($this->pageRolesTable->hasBehavior('Translate'));

		$behavior = $this->pageRolesTable->getBehavior('Translate');
		$config = $behavior->getConfig();

		$this->assertArrayHasKey('fields', $config);
		$this->assertContains('title', $config['fields']);
	}
}
