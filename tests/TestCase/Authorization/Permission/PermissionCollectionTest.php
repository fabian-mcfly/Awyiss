<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Authorization\Permission;


use Awyiss\Authorization\AuthorizationService;
use Awyiss\Authorization\Permission\Permission;
use Awyiss\Authorization\Permission\PermissionAccess;
use Awyiss\Authorization\Permission\PermissionCollection;
use Awyiss\Authorization\Permission\PermissionInterface;
use Awyiss\Test\TestSuite\TestCase;
use TypeError;


/**
 * PermissionCollectionTest class
 */
class PermissionCollectionTest extends TestCase {
	/**
	 * @return void
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 */
	public function testConstructorInitializesPermissionsCorrectly(): void {
		$authorizationService = $this->createMock(AuthorizationService::class);

		$permissionInterface = $this->createMock(PermissionInterface::class);
		$permissionInterface->method('getScope')->willReturn('FromObjectScope');
		$permissionInterface->method('getIdentifier')->willReturn('unknown identifier');
		$permissionInterface->method('getAccess')->willReturn('access');
		$permissionInterface->method('getSettings')->willReturn(['setting1']);

		$collection = new PermissionCollection($authorizationService, [
			Permission::createFromArray([
				'scope' => 'FooScope',
				'identifier' => 'foo_identifier',
				'access' => PermissionAccess::Granted,
			]),
			[
				'scope' => 'barScope',
				'identifier' => 'bar_identifier',
				'access' => PermissionAccess::Granted,
			],
			[
				'scope' => 'bazScope',
				'identifier' => 'baz_identifier',
				'access' => PermissionAccess::Denied,
			],
			$permissionInterface,
		]);

		$this->assertIsArray($collection->get());
		$this->assertCount(4, $collection->get());
	}


	/**
	 * @return void
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 */
	public function testAddPermission(): void {
		$authorizationService = $this->createMock(AuthorizationService::class);

		$collection = new PermissionCollection($authorizationService);

		$collection->add(Permission::createFromArray([
			'scope' => 'FooScope',
			'identifier' => 'foo_identifier',
			'access' => PermissionAccess::Granted,
		]));

		$this->assertTrue($collection->hasPermissions('fooScope'));
		$this->assertTrue($collection->hasPermissions('foo_scope'));

		$this->assertTrue($collection->hasPermissions('foo_scope', 'foo_identifier'));
		$this->assertTrue($collection->hasPermissions('foo_scope', 'fooIdentifier'));
	}


	/**
	 * @return void
	 */
	public function testGetPermissionsReturnsNullWhenScopeNotFound(): void {
		$collection = new PermissionCollection(null);

		$collection->add(Permission::createFromArray([
			'scope' => 'FooScope',
			'identifier' => 'foo_identifier',
			'access' => PermissionAccess::Granted,
		]));

		$this->assertNull($collection->getPermissions('non_existent_scope'));
	}


	/**
	 * @return void
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 */
	public function testGetPermissionsReturnsNullWhenIdentifierNotFound(): void {
		$authorizationService = $this->createMock(AuthorizationService::class);

		$permission = Permission::createFromArray([
			'scope' => 'FooScope',
			'identifier' => 'foo_identifier',
			'access' => PermissionAccess::Granted,
		]);

		$collection = new PermissionCollection($authorizationService);
		$collection->add($permission);

		$this->assertNull($collection->getPermissions('test_scope', 'non_existent_identifier'));
	}


	/**
	 * @return void
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 */
	public function testGetPermissionsReturnsPermissionsForScope(): void {
		$authorizationService = $this->createMock(AuthorizationService::class);

		$permission = Permission::createFromArray([
			'scope' => 'FooScope',
			'identifier' => 'foo_identifier',
			'access' => PermissionAccess::Granted,
		]);

		$collection = new PermissionCollection($authorizationService);
		$collection->add($permission);

		$this->assertSame([
			'fooIdentifier' => [$permission],
		], $collection->getPermissions('FooScope'));
	}


	/**
	 * @return void
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 */
	public function testGetPermissionsReturnsPermissionsForScopeAndIdentifier(): void {
		$authorizationService = $this->createMock(AuthorizationService::class);

		$permission = Permission::createFromArray([
			'scope' => 'FooScope',
			'identifier' => 'foo_identifier',
			'access' => PermissionAccess::Granted,
		]);

		$collection = new PermissionCollection($authorizationService);
		$collection->add($permission);

		$this->assertSame([$permission], $collection->getPermissions('FooScope', 'fooIdentifier'));
	}


	/**
	 * @return void
	 */
	public function testHasPermissionsReturnsFalseWhenScopeNotFound(): void {
		$collection = new PermissionCollection(null);

		$this->assertFalse($collection->hasPermissions('non_existent_scope'));
	}


	/**
	 * @return void
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 */
	public function testHasPermissionsReturnsFalseWhenIdentifierNotFound(): void {
		$authorizationService = $this->createMock(AuthorizationService::class);

		$permission = Permission::createFromArray([
			'scope' => 'test_scope',
			'identifier' => 'foo_identifier',
			'access' => PermissionAccess::Granted,
		]);

		$collection = new PermissionCollection($authorizationService);
		$collection->add($permission);

		$this->assertFalse($collection->hasPermissions('test_scope', 'non_existent_identifier'));
	}


	/**
	 * @return void
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 */
	public function testHasPermissionsReturnsTrueWhenScopeFound(): void {
		$authorizationService = $this->createMock(AuthorizationService::class);

		$permission = Permission::createFromArray([
			'scope' => 'test_scope',
			'identifier' => 'foo_identifier',
			'access' => PermissionAccess::Granted,
		]);

		$collection = new PermissionCollection($authorizationService);
		$collection->add($permission);

		$this->assertTrue($collection->hasPermissions('test_scope'));
	}


