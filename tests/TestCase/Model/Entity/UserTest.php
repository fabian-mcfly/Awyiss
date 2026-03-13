<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Model\Entity;


use Authentication\IdentityInterface;
use Awyiss\Authorization\AuthorizationService;
use Awyiss\Authorization\IdentityPermissionsInterface;
use Awyiss\Authorization\Permission\PermissionCollection;
use Awyiss\Model\Entity\User;
use Awyiss\Routing\Router;
use Awyiss\Test\TestSuite\TestCase;
use Cake\Datasource\FactoryLocator;
use Cake\Http\ServerRequest;
use Cake\TestSuite\IntegrationTestTrait;
use ReflectionClass;


/**
 * User Entity Test Case
 *
 * @see \Awyiss\Model\Entity\User
 */
class UserTest extends TestCase {
	use IntegrationTestTrait;


	/**
	 * @inheritDoc
	 */
	protected function setUp(): void {
		parent::setUp();

		$request = new ServerRequest([
			'url' => '/dummy',
			'params' => [
				'lang' => 'xy',
				'controller' => 'dashboard',
				'action' => 'overview',
				'_name' => 'Backend',
				'prefix' => 'Backend',
				'parts' => [],
				'pass' => [],
				'plugin' => null,
			],
		]);

		$request = $request->withAttribute('authorization', new AuthorizationService('Backend'));

		Router::setRequest($request);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\User::$fieldMap
	 */
	public function testFieldMapCompleteness(): void {
		/** @var \Awyiss\Model\Table\UsersTable $table */
		$table = FactoryLocator::get('Table')->get('Users');
		$entity = $table->newDefaultEntity();
		$entityArray = $entity->toArray();

		foreach ($entityArray as $key => $value) {
			$this->assertStringNotContainsString('_', $key);
		}
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\User::$_accessible
	 */
	public function testAccessibleFields(): void {
		$entity = new User();

		$this->assertSame([
			'username' => true,
			'password' => true,
			'firstname' => true,
			'lastname' => true,
			'email' => true,
			'active' => true,
			'usergroups' => true,
			'_translations' => true,
			'_publicationData' => true,
			'customerGroupAccessSettings' => true,
			'customerGroupAssignments' => true,
			'mediaAssignments' => true,
			'mediaElementAssignments' => true,
		], $entity->getAccessible());
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\User::$_hidden
	 */
	public function testHiddenFields(): void {
		$entity = new User(['password' => 'secret']);
		$entityArray = $entity->toArray();

		$this->assertArrayNotHasKey('password', $entityArray);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\User
	 */
	public function testImplementsInterfaces(): void {
		$entity = new User();

		$this->assertInstanceOf(IdentityInterface::class, $entity);
		$this->assertInstanceOf(IdentityPermissionsInterface::class, $entity);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\User::getIdentifier()
	 */
	public function testGetIdentifier(): void {
		$entity = new User(['id' => 123]);

		$this->assertEquals(123, $entity->getIdentifier());
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\User::getIdentifier()
	 */
	public function testGetIdentifierWithNullId(): void {
		$entity = new User();

		$this->assertNull($entity->getIdentifier());
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\User::getOriginalData()
	 */
	public function testGetOriginalData(): void {
		$entity = new User(['id' => 1]);

		$originalData = $entity->getOriginalData();

		$this->assertSame($entity, $originalData);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\User::_setPassword()
	 */
	public function testPasswordHashingViaPropertyAssignment(): void {
		$entity = new User();

		$entity->password = 'test_password';
		$this->assertNotEquals('test_password', $entity->password);
		$this->assertStringStartsWith('$2y$', $entity->password);

		$entity->password = '';
		$this->assertNull($entity->password);

		$entity->password = null;
		$this->assertNull($entity->password);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\User::_setPassword()
	 */
	public function testPasswordHashingViaSetMethod(): void {
		$entity = new User();

		$entity->set('password', 'test_password');
		$this->assertNotEquals('test_password', $entity->password);
		$this->assertStringStartsWith('$2y$', $entity->password);

		$entity->set('password', '');
		$this->assertNull($entity->password);

		/** @noinspection PhpRedundantOptionalArgumentInspection */
		$entity->set('password', null);
		$this->assertNull($entity->password);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\User::_getLabel()
	 */
	public function testLabelVirtualPropertyWithActiveUser(): void {
		$entity = new User(['username' => 'test_user', 'active' => true]);

		$label = $entity->label;

		$this->assertSame('test_user', $label);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\User::_getLabel()
	 */
	public function testLabelVirtualPropertyWithInactiveUser(): void {
		$entity = new User(['username' => 'test_user', 'active' => false]);

		$label = $entity->label;

		$this->assertSame('Users::inactive test_user', $label);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\User::_setUsergroups()
	 */
	public function testSetUsergroupsResetsPermissionCollection(): void {
		/** @noinspection PhpRedundantOptionalArgumentInspection */
		$this->login(1);

		/** @var \Awyiss\Model\Table\UsersTable $table */
		$table = FactoryLocator::get('Table')->get('Users');
		/** @var \Awyiss\Model\Entity\User $user */
		$user = $table->get(1);

		// Initialize permission collection
		$collection = $user->getPermissionCollection();
		/** @noinspection PhpConditionAlreadyCheckedInspection */
		$this->assertInstanceOf(PermissionCollection::class, $collection);

		// Setting usergroups should reset the permission collection
		$user->usergroups = [];

		// Should get a fresh permission collection after reset
		$newCollection = $user->getPermissionCollection();
		/** @noinspection PhpConditionAlreadyCheckedInspection */
		$this->assertInstanceOf(PermissionCollection::class, $newCollection);

		$this->assertNotSame($collection, $newCollection);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\User::unsetPermissionCollection()
	 */
	public function testUnsetPermissionCollection(): void {
		/** @noinspection PhpRedundantOptionalArgumentInspection */
		$this->login(1);

		/** @var \Awyiss\Model\Table\UsersTable $table */
		$table = FactoryLocator::get('Table')->get('Users');
		/** @var \Awyiss\Model\Entity\User $user */
		$user = $table->get(1);

		// Initialize permission collection
		$collection = $user->getPermissionCollection();
		$this->assertNotEmpty($collection);

		$result = $user->unsetPermissionCollection();

		$this->assertSame($user, $result);

		$reflection = new ReflectionClass($user);
		$property = $reflection->getProperty('permissionCollection');
		/** @noinspection PhpExpressionResultUnusedInspection */
		$property->setAccessible(true);
		$this->assertNull($property->getValue($user));
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\User::getPermissionCollection()
	 */
	public function testGetPermissionCollectionWithAllAccessUser(): void {
		/** @noinspection PhpRedundantOptionalArgumentInspection */
		$this->login(1);

		/** @var \Awyiss\Model\Table\UsersTable $table */
		$table = FactoryLocator::get('Table')->get('Users');
		/** @var \Awyiss\Model\Entity\User $user */
		$user = $table->get(1);

		$permissionCollection = $user->getPermissionCollection();

		/** @noinspection PhpConditionAlreadyCheckedInspection */
		$this->assertInstanceOf(PermissionCollection::class, $permissionCollection);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\User::getPermissionCollection()
	 */
	public function testGetPermissionCollectionWithNoAccessUser(): void {
		$this->login(3);

		/** @var \Awyiss\Model\Table\UsersTable $table */
		$table = FactoryLocator::get('Table')->get('Users');
		/** @var \Awyiss\Model\Entity\User $user */
		$user = $table->get(3);

		$permissionCollection = $user->getPermissionCollection();

		/** @noinspection PhpConditionAlreadyCheckedInspection */
		$this->assertInstanceOf(PermissionCollection::class, $permissionCollection);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\User::scopeIsAccessible()
	 * @throws \ReflectionException
	 */
	public function testScopeIsAccessibleWithAllAccessUser(): void {
		/** @noinspection PhpRedundantOptionalArgumentInspection */
		$this->login(1);

		/** @var \Awyiss\Model\Table\UsersTable $table */
		$table = FactoryLocator::get('Table')->get('Users');
		/** @var \Awyiss\Model\Entity\User $user */
		$user = $table->get(1);

		$this->assertTrue($user->scopeIsAccessible('News', [], 'read'));
		$this->assertTrue($user->scopeIsAccessible('News', [], 'create'));
		$this->assertTrue($user->scopeIsAccessible('News', [], 'update'));
		$this->assertTrue($user->scopeIsAccessible('News', [], 'delete'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\User::scopeIsAccessible()
	 * @throws \ReflectionException
	 */
	public function testScopeIsAccessibleWithNoAccessUser(): void {
		$this->login(3);

		/** @var \Awyiss\Model\Table\UsersTable $table */
		$table = FactoryLocator::get('Table')->get('Users');
		/** @var \Awyiss\Model\Entity\User $user */
		$user = $table->get(3);

		$this->assertFalse($user->scopeIsAccessible('News', [], 'read'));
		$this->assertFalse($user->scopeIsAccessible('News', [], 'create'));
		$this->assertFalse($user->scopeIsAccessible('News', [], 'update'));
		$this->assertFalse($user->scopeIsAccessible('News', [], 'delete'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\User::scopeIsAccessible()
	 * @throws \ReflectionException
	 */
	public function testScopeIsAccessibleUserConfigurationAlwaysGrantedForSystem(): void {
		$this->login(3);

		/** @var \Awyiss\Model\Table\UsersTable $table */
		$table = FactoryLocator::get('Table')->get('Users');
		/** @var \Awyiss\Model\Entity\User $user */
		$user = $table->get(3);

		$this->assertTrue($user->scopeIsAccessible('UserConfiguration', ['scope' => 'System'], 'read'));
		$this->assertFalse($user->scopeIsAccessible('UserConfiguration', ['scope' => 'News'], 'read'));
		$this->assertTrue($user->scopeIsAccessible('UserConfiguration', ['scope' => 'System'], 'create'));
		$this->assertFalse($user->scopeIsAccessible('UserConfiguration', ['scope' => 'News'], 'create'));
		$this->assertTrue($user->scopeIsAccessible('UserConfiguration', ['scope' => 'System'], 'update'));
		$this->assertFalse($user->scopeIsAccessible('UserConfiguration', ['scope' => 'News'], 'update'));
		$this->assertTrue($user->scopeIsAccessible('UserConfiguration', ['scope' => 'System'], 'delete'));
		$this->assertFalse($user->scopeIsAccessible('UserConfiguration', ['scope' => 'News'], 'delete'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\User::getUsergroups()
	 */
	public function testGetUsergroupsWithAllAccessUser(): void {
		/** @var \Awyiss\Model\Table\UsersTable $table */
		$table = FactoryLocator::get('Table')->get('Users');
		/** @var \Awyiss\Model\Entity\User $user */
		$user = $table->get(1);

		$usergroups = $user->getUsergroups();

		$this->assertIsArray($usergroups);
		$this->assertNotEmpty($usergroups);
		$this->assertEquals(2, count($usergroups));
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\User::getUsergroups()
	 */
	public function testGetUsergroupsWithNoAccessUser(): void {
		/** @var \Awyiss\Model\Table\UsersTable $table */
		$table = FactoryLocator::get('Table')->get('Users');
		/** @var \Awyiss\Model\Entity\User $user */
		$user = $table->get(3);

		$usergroups = $user->getUsergroups();

		$this->assertIsArray($usergroups);
		$this->assertNotEmpty($usergroups);
		$this->assertEquals(2, count($usergroups));
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\User::getConfiguration()
	 */
	public function testGetConfigurationWithExistingUserConfig(): void {
		/** @var \Awyiss\Model\Table\UsersTable $table */
		$table = FactoryLocator::get('Table')->get('Users');
		/** @var \Awyiss\Model\Entity\User $user */
		$user = $table->get(1);

		$configuration = $user->getConfiguration();

		$this->assertIsArray($configuration);
		$this->assertNotEmpty($configuration);
		$this->assertArrayHasKey('System', $configuration);
		$this->assertArrayHasKey('interface', $configuration['System']);
		$this->assertTrue($configuration['System']['interface']['darkMode']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\User::getConfiguration()
	 */
	public function testGetConfigurationWithNoUserConfig(): void {
		/** @var \Awyiss\Model\Table\UsersTable $table */
		$table = FactoryLocator::get('Table')->get('Users');
		/** @var \Awyiss\Model\Entity\User $user */
		$user = $table->get(4);

		$configuration = $user->getConfiguration();

		$this->assertIsArray($configuration);
		$this->assertEmpty($configuration);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\User::resetConfiguration()
	 */
	public function testResetConfiguration(): void {
		/** @var \Awyiss\Model\Table\UsersTable $table */
		$table = FactoryLocator::get('Table')->get('Users');
		/** @var \Awyiss\Model\Entity\User $user */
		$user = $table->get(1);

		$configuration = $user->getConfiguration();
		$this->assertIsArray($configuration);
		$this->assertNotEmpty($configuration);

		 $user->resetConfiguration();

		$reflection = new ReflectionClass($user);
		$property = $reflection->getProperty('userConfiguration');
		/** @noinspection PhpExpressionResultUnusedInspection */
		$property->setAccessible(true);
		$value = $property->getValue($user);
		$this->assertNull($value);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\User
	 */
	public function testEntityConstruction(): void {
		$properties = [
			'id' => 1,
			'username' => 'test_user',
			'password' => 'test_password',
			'failedAttempts' => 0,
			'lastLogin' => '2023-01-01 12:00:00',
			'firstname' => 'Test',
			'lastname' => 'User',
			'email' => 'test@example.com',
			'active' => true,
			'deleted' => false,
		];

		$entity = new User($properties);

		$this->assertEquals(1, $entity->id);
		$this->assertEquals('test_user', $entity->username);
		$this->assertNotEquals('test_password', $entity->password);
		$this->assertStringStartsWith('$2y$', $entity->password);
		$this->assertEquals(0, $entity->failedAttempts);
		$this->assertEquals('Test', $entity->firstname);
		$this->assertEquals('User', $entity->lastname);
		$this->assertEquals('test@example.com', $entity->email);
		$this->assertTrue($entity->active);
		$this->assertFalse($entity->deleted);
	}
}
