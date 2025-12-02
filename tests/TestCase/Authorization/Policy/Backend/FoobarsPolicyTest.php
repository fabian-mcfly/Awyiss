<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Authorization\Policy\Backend;


use Awyiss\Authorization\PermissionOption\SimplePermissionOption;
use Awyiss\Test\TestSuite\TestCase;
use Customer\Authorization\Policy\Backend\FoobarsPolicy;


/**
 * Test the AbstractPolicy
 */
class FoobarsPolicyTest extends TestCase {
	/**
	 * @return void
	 */
	public function testGetScope(): void {
		$this->assertSame('foobars', FoobarsPolicy::getScope());
	}


	/**
	 * @return void
	 * @throws \Exception
	 */
	public function testGetPermissionOptions(): void {
		$permissionOptions = FoobarsPolicy::getPermissionOptions();

		$this->assertCount(4, $permissionOptions);

		$this->assertInstanceOf(SimplePermissionOption::class, $permissionOptions->get('create'));
	}


	/**
	 * @return void
	 * @throws \Exception
	 */
	public function testGetPermissionOption(): void {
		$permissionOption = FoobarsPolicy::getPermissionOption('create');

		$this->assertSame('create', $permissionOption->getConfig('identifier'));
		$this->assertInstanceOf(SimplePermissionOption::class, $permissionOption);
	}
}
