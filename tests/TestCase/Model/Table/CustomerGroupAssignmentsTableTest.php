<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Model\Table;


use Awyiss\Model\Entity\CustomerGroupAssignment;
use Awyiss\Model\Table\CustomerGroupAssignmentsTable;
use Awyiss\ORM\Association\BelongsTo;
use Awyiss\Test\TestSuite\TestCase;
use Awyiss\Validation\Validator;
use Cake\Datasource\FactoryLocator;


/**
 * CustomerGroupAssignmentsTable Test Case
 *
 * @see \Awyiss\Model\Table\CustomerGroupAssignmentsTable
 */
class CustomerGroupAssignmentsTableTest extends TestCase {
	/**
	 * @var \Awyiss\Model\Table\CustomerGroupAssignmentsTable
	 */
	protected CustomerGroupAssignmentsTable $customerGroupAssignmentsTable;


	/**
	 * @inheritDoc
	 */
	protected function setUp(): void {
		parent::setUp();

		/** @noinspection PhpFieldAssignmentTypeMismatchInspection */
		$this->customerGroupAssignmentsTable = FactoryLocator::get('Table')->get('CustomerGroupAssignments');
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\CustomerGroupAssignmentsTable::ATTRIBUTABLE
	 */
	public function testAttributableConstant(): void {
		$this->assertFalse($this->customerGroupAssignmentsTable::ATTRIBUTABLE);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\CustomerGroupAssignmentsTable::TABLE
	 */
	public function testTableConstant(): void {
		$this->assertEquals('customer_group_assignments', $this->customerGroupAssignmentsTable::TABLE);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\CustomerGroupAssignmentsTable::initializeAssociations()
	 */
	public function testInitializeAssociations(): void {
		$this->assertCount(4, $this->customerGroupAssignmentsTable->associations()->keys());

		// Test CustomerGroups association (BelongsTo)
		$this->assertTrue($this->customerGroupAssignmentsTable->hasAssociation('CustomerGroups'));
		$customerGroupsAssociation = $this->customerGroupAssignmentsTable->getAssociation('CustomerGroups');
		$this->assertInstanceOf(BelongsTo::class, $customerGroupsAssociation);
		$this->assertFalse($customerGroupsAssociation->getCascadeCallbacks());
		$this->assertFalse($customerGroupsAssociation->getDependent());
		$this->assertSame('customer_group_id', $customerGroupsAssociation->getForeignKey());
		$this->assertSame('INNER', $customerGroupsAssociation->getJoinType());

		// Test user tracking associations
		$this->assertTrue($this->customerGroupAssignmentsTable->hasAssociation('CreatedByUser'));
		$createdByUserAssociation = $this->customerGroupAssignmentsTable->getAssociation('CreatedByUser');
		$this->assertInstanceOf(BelongsTo::class, $createdByUserAssociation);
		$this->assertFalse($createdByUserAssociation->getCascadeCallbacks());
		$this->assertFalse($createdByUserAssociation->getDependent());

		$this->assertTrue($this->customerGroupAssignmentsTable->hasAssociation('ChangedByUser'));
		$changedByUserAssociation = $this->customerGroupAssignmentsTable->getAssociation('ChangedByUser');
		$this->assertInstanceOf(BelongsTo::class, $changedByUserAssociation);
		$this->assertFalse($changedByUserAssociation->getCascadeCallbacks());
		$this->assertFalse($changedByUserAssociation->getDependent());

		$this->assertTrue($this->customerGroupAssignmentsTable->hasAssociation('DeletedByUser'));
		$deletedByUserAssociation = $this->customerGroupAssignmentsTable->getAssociation('DeletedByUser');
		$this->assertInstanceOf(BelongsTo::class, $deletedByUserAssociation);
		$this->assertFalse($deletedByUserAssociation->getCascadeCallbacks());
		$this->assertFalse($deletedByUserAssociation->getDependent());
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\CustomerGroupAssignmentsTable::validationDefault()
	 */
	public function testValidationDefault(): void {
		$validator = new Validator();
		$result = $this->customerGroupAssignmentsTable->validationDefault($validator);

		$this->assertInstanceOf(Validator::class, $result);
		$this->assertSame('customer_group_assignments', $result->getI18nDomain());

		// Test fields exist
		$this->assertTrue($result->hasField('id'));
		$this->assertTrue($result->hasField('customerGroupId'));
		$this->assertTrue($result->hasField('scope'));
		$this->assertTrue($result->hasField('foreignKey'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\CustomerGroupAssignmentsTable::validationDefault()
	 */
	public function testEntityValidationSuccess(): void {
		$data = [
			'customerGroupId' => 1,
			'scope' => 'pages',
			'foreignKey' => 1,
		];

		$entity = $this->customerGroupAssignmentsTable->newEntity($data);
		$errors = $entity->getErrors();

		$this->assertEmpty($errors, 'Valid data should not produce validation errors');
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\CustomerGroupAssignmentsTable::validationDefault()
	 */
	public function testEntityValidationSuccessWithNullForeignKey(): void {
		$data = [
			'customerGroupId' => 2,
			'scope' => 'surveys',
			'foreignKey' => null,
		];

		$entity = $this->customerGroupAssignmentsTable->newEntity($data);
		$errors = $entity->getErrors();

		$this->assertEmpty($errors, 'Valid data with null foreign key should not produce validation errors');
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\CustomerGroupAssignmentsTable::validationDefault()
	 */
	public function testEntityValidationMissingRequired(): void {
		$data = [
			'foreignKey' => 1,
		];

		$entity = $this->customerGroupAssignmentsTable->newEntity($data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('customerGroupId', $errors);
		$this->assertArrayHasKey('scope', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\CustomerGroupAssignmentsTable::validationDefault()
	 */
	public function testEntityValidationInvalidTypes(): void {
		$data = [
			'id' => 'not_an_integer',
			'customerGroupId' => 'not_an_integer',
			'scope' => true,
			'foreignKey' => 'not_an_integer',
		];

		$entity = $this->customerGroupAssignmentsTable->newEntity($data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('id', $errors);
		$this->assertArrayHasKey('customerGroupId', $errors);
		$this->assertArrayHasKey('scope', $errors);
		$this->assertArrayHasKey('foreignKey', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\CustomerGroupAssignmentsTable::validationDefault()
	 */
	public function testEntityValidationFieldLength(): void {
		$data = [
			'id' => 123456789123, // exceeds 11 char limit
			'customerGroupId' => 123456789123, // exceeds 11 char limit
			'scope' => str_repeat('a', 51), // exceeds 50 char limit
			'foreignKey' => 123456789123, // exceeds 11 char limit
		];

		$entity = $this->customerGroupAssignmentsTable->newEntity($data, ['guard' => false]);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('id', $errors);
		$this->assertArrayHasKey('customerGroupId', $errors);
		$this->assertArrayHasKey('scope', $errors);
		$this->assertArrayHasKey('foreignKey', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\CustomerGroupAssignmentsTable::validationDefault()
	 */
	public function testEntityValidationNotBlank(): void {
		$data = [
			'customerGroupId' => 1,
			'scope' => '   ', // only whitespace
		];

		$entity = $this->customerGroupAssignmentsTable->newEntity($data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('scope', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\CustomerGroupAssignmentsTable::validationDefault()
	 */
	public function testEntityValidationForeignKeyAllowEmpty(): void {
		$data = [
			'customerGroupId' => 1,
			'scope' => 'Pages',
			'foreignKey' => null, // Should be allowed
		];

		$entity = $this->customerGroupAssignmentsTable->newEntity($data);
		$errors = $entity->getErrors();

		$this->assertArrayNotHasKey('foreignKey', $errors, 'foreignKey should allow empty values');
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\CustomerGroupAssignmentsTable::buildRules()
	 */
	public function testBuildRulesCustomerGroupExists(): void {
		// Test with existing customer group
		$data = [
			'customerGroupId' => 1,
			'scope' => 'pages',
			'foreignKey' => 1,
		];

		$entity = $this->customerGroupAssignmentsTable->newEntity($data);
		$result = $this->customerGroupAssignmentsTable->checkRules($entity);
		$this->assertTrue($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\CustomerGroupAssignmentsTable::buildRules()
	 */
	public function testBuildRulesCustomerGroupNotExists(): void {
		// Test with non-existing customer group
		$data = [
			'customerGroupId' => 99999,
			'scope' => 'pages',
			'foreignKey' => 1,
		];

		$entity = $this->customerGroupAssignmentsTable->newEntity($data);
		$result = $this->customerGroupAssignmentsTable->checkRules($entity);
		$this->assertFalse($result);

		$errors = $entity->getErrors();
		$this->assertArrayHasKey('customerGroupId', $errors);
		$this->assertArrayHasKey('customerGroupExists', $errors['customerGroupId']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\CustomerGroupAssignmentsTable::newDefaultEntity()
	 */
	public function testNewDefaultEntity(): void {
		/** @var \Awyiss\Model\Entity\CustomerGroupAssignment $entity */
		$entity = $this->customerGroupAssignmentsTable->newDefaultEntity();

		$this->assertInstanceOf(CustomerGroupAssignment::class, $entity);
		$this->assertTrue($entity->isNew());

		// Check default values
		$this->assertNull($entity->customerGroupId);
		$this->assertNull($entity->scope);
		$this->assertNull($entity->foreignKey);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\CustomerGroupAssignmentsTable::newDefaultEntity()
	 */
	public function testNewDefaultEntityWithData(): void {
		$additionalData = [
			'customerGroupId' => 2,
			'scope' => 'surveys',
			'foreignKey' => 2,
		];

		/** @var \Awyiss\Model\Entity\CustomerGroupAssignment $entity */
		$entity = $this->customerGroupAssignmentsTable->newDefaultEntity($additionalData);

		$this->assertInstanceOf(CustomerGroupAssignment::class, $entity);
		$this->assertTrue($entity->isNew());

		// Check custom values
		$this->assertSame(2, $entity->customerGroupId);
		$this->assertSame('surveys', $entity->scope);
		$this->assertSame(2, $entity->foreignKey);
	}
}
