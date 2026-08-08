<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Authorization;


use Authentication\AuthenticationServiceInterface;
use Awyiss\Authorization\AuthorizationService;
use Awyiss\Authorization\Policy\Backend\GenericDatatablesPolicy;
use Awyiss\Authorization\Policy\Backend\GenericPagesPolicy;
use Awyiss\Test\TestSuite\TestCase;


/**
 * AuthorizationServiceTest class
 */
class AuthorizationServiceTest extends TestCase {
	/**
	 * @return void
	 */
	public function testGetAuthenticationService(): void {
		$service = new AuthorizationService('realm');

		$this->assertNull($service->getAuthenticationService());
	}


	/**
	 * @return void
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 */
	public function testSetAuthenticationService(): void {
		$service = new AuthorizationService('realm');

		$service->setAuthenticationService($this->createStub(AuthenticationServiceInterface::class));
		$this->assertInstanceOf(AuthenticationServiceInterface::class, $service->getAuthenticationService());
	}


	/**
	 * @return void
	 */
	public function testGetRealm(): void {
		$service = new AuthorizationService('realm');

		$this->assertEquals('realm', $service->getRealm());
	}


	/**
	 * @return void
	 */
	public function testGetPolicies(): void {
		$service = new AuthorizationService('Backend');
		$policies = $service->getPolicies();

		$this->assertNotEmpty($policies);

		//Make sure the key `Languages` is a string (`\Awyiss\Authorization\Policy\Backend\LanguagesPolicy`)
		$this->assertArrayHasKey('Languages', $policies);
		$this->assertEquals('\Awyiss\Authorization\Policy\Backend\LanguagesPolicy', $policies['Languages']);

		// Key `News` must be an instance of `\Awyiss\Authorization\Policy\Backend\GenericPagesPolicy`
		$this->assertArrayHasKey('News', $policies);
		$this->assertInstanceOf('\Awyiss\Authorization\Policy\Backend\GenericPagesPolicy', $policies['News']);

		// Key `Employees` must be an instance of `\Awyiss\Authorization\Policy\Backend\GenericDatatablesPolicy`
		$this->assertArrayHasKey('Employees', $policies);
		$this->assertInstanceOf('\Awyiss\Authorization\Policy\Backend\GenericDatatablesPolicy', $policies['Employees']);

		// Scopes `IgnoredTests` and `abstract_tests` must not exist
		$this->assertArrayNotHasKey('AbstractTests', $policies);
		$this->assertArrayNotHasKey('IgnoredTests', $policies);

		// But `Foobars` must exist
		$this->assertArrayHasKey('Foobars', $policies);
	}


	/**
	 * @return void
	 */
	public function testGetPolicy(): void {
		$service = new AuthorizationService('frontend');

		$policy = $service->getPolicy('foobar', 'Backend');
		$this->assertEquals('\Customer\Authorization\Policy\Backend\FoobarsPolicy', $policy);

		$policy = $service->getPolicy('languages');
		$this->assertNull($policy);

		$policy = $service->getPolicy('languages', 'backend');
		$this->assertNull($policy);

		$policy = $service->getPolicy('languages', 'Backend');
		$this->assertEquals('\Awyiss\Authorization\Policy\Backend\LanguagesPolicy', $policy);

		$policy = $service->getPolicy('new', 'Backend');
		$this->assertInstanceOf(GenericPagesPolicy::class, $policy);

		$policy = $service->getPolicy('news', 'Backend');
		$this->assertInstanceOf(GenericPagesPolicy::class, $policy);

		$policy = $service->getPolicy('Employee', 'Backend');
		$this->assertInstanceOf(GenericDatatablesPolicy::class, $policy);

		$policy = $service->getPolicy('employees', 'Backend');
		$this->assertInstanceOf(GenericDatatablesPolicy::class, $policy);

		$policy = $service->getPolicy('abstract_tests', 'Backend');
		$this->assertNull($policy);

		$policy = $service->getPolicy('ignored_tests', 'Backend');
		$this->assertNull($policy);
	}


	/**
	 * @return void
	 */
	public function testSanitizeScope(): void {
		$service = new AuthorizationService('realm');

		$this->assertEquals('FooBars', $service->sanitizeScope('FooBar'));
		$this->assertEquals('Media', $service->sanitizeScope('Media'));
		$this->assertEquals('News', $service->sanitizeScope('new'));
		$this->assertEquals('News', $service->sanitizeScope('News'));
		$this->assertEquals('Pages', $service->sanitizeScope('Page'));
		$this->assertEquals('System', $service->sanitizeScope('system'));
	}


	/**
	 * @return void
	 */
	public function testSanitizeIdentifier(): void {
		$service = new AuthorizationService('realm');

		$this->assertEquals('fooBar', $service->sanitizeIdentifier('FooBar'));
		$this->assertEquals('mediaFolder', $service->sanitizeIdentifier('Media_folder'));
		$this->assertEquals('new', $service->sanitizeIdentifier('new'));
		$this->assertEquals('news', $service->sanitizeIdentifier('News'));
		$this->assertEquals('page', $service->sanitizeIdentifier('Page'));
	}
}