	/**
	 * @return void
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 */
	public function testHasPermissionsReturnsTrueWhenScopeAndIdentifierFound(): void {
		$authorizationService = $this->createMock(AuthorizationService::class);

		$permission = Permission::createFromArray([
			'scope' => 'test_scope',
			'identifier' => 'foo_identifier',
			'access' => PermissionAccess::Granted,
		]);

		$collection = new PermissionCollection($authorizationService);
		$collection->add($permission);

		$this->assertTrue($collection->hasPermissions('TestScopes', 'fooIdentifier'));
	}


	/**
	 * @return void
	 */
	public function testScopeIsAccessibleReturnsFalseWhenNoPermissions(): void {
		$collection = new PermissionCollection(null);

		$this->assertFalse($collection->scopeIsAccessible('test_scope', [], 'test_identifier'));
	}


	/**
	 * @return void
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 */
	public function testScopeIsAccessibleReturnsTrueWhenPermissionIsAccessible(): void {
		$authorizationService = $this->createMock(AuthorizationService::class);

		$permission = $this->createMock(Permission::class);
		$permission->method('getScope')->willReturn('test_scope');
		$permission->method('getIdentifier')->willReturn('test_identifier');
		$permission->method('isAccessible')->willReturn(true);

		$collection = new PermissionCollection($authorizationService);
		$collection->add($permission);

		$this->assertTrue($collection->scopeIsAccessible('test_scope', [], 'test_identifier'));
	}


	/**
	 * @return void
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 */
	public function testScopeIsAccessibleReturnsFalseWhenPermissionIsNotAccessible(): void {
		$authorizationService = $this->createMock(AuthorizationService::class);

		$permission = $this->createMock(Permission::class);
		$permission->method('getScope')->willReturn('test_scope');
		$permission->method('getIdentifier')->willReturn('test_identifier');
		$permission->method('isAccessible')->willReturn(false);

		$collection = new PermissionCollection($authorizationService);
		$collection->add($permission);

		$this->assertFalse($collection->scopeIsAccessible('test_scope', [], 'test_identifier'));
	}


	/**
	 * @return void
	 */
	public function testIdentifierIsAccessibleThrowsExceptionForInvalidIdentifier(): void {
		$this->expectException(TypeError::class);

		$collection = new PermissionCollection(null);
		$collection->scopeIsAccessible('test_scope', [], [true]);
	}


	/**
	 * @return void
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 */
	public function testMultipleIdentifiers(): void {
		$authorizationService = $this->createMock(AuthorizationService::class);

		$permission = $this->createMock(Permission::class);
		$permission->method('getScope')->willReturn('test_scope');
		$permission->method('getIdentifier')->willReturn('identifier1');
		$permission->method('isAccessible')->willReturn(true);

		$permission2 = $this->createMock(Permission::class);
		$permission2->method('getScope')->willReturn('test_scope');
		$permission2->method('getIdentifier')->willReturn('identifier2');
		$permission2->method('isAccessible')->willReturn(false);

		$permission3 = $this->createMock(Permission::class);
		$permission3->method('getScope')->willReturn('test_scope');
		$permission3->method('getIdentifier')->willReturn('identifier3');
		$permission3->method('isAccessible')->willReturn(true);

		$collection = new PermissionCollection($authorizationService);
		$collection->add($permission)
		->add($permission2)
		->add($permission3);

		/**
		 * Checking two identifiers in a row
		 * requires both to be accessible
		 */
		$this->assertFalse($collection->scopeIsAccessible('test_scope', [], 'identifier1', 'identifier2'));

		/**
		 * Checking two identifiers in a row
		 * requires both to be accessible
		 */
		$this->assertTrue($collection->scopeIsAccessible('test_scope', [], 'identifier1', 'identifier3'));
	}


	/**
	 * @return void
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 */
	public function testMultipleCombinedIdentifiers(): void {
		$authorizationService = $this->createMock(AuthorizationService::class);

		$permission = $this->createMock(Permission::class);
		$permission->method('getScope')->willReturn('test_scope');
		$permission->method('getIdentifier')->willReturn('identifier1');
		$permission->method('isAccessible')->willReturn(true);

		$permission2 = $this->createMock(Permission::class);
		$permission2->method('getScope')->willReturn('test_scope');
		$permission2->method('getIdentifier')->willReturn('identifier2');
		$permission2->method('isAccessible')->willReturn(false);

		$permission3 = $this->createMock(Permission::class);
		$permission3->method('getScope')->willReturn('test_scope');
		$permission3->method('getIdentifier')->willReturn('identifier3');
		$permission3->method('isAccessible')->willReturn(true);

		$collection = new PermissionCollection($authorizationService);
		$collection->add($permission)
		->add($permission2)
		->add($permission3);

		/**
		 * Checking two identifiers as an array
		 * requires only one to be accessible
		 */
		$this->assertTrue($collection->scopeIsAccessible('test_scope', [], ['identifier1', 'identifier2']));

		/**
		 * Checking two identifiers as an array
		 * requires only one to be accessible.
		 *
		 * When providing multiple identifiers in a row,
		 * each of them still must have at least one accessible permission,
		 * otherwise the whole check will fail.
		 */
		$this->assertTrue($collection->scopeIsAccessible('test_scope', [], ['identifier1', 'identifier2'], ['identifier2', 'identifier3']));

		/**
		 * Checking two identifiers as an array
		 * requires only one to be accessible.
		 *
		 * When providing multiple identifiers in a row,
		 * each of them still must have at least one accessible permission,
		 * otherwise the whole check will fail.
		 */
		$this->assertFalse($collection->scopeIsAccessible('test_scope', [], ['identifier1', 'identifier2'], ['identifier2']));
	}
}
