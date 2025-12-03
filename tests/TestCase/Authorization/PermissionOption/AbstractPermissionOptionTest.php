<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Authorization\PermissionOption;


use Awyiss\Authorization\Permission\PermissionAccess;
use Awyiss\Authorization\Permission\PermissionCollection;
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
	 */
	public function testGetTypeReturnsCorrectType(): void {
		$permissionOptionCollection = new PermissionOptionCollection('TestScope');
		$config = ['identifier' => 'test_identifier'];

		$abstractPermissionOption = new class ($config, $permissionOptionCollection) extends AbstractPermissionOption {
			/**
			 * @var string
			 */
			protected string $type = 'test_type';


			/**
			 * @inheritDoc
			 */
			public function harmonizeOptionValue(mixed $value): ?PermissionAccess {
				return null;
			}


			/**
			 * @inheritDoc
			 */
			public function isAccessible(mixed $access, mixed $settings, array $additionalData, PermissionCollection $permissionCollection): ?bool {
				return null;
			}
		};

		// The type will be the underscored class name with the last 10 characters removed
		$this->assertSame('test_type', $abstractPermissionOption->getType());
	}


	/**
	 * @return void
	 * @throws \PHPUnit\Framework\MockObject\Exception
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
