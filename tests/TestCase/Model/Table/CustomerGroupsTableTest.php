<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Model\Table;


use Awyiss\Model\Entity\CustomerGroup;
use Awyiss\Model\Table\CustomerGroupsTable;
use Awyiss\ORM\Association\BelongsTo;
use Awyiss\ORM\Association\BelongsToMany;
use Awyiss\ORM\Association\HasMany;
use Awyiss\ORM\Association\HasOne;
use Awyiss\Test\TestSuite\TestCase;
use Awyiss\Validation\Validator;
use Cake\Datasource\FactoryLocator;


/**
 * CustomerGroupsTable Test Case
 *
 * @see \Awyiss\Model\Table\CustomerGroupsTable
 */
class CustomerGroupsTableTest extends TestCase {
	/**
	 * @var \Awyiss\Model\Table\CustomerGroupsTable
	 */
	protected CustomerGroupsTable $customerGroupsTable;


	/**
	 * @inheritDoc
	 */
	protected function setUp(): void {
		parent::setUp();

		/** @noinspection PhpFieldAssignmentTypeMismatchInspection */
		$this->customerGroupsTable = FactoryLocator::get('Table')->get('CustomerGroups');
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\CustomerGroupsTable::ATTRIBUTABLE
	 */
	public function testAttributableConstant(): void {
		/** @noinspection PhpUnitAssertTrueWithIncompatibleTypeArgumentInspection */
		$this->assertTrue($this->customerGroupsTable::ATTRIBUTABLE);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\CustomerGroupsTable::TABLE
	 */
	public function testTableConstant(): void {
		$this->assertEquals('customer_groups', $this->customerGroupsTable::TABLE);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\CustomerGroupsTable::initializeAssociations()
	 */
	public function testInitializeAssociations(): void {
		$this->assertCount(9, $this->customerGroupsTable->associations()->keys());

		$this->assertTrue($this->customerGroupsTable->hasAssociation('Customers'));
		$customersAssociation = $this->customerGroupsTable->getAssociation('Customers');
		$this->assertInstanceOf(BelongsToMany::class, $customersAssociation);

		// 'CustomerGroupAccessSettings' must also exist
		$this->assertTrue($this->customerGroupsTable->hasAssociation('CustomerGroupAccessSettings'));
		$customerGroupAccessSettingsAssociation = $this->customerGroupsTable->getAssociation('CustomerGroupAccessSettings');
		$this->assertInstanceOf(HasOne::class, $customerGroupAccessSettingsAssociation);
		$this->assertTrue($customerGroupAccessSettingsAssociation->getCascadeCallbacks());
		$this->assertTrue($customerGroupAccessSettingsAssociation->getDependent());

		// 'CustomerGroupAssignments' must also exist
		$this->assertTrue($this->customerGroupsTable->hasAssociation('CustomerGroupAssignments'));
		$customerGroupAssignmentsAssociation = $this->customerGroupsTable->getAssociation('CustomerGroupAssignments');
		$this->assertInstanceOf(HasMany::class, $customerGroupAssignmentsAssociation);
		$this->assertTrue($customerGroupAssignmentsAssociation->getCascadeCallbacks());
		$this->assertTrue($customerGroupAssignmentsAssociation->getDependent());

		// 'MediaAssignments' must also exist
		$this->assertTrue($this->customerGroupsTable->hasAssociation('MediaAssignments'));
		$mediaAssignmentsAssociation = $this->customerGroupsTable->getAssociation('MediaAssignments');
		$this->assertInstanceOf(HasMany::class, $mediaAssignmentsAssociation);
		$this->assertTrue($mediaAssignmentsAssociation->getCascadeCallbacks());
		$this->assertTrue($mediaAssignmentsAssociation->getDependent());

		// 'CreatedByUser' must also exist
		$this->assertTrue($this->customerGroupsTable->hasAssociation('CreatedByUser'));
		$createdByUserAssociation = $this->customerGroupsTable->getAssociation('CreatedByUser');
		$this->assertInstanceOf(BelongsTo::class, $createdByUserAssociation);
		$this->assertFalse($createdByUserAssociation->getCascadeCallbacks());
		$this->assertFalse($createdByUserAssociation->getDependent());

		// 'ChangedByUser' must also exist
		$this->assertTrue($this->customerGroupsTable->hasAssociation('ChangedByUser'));
		$changedByUserAssociation = $this->customerGroupsTable->getAssociation('ChangedByUser');
		$this->assertInstanceOf(BelongsTo::class, $changedByUserAssociation);
		$this->assertFalse($changedByUserAssociation->getCascadeCallbacks());
		$this->assertFalse($changedByUserAssociation->getDependent());

		// 'DeletedByUser' must also exist
		$this->assertTrue($this->customerGroupsTable->hasAssociation('DeletedByUser'));
		$deletedByUserAssociation = $this->customerGroupsTable->getAssociation('DeletedByUser');
		$this->assertInstanceOf(BelongsTo::class, $deletedByUserAssociation);
		$this->assertFalse($deletedByUserAssociation->getCascadeCallbacks());
		$this->assertFalse($deletedByUserAssociation->getDependent());

		// 'CustomerGroups_title_translation' must also exist
		$this->assertTrue($this->customerGroupsTable->hasAssociation('CustomerGroups_title_translation'));
		$titleTranslationAssociation = $this->customerGroupsTable->getAssociation('CustomerGroups_title_translation');
		$this->assertInstanceOf(HasOne::class, $titleTranslationAssociation);
		$this->assertFalse($titleTranslationAssociation->getCascadeCallbacks());
		$this->assertFalse($titleTranslationAssociation->getDependent());

		// 'I18n' must also exist
		$this->assertTrue($this->customerGroupsTable->hasAssociation('I18n'));
		$i18nAssociation = $this->customerGroupsTable->getAssociation('I18n');
		$this->assertInstanceOf(HasMany::class, $i18nAssociation);
		$this->assertFalse($i18nAssociation->getCascadeCallbacks());
		$this->assertTrue($i18nAssociation->getDependent());
		$this->assertEquals('append', $i18nAssociation->getSaveStrategy());
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\CustomerGroupsTable::validationDefault()
	 */
	public function testValidationDefault(): void {
		$validator = new Validator();
		$result = $this->customerGroupsTable->validationDefault($validator);

		$this->assertInstanceOf(Validator::class, $result);
		$this->assertSame('customer_groups', $result->getI18nDomain());

		// Test required fields
		$this->assertTrue($result->hasField('title'));
		$this->assertSame('create', $result->field('title')->isPresenceRequired());

		// Test other fields
		$this->assertTrue($result->hasField('id'));
		$this->assertTrue($result->hasField('active'));
		$this->assertTrue($result->hasField('deleted'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\CustomerGroupsTable::validationDefault()
	 */
	public function testEntityValidationSuccess(): void {
		$data = [
			'title' => 'Test Customer Group',
			'active' => true,
			'deleted' => false,
		];

		$entity = $this->customerGroupsTable->newDefaultEntity($data);
		$errors = $entity->getErrors();

		$this->assertEmpty($errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\CustomerGroupsTable::validationDefault()
	 */
	public function testEntityValidationMissingRequired(): void {
		$data = [
			'active' => true,
		];

		$entity = $this->customerGroupsTable->newDefaultEntity();
		$this->customerGroupsTable->patchEntity($entity, $data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('title', $errors);
		$this->assertArrayHasKey('_required', $errors['title']);
		$this->assertSame('customer_groups::error_required', $errors['title']['_required']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\CustomerGroupsTable::validationDefault()
	 */
	public function testEntityValidationInvalidTypes(): void {
		$data = [
			'id' => 'not_an_integer',
			'title' => true,
			'active' => 'not_a_boolean',
			'deleted' => 'not_a_boolean',
		];

		$entity = $this->customerGroupsTable->newDefaultEntity();
		$this->customerGroupsTable->patchEntity($entity, $data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('id', $errors);
		$this->assertArrayHasKey('title', $errors);
		$this->assertArrayHasKey('active', $errors);
		$this->assertArrayHasKey('deleted', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\CustomerGroupsTable::validationDefault()
	 */
	public function testEntityValidationFieldLength(): void {
		$data = [
			'id' => 123456789123, // exceeds 11 char limit
			'title' => str_repeat('a', 101), // exceeds 100 char limit
		];

		$entity = $this->customerGroupsTable->newDefaultEntity();
		$this->customerGroupsTable->patchEntity($entity, $data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('id', $errors);
		$this->assertArrayHasKey('title', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\CustomerGroupsTable::validationDefault()
	 */
	public function testEntityValidationEmptyTitle(): void {
		$data = [
			'title' => '',
		];

		$entity = $this->customerGroupsTable->newDefaultEntity();
		$this->customerGroupsTable->patchEntity($entity, $data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('title', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\CustomerGroupsTable::validationDefault()
	 */
	public function _estEntityValidationBlankTitle(): void {
		$data = [
			'title' => '   ', // Only whitespace
		];

		$entity = $this->customerGroupsTable->newDefaultEntity();
		$this->customerGroupsTable->patchEntity($entity, $data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('title', $errors);
		$this->assertArrayHasKey('notBlank', $errors['title']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\CustomerGroupsTable::buildRules()
	 */
	public function testBuildRulesUniqueTitle(): void {
		$data = [
			'title' => 'Unique Customer Group Title',
			'active' => true,
			'deleted' => false,
		];

		$entity = $this->customerGroupsTable->newDefaultEntity();
		$this->customerGroupsTable->patchEntity($entity, $data);

		$result = $this->customerGroupsTable->checkRules($entity);
		$this->assertTrue($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\CustomerGroupsTable::buildRules()
	 */
	public function testBuildRulesExistingTitle(): void {
		// Create first customer group
		$data1 = [
			'title' => 'Existing Customer Group',
			'active' => true,
			'deleted' => false,
		];

		$entity1 = $this->customerGroupsTable->newDefaultEntity();
		$this->customerGroupsTable->patchEntity($entity1, $data1);
		$this->customerGroupsTable->saveOrFail($entity1, ['audit' => ['skip' => true]]);

		// Try to create another customer group with the same title
		$data2 = [
			'title' => 'Existing Customer Group',
			'active' => true,
			'deleted' => false,
		];

		$entity2 = $this->customerGroupsTable->newDefaultEntity();
		$this->customerGroupsTable->patchEntity($entity2, $data2);

		$result = $this->customerGroupsTable->checkRules($entity2);

		$this->assertFalse($result);

		$errors = $entity2->getErrors();
		$this->assertArrayHasKey('title', $errors);
		$this->assertArrayHasKey('titleUnique', $errors['title']);
		$this->assertSame('customer_groups::error_title_unique', $errors['title']['titleUnique']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\CustomerGroupsTable::buildRules()
	 */
	public function testBuildRulesUpdateWithSameTitle(): void {
		// Create a customer group
		$data = [
			'title' => 'Update Test Customer Group',
			'active' => true,
			'deleted' => false,
		];

		$entity = $this->customerGroupsTable->newDefaultEntity();
		$this->customerGroupsTable->patchEntity($entity, $data);
		$this->customerGroupsTable->saveOrFail($entity, ['audit' => ['skip' => true]]);

		// Update with the same title should be allowed
		$this->customerGroupsTable->patchEntity($entity, ['title' => 'Update Test Customer Group']);

		$result = $this->customerGroupsTable->checkRules($entity);
		$this->assertTrue($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\CustomerGroupsTable::newDefaultEntity()
	 */
	public function testNewDefaultEntity(): void {
		/** @var \Awyiss\Model\Entity\CustomerGroup $entity */
		$entity = $this->customerGroupsTable->newDefaultEntity();

		$this->assertInstanceOf(CustomerGroup::class, $entity);
		$this->assertTrue($entity->isNew());

		// Test default values
		$this->assertNull($entity->title);
		$this->assertTrue($entity->active);
		$this->assertFalse($entity->deleted);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\CustomerGroupsTable::newDefaultEntity()
	 */
	public function testNewDefaultEntityWithData(): void {
		$additionalData = [
			'title' => 'Custom Customer Group',
			'active' => false,
			'deleted' => true,
		];

		$entity = $this->customerGroupsTable->newDefaultEntity($additionalData);

		$this->assertInstanceOf(CustomerGroup::class, $entity);
		$this->assertTrue($entity->isNew());

		$this->assertSame('Custom Customer Group', $entity->title);
		$this->assertFalse($entity->active);
		$this->assertTrue($entity->deleted);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\CustomerGroupsTable::$translate
	 */
	public function testTranslateBehavior(): void {
		$this->assertTrue($this->customerGroupsTable->hasBehavior('Translate'));

		$config = $this->customerGroupsTable->getBehavior('Translate')->getConfig();

		$this->assertIsArray($config['fields']);
		$this->assertSame(['title'], $config['fields']);
	}
}
