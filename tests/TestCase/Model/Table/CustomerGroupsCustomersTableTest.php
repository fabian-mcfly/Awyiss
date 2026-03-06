<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Model\Table;


use Awyiss\Model\Entity\CustomerGroupsCustomer;
use Awyiss\Model\Table\CustomerGroupsCustomersTable;
use Awyiss\ORM\Association\BelongsTo;
use Awyiss\Test\TestSuite\TestCase;
use Awyiss\Validation\Validator;
use Cake\Datasource\FactoryLocator;


/**
 * CustomerGroupsCustomersTable Test Case
 *
 * @see \Awyiss\Model\Table\CustomerGroupsCustomersTable
 */
class CustomerGroupsCustomersTableTest extends TestCase {
	/**
	 * @var \Awyiss\Model\Table\CustomerGroupsCustomersTable
	 */
	protected CustomerGroupsCustomersTable $customerGroupsCustomersTable;


	/**
	 * @inheritDoc
	 */
	protected function setUp(): void {
		parent::setUp();

		/** @noinspection PhpFieldAssignmentTypeMismatchInspection */
		$this->customerGroupsCustomersTable = FactoryLocator::get('Table')->get('CustomerGroupsCustomers');
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\CustomerGroupsCustomersTable::ATTRIBUTABLE
	 */
	public function testAttributableConstant(): void {
		$this->assertFalse($this->customerGroupsCustomersTable::ATTRIBUTABLE);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\CustomerGroupsCustomersTable::TABLE
	 */
	public function testTableConstant(): void {
		$this->assertEquals('customer_groups_customers', $this->customerGroupsCustomersTable::TABLE);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\CustomerGroupsCustomersTable::initializeAssociations()
	 */
	public function testInitializeAssociations(): void {
		$this->assertCount(2, $this->customerGroupsCustomersTable->associations()->keys());

		$this->assertTrue($this->customerGroupsCustomersTable->hasAssociation('CustomerGroups'));
		$customerGroupsAssociation = $this->customerGroupsCustomersTable->getAssociation('CustomerGroups');
		$this->assertInstanceOf(BelongsTo::class, $customerGroupsAssociation);
		$this->assertSame('INNER', $customerGroupsAssociation->getJoinType());

		$this->assertTrue($this->customerGroupsCustomersTable->hasAssociation('Customers'));
		$customersAssociation = $this->customerGroupsCustomersTable->getAssociation('Customers');
		$this->assertInstanceOf(BelongsTo::class, $customersAssociation);
		$this->assertSame('INNER', $customersAssociation->getJoinType());
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\CustomerGroupsCustomersTable::validationDefault()
	 */
	public function testValidationDefault(): void {
		$validator = new Validator();
		$result = $this->customerGroupsCustomersTable->validationDefault($validator);

		$this->assertInstanceOf(Validator::class, $result);
		$this->assertSame('CustomerGroupsCustomers', $result->getI18nDomain());

		// Test required fields
		$this->assertTrue($result->hasField('id'));
		$this->assertTrue($result->hasField('customerGroupId'));
		$this->assertTrue($result->hasField('customerId'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\CustomerGroupsCustomersTable::validationDefault()
	 */
	public function testEntityValidationSuccess(): void {
		$data = [
			'customerGroupId' => 1,
			'customerId' => 1,
		];

		$entity = $this->customerGroupsCustomersTable->newDefaultEntity($data);
		$errors = $entity->getErrors();

		$this->assertEmpty($errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\CustomerGroupsCustomersTable::validationDefault()
	 */
	public function testEntityValidationInvalidTypes(): void {
		$data = [
			'id' => 'not_an_integer',
			'customerGroupId' => 'not_an_integer',
			'customerId' => 'not_an_integer',
		];

		$entity = $this->customerGroupsCustomersTable->newDefaultEntity();
		$this->customerGroupsCustomersTable->patchEntity($entity, $data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('id', $errors);
		$this->assertArrayHasKey('customerGroupId', $errors);
		$this->assertArrayHasKey('customerId', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\CustomerGroupsCustomersTable::validationDefault()
	 */
	public function testEntityValidationFieldLength(): void {
		$data = [
			'id' => 123456789123, // exceeds 11 char limit
			'customerGroupId' => 123456789123, // exceeds 11 char limit
			'customerId' => 123456789123, // exceeds 11 char limit
		];

		$entity = $this->customerGroupsCustomersTable->newDefaultEntity();
		$this->customerGroupsCustomersTable->patchEntity($entity, $data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('id', $errors);
		$this->assertArrayHasKey('customerGroupId', $errors);
		$this->assertArrayHasKey('customerId', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\CustomerGroupsCustomersTable::buildRules()
	 */
	public function testBuildRulesValid(): void {
		$data = [
			'customerGroupId' => 1, // Existing customer group
			'customerId' => 1, // Existing customer
		];

		$entity = $this->customerGroupsCustomersTable->newDefaultEntity();
		$this->customerGroupsCustomersTable->patchEntity($entity, $data);

		$result = $this->customerGroupsCustomersTable->checkRules($entity);
		$this->assertTrue($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\CustomerGroupsCustomersTable::buildRules()
	 */
	public function testBuildRulesInvalidCustomerGroup(): void {
		$data = [
			'customerGroupId' => 99999, // Non-existing customer group
			'customerId' => 1, // Existing customer
		];

		$entity = $this->customerGroupsCustomersTable->newDefaultEntity();
		$this->customerGroupsCustomersTable->patchEntity($entity, $data);

		$result = $this->customerGroupsCustomersTable->checkRules($entity);

		$this->assertFalse($result);

		$errors = $entity->getErrors();
		$this->assertArrayHasKey('customerGroupId', $errors);
		$this->assertArrayHasKey('customerGroupExists', $errors['customerGroupId']);
		$this->assertSame('customer_groups_customers::error_customer_group_exists', $errors['customerGroupId']['customerGroupExists']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\CustomerGroupsCustomersTable::buildRules()
	 */
	public function testBuildRulesInvalidCustomer(): void {
		$data = [
			'customerGroupId' => 1, // Existing customer group
			'customerId' => 99999, // Non-existing customer
		];

		$entity = $this->customerGroupsCustomersTable->newDefaultEntity();
		$this->customerGroupsCustomersTable->patchEntity($entity, $data);

		$result = $this->customerGroupsCustomersTable->checkRules($entity);

		$this->assertFalse($result);

		$errors = $entity->getErrors();
		$this->assertArrayHasKey('customerId', $errors);
		$this->assertArrayHasKey('customerExists', $errors['customerId']);
		$this->assertSame('customer_groups_customers::error_customer_exists', $errors['customerId']['customerExists']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\CustomerGroupsCustomersTable::newDefaultEntity()
	 */
	public function testNewDefaultEntity(): void {
		/** @var \Awyiss\Model\Entity\CustomerGroupsCustomer $entity */
		$entity = $this->customerGroupsCustomersTable->newDefaultEntity();

		$this->assertInstanceOf(CustomerGroupsCustomer::class, $entity);
		$this->assertTrue($entity->isNew());

		// Test default values
		$this->assertNull($entity->customerGroupId);
		$this->assertNull($entity->customerId);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\CustomerGroupsCustomersTable::newDefaultEntity()
	 */
	public function testNewDefaultEntityWithData(): void {
		$additionalData = [
			'customerGroupId' => 2,
			'customerId' => 3,
		];

		$entity = $this->customerGroupsCustomersTable->newDefaultEntity($additionalData);

		$this->assertInstanceOf(CustomerGroupsCustomer::class, $entity);
		$this->assertTrue($entity->isNew());

		$this->assertSame(2, $entity->customerGroupId);
		$this->assertSame(3, $entity->customerId);
	}
}
