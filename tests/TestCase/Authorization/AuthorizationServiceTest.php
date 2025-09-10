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
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testGetAuthenticationService(): void {
		$service = new AuthorizationService('realm');

		$this->assertNull($service->getAuthenticationService());
	}


	/**
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 */
	public function testSetAuthenticationService(): void {
		$service = new AuthorizationService('realm');

		$service->setAuthenticationService($this->createMock(AuthenticationServiceInterface::class));
		$this->assertInstanceOf(AuthenticationServiceInterface::class, $service->getAuthenticationService());
	}


	/**
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testGetRealm(): void {
		$service = new AuthorizationService('realm');

		$this->assertEquals('realm', $service->getRealm());
	}


	/**
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testGetPolicies(): void {
		$service = new AuthorizationService('Backend');
		$policies = $service->getPolicies();

		$this->assertNotEmpty($policies);

		//Make sure the key `languages` is a string (`\Awyiss\Authorization\Policy\Backend\LanguagesPolicy`)
		$this->assertArrayHasKey('languages', $policies);
		$this->assertEquals('\Awyiss\Authorization\Policy\Backend\LanguagesPolicy', $policies['languages']);

		// Key `news` must be an instance of `\Awyiss\Authorization\Policy\Backend\GenericPagesPolicy`
		$this->assertArrayHasKey('news', $policies);
		$this->assertInstanceOf('\Awyiss\Authorization\Policy\Backend\GenericPagesPolicy', $policies['news']);

		// Key `employees` must be an instance of `\Awyiss\Authorization\Policy\Backend\GenericDatatablesPolicy`
		$this->assertArrayHasKey('employees', $policies);
		$this->assertInstanceOf('\Awyiss\Authorization\Policy\Backend\GenericDatatablesPolicy', $policies['employees']);

		// Scopes `ignored_tests` and `abstract_tests` must not exist
		$this->assertArrayNotHasKey('abstract_tests', $policies);
		$this->assertArrayNotHasKey('ignored_tests', $policies);

		// But `foobars` must exist
		$this->assertArrayHasKey('foobars', $policies);
	}


	/**
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
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
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testSanitizeScope(): void {
		$service = new AuthorizationService('realm');

		$this->assertEquals('foo_bars', $service->sanitizeScope('FooBar'));
		$this->assertEquals('media', $service->sanitizeScope('Media'));
		$this->assertEquals('news', $service->sanitizeScope('new'));
		$this->assertEquals('news', $service->sanitizeScope('News'));
		$this->assertEquals('pages', $service->sanitizeScope('Page'));
		$this->assertEquals('system', $service->sanitizeScope('system'));
	}


	/**
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
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
