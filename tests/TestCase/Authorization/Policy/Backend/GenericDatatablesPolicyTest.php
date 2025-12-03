<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Authorization\Policy\Backend;


use Awyiss\Authorization\PermissionOption\SimplePermissionOption;
use Awyiss\Authorization\Policy\Backend\GenericDatatablesPolicy;
use Awyiss\Test\TestSuite\TestCase;


/**
 * Test the GenericDatatablesPolicy
 */
class GenericDatatablesPolicyTest extends TestCase {
	/**
	 * @return void
	 */
	public function testGetScope(): void {
		$policy = new GenericDatatablesPolicy('TestScope');

		$this->assertSame('test_scopes', $policy->getScope());
	}


	/**
	 * @return void
	 * @throws \Exception
	 */
	public function testGetPermissionOptions(): void {
		$policy = new GenericDatatablesPolicy('TestScope');

		$permissionOptions = $policy->getPermissionOptions();

		$this->assertCount(4, $permissionOptions);

		$this->assertInstanceOf(SimplePermissionOption::class, $permissionOptions->get('create'));
	}


	/**
	 * @return void
	 * @throws \Exception
	 */
	public function testGetPermissionOption(): void {
		$policy = new GenericDatatablesPolicy('TestScope');

		$permissionOption = $policy->getPermissionOption('create');

		$this->assertSame('create', $permissionOption->getConfig('identifier'));
		$this->assertInstanceOf(SimplePermissionOption::class, $permissionOption);
	}
}
