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
	 */
	public function testPermissionThrowsExceptionWhenScopeIsEmpty(): void {
		$this->expectException(RuntimeException::class);
		new Permission('', 'identifier');
	}


	/**
	 * @return void
	 */
	public function testPermissionThrowsExceptionWhenIdentifierIsEmpty(): void {
		$this->expectException(RuntimeException::class);
		new Permission('scope', '');
	}


	/**
	 * @return void
	 */
	public function testGetAccessReturnsCorrectValue(): void {
		$permission = new Permission('scope', 'identifier', PermissionAccess::Granted);
		$this->assertSame(PermissionAccess::Granted, $permission->getAccess());
	}


	/**
	 * @return void
	 */
	public function testGetIdentifierReturnsSanitizedIdentifier(): void {
		$permission = new Permission('scope', 'Test Identifier!');
		$this->assertSame('testIdentifier', $permission->getIdentifier());
	}


	/**
	 * @return void
	 */
	public function testGetScopeReturnsSanitizedScope(): void {
		$permission = new Permission('Test Scope!', 'identifier');
		$this->assertSame('TestScopes', $permission->getScope());
	}


	/**
	 * @return void
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 */
	public function testGetPolicyClassReturnsNullWhenNotSet(): void {
		$authorizationService = $this->createStub(AuthorizationService::class);
		$authorizationService->method('getPolicy')->willReturn(null);

		$permission = new Permission('scope', 'identifier');
		$permission->setAuthorizationService($authorizationService);

		$this->assertNull($permission->getPolicyClass());
	}


	/**
	 * @return void
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 */
	public function testGetPolicyClassReturnsPolicyWhenSet(): void {
		$policy = $this->createStub(AbstractGenericPolicy::class);

		$authorizationService = $this->createStub(AuthorizationService::class);
		$authorizationService->method('getPolicy')->willReturn($policy);

		$permission = new Permission('scope', 'identifier');
		$permission->setAuthorizationService($authorizationService);

		$this->assertSame($policy, $permission->getPolicyClass());
	}


	/**
	 * @return void
	 */
	public function testSetPolicyClassThrowsExceptionForInvalidPolicy(): void {
		$this->expectException(RuntimeException::class);
		$permission = new Permission('scope', 'identifier');
		$permission->setPolicyClass(stdClass::class);
	}


	/**
	 * @return void
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 * @throws \Exception
	 */
	public function testIsAccessibleReturnsDefaultPermissionWhenNoPolicy(): void {
		$authorizationService = $this->createStub(AuthorizationService::class);
		$authorizationService->method('getPolicy')->willReturn(null);

		$permission = new Permission('scope', 'identifier');
		$permission->setAuthorizationService($authorizationService);

		$this->assertFalse($permission->isAccessible([], $this->createStub(PermissionCollection::class)));
	}


	/**
	 * @return void
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 * @throws \Exception
	 */
	public function testIsAccessibleReturnsDefaultPermissionWhenGetPermissionOptionReturnsNull(): void {
		$policy = $this->createStub(AbstractGenericPolicy::class);
		$policy->method('getPermissionOption')->willReturn(null);

		$authorizationService = $this->createStub(AuthorizationService::class);
		$authorizationService->method('getPolicy')->willReturn($policy);

		$permission = new Permission('scope', 'identifier');
		$permission->setAuthorizationService($authorizationService);

		$this->assertFalse($permission->isAccessible([], $this->createStub(PermissionCollection::class)));
	}


	/**
	 * @return void
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 * @throws \Exception
	 */
	public function testIsAccessibleReturnsCorrectAccesssible(): void {
		$permissionOptionDenied = $this->createStub(PermissionOptionInterface::class);
		$permissionOptionDenied->method('isAccessible')->willReturn(false);

		$permissionOptionAllowed = $this->createStub(PermissionOptionInterface::class);
		$permissionOptionAllowed->method('isAccessible')->willReturn(true);

		$permissionOptionUndecided = $this->createStub(PermissionOptionInterface::class);
		$permissionOptionUndecided->method('isAccessible')->willReturn(null);

		$policy = $this->createStub(AbstractGenericPolicy::class);
		$policy->method('getPermissionOption')->willReturnOnConsecutiveCalls($permissionOptionDenied, $permissionOptionAllowed, $permissionOptionUndecided, null);

		$permission = new Permission('scope', 'identifier');
		$permission->setAuthorizationService($this->createStub(AuthorizationService::class));
		$permission->setPolicyClass($policy);

		$permissionCollection = $this->createStub(PermissionCollection::class);

		$this->assertFalse($permission->isAccessible([], $permissionCollection));
		$this->assertTrue($permission->isAccessible([], $permissionCollection));
		$this->assertNull($permission->isAccessible([], $permissionCollection));
		$this->assertFalse($permission->isAccessible([], $permissionCollection));
	}


	/**
	 * @return void
	 */
	public function testCreateFromArrayCreatesPermissionCorrectly(): void {
		$data = ['scope' => 'FoobarScope', 'identifier' => 'unknown identifier', 'access' => 'access', 'settings' => ['setting1']];
		$permission = Permission::createFromArray($data);

		$this->assertSame('FoobarScopes', $permission->getScope());
		$this->assertSame('unknownIdentifier', $permission->getIdentifier());
		$this->assertSame('access', $permission->getAccess());
		$this->assertSame(['setting1'], $permission->getSettings());
	}


	/**
	 * @return void
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 */
	public function testCreateFromObjectCreatesPermissionCorrectly(): void {
		$permissionInterface = $this->createStub(PermissionInterface::class);
		$permissionInterface->method('getScope')->willReturn('FoobarScope');
		$permissionInterface->method('getIdentifier')->willReturn('unknown identifier');
		$permissionInterface->method('getAccess')->willReturn('access');
		$permissionInterface->method('getSettings')->willReturn(['setting1']);

		$permission = Permission::createFromObject($permissionInterface);

		$this->assertSame('FoobarScopes', $permission->getScope());
		$this->assertSame('unknownIdentifier', $permission->getIdentifier());
		$this->assertSame('access', $permission->getAccess());
		$this->assertSame(['setting1'], $permission->getSettings());
	}
}
