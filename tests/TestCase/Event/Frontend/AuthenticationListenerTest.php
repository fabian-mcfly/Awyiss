<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Event\Frontend;


use Authentication\Authenticator\SessionAuthenticator;
use Authentication\IdentityInterface;
use Awyiss\Authentication\IdentityAwareTrait;
use Awyiss\Awyiss;
use Awyiss\Event\EventListenersProvider;
use Awyiss\Event\Frontend\AuthenticationListener;
use Awyiss\Middleware\LocaleMiddleware;
use Awyiss\Model\Table;
use Awyiss\Routing\Router;
use Awyiss\Test\TestSuite\TestCase;
use Cake\Datasource\FactoryLocator;
use Cake\Event\Event;
use Cake\Http\ServerRequest;
use Cake\TestSuite\IntegrationTestTrait;


/**
 * AuthenticationListener Test Case
 *
 * @see \Awyiss\Event\Frontend\AuthenticationListener
 */
class AuthenticationListenerTest extends TestCase {
	use IntegrationTestTrait;


	/**
	 * @var \Awyiss\Event\Frontend\AuthenticationListener
	 */
	protected AuthenticationListener $listener;


	/**
	 * @inheritDoc
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->configApplication(Awyiss::class, []);

		Awyiss::setRealm('Frontend');
		LocaleMiddleware::setRealm(Awyiss::REALM_FRONTEND);
		Awyiss::loadConfiguration('xy', 'yx');

		$request = new ServerRequest([
			'url' => '/',
			'params' => [
				'lang' => 'de',
				'slug' => 'dummy',
				'_name' => Awyiss::REALM_FRONTEND,
				'prefix' => Awyiss::REALM_FRONTEND,
				'parts' => [],
				'pass' => [],
				'plugin' => null,
			],
		]);
		Router::setRequest($request);

		$this->loadRoutes();

		$this->listener = new AuthenticationListener();
	}


	/**
	 * @inheritDoc
	 */
	protected function tearDown(): void {
		parent::tearDown();

		EventListenersProvider::reset();

		$tableLocator = FactoryLocator::get('Table');
		$tableLocator->clear();
	}


