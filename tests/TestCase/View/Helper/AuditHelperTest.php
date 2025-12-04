<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\View\Helper;


use Awyiss\Model\Entity\User;
use Awyiss\Model\Table\UsersTable;
use Awyiss\Test\TestSuite\TestCase;
use Awyiss\View\BackendView;
use Awyiss\View\Helper\AuditHelper;
use Cake\Datasource\FactoryLocator;
use Cake\Datasource\ResultSetInterface;
use Cake\ORM\Query\SelectQuery;
use ReflectionClass;


/**
 * AuditHelperTest class
 */
class AuditHelperTest extends TestCase {
	/**
	 * @var \Awyiss\View\Helper\AuditHelper
	 */
	protected AuditHelper $helper;


	/**
	 * @inheritDoc
	 */
	protected function setUp(): void {
		parent::setUp();

		$view = new BackendView();
		$this->helper = new AuditHelper($view);
	}


	/**
	 * @inheritDoc
	 */
	protected function tearDown(): void {
		parent::tearDown();

		$tableLocator = FactoryLocator::get('Table');
		$tableLocator->clear();
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\AuditHelper::getUser()
	 */
	public function testGetUserReturnsUserWhenExists(): void {
		$user1 = new User(['id' => 1, 'username' => 'testuser1']);
		$user2 = new User(['id' => 2, 'username' => 'testuser2']);

		$resultSet = $this->createMock(ResultSetInterface::class);
		$resultSet->method('indexBy')->willReturnSelf();
		$resultSet->method('toArray')->willReturn([1 => $user1, 2 => $user2]);

		$query = $this->createMock(SelectQuery::class);
		$query->method('all')->willReturn($resultSet);

		$usersTable = $this->getMockBuilder(UsersTable::class)->disableOriginalConstructor()->onlyMethods(['find'])->getMock();
		$usersTable->method('find')->willReturn($query);

		$tableLocator = FactoryLocator::get('Table');
		$tableLocator->clear();
		$tableLocator->set('Users', $usersTable);

		$result = $this->helper->getUser(1);

		$this->assertInstanceOf(User::class, $result);
		$this->assertSame(1, $result->id);
		$this->assertSame('testuser1', $result->username);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\AuditHelper::getUser()
	 */
	public function testGetUserReturnsNullWhenNotExists(): void {
		$user1 = new User(['id' => 1, 'username' => 'testuser1']);

		$resultSet = $this->createMock(ResultSetInterface::class);
		$resultSet->method('indexBy')->willReturnSelf();
		$resultSet->method('toArray')->willReturn([1 => $user1]);

		$query = $this->createMock(SelectQuery::class);
		$query->method('all')->willReturn($resultSet);

		$usersTable = $this->getMockBuilder(UsersTable::class)->disableOriginalConstructor()->onlyMethods(['find'])->getMock();
		$usersTable->method('find')->willReturn($query);

		$tableLocator = FactoryLocator::get('Table');
		$tableLocator->clear();
		$tableLocator->set('Users', $usersTable);

		$result = $this->helper->getUser(999);

		$this->assertNull($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\AuditHelper::getUsername()
	 */
	public function testGetUsernameReturnsUsernameWhenUserExists(): void {
		$user1 = new User(['id' => 1, 'username' => 'testuser1']);
		$user2 = new User(['id' => 2, 'username' => 'testuser2']);

		$resultSet = $this->createMock(ResultSetInterface::class);
		$resultSet->method('indexBy')->willReturnSelf();
		$resultSet->method('toArray')->willReturn([1 => $user1, 2 => $user2]);

		$query = $this->createMock(SelectQuery::class);
		$query->method('all')->willReturn($resultSet);

		$usersTable = $this->getMockBuilder(UsersTable::class)->disableOriginalConstructor()->onlyMethods(['find'])->getMock();
		$usersTable->method('find')->willReturn($query);

		$tableLocator = FactoryLocator::get('Table');
		$tableLocator->clear();
		$tableLocator->set('Users', $usersTable);

		$result = $this->helper->getUsername(2);

		$this->assertSame('testuser2', $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\AuditHelper::getUsername()
	 */
	public function testGetUsernameReturnsUnknownWhenUserNotExists(): void {
		$user1 = new User(['id' => 1, 'username' => 'testuser1']);

		$resultSet = $this->createMock(ResultSetInterface::class);
		$resultSet->method('indexBy')->willReturnSelf();
		$resultSet->method('toArray')->willReturn([1 => $user1]);

		$query = $this->createMock(SelectQuery::class);
		$query->method('all')->willReturn($resultSet);

		$usersTable = $this->getMockBuilder(UsersTable::class)->disableOriginalConstructor()->onlyMethods(['find'])->getMock();
		$usersTable->method('find')->willReturn($query);

		$tableLocator = FactoryLocator::get('Table');
		$tableLocator->clear();
		$tableLocator->set('Users', $usersTable);

		$result = $this->helper->getUsername(999);

		$this->assertSame('user_unknown', $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\AuditHelper::getUser()
	 */
	public function testGetUserCachesUsersTableResults(): void {
		// Reset the cache
		$reflection = new ReflectionClass(AuditHelper::class);
		$property = $reflection->getProperty('userCache');
		/** @noinspection PhpExpressionResultUnusedInspection */
		$property->setAccessible(true);
		$property->setValue(null, null);

		$user1 = new User(['id' => 1, 'username' => 'testuser1']);
		$user2 = new User(['id' => 2, 'username' => 'testuser2']);

		$resultSet = $this->createMock(ResultSetInterface::class);
		$resultSet->method('indexBy')->willReturnSelf();
		$resultSet->method('toArray')->willReturn([1 => $user1, 2 => $user2]);

		$query = $this->createMock(SelectQuery::class);
		$query->method('all')->willReturn($resultSet);

		$usersTable = $this->getMockBuilder(UsersTable::class)->disableOriginalConstructor()->onlyMethods(['find'])->getMock();
		// Ensure that 'find' is called only once
		$usersTable->expects($this->once())->method('find')->willReturn($query);

		$tableLocator = FactoryLocator::get('Table');
		$tableLocator->clear();
		$tableLocator->set('Users', $usersTable);

		$this->helper->getUser(1);
		$this->helper->getUser(2);
		$this->helper->getUser(1);
	}
}
