<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Authorization\PermissionOption;


use Awyiss\Authorization\PermissionOption\AbstractPermissionOption;
use Awyiss\Authorization\PermissionOption\PermissionOptionCollection;
use Awyiss\Test\TestSuite\TestCase;
use RuntimeException;


/**
 * AbstractPermissionOptionTest class
 */
class AbstractPermissionOptionTest extends TestCase {
	/**
	 * @return void
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testConstructorInitializesCorrectly(): void {
		$permissionOptionCollection = new PermissionOptionCollection('TestScope');
		$config = ['identifier' => 'test_identifier'];

		$abstractPermissionOption = $this->getMockBuilder(AbstractPermissionOption::class)
		->setConstructorArgs([$config, $permissionOptionCollection])
		->onlyMethods(['harmonizeOptionValue', 'isAccessible'])
		->getMock();

		$this->assertSame($permissionOptionCollection, $abstractPermissionOption->getPermissionOptionCollection());
		$this->assertSame('test_identifier', $abstractPermissionOption->getConfig('identifier'));
	}


	/**
	 * @return void
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testGetTypeReturnsCorrectType(): void {
		$permissionOptionCollection = new PermissionOptionCollection('TestScope');
		$config = ['identifier' => 'test_identifier'];

		$abstractPermissionOption = $this->getMockBuilder(AbstractPermissionOption::class)
		->setConstructorArgs([$config, $permissionOptionCollection])
		->onlyMethods(['harmonizeOptionValue', 'isAccessible'])
		->getMock();

		// The type will be the underscored class name with the last 10 characters removed
		$this->assertSame('mock_object_abstract_permission_optio', $abstractPermissionOption->getType());
	}


	/**
	 * @return void
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testGetOptionsReturnsOptions(): void {
		$permissionOptionCollection = new PermissionOptionCollection('TestScope');
		$config = ['identifier' => 'test_identifier'];

		$abstractPermissionOption = $this->getMockBuilder(AbstractPermissionOption::class)
		->setConstructorArgs([$config, $permissionOptionCollection])
		->onlyMethods(['harmonizeOptionValue', 'isAccessible'])
		->getMock();

		$this->assertSame([], $abstractPermissionOption->getOptions());
	}


	/**
	 * @return void
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testSetOptionsThrowsException(): void {
		$permissionOptionCollection = new PermissionOptionCollection('TestScope');
		$config = ['identifier' => 'test_identifier'];

		$abstractPermissionOption = $this->getMockBuilder(AbstractPermissionOption::class)
		->setConstructorArgs([$config, $permissionOptionCollection])
		->onlyMethods(['harmonizeOptionValue', 'isAccessible'])
		->getMock();

		$this->expectException(RuntimeException::class);
		$abstractPermissionOption->setOptions(['test_option' => 'test_value']);
	}
}
