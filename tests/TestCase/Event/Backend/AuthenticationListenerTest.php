<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Event\Backend;


use Authentication\Authenticator\SessionAuthenticator;
use Authentication\Identifier\IdentifierCollection;
use Awyiss\Authentication\IdentityAwareTrait;
use Awyiss\Event\Backend\AuthenticationListener;
use Awyiss\Event\Backend\MediaFoldersListener;
use Awyiss\Event\EventListenersProvider;
use Awyiss\Event\Global\PagesListener;
use Awyiss\Model\Entity\User;
use Awyiss\Test\TestSuite\TestCase;
use Cake\Datasource\FactoryLocator;
use Cake\Event\Event;


/**
 * AuthenticationListener Test Case
 *
 * @see \Awyiss\Event\Backend\AuthenticationListener
 */
class AuthenticationListenerTest extends TestCase {
	/**
	 * @var \Awyiss\Event\Backend\AuthenticationListener
	 */
	protected AuthenticationListener $listener;


	/**
	 * @inheritDoc
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->listener = new AuthenticationListener();
	}


	/**
	 * @inheritDoc
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	protected function tearDown(): void {
		parent::tearDown();

		EventListenersProvider::reset();

		$tableLocator = FactoryLocator::get('Table');
		$tableLocator->clear();
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\AuthenticationListener::implementedEvents()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testImplementedEvents(): void {
		$result = $this->listener->implementedEvents();

		$this->assertSame([
			'Authentication.afterAuthenticate' => 'authenticationAfterAuthenticate',
			'Authentication.requestIdentity' => 'authenticationRequestIdentity',
			'Authentication.requestLoginUrl' => 'authenticationRequestLoginUrl',
			'Model.initialize' => 'modelInitialize',
		], $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\AuthenticationListener::authenticationAfterAuthenticate()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testAuthenticationAfterAuthenticateSetsIdentityOnRequestingClasses(): void {
		$mediaFoldersListener = new MediaFoldersListener();
		$pagesListener = new PagesListener();

		$requestIdentityEvent = new Event('Authentication.requestIdentity');
		$mediaFoldersListener->getEventManager()->dispatch($requestIdentityEvent);
		$pagesListener->getEventManager()->dispatch($requestIdentityEvent);

		$this->assertEmpty($mediaFoldersListener->getIdentity());
		$this->assertEmpty($pagesListener->getIdentity());

		$user = new User();
		$user->set('id', 123);
		$user->setSource('Users');
		$user->setNew(false);

		$authenticator = new SessionAuthenticator(new IdentifierCollection());

		$event = new Event('Authentication.afterAuthenticate');
		$this->listener->authenticationAfterAuthenticate($event, $authenticator, $user);

		$this->assertSame($user, $mediaFoldersListener->getIdentity());
		$this->assertSame($user, $pagesListener->getIdentity());
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\AuthenticationListener::authenticationAfterAuthenticate()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testAuthenticationAfterAuthenticateSetsIdentityInAuditBehaviorOfRequestingModels(): void {
		$tableLocator = FactoryLocator::get('Table');
		$tableLocator->clear();
		$mediaFoldersTable = $tableLocator->get('MediaFolders');
		$pagesTable = $tableLocator->get('Pages');

		$this->assertEmpty($mediaFoldersTable->behaviors()->get('Audit')->getIdentity());
		$this->assertEmpty($pagesTable->behaviors()->get('Audit')->getIdentity());

		$user = new User();
		$user->set('id', 123);
		$user->setSource('Users');
		$user->setNew(false);

		$authenticator = new SessionAuthenticator(new IdentifierCollection());

		$event = new Event('Authentication.afterAuthenticate');
		$this->listener->authenticationAfterAuthenticate($event, $authenticator, $user);

		$this->assertSame($user, $mediaFoldersTable->behaviors()->get('Audit')->getIdentity());
		$this->assertSame($user, $pagesTable->behaviors()->get('Audit')->getIdentity());
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\AuthenticationListener::authenticationRequestIdentity()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testAuthenticationRequestIdentitySetsIdentityAsResult(): void {
		$user = new User();
		$user->set('id', 123);
		$user->setSource('Users');
		$user->setNew(false);

		$authenticator = new SessionAuthenticator(new IdentifierCollection());

		$event = new Event('Authentication.afterAuthenticate');
		$this->listener->authenticationAfterAuthenticate($event, $authenticator, $user);

		$requestIdentityEvent = new Event('Authentication.requestIdentity');
		$this->listener->authenticationRequestIdentity($requestIdentityEvent);

		$this->assertSame($user, $requestIdentityEvent->getResult());
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\AuthenticationListener::authenticationRequestIdentity()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testAuthenticationRequestIdentitySetsIdentityOnRequestingClassesWhenMethodSetIdentityExists(): void {
		$user = new User();
		$user->set('id', 123);
		$user->setSource('Users');
		$user->setNew(false);

		$authenticator = new SessionAuthenticator(new IdentifierCollection());

		$event = new Event('Authentication.afterAuthenticate');
		$this->listener->authenticationAfterAuthenticate($event, $authenticator, $user);

		$class = new class {
			use IdentityAwareTrait;
		};

		$requestIdentityEvent = new Event('Authentication.requestIdentity', $class);
		$this->listener->authenticationRequestIdentity($requestIdentityEvent);

		$this->assertSame($user, $class->getIdentity());
	}
}
