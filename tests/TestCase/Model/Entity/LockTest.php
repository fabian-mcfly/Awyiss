<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Model\Entity;


use Awyiss\Authorization\AuthorizationService;
use Awyiss\Model\Entity\Lock;
use Awyiss\Routing\Router;
use Awyiss\Test\TestSuite\TestCase;
use Cake\Datasource\FactoryLocator;
use Cake\Http\ServerRequest;
use Cake\TestSuite\IntegrationTestTrait;


/**
 * Lock Entity Test Case
 *
 * @see \Awyiss\Model\Entity\Lock
 */
class LockTest extends TestCase {
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
	 * @see \Awyiss\Model\Entity\Lock::$fieldMap
	 */
	public function testFieldMapCompleteness(): void {
		/** @var \Awyiss\Model\Table\LocksTable $table */
		$table = FactoryLocator::get('Table')->get('Locks');
		$entity = $table->newDefaultEntity();
		$entityArray = $entity->toArray();

		foreach ($entityArray as $key => $value) {
			$this->assertStringNotContainsString('_', $key);
		}
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Lock::$_accessible
	 */
	public function testAccessibleFields(): void {
		$entity = new Lock();

		$this->assertSame([
			'scope' => true,
			'foreignKey' => true,
			'uniqueId' => true,
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
	 * @see \Awyiss\Model\Entity\Lock::_setScope()
	 * @noinspection PhpConditionAlreadyCheckedInspection
	 */
	public function testScopeCleaningViaPropertyAssignment(): void {
		$entity = new Lock();

		$entity->scope = 'TestScope';
		$this->assertEquals('TestScope', $entity->scope);

		$entity->scope = 'Test Scope';
		$this->assertEquals('TestScope', $entity->scope);

		$entity->scope = 'Test-Scope';
		$this->assertEquals('TestScope', $entity->scope);

		$entity->scope = 'Test Scope!@#$%';
		$this->assertEquals('TestScope', $entity->scope);

		$entity->scope = 'UPPERCASE SCOPE';
		$this->assertEquals('UPPERCASESCOPE', $entity->scope);

		$entity->scope = 'testHTMLScope';
		$this->assertEquals('TestHTMLScope', $entity->scope);

		$entity->scope = 'is_underscored';
		$this->assertEquals('IsUnderscored', $entity->scope);

		$entity->scope = null;
		$this->assertNull($entity->scope);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Lock::_setScope()
	 */
	public function testScopeCleaningViaSetMethod(): void {
		$entity = new Lock();

		$entity->set('scope', 'TestScope');
		$this->assertEquals('TestScope', $entity->scope);

		$entity->set('scope', 'Test Scope');
		$this->assertEquals('TestScope', $entity->scope);

		$entity->set('scope', 'Test-Scope');
		$this->assertEquals('TestScope', $entity->scope);

		$entity->set('scope', 'Test Scope!@#$%');
		$this->assertEquals('TestScope', $entity->scope);

		$entity->set('scope', 'UPPERCASE SCOPE');
		$this->assertEquals('UPPERCASESCOPE', $entity->scope);

		$entity->set('scope', 'testHTMLScope');
		$this->assertEquals('TestHTMLScope', $entity->scope);

		$entity->set('scope', 'is_underscored');
		$this->assertEquals('IsUnderscored', $entity->scope);

		/** @noinspection PhpRedundantOptionalArgumentInspection */
		$entity->set('scope', null);
		$this->assertNull($entity->scope);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Lock
	 */
	public function testEntityConstruction(): void {
		$properties = [
			'id' => 1,
			'scope' => 'TestScope',
			'foreignKey' => 123,
			'uniqueId' => 'test-unique-id',
			'createdBy' => 456,
			'createdOn' => '2025-01-06 12:00:00',
		];

		$entity = new Lock($properties);

		$this->assertEquals(1, $entity->id);
		$this->assertEquals('TestScope', $entity->scope); // Should be cleaned by setter
		$this->assertEquals(123, $entity->foreignKey);
		$this->assertEquals('test-unique-id', $entity->uniqueId);
		$this->assertEquals(456, $entity->createdBy);
		$this->assertNotNull($entity->createdOn);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Lock::isOwnLock()
	 */
	public function testIsOwnLockWithMatchingUserAndSession(): void {
		/** @noinspection PhpRedundantOptionalArgumentInspection */
		$this->login(1);

		// Create a session and set the lock identifier
		$session = Router::getRequest()->getSession();
		$session->write('Backend.lockIdentifier', 'test-unique-id');

		$entity = new Lock([
			'createdBy' => 1,
			'uniqueId' => 'test-unique-id',
		]);

		$this->assertTrue($entity->isOwnLock());
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Lock::isOwnLock()
	 */
	public function testIsOwnLockWithDifferentUser(): void {
		/** @noinspection PhpRedundantOptionalArgumentInspection */
		$this->login(1);

		// Create a session and set the lock identifier
		$session = Router::getRequest()->getSession();
		$session->write('Backend.lockIdentifier', 'test-unique-id');

		$entity = new Lock([
			'createdBy' => 2, // Different user
			'uniqueId' => 'test-unique-id',
		]);

		$this->assertFalse($entity->isOwnLock());
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Lock::isOwnLock()
	 */
	public function testIsOwnLockWithDifferentUniqueId(): void {
		/** @noinspection PhpRedundantOptionalArgumentInspection */
		$this->login(1);

		// Create a session and set the lock identifier
		$session = Router::getRequest()->getSession();
		$session->write('Backend.lockIdentifier', 'session-unique-id');

		$entity = new Lock([
			'createdBy' => 1,
			'uniqueId' => 'different-unique-id', // Different unique ID
		]);

		$this->assertFalse($entity->isOwnLock());
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Lock::isOwnLock()
	 */
	public function testIsOwnLockWithNoAuthentication(): void {
		// Create a session and set the lock identifier
		$session = Router::getRequest()->getSession();
		$session->write('Backend.lockIdentifier', 'session-unique-id');

		$entity = new Lock([
			'createdBy' => 1,
			'uniqueId' => 'session-unique-id',
		]);

		$this->assertFalse($entity->isOwnLock());
	}
}