	/**
	 * Create a mock identity for testing
	 *
	 * @param int $id
	 * @return \Authentication\IdentityInterface
	 */
	protected function createMockIdentity(int $id = 456): IdentityInterface {
		$identity = $this->createMock(IdentityInterface::class);
		$identity->method('getIdentifier')->willReturn($id);

		return $identity;
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Frontend\AuthenticationListener::implementedEvents()
	 */
	public function testImplementedEvents(): void {
		$result = $this->listener->implementedEvents();

		$this->assertSame([
			'Authentication.afterAuthenticate' => 'authenticationAfterAuthenticate',
			'Authentication.requestIdentity' => 'authenticationRequestIdentity',
			'Authentication.requestLoginUrl' => 'authenticationRequestLoginUrl',
		], $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Frontend\AuthenticationListener::authenticationAfterAuthenticate()
	 */
	public function testAuthenticationAfterAuthenticateSetsIdentity(): void {
		$identity = $this->createMockIdentity(456);

		$authenticator = new SessionAuthenticator(null);

		$event = new Event('Authentication.afterAuthenticate');
		$this->listener->authenticationAfterAuthenticate($event, $authenticator, $identity);

		// Verify identity is set by requesting it
		$requestIdentityEvent = new Event('Authentication.requestIdentity');
		$this->listener->authenticationRequestIdentity($requestIdentityEvent);

		$this->assertSame($identity, $requestIdentityEvent->getResult());
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Frontend\AuthenticationListener::authenticationRequestIdentity()
	 */
	public function testAuthenticationRequestIdentitySetsIdentityAsResult(): void {
		$identity = $this->createMockIdentity(456);

		$authenticator = new SessionAuthenticator(null);

		$event = new Event('Authentication.afterAuthenticate');
		$this->listener->authenticationAfterAuthenticate($event, $authenticator, $identity);

		$requestIdentityEvent = new Event('Authentication.requestIdentity');
		$this->listener->authenticationRequestIdentity($requestIdentityEvent);

		$this->assertSame($identity, $requestIdentityEvent->getResult());
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Frontend\AuthenticationListener::authenticationRequestIdentity()
	 */
	public function testAuthenticationRequestIdentitySetsIdentityOnRequestingClassesWhenMethodSetIdentityExists(): void {
		$identity = $this->createMockIdentity(456);

		$authenticator = new SessionAuthenticator(null);

		$event = new Event('Authentication.afterAuthenticate');
		$this->listener->authenticationAfterAuthenticate($event, $authenticator, $identity);

		$class = new class {
			use IdentityAwareTrait;
		};

		$requestIdentityEvent = new Event('Authentication.requestIdentity', $class);
		$this->listener->authenticationRequestIdentity($requestIdentityEvent);

		$this->assertSame($identity, $class->getIdentity());
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Frontend\AuthenticationListener::authenticationRequestIdentity()
	 */
	public function testAuthenticationRequestIdentityDoesNotFailWithoutIdentity(): void {
		$requestIdentityEvent = new Event('Authentication.requestIdentity');
		$this->listener->authenticationRequestIdentity($requestIdentityEvent);

		$this->assertNull($requestIdentityEvent->getResult());
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Frontend\AuthenticationListener::authenticationRequestIdentity()
	 */
	public function testAuthenticationRequestIdentityDoesNotFailWhenSubjectHasNoSetIdentityMethod(): void {
		$identity = $this->createMockIdentity(456);

		$authenticator = new SessionAuthenticator(null);

		$event = new Event('Authentication.afterAuthenticate');
		$this->listener->authenticationAfterAuthenticate($event, $authenticator, $identity);

		$class = new class {
			// No setIdentity method
		};

		$requestIdentityEvent = new Event('Authentication.requestIdentity', $class);
		$this->listener->authenticationRequestIdentity($requestIdentityEvent);

		// Should still return the identity
		$this->assertSame($identity, $requestIdentityEvent->getResult());
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Frontend\AuthenticationListener::authenticationRequestLoginUrl()
	 * @throws \Exception
	 */
	public function testAuthenticationRequestLoginUrlReturnsCorrectUrl(): void {
		$event = new Event('Authentication.requestLoginUrl');
		$this->listener->authenticationRequestLoginUrl($event);

		$result = $event->getResult();

		$this->assertIsString($result);
		$this->assertStringContainsString('/de/konto/anmelden/', $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Frontend\AuthenticationListener::authenticationRequestIdentity()
	 */
	public function testAuthenticationRequestIdentitySetsIdentityOnTable(): void {
		$tableLocator = FactoryLocator::get('Table');
		$tableLocator->clear();

		$identity = $this->createMockIdentity(456);

		$authenticator = new SessionAuthenticator(null);

		$event = new Event('Authentication.afterAuthenticate');
		$this->listener->authenticationAfterAuthenticate($event, $authenticator, $identity);

		// Get a table with setIdentity method
		$table = new class extends Table {
			use IdentityAwareTrait;


			/**
			 * @var string
			 */
			public const string TABLE = 'customers';


			/**
			 * @return string
			 */
			public static function defaultConnectionName(): string {
				return 'test';
			}
		};

		$requestIdentityEvent = new Event('Authentication.requestIdentity', $table);
		$this->listener->authenticationRequestIdentity($requestIdentityEvent);

		$this->assertSame($identity, $table->getIdentity());
		$this->assertSame($identity, $requestIdentityEvent->getResult());
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Frontend\AuthenticationListener::authenticationRequestIdentity()
	 */
	public function testAuthenticationRequestIdentityHandlesExceptionGracefully(): void {
		$identity = $this->createMockIdentity(456);

		$authenticator = new SessionAuthenticator(null);

		$event = new Event('Authentication.afterAuthenticate');
		$this->listener->authenticationAfterAuthenticate($event, $authenticator, $identity);

		// Create an event with a subject that might throw an exception
		$requestIdentityEvent = new Event('Authentication.requestIdentity', null);
		$this->listener->authenticationRequestIdentity($requestIdentityEvent);

		// Should still return the identity
		$this->assertSame($identity, $requestIdentityEvent->getResult());
	}
}
