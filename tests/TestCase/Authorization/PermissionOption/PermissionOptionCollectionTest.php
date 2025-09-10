<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Authorization\PermissionOption;


use Awyiss\Authorization\AuthorizationService;
use Awyiss\Authorization\PermissionOption\PermissionOptionCollection;
use Awyiss\Authorization\PermissionOption\SimplePermissionOption;
use Awyiss\Test\TestSuite\TestCase;
use RuntimeException;


/**
 * CallbackPermissionOptionTest class
 */
class PermissionOptionCollectionTest extends TestCase {
	/**
	 * @return void
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testConstructorAndGetScope(): void {
		$scope = 'TestScope';
		$collection = new PermissionOptionCollection($scope);

		$this->assertSame(AuthorizationService::sanitizeScope($scope), $collection->getScope());
	}


	/**
	 * @return void
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testConstructorWithConfig(): void {
		$config = [
			'testPermission' => SimplePermissionOption::class,
		];

		$collection = new PermissionOptionCollection('TestScope', $config);

		$this->assertFalse($collection->isEmpty());
		$this->assertInstanceOf(SimplePermissionOption::class, $collection->get('testPermission'));
	}


	/**
	 * @return void
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testAdd(): void {
		$collection = new PermissionOptionCollection('TestScope');

		// Add as array
		$collection->add('test_permission', ['className' => SimplePermissionOption::class]);

		$this->assertInstanceOf(SimplePermissionOption::class, $collection->get('testPermission'));

		// Add as string
		$collection->add('test_permission_2', SimplePermissionOption::class);

		$this->assertInstanceOf(SimplePermissionOption::class, $collection->get('testPermission2'));
	}


	/**
	 * @return void
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testLoad(): void {
		$collection = new PermissionOptionCollection('TestScope');

		$loadedPermission = $collection->load('testPermission', ['className' => SimplePermissionOption::class]);

		$this->assertInstanceOf(SimplePermissionOption::class, $loadedPermission);
	}


	/**
	 * @return void
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testLoadThrowsExceptionForMissingClassName(): void {
		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('Missing config key `className`');

		$collection = new PermissionOptionCollection('TestScope');

		/** @noinspection PhpRedundantOptionalArgumentInspection */
		$collection->load('testPermission', []);
	}
}
