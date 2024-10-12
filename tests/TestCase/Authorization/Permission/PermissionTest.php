<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Authorization\Permission;


use Awyiss\Authorization\AuthorizationService;
use Awyiss\Authorization\Permission\Permission;
use Awyiss\Authorization\Permission\PermissionAccess;
use Awyiss\Authorization\Permission\PermissionCollection;
use Awyiss\Authorization\Permission\PermissionInterface;
use Awyiss\Authorization\PermissionOption\PermissionOptionInterface;
use Awyiss\Authorization\Policy\AbstractGenericPolicy;
use Awyiss\Test\TestSuite\TestCase;
use RuntimeException;
use stdClass;


/**
 * PermissionTest class
 */
class PermissionTest extends TestCase {
	/**
	 * @return void
	 * @noinspection PhpMethodNamingConventionInspection
	 */
	public function testPermissionThrowsExceptionWhenScopeIsEmpty(): void {
		$this->expectException(RuntimeException::class);
		new Permission('', 'identifier');
	}


	/**
	 * @return void
	 * @noinspection PhpMethodNamingConventionInspection
	 */
	public function testPermissionThrowsExceptionWhenIdentifierIsEmpty(): void {
		$this->expectException(RuntimeException::class);
		new Permission('scope', '');
	}


	/**
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testGetAccessReturnsCorrectValue(): void {
		$permission = new Permission('scope', 'identifier', PermissionAccess::Granted);
		$this->assertSame(PermissionAccess::Granted, $permission->getAccess());
	}


	/**
	 * @return void
	 * @noinspection PhpMethodNamingConventionInspection
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testGetIdentifierReturnsSanitizedIdentifier(): void {
		$permission = new Permission('scope', 'Test Identifier!');
		$this->assertSame('testIdentifier', $permission->getIdentifier());
	}


	/**
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testGetScopeReturnsSanitizedScope(): void {
		$permission = new Permission('Test Scope!', 'identifier');
		$this->assertSame('test_scopes', $permission->getScope());
	}


	/**
	 * @return void
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 * @throws \ReflectionException
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testGetPolicyClassReturnsNullWhenNotSet(): void {
		$authorizationService = $this->createMock(AuthorizationService::class);
		$authorizationService->method('getPolicy')->willReturn(null);

		$permission = new Permission('scope', 'identifier');
		$permission->setAuthorizationService($authorizationService);

		$this->assertNull($permission->getPolicyClass());
	}


	/**
	 * @return void
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 * @throws \ReflectionException
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testGetPolicyClassReturnsPolicyWhenSet(): void {
		$policy = $this->createMock(AbstractGenericPolicy::class);

		$authorizationService = $this->createMock(AuthorizationService::class);
		$authorizationService->method('getPolicy')->willReturn($policy);

		$permission = new Permission('scope', 'identifier');
		$permission->setAuthorizationService($authorizationService);

		$this->assertSame($policy, $permission->getPolicyClass());
	}


	/**
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
	 * @noinspection PhpMethodNamingConventionInspection
	 * @throws \ReflectionException
	 */
	public function testSetPolicyClassThrowsExceptionForInvalidPolicy(): void {
		$this->expectException(RuntimeException::class);
		$permission = new Permission('scope', 'identifier');
		$permission->setPolicyClass(stdClass::class);
	}


	/**
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
	 * @noinspection PhpMethodNamingConventionInspection
	 * @throws \ReflectionException
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 */
	public function testIsAccessibleReturnsDefaultPermissionWhenNoPolicy(): void {
		$authorizationService = $this->createMock(AuthorizationService::class);
		$authorizationService->method('getPolicy')->willReturn(null);

		$permission = new Permission('scope', 'identifier');
		$permission->setAuthorizationService($authorizationService);

		$this->assertFalse($permission->isAccessible([], $this->createMock(PermissionCollection::class)));
	}


	/**
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
	 * @noinspection PhpMethodNamingConventionInspection
	 * @throws \ReflectionException
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 */
	public function testIsAccessibleReturnsDefaultPermissionWhenGetPermissionOptionReturnsNull(): void {
		$policy = $this->createMock(AbstractGenericPolicy::class);
		$policy->method('getPermissionOption')->willReturn(null);

		$authorizationService = $this->createMock(AuthorizationService::class);
		$authorizationService->method('getPolicy')->willReturn($policy);

		$permission = new Permission('scope', 'identifier');
		$permission->setAuthorizationService($authorizationService);

		$this->assertFalse($permission->isAccessible([], $this->createMock(PermissionCollection::class)));
	}


	/**
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
	 * @noinspection PhpMethodNamingConventionInspection
	 * @throws \ReflectionException
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 */
	public function testIsAccessibleReturnsCorrectAccesssible(): void {
		$permissionOptionDenied = $this->createMock(PermissionOptionInterface::class);
		$permissionOptionDenied->method('isAccessible')->willReturn(false);

		$permissionOptionAllowed = $this->createMock(PermissionOptionInterface::class);
		$permissionOptionAllowed->method('isAccessible')->willReturn(true);

		$permissionOptionUndecided = $this->createMock(PermissionOptionInterface::class);
		$permissionOptionUndecided->method('isAccessible')->willReturn(null);

		$policy = $this->createMock(AbstractGenericPolicy::class);
		$policy->method('getPermissionOption')->willReturnOnConsecutiveCalls($permissionOptionDenied, $permissionOptionAllowed, $permissionOptionUndecided, null);

		$permission = new Permission('scope', 'identifier');
		$permission->setAuthorizationService($this->createMock(AuthorizationService::class));
		$permission->setPolicyClass($policy);

		$permissionCollection = $this->createMock(PermissionCollection::class);

		$this->assertFalse($permission->isAccessible([], $permissionCollection));
		$this->assertTrue($permission->isAccessible([], $permissionCollection));
		$this->assertNull($permission->isAccessible([], $permissionCollection));
		$this->assertFalse($permission->isAccessible([], $permissionCollection));
	}


	/**
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
	 * @noinspection PhpMethodNamingConventionInspection
	 */
	public function testCreateFromArrayCreatesPermissionCorrectly(): void {
		$data = ['scope' => 'FoobarScope', 'identifier' => 'unknown identifier', 'access' => 'access', 'settings' => ['setting1']];
		$permission = Permission::createFromArray($data);

		$this->assertSame('foobar_scopes', $permission->getScope());
		$this->assertSame('unknownIdentifier', $permission->getIdentifier());
		$this->assertSame('access', $permission->getAccess());
		$this->assertSame(['setting1'], $permission->getSettings());
	}


	/**
	 * @return void
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 * @noinspection PhpMethodNamingConventionInspection
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testCreateFromObjectCreatesPermissionCorrectly(): void {
		$permissionInterface = $this->createMock(PermissionInterface::class);
		$permissionInterface->method('getScope')->willReturn('FoobarScope');
		$permissionInterface->method('getIdentifier')->willReturn('unknown identifier');
		$permissionInterface->method('getAccess')->willReturn('access');
		$permissionInterface->method('getSettings')->willReturn(['setting1']);

		$permission = Permission::createFromObject($permissionInterface);

		$this->assertSame('foobar_scopes', $permission->getScope());
		$this->assertSame('unknownIdentifier', $permission->getIdentifier());
		$this->assertSame('access', $permission->getAccess());
		$this->assertSame(['setting1'], $permission->getSettings());
	}
}
