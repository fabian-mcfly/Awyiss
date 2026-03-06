<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Model\Entity;


use Awyiss\Model\Entity\CustomerGroupsCustomer;
use Awyiss\Test\TestSuite\TestCase;
use Cake\Datasource\FactoryLocator;


/**
 * CustomerGroupsCustomer Entity Test Case
 *
 * @see \Awyiss\Model\Entity\CustomerGroupsCustomer
 */
class CustomerGroupsCustomerTest extends TestCase {
	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\CustomerGroupsCustomer::$fieldMap
	 */
	public function testFieldMapCompleteness(): void {
		/** @var \Awyiss\Model\Table\CustomerGroupsCustomersTable $table */
		$table = FactoryLocator::get('Table')->get('CustomerGroupsCustomers');
		$entity = $table->newDefaultEntity();
		$entityArray = $entity->toArray();

		foreach ($entityArray as $key => $value) {
			$this->assertStringNotContainsString('_', $key);
		}
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\CustomerGroupsCustomer::$_accessible
	 */
	public function testAccessibleFields(): void {
		$entity = new CustomerGroupsCustomer();

		$this->assertSame([
			'customerGroupId' => true,
			'customerId' => true,
			'customerGroup' => true,
			'customer' => true,
			'_translations' => true,
			'_publicationData' => true,
			'customerGroupAccessSettings' => true,
			'customerGroupAssignments' => true,
			'mediaAssignments' => true,
			'mediaElementAssignments' => true,
		], $entity->getAccessible());
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\CustomerGroupsCustomer
	 */
	public function testEntityConstruction(): void {
		$properties = [
			'id' => 1,
			'customerGroupId' => 123,
			'customerId' => 456,
		];

		$entity = new CustomerGroupsCustomer($properties);

		$this->assertEquals(1, $entity->id);
		$this->assertEquals(123, $entity->customerGroupId);
		$this->assertEquals(456, $entity->customerId);
	}
}
