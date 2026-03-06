<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Event\Backend;


use Awyiss\Authentication\Authenticator\SessionAuthenticator;
use Awyiss\Authentication\Identifier\IdentifierCollection;
use Awyiss\Authorization\AuthorizationService;
use Awyiss\Event\Backend\UsergroupsListener;
use Awyiss\Event\EventListenersProvider;
use Awyiss\Model\Entity\User;
use Awyiss\Routing\Router;
use Awyiss\Test\TestSuite\TestCase;
use Cake\Datasource\FactoryLocator;
use Cake\Event\Event;
use Cake\I18n\DateTime;


/**
 * UsergroupsListener Test Case
 *
 * @see \Awyiss\Event\Backend\UsergroupsListener
 */
class UsergroupsListenerTest extends TestCase {
	/**
	 * @var \Awyiss\Event\Backend\UsergroupsListener
	 */
	protected UsergroupsListener $listener;


	/**
	 * @inheritDoc
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->listener = new UsergroupsListener();
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
	 * @return void
	 * @see \Awyiss\Event\Backend\UsergroupsListener::implementedEvents()
	 */
	public function testImplementedEvents(): void {
		$result = $this->listener->implementedEvents();

		$this->assertSame([
			'Model.Usergroups.afterSave' => 'afterSave',
		], $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\UsergroupsListener::afterSave()
	 * @throws \Exception
	 */
	public function testAfterSaveNotUnsetsUsergroupsOnCurrentUserWhenNotInGroupAndGroupNotEmpty(): void {
		$request = Router::getRequest();

		$request = $request->withAttribute('authorization', new AuthorizationService('Backend'));

		/** @var \Awyiss\Model\Entity\User $user */
		$user = $this->getMockBuilder(User::class)->onlyMethods(['unsetPermissionCollection'])->getMock();
		$user->expects($this->never())->method('unsetPermissionCollection');
		$user->set('id', 1);
		$user->setSource('Users');
		$user->setNew(false);

		$this->dispatchEvent('Authentication.afterAuthenticate', [
			'authenticator' => new SessionAuthenticator(new IdentifierCollection()),
			'identity' => $user,
		], $this);

		$request = $request->withAttribute('BackendIdentity', $user);

		Router::setRequest($request);

		$entity = $this->fetchTable('Usergroups')->newDefaultEntity();
		$entity->id = 3;

		$user->getUsergroups();

		$this->assertNotEmpty($user->usergroups);
		$this->assertNotEmpty($user->getPermissionCollection());

		$event = new Event('Model.Usergroups.afterSave', $this->fetchTable('Usergroups'));

		$this->listener->afterSave($event, $entity);

		$this->assertNotEmpty($user->usergroups);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\UsergroupsListener::afterSave()
	 * @throws \Exception
	 */
	public function testAfterSaveNotUnsetsUsergroupsOnCurrentUserWhenNotInGroupAndGroupEmpty(): void {
		$request = Router::getRequest();

		$request = $request->withAttribute('authorization', new AuthorizationService('Backend'));

		/** @var \Awyiss\Model\Entity\User $user */
		$user = $this->getMockBuilder(User::class)->onlyMethods(['unsetPermissionCollection'])->getMock();
		$user->expects($this->once())->method('unsetPermissionCollection');
		$user->set('id', 1);
		$user->setSource('Users');
		$user->setNew(false);

		$this->dispatchEvent('Authentication.afterAuthenticate', [
			'authenticator' => new SessionAuthenticator(new IdentifierCollection()),
			'identity' => $user,
		], $this);

		$request = $request->withAttribute('BackendIdentity', $user);

		Router::setRequest($request);

		$entity = $this->fetchTable('Usergroups')->newDefaultEntity();
		$entity->id = 4;

		$user->getUsergroups();

		$this->assertNotEmpty($user->usergroups);
		$this->assertNotEmpty($user->getPermissionCollection());

		$event = new Event('Model.Usergroups.afterSave', $this->fetchTable('Usergroups'));

		$this->listener->afterSave($event, $entity);

		$this->assertEmpty($user->usergroups);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\UsergroupsListener::afterSave()
	 * @throws \Exception
	 */
	public function testAfterSaveUnsetsUsergroupsOnCurrentUserWhenInGroup(): void {
		$request = Router::getRequest();

		$request = $request->withAttribute('authorization', new AuthorizationService('Backend'));

		/** @var \Awyiss\Model\Entity\User $user */
		$user = $this->getMockBuilder(User::class)->onlyMethods(['unsetPermissionCollection'])->getMock();
		$user->expects($this->once())->method('unsetPermissionCollection');
		$user->set('id', 1);
		$user->setSource('Users');
		$user->setNew(false);

		$this->dispatchEvent('Authentication.afterAuthenticate', [
			'authenticator' => new SessionAuthenticator(new IdentifierCollection()),
			'identity' => $user,
		], $this);

		$request = $request->withAttribute('BackendIdentity', $user);

		Router::setRequest($request);

		$entity = $this->fetchTable('Usergroups')->newDefaultEntity();
		$entity->id = 1;

		$user->getUsergroups();

		$this->assertNotEmpty($user->usergroups);
		$this->assertNotEmpty($user->getPermissionCollection());

		$event = new Event('Model.Usergroups.afterSave', $this->fetchTable('Usergroups'));

		$this->listener->afterSave($event, $entity);

		$this->assertEmpty($user->usergroups);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\UsergroupsListener::afterSave()
	 * @throws \Exception
	 */
	public function testAfterSaveUpdatesChangedOnOnAllAssignedUsers(): void {
		$request = Router::getRequest();
		$request = $request->withAttribute('authorization', new AuthorizationService('Backend'));
		Router::setRequest($request);

		$this->login();

		$usersTable = $this->fetchTable('Users');

		$result = $usersTable->updateAll([
			'changedOn' => new DateTime('2000-01-01 00:00:00'),
		], [
			'id IN' => [1, 2, 3, 4],
		]);

		$this->assertNotFalse($result);

		$users = $usersTable->find()->where(['id IN' => [1, 2, 3]])->all();
		foreach ($users as $user) {
			$this->assertEquals('2000-01-01 00:00:00', $user->changedOn->format('Y-m-d H:i:s'));
		}

		$entity = $this->fetchTable('Usergroups')->newDefaultEntity();
		$entity->id = 2;

		$event = new Event('Model.Usergroups.afterSave', $this->fetchTable('Usergroups'));

		$this->listener->afterSave($event, $entity);

		$users = $usersTable->find()->where(['id IN' => [1, 2, 3, 4]])->all();
		foreach ($users as $user) {
			// User with ID 4 is not in usergroup 2
			if ($user->id === 4) {
				$this->assertEquals('2000-01-01 00:00:00', $user->changedOn->format('Y-m-d H:i:s'));
				continue;
			}

			$this->assertEquals(date('Y-m-d'), $user->changedOn->format('Y-m-d'));
		}
	}
}
