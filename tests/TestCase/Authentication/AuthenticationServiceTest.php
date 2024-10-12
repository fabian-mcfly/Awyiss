<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Authentication;


use Authentication\Authenticator\FormAuthenticator;
use Authentication\Authenticator\Result;
use Authentication\Identifier\AbstractIdentifier;
use Authentication\Identifier\PasswordIdentifier;
use Authentication\Identifier\Resolver\OrmResolver;
use Awyiss\Authentication\AuthenticationService;
use Awyiss\Authentication\Authenticator\SessionAuthenticator;
use Awyiss\Authentication\Identifier\IdentifierCollection;
use Awyiss\Awyiss;
use Awyiss\Model\Entity\User;
use Awyiss\Routing\Router;
use Awyiss\Test\TestSuite\TestCase;
use Cake\Http\ServerRequestFactory;
use Cake\Http\Session;
use Cake\TestSuite\IntegrationTestTrait;


/**
 * AuthenticationServiceTest class
 */
class AuthenticationServiceTest extends TestCase {
	use IntegrationTestTrait;


	/**
	 * @inheritDoc
	 */
	public function setUp(): void {
		$this->configApplication(Awyiss::class, []);

		parent::setUp();

		$this->loadRoutes();
	}


	/**
	 * Test that the authenticators method returns an instance of AuthenticatorCollection
	 *
	 * @return void
	 * @noinspection PhpMethodNamingConventionInspection
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testIdentifiersReturnsIdentifierCollection(): void {
		$service = new AuthenticationService();
		$result = $service->identifiers();
		$this->assertInstanceOf(IdentifierCollection::class, $result);
	}


	/**
	 * Tests that the `unauthenticatedRedirect` config value is set correctly
	 *
	 * @return void
	 * @noinspection PhpMethodNamingConventionInspection
	 * @noinspection PhpVariableNamingConventionInspection
	 * @throws \Exception
	 */
	public function testUnauthenticatdRedirectUrlReturnsCorrectUrl(): void {
		$service = new AuthenticationService();

		$service->setConfig([
			'unauthenticatedRedirect' => Router::url([
				'_name' => Awyiss::REALM_BACKEND,
				'action' => 'login',
				'controller' => 'Users',
				'lang' => 'xy',
				'prefix' => false,
				'plugin' => null,
			]),
			'queryParam' => null,
		]);

		$this->assertEquals('/backend/xy/users/login/', $service->getConfig('unauthenticatedRedirect'));
	}


	/**
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testLoadAuthenticators(): void {
		$service = new AuthenticationService();

		$service->loadAuthenticator(SessionAuthenticator::class);

		$this->assertInstanceOf(SessionAuthenticator::class, $service->authenticators()->get('Awyiss\Authentication\Authenticator\SessionAuthenticator'));
	}


	/**
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testLoadIdentifier(): void {
		$service = new AuthenticationService();

		$service->loadIdentifier(PasswordIdentifier::class, [
			'resolver' => [
				'className' => OrmResolver::class,
				'finder' => 'active',
			],
		]);

		$this->assertInstanceOf(PasswordIdentifier::class, $service->identifiers()->get('Authentication\Identifier\PasswordIdentifier'));
	}


	/**
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testLogin(): void {
		$service = new AuthenticationService();

		$service->loadIdentifier(PasswordIdentifier::class, [
			'resolver' => [
				'className' => OrmResolver::class,
				'finder' => 'active',
			],
		]);

		$service->loadAuthenticator(FormAuthenticator::class, [
			'fields' => [
				AbstractIdentifier::CREDENTIAL_USERNAME => 'username',
				AbstractIdentifier::CREDENTIAL_PASSWORD => 'password',
			],
			'loginUrl' => $this->dispatchEvent('Authentication.requestLoginUrl', [], $this)->getResult(),
		]);


		$request = ServerRequestFactory::fromGlobals(
			['REQUEST_URI' => '/backend/de/users/login/'],
			[],
			['username' => 'mariano', 'password' => 'password'],
		);

		$result = $service->authenticate($request);

		$this->assertInstanceOf(Result::class, $result);
		$this->assertFalse($result->isValid());
		$this->assertEquals('FAILURE_IDENTITY_NOT_FOUND', $result->getStatus());

		$request = ServerRequestFactory::fromGlobals(
			['REQUEST_URI' => '/backend/de/users/login/'],
			[],
			['username' => 'awyiss', 'password' => 'awyiss'],
		);

		$result = $service->authenticate($request);
		$this->assertTrue($result->isValid());
		$this->assertInstanceOf(User::class, $result->getData());
	}


	/**
	 * @return void
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testGetLoginRedirect(): void {
		$service = new AuthenticationService();

		$service->setConfig([
			'unauthenticatedRedirect' => Router::url([
				'_name' => Awyiss::REALM_BACKEND,
				'action' => 'login',
				'controller' => 'Users',
				'lang' => 'xy',
				'prefix' => false,
				'plugin' => null,
			]),
			'queryParam' => null,
		]);

		$request = ServerRequestFactory::fromGlobals(
			['REQUEST_URI' => '/dummy/'],
			[],
			[],
		);

		$sessionMock = $this->getMockBuilder(Session::class)->disableOriginalConstructor()->onlyMethods(['read', 'write', 'delete', 'renew', 'check'])->getMock();
		$sessionMock->expects($this->once())->method('read')->with('unauthenticatedRedirectUrl')->willReturn('/home/');

		$request = $request->withAttribute('session', $sessionMock);

		$this->assertEquals('/home/', $service->getLoginRedirect($request));
	}
}
