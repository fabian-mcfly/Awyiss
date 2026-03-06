<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Model\Entity;


use Awyiss\Authorization\AuthorizationService;
use Awyiss\Model\Entity\Customer;
use Awyiss\Routing\Router;
use Awyiss\Test\TestSuite\TestCase;
use Cake\Datasource\FactoryLocator;
use Cake\Http\ServerRequest;


/**
 * Customer Entity Test Case
 *
 * @see \Awyiss\Model\Entity\Customer
 */
class CustomerTest extends TestCase {
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
	 * @see \Awyiss\Model\Entity\Customer::$fieldMap
	 */
	public function testFieldMapCompleteness(): void {
		/** @var \Awyiss\Model\Table\CustomersTable $table */
		$table = FactoryLocator::get('Table')->get('Customers');
		$entity = $table->newDefaultEntity();
		$entityArray = $entity->toArray();

		foreach ($entityArray as $key => $value) {
			$this->assertStringNotContainsString('_', $key);
		}
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Customer::$_accessible
	 */
	public function testAccessibleFields(): void {
		$entity = new Customer();

		$this->assertSame([
			'email' => true,
			'password' => true,
			'firstname' => true,
			'lastname' => true,
			'lastLogin' => true,
			'failedAttempts' => true,
			'verified' => true,
			'verifiedOn' => true,
			'verificationCode' => true,
			'passwordResetCode' => true,
			'passwordResetOn' => true,
			'active' => true,
			'customerGroups' => true,
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
	 * @see \Awyiss\Model\Entity\Customer::$_hidden
	 */
	public function testHiddenFields(): void {
		$entity = new Customer(['password' => 'secret']);
		$entityArray = $entity->toArray();

		$this->assertArrayNotHasKey('password', $entityArray);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Customer::_setPassword()
	 */
	public function testPasswordHashingViaPropertyAssignment(): void {
		$entity = new Customer();

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
	 * @see \Awyiss\Model\Entity\Customer::_setPassword()
	 */
	public function testPasswordHashingViaSetMethod(): void {
		$entity = new Customer();

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
	 * @see \Awyiss\Model\Entity\Customer
	 */
	public function testEntityConstruction(): void {
		$properties = [
			'id' => 1,
			'email' => 'test@example.com',
			'firstname' => 'Test',
			'lastname' => 'Customer',
			'verified' => false,
			'active' => true,
		];

		$entity = new Customer($properties);

		$this->assertEquals(1, $entity->id);
		$this->assertEquals('test@example.com', $entity->email);
		$this->assertEquals('Test', $entity->firstname);
		$this->assertEquals('Customer', $entity->lastname);
		$this->assertFalse($entity->verified);
		$this->assertTrue($entity->active);
	}
}
