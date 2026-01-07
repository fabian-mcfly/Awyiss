<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Model\Trait;


use Awyiss\Model\Entity\Content;
use Awyiss\Model\Entity\Customer;
use Awyiss\Model\Entity\CustomerGroup;
use Awyiss\Model\Entity\CustomerGroupAccessSetting;
use Awyiss\Model\Entity\CustomerGroupAssignment;
use Awyiss\Model\Enum\CustomerGroupAccessType;
use Awyiss\Test\TestSuite\TestCase;


/**
 * CustomerGroupAccessTrait Test Case
 *
 * @see \Awyiss\Model\Trait\CustomerGroupAccessTrait
 */
class CustomerGroupAccessTraitTest extends TestCase {
	/**
	 * Test isAccessibleBy when no access settings exist
	 *
	 * @return void
	 */
	public function testIsAccessibleByWithNoAccessSettings(): void {
		$content = new Content();

		// Without access settings, should be accessible to everyone
		$this->assertTrue($content->isAccessibleBy(null));
		$this->assertTrue($content->isAccessibleBy($this->createCustomer()));
	}


	/**
	 * Test isAccessibleBy with AllGroups access type
	 *
	 * @return void
	 */
	public function testIsAccessibleByWithAllGroups(): void {
		$content = new Content();
		$content->customerGroupAccessSettings = new CustomerGroupAccessSetting([
			'accessType' => CustomerGroupAccessType::AllGroups,
		]);

		// Should NOT be accessible to non-logged-in users
		$this->assertFalse($content->isAccessibleBy(null));

		// Should NOT be accessible to customers without groups
		$this->assertFalse($content->isAccessibleBy($this->createCustomer([])));

		// Should be accessible to customers with at least one group
		$this->assertTrue($content->isAccessibleBy($this->createCustomer([
			new CustomerGroup(['id' => 1]),
		])));
	}


	/**
	 * Test isAccessibleBy with HideOnLogin access type
	 *
	 * @return void
	 */
	public function testIsAccessibleByWithHideOnLogin(): void {
		$content = new Content();
		$content->customerGroupAccessSettings = new CustomerGroupAccessSetting([
			'accessType' => CustomerGroupAccessType::HideOnLogin,
		]);

		// Should be accessible only to non-logged-in users
		$this->assertTrue($content->isAccessibleBy(null));
		$this->assertFalse($content->isAccessibleBy($this->createCustomer()));
	}


	/**
	 * Test isAccessibleBy with SpecificGroups when user is not logged in
	 *
	 * @return void
	 */
	public function testIsAccessibleByWithSpecificGroupsNoLogin(): void {
		$content = new Content();
		$content->customerGroupAccessSettings = new CustomerGroupAccessSetting([
			'accessType' => CustomerGroupAccessType::SpecificGroups,
		]);

		// Should not be accessible to non-logged-in users
		$this->assertFalse($content->isAccessibleBy(null));
	}


	/**
	 * Test isAccessibleBy with SpecificGroups when customer has no groups
	 *
	 * @return void
	 */
	public function testIsAccessibleByWithSpecificGroupsNoCustomerGroups(): void {
		$content = new Content();
		$content->customerGroupAccessSettings = new CustomerGroupAccessSetting([
			'accessType' => CustomerGroupAccessType::SpecificGroups,
		]);
		$content->customerGroupAssignments = [
			new CustomerGroupAssignment(['customerGroupId' => 1]),
		];

		$customer = $this->createCustomer([]);

		// Should not be accessible - customer has no groups
		$this->assertFalse($content->isAccessibleBy($customer));
	}


	/**
	 * Test isAccessibleBy with SpecificGroups when no assignments exist
	 *
	 * @return void
	 */
	public function testIsAccessibleByWithSpecificGroupsNoAssignments(): void {
		$content = new Content();
		$content->customerGroupAccessSettings = new CustomerGroupAccessSetting([
			'accessType' => CustomerGroupAccessType::SpecificGroups,
		]);
		$content->customerGroupAssignments = [];

		$customer = $this->createCustomer([
			new CustomerGroup(['id' => 1]),
		]);

		// Should not be accessible - no assigned groups
		$this->assertFalse($content->isAccessibleBy($customer));
	}


	/**
	 * Test isAccessibleBy with SpecificGroups with matching customer group
	 *
	 * @return void
	 */
	public function testIsAccessibleByWithSpecificGroupsMatching(): void {
		$content = new Content();
		$content->customerGroupAccessSettings = new CustomerGroupAccessSetting([
			'accessType' => CustomerGroupAccessType::SpecificGroups,
		]);
		$content->customerGroupAssignments = [
			new CustomerGroupAssignment(['customerGroupId' => 1]),
			new CustomerGroupAssignment(['customerGroupId' => 2]),
		];

		$customer = $this->createCustomer([
			new CustomerGroup(['id' => 1]),
		]);

		// Should be accessible - customer belongs to group 1
		$this->assertTrue($content->isAccessibleBy($customer));
	}


	/**
	 * Test isAccessibleBy with SpecificGroups with non-matching customer groups
	 *
	 * @return void
	 */
	public function testIsAccessibleByWithSpecificGroupsNotMatching(): void {
		$content = new Content();
		$content->customerGroupAccessSettings = new CustomerGroupAccessSetting([
			'accessType' => CustomerGroupAccessType::SpecificGroups,
		]);
		$content->customerGroupAssignments = [
			new CustomerGroupAssignment(['customerGroupId' => 1]),
			new CustomerGroupAssignment(['customerGroupId' => 2]),
		];

		$customer = $this->createCustomer([
			new CustomerGroup(['id' => 3]),
			new CustomerGroup(['id' => 4]),
		]);

		// Should not be accessible - customer doesn't belong to any assigned groups
		$this->assertFalse($content->isAccessibleBy($customer));
	}


	/**
	 * Test isAccessibleBy with SpecificGroups with multiple customer groups (one matching)
	 *
	 * @return void
	 */
	public function testIsAccessibleByWithSpecificGroupsMultipleGroupsOneMatching(): void {
		$content = new Content();
		$content->customerGroupAccessSettings = new CustomerGroupAccessSetting([
			'accessType' => CustomerGroupAccessType::SpecificGroups,
		]);
		$content->customerGroupAssignments = [
			new CustomerGroupAssignment(['customerGroupId' => 1]),
			new CustomerGroupAssignment(['customerGroupId' => 2]),
		];

		$customer = $this->createCustomer([
			new CustomerGroup(['id' => 3]),
			new CustomerGroup(['id' => 2]), // This one matches
			new CustomerGroup(['id' => 4]),
		]);

		// Should be accessible - customer belongs to group 2
		$this->assertTrue($content->isAccessibleBy($customer));
	}



	/**
	 * Create a mock customer with the given customer groups
	 *
	 * @param array<\Awyiss\Model\Entity\CustomerGroup> $customerGroups
	 * @return \Awyiss\Model\Entity\Customer
	 */
	protected function createCustomer(array $customerGroups = []): Customer {
		$customer = new Customer(['id' => 1]);
		$customer->customerGroups = $customerGroups;

		return $customer;
	}
}
