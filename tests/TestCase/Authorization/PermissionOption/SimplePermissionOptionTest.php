<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Authorization\PermissionOption;


use Awyiss\Authorization\Permission\PermissionAccess;
use Awyiss\Authorization\Permission\PermissionCollection;
use Awyiss\Authorization\PermissionOption\PermissionOptionCollection;
use Awyiss\Authorization\PermissionOption\SimplePermissionOption;
use Awyiss\Test\TestSuite\TestCase;


/**
 * SimplePermissionOptionTest class
 */
class SimplePermissionOptionTest extends TestCase {
	/**
	 * @return void
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 */
	public function testConstructorInitializesOptionsCorrectly(): void {
		$permissionOptionCollection = $this->createMock(PermissionOptionCollection::class);
		$config = [];

		$simplePermissionOption = new SimplePermissionOption($config, $permissionOptionCollection);

		$expectedOptions = [
			'granted' => PermissionAccess::Granted,
			'denied' => PermissionAccess::Denied,
			'indifferent' => null,
		];

		$this->assertSame($expectedOptions, $simplePermissionOption->getOptions());
	}


	/**
	 * @return void
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 */
	public function testHarmonizeOptionValueReturnsNullForEmptyValue(): void {
		$permissionOptionCollection = $this->createMock(PermissionOptionCollection::class);
		$config = [];
		$simplePermissionOption = new SimplePermissionOption($config, $permissionOptionCollection);

		$this->assertNull($simplePermissionOption->harmonizeOptionValue(''));
		$this->assertNull($simplePermissionOption->harmonizeOptionValue(null));
	}


	/**
	 * @return void
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 */
	public function testHarmonizeOptionValueReturnsPermissionAccess(): void {
		$permissionOptionCollection = $this->createMock(PermissionOptionCollection::class);
		$config = [];
		$simplePermissionOption = new SimplePermissionOption($config, $permissionOptionCollection);

		$this->assertSame(PermissionAccess::Granted, $simplePermissionOption->harmonizeOptionValue('1'));
		$this->assertSame(PermissionAccess::Granted, $simplePermissionOption->harmonizeOptionValue(1));
		$this->assertSame(PermissionAccess::Granted, $simplePermissionOption->harmonizeOptionValue(true));
		$this->assertSame(PermissionAccess::Granted, $simplePermissionOption->harmonizeOptionValue(PermissionAccess::Granted->value));

		$this->assertSame(PermissionAccess::Denied, $simplePermissionOption->harmonizeOptionValue('0'));
		$this->assertSame(PermissionAccess::Denied, $simplePermissionOption->harmonizeOptionValue(0));
		$this->assertSame(PermissionAccess::Denied, $simplePermissionOption->harmonizeOptionValue(false));
		$this->assertSame(PermissionAccess::Denied, $simplePermissionOption->harmonizeOptionValue(PermissionAccess::Denied->value));
	}


	/**
	 * @return void
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 */
	public function testIsAccessibleReturnsTrueForGrantedAccess(): void {
		$permissionOptionCollection = $this->createMock(PermissionOptionCollection::class);
		$permissionCollection = $this->createMock(PermissionCollection::class);

		$simplePermissionOption = new SimplePermissionOption([], $permissionOptionCollection);

		$this->assertTrue($simplePermissionOption->isAccessible('1', [], [], $permissionCollection));
		$this->assertTrue($simplePermissionOption->isAccessible(1, [], [], $permissionCollection));
		$this->assertTrue($simplePermissionOption->isAccessible(true, [], [], $permissionCollection));
		$this->assertTrue($simplePermissionOption->isAccessible(PermissionAccess::Granted, [], [], $permissionCollection));
	}


	/**
	 * @return void
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 */
	public function testIsAccessibleReturnsFalseForDeniedAccess(): void {
		$permissionOptionCollection = $this->createMock(PermissionOptionCollection::class);

		$permissionCollection = $this->createMock(PermissionCollection::class);

		$simplePermissionOption = new SimplePermissionOption([], $permissionOptionCollection);

		$this->assertFalse($simplePermissionOption->isAccessible('0', [], [], $permissionCollection));
		$this->assertFalse($simplePermissionOption->isAccessible(0, [], [], $permissionCollection));
		$this->assertFalse($simplePermissionOption->isAccessible(false, [], [], $permissionCollection));
		$this->assertFalse($simplePermissionOption->isAccessible(PermissionAccess::Denied, [], [], $permissionCollection));
	}


	/**
	 * @return void
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 */
	public function testIsAccessibleReturnsNullForIndifferentAccess(): void {
		$permissionOptionCollection = $this->createMock(PermissionOptionCollection::class);
		$permissionCollection = $this->createMock(PermissionCollection::class);

		$simplePermissionOption = new SimplePermissionOption([], $permissionOptionCollection);

		$this->assertNull($simplePermissionOption->isAccessible(null, [], [], $permissionCollection));
	}
}
