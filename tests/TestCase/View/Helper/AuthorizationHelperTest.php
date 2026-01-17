<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\View\Helper;


use Awyiss\Authorization\AuthorizationService;
use Awyiss\Authorization\IdentityPermissionsInterface;
use Awyiss\Authorization\PermissionOption\PermissionOptionInterface;
use Awyiss\Authorization\PermissionOption\SimplePermissionOption;
use Awyiss\Awyiss;
use Awyiss\Model\Entity\Media;
use Awyiss\Routing\Router;
use Awyiss\Test\TestSuite\TestCase;
use Awyiss\View\BackendView;
use Awyiss\View\Helper\AuthorizationHelper;
use Cake\Http\ServerRequest;
use Cake\TestSuite\IntegrationTestTrait;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\TestWith;
use RuntimeException;


/**
 * AuthorizationHelperTest class
 */
class AuthorizationHelperTest extends TestCase {
	use IntegrationTestTrait;


	/**
	 * @var \Awyiss\View\BackendView
	 */
	protected BackendView $view;
	/**
	 * @var \Awyiss\View\Helper\AuthorizationHelper
	 */
	protected AuthorizationHelper $helper;


	/**
	 * @inheritDoc
	 */
	public function setUp(): void {
		$this->configApplication(Awyiss::class, []);

		parent::setUp();

		$this->loadRoutes();

		$request = new ServerRequest([
			'url' => '/dummy',
			'params' => [
				'lang' => 'xy',
				'controller' => 'TheController',
				'action' => 'theAction',
				'_name' => 'Backend',
				'prefix' => 'Backend',
				'parts' => [],
				'pass' => [],
				'plugin' => null,
			],
		]);

		Router::setRequest($request);

		$this->login();

		$this->view = new BackendView($request, null, null, [
			'name' => 'TheController',
		]);
		$this->helper = new AuthorizationHelper($this->view);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\AuthorizationHelper::getAdditionalData()
	 */
	public function testGetAdditionalData(): void {
		$this->helper->setConfig('additionalData', ['key' => 'value']);
		$this->assertEquals(['key' => 'value'], $this->helper->getAdditionalData());
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\AuthorizationHelper::setAdditionalData()
	 */
	public function testSetAdditionalData(): void {
		$this->helper->setAdditionalData(['key' => 'value']);
		$this->assertEquals(['key' => 'value'], $this->helper->getConfig('additionalData'));
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\AuthorizationHelper::resetAdditionalData()
	 */
	public function testResetAdditionalData(): void {
		$this->helper->setAdditionalData(['key' => 'value']);
		$this->helper->resetAdditionalData();
		$this->assertEquals([], $this->helper->getConfig('additionalData'));
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\AuthorizationHelper::getIdentity()
	 */
	public function testGetIdentityThrowsExceptionWithoutIdentityInRequest(): void {
		$helper = new AuthorizationHelper($this->view);

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('No identity found in the request.');

		$helper->getIdentity();
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\AuthorizationHelper::getIdentity()
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 */
	public function testGetIdentityReturnsIdentityFromRequestIfNotSet(): void {
		$helper = new AuthorizationHelper($this->view);

		$identityMock = $this->createMock(IdentityPermissionsInterface::class);

		$request = $helper->getView()->getRequest()->withAttribute('BackendIdentity', $identityMock);

		$helper->getView()->setRequest($request);

		$identity = $helper->getIdentity();

		$this->assertSame($identityMock, $identity);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\AuthorizationHelper::getIdentity()
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 */
	public function testGetIdentity(): void {
		$identity = $this->createMock(IdentityPermissionsInterface::class);

		$this->helper->setConfig('identity', $identity);

		$this->assertSame($identity, $this->helper->getIdentity());
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\AuthorizationHelper::setIdentity()
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 */
	public function testSetIdentity(): void {
		$identity = $this->createMock(IdentityPermissionsInterface::class);

		$this->helper->setIdentity($identity);

		$this->assertSame($identity, $this->helper->getConfig('identity'));
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\AuthorizationHelper::resetIdentity()
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 */
	public function testResetIdentity(): void {
		$identity = $this->createMock(IdentityPermissionsInterface::class);

		$this->helper->setIdentity($identity);
		$this->helper->resetIdentity();

		$this->assertNull($this->helper->getConfig('identity'));
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\AuthorizationHelper::getScope()
	 */
	public function testGetScopeReturnsControllerNameIfNotSet(): void {
		$helper = new AuthorizationHelper($this->view);

		$scope = $helper->getScope();

		$this->assertSame('the_controller', $scope);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\AuthorizationHelper::setScope()
	 */
	public function testSetScope(): void {
		$this->helper->setScope('CustomScope');

		$this->assertEquals('custom_scopes', $this->helper->getConfig('scope'));
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\AuthorizationHelper::resetScope()
	 */
	public function testResetScope(): void {
		$this->helper->setScope('CustomScope');
		$this->helper->resetScope();

		$this->assertNull($this->helper->getConfig('scope'));
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\AuthorizationHelper::isAccessible()
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 * @throws \Exception
	 */
	public function testIsAccessible(): void {
		$identity = $this->createMock(IdentityPermissionsInterface::class);
		$identity->method('scopeIsAccessible')->willReturn(true);

		$this->helper->setConfig('identity', $identity);

		$this->assertTrue($this->helper->isAccessible('identifier'));
	}


	/**
	 * Make sure that isAccessible calls scopeIsAccessible with the current scope
	 * and passes the configured additional data to it.
	 *
	 * @return void
	 * @see \Awyiss\View\Helper\AuthorizationHelper::isAccessible()
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 * @throws \Exception
	 */
	public function testIsAccessibleCallsScopeIsAccessibleWithCurrentScope(): void {
		$helper = $this->getMockBuilder(AuthorizationHelper::class)
		->onlyMethods(['scopeIsAccessible'])
		->setConstructorArgs([$this->view])->getMock();

		$helper->expects($this->once())->method('scopeIsAccessible')
		->with('the_controller', ['foo' => 'bar'], 'identifier')
		->willReturn(true);

		$helper->setConfig('scope', 'the_controller');
		$helper->setConfig('additionalData', ['foo' => 'bar']);

		$result = $helper->isAccessible('identifier');

		$this->assertTrue($result);
	}


	/**
	 * Make sure that isAccessible calls scopeIsAccessible with
	 * identifiers spread as arguments.
	 *
	 * @return void
	 * @see \Awyiss\View\Helper\AuthorizationHelper::isAccessible()
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 * @throws \Exception
	 */
	public function testIsAccessibleCallsScopeIsAccessibleWithSpreadIdentifiers(): void {
		$helper = $this->getMockBuilder(AuthorizationHelper::class)
			->onlyMethods(['scopeIsAccessible'])
			->setConstructorArgs([$this->view])
			->getMock();

		$helper->expects($this->once())->method('scopeIsAccessible')
			->with('the_controller', ['foo' => 'bar'], 'identifier1', 'identifier2')
			->willReturn(true);

		$helper->setConfig('scope', 'the_controller');
		$helper->setConfig('additionalData', ['foo' => 'bar']);

		$result = $helper->isAccessible('identifier1', 'identifier2');

		$this->assertTrue($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\AuthorizationHelper::scopeIsAccessible()
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 * @throws \Exception
	 */
	public function testScopeIsAccessible(): void {
		$identity = $this->createMock(IdentityPermissionsInterface::class);
		$this->helper->setConfig('identity', $identity);

		$identity->method('scopeIsAccessible')->willReturnOnConsecutiveCalls(true, false);

		$this->assertTrue($this->helper->scopeIsAccessible('scope', [], 'identifier'));
		$this->assertFalse($this->helper->scopeIsAccessible('scope2', [], 'identifier2'));
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\AuthorizationHelper::scopeIsAccessible()
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 * @throws \Exception
	 */
	public function testScopeIsAccessiblePassesAdditionDataAsIs(): void {
		$identity = $this->createMock(IdentityPermissionsInterface::class);
		$identity->expects($this->once())->method('scopeIsAccessible')
		->with('scope', ['foo2' => 'bar2'], 'identifier')
		->willReturn(true);

		$this->helper->setConfig('identity', $identity);
		$this->helper->setConfig('additionalData', ['foo' => 'bar']);

		$this->assertTrue($this->helper->scopeIsAccessible('scope', ['foo2' => 'bar2'], 'identifier'));

		$identity = $this->createMock(IdentityPermissionsInterface::class);
		$identity->expects($this->once())->method('scopeIsAccessible')
		->with('scope', ['foo' => 'bar'], 'identifier')
		->willReturn(true);

		$this->helper->setConfig('identity', $identity);
		$this->helper->setConfig('additionalData', ['foo' => 'bar']);

		$this->assertTrue($this->helper->scopeIsAccessible('scope', [], 'identifier'));
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\AuthorizationHelper::scopeIsAccessible()
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 * @throws \Exception
	 */
	public function testScopeIsAccessiblePassesSpreadIdentifiers(): void {
		$identity = $this->createMock(IdentityPermissionsInterface::class);
		$identity->expects($this->once())->method('scopeIsAccessible')
		->with('scope', ['foo' => 'bar'], 'identifier1', 'identifier2')
		->willReturn(true);

		$this->helper->setConfig('identity', $identity);

		$this->assertTrue($this->helper->scopeIsAccessible('scope', ['foo' => 'bar'], 'identifier1', 'identifier2'));
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\AuthorizationHelper::anyIsAccessible()
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 * @throws \Exception
	 */
	public function testAnyIsAccessibleReturnsTrueForOneAvailable(): void {
		$identity = $this->createMock(IdentityPermissionsInterface::class);
		$identity->expects($this->once())->method('scopeIsAccessible')->willReturnOnConsecutiveCalls(true, false, false);

		$this->helper->setConfig('identity', $identity);

		$this->assertTrue($this->helper->anyIsAccessible(
			['scope' => 'foo', 'identifier' => 'bar1'],
			['scope' => 'foo2', 'identifier' => 'bar2'],
			['scope' => 'foo3', 'identifier' => 'bar3'],
		));


		$identity = $this->createMock(IdentityPermissionsInterface::class);
		$identity->expects($this->exactly(2))->method('scopeIsAccessible')->willReturnOnConsecutiveCalls(false, true, false);

		$this->helper->setConfig('identity', $identity);

		$this->assertTrue($this->helper->anyIsAccessible(
			['scope' => 'foo', 'identifier' => 'bar1'],
			['scope' => 'foo2', 'identifier' => 'bar2'],
			['scope' => 'foo3', 'identifier' => 'bar3'],
		));

		$identity = $this->createMock(IdentityPermissionsInterface::class);
		$identity->expects($this->exactly(3))->method('scopeIsAccessible')->willReturnOnConsecutiveCalls(false, false, true);

		$this->helper->setConfig('identity', $identity);

		$this->assertTrue($this->helper->anyIsAccessible(
			['scope' => 'foo', 'identifier' => 'bar1'],
			['scope' => 'foo2', 'identifier' => 'bar2'],
			['scope' => 'foo3', 'identifier' => 'bar3'],
		));
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\AuthorizationHelper::anyIsAccessible()
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 * @throws \Exception
	 */
	public function testAnyIsAccessibleReturnsFalseIfNoneAvailable(): void {
		$identity = $this->createMock(IdentityPermissionsInterface::class);
		$identity->expects($this->exactly(3))->method('scopeIsAccessible')->willReturn(false);

		$this->helper->setConfig('identity', $identity);

		$this->assertFalse($this->helper->anyIsAccessible(
			['scope' => 'foo', 'identifier' => 'bar1'],
			['scope' => 'foo2', 'identifier' => 'bar2'],
			['scope' => 'foo3', 'identifier' => 'bar3'],
		));
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\AuthorizationHelper::anyIsAccessible()
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 * @throws \Exception
	 */
	public function testAnyIsAccessiblePassesSpreadIdentifiers(): void {
		$identity = $this->createMock(IdentityPermissionsInterface::class);
		$identity->expects($this->once())->method('scopeIsAccessible')
			->with('scope', [], 'identifier1', 'identifier2')
			->willReturn(true);

		$this->helper->setConfig('identity', $identity);

		$this->assertTrue($this->helper->anyIsAccessible(
			['scope' => 'scope', 'identifier' => ['identifier1', 'identifier2']],
			['scope' => 'scope', 'identifier' => ['identifier1', 'identifier2']],
		));
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\AuthorizationHelper::anyIsAccessible()
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 * @throws \Exception
	 */
	public function testAnyIsAccessiblePassesSpreadIdentifiersWithAdditionalData(): void {
		$identity = $this->createMock(IdentityPermissionsInterface::class);
		$identity->expects($this->once())->method('scopeIsAccessible')
			->with('scope', ['foo' => 'bar'], 'identifier1', 'identifier2')
			->willReturn(true);

		$this->helper->setConfig('identity', $identity);

		$this->assertTrue($this->helper->anyIsAccessible(
			['scope' => 'scope', 'identifier' => ['identifier1', 'identifier2'], 'additionalData' => ['foo' => 'bar']],
			['scope' => 'scope', 'identifier' => ['identifier1', 'identifier2'], 'additionalData' => ['foo' => 'bar']],
		));
	}


	/**
	 * @param array $data
	 * @return void
	 * @see \Awyiss\View\Helper\AuthorizationHelper::anyIsAccessible()
	 * @throws \Exception
	 */
	#[TestWith([['foo']])]
	#[TestWith([['foo' => 'bar']])]
	#[TestWith([['scope' => 'foo']])]
	#[TestWith([['identifier' => 'bar']])]
	public function testAnyIsAccessibleThrowsExceptionIfInvalidArray(array $data): void {
		$this->expectException(InvalidArgumentException::class);

		$this->helper->anyIsAccessible($data);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\AuthorizationHelper::permissionOptions()
	 * @throws \Exception
	 */
	public function testPermissionOptions(): void {
		$service = new AuthorizationService('Backend');
		/** @var class-string<\Customer\Authorization\Policy\Backend\FoobarsPolicy> $policy */
		$policy = $service->getPolicy('foobars');

		$result = $this->helper->permissionOptions($policy::getPermissionOption('read'), null, null, 'Usergroups');

		$this->assertStringContainsString('<input type="radio" name="permissions[foobars][read]" value="1"', $result);
		$this->assertStringContainsString('<input type="radio" name="permissions[foobars][read]" value=""', $result);
		$this->assertStringContainsString('<input type="radio" name="permissions[foobars][read]" value="0"', $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\AuthorizationHelper::permissionOptions()
	 * @throws \Exception
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 */
	public function testPermissionOptionsRendersElement(): void {
		$service = new AuthorizationService('Backend');
		/** @var class-string<\Customer\Authorization\Policy\Backend\FoobarsPolicy> $policy */
		$policy = $service->getPolicy('foobars');

		$fakeEntity = $this->createMock(Media::class);

		$view = $this->getMockBuilder(BackendView::class)
			->disableOriginalConstructor()
			->getMock();

		$helper = new AuthorizationHelper($view);

		$view->expects($this->once())->method('element')
		->with('authorization/permission_option/simple_radio', $this->callback(function ($data) use ($fakeEntity, $helper): bool {
			$this->assertIsArray($data);

			$this->assertInstanceOf(SimplePermissionOption::class, $data['permission']);
			$this->assertSame($fakeEntity, $data['entity']);
			$this->assertSame('foobars', $data['scope']);
			$this->assertSame('read', $data['identifier']);
			$this->assertSame($helper, $data['AuthorizationHelper']);

			return true;
		}));

		$helper->permissionOptions($policy::getPermissionOption('read'), $fakeEntity);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\AuthorizationHelper::permissionOptions()
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 */
	public function testPermissionOptionsThrowsExceptionWithoutIdentifier(): void {
		$this->expectException(RuntimeException::class);

		$permission = $this->createMock(PermissionOptionInterface::class);
		$permission->method('getConfig')->willReturnMap([
			['preferredInput', (object)['value' => 'input']],
			['identifier', null],
		]);

		$this->helper->permissionOptions($permission);
	}
}
