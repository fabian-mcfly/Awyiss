<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Event\Backend;


use Awyiss\Authentication\Authenticator\SessionAuthenticator;
use Awyiss\Authentication\Identifier\IdentifierCollection;
use Awyiss\Authorization\AuthorizationService;
use Awyiss\Event\Backend\UserConfigurationListener;
use Awyiss\Event\EventListenersProvider;
use Awyiss\Model\Entity\User;
use Awyiss\Routing\Router;
use Awyiss\Test\TestSuite\TestCase;
use Cake\Datasource\FactoryLocator;
use Cake\Event\Event;


/**
 * UserConfigurationListener Test Case
 *
 * @see \Awyiss\Event\Backend\UserConfigurationListener
 */
class UserConfigurationListenerTest extends TestCase {
	/**
	 * @var \Awyiss\Event\Backend\UserConfigurationListener
	 */
	protected UserConfigurationListener $listener;
	/**
	 * @var \Awyiss\Model\Entity\User
	 */
	protected User $user;


	/**
	 * @inheritDoc
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->listener = new UserConfigurationListener();

		$request = Router::getRequest();
		$request = $request->withAttribute('authorization', new AuthorizationService('Backend'));
		Router::setRequest($request);

		// Set up a mock user identity
		$this->user = $this->getMockBuilder(User::class)->onlyMethods(['resetConfiguration'])->getMock();
		$this->user->id = 1;
		$this->user->setSource('Users');
		$this->user->getUsergroups();

		$this->dispatchEvent('Authentication.afterAuthenticate', [
			'authenticator' => new SessionAuthenticator(new IdentifierCollection()),
			'identity' => $this->user,
		], $this);
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
	 * @see \Awyiss\Event\Backend\UserConfigurationListener::implementedEvents()
	 */
	public function testImplementedEvents(): void {
		$result = $this->listener->implementedEvents();

		$this->assertSame([
			'Model.UserConfiguration.beforeSave' => 'beforeSave',
			'Model.UserConfiguration.afterSave' => 'afterSave',
			'Model.UserConfiguration.afterDelete' => 'afterDelete',
		], $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\UserConfigurationListener::beforeSave()
	 */
	public function testBeforeSaveTypecastsValue(): void {
		$entity = $this->fetchTable('UserConfiguration')->newDefaultEntity();
		$entity->patch([
			'scope' => 'Newscategories',
			'identifier' => 'paginate.limit',
			'value' => '10',
		]);

		$event = new Event('Model.UserConfiguration.beforeSave', $this->fetchTable('UserConfiguration'));

		$this->listener->beforeSave($event, $entity);

		$this->assertEquals(10, $entity->value);

		$entity->patch([
			'scope' => 'Newscategories',
			'identifier' => 'paginate.enabled',
			'value' => 'true',
		]);

		$this->listener->beforeSave($event, $entity);

		$this->assertSame(true, $entity->value);

		$entity->patch([
			'scope' => 'Newscategories',
			'identifier' => 'paginate.enabled',
			'value' => 'false',
		]);

		$this->listener->beforeSave($event, $entity);

		$this->assertSame(0, $entity->value);

		$entity->patch([
			'scope' => 'Media',
			'identifier' => 'overview.displayedFields',
			'value' => ['id', 'name', 'createdBy', 'unknown_column'],
		]);

		$this->listener->beforeSave($event, $entity);

		$this->assertSame('{"1":"name","2":"createdBy"}', $entity->value);

		$entity->patch([
			'scope' => 'Media',
			'identifier' => 'overview.displayed_fields',
			'value' => null,
		]);

		$this->listener->beforeSave($event, $entity);

		$this->assertSame(null, $entity->value);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\UserConfigurationListener::beforeSave()
	 */
	public function testBeforeSaveSetsCurrentUserId(): void {
		$entity = $this->fetchTable('UserConfiguration')->newDefaultEntity([
			'scope' => 'TestScope',
			'identifier' => 'testIdentifier',
			'value' => 'test_value',
		]);

		$this->user->id = 123;

		$event = new Event('Model.UserConfiguration.beforeSave', $this->fetchTable('UserConfiguration'));

		$this->listener->beforeSave($event, $entity);

		$this->assertSame(123, $entity->userId);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\UserConfigurationListener::afterSave()
	 */
	public function testAfterSaveCallsResetConfiguration(): void {
		$this->user->expects($this->once())->method('resetConfiguration');

		$this->listener->afterSave();
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\UserConfigurationListener::afterDelete()
	 */
	public function testAfterDeleteCallsResetConfiguration(): void {
		$this->user->expects($this->once())->method('resetConfiguration');

		$this->listener->afterDelete();
	}
}
