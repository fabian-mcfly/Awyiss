<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Event\Global;


use Awyiss\Event\Global\CustomersListener;
use Awyiss\Test\TestSuite\TestCase;
use Cake\Core\Configure;
use Cake\I18n\DateTime;


/**
 * CustomersListener Test Case
 *
 * @see \Awyiss\Event\Global\CustomersListener
 */
class CustomersListenerTest extends TestCase {
	/**
	 * @var \Awyiss\Event\Global\CustomersListener
	 */
	protected CustomersListener $listener;


	/**
	 * @inheritDoc
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->listener = new CustomersListener();
		DateTime::setTestNow('2026-01-06 12:00:00');
	}


	/**
	 * @inheritDoc
	 */
	protected function tearDown(): void {
		parent::tearDown();

		DateTime::setTestNow();

		// Delete all customers after each test
		$customersTable = $this->getTableLocator()->get('Customers');
		$customersTable->deleteAll([
			'id >' => 3,
		]);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Global\CustomersListener::implementedEvents()
	 */
	public function testImplementedEvents(): void {
		$result = $this->listener->implementedEvents();

		$this->assertSame([
			'Customers.cleanupUnverifiedCustomers' => 'cleanupUnverifiedCustomers',
		], $result);
	}


	/**
	 * Test cleanup when feature is enabled and customers exist past validity period
	 *
	 * @return void
	 * @see \Awyiss\Event\Global\CustomersListener::cleanupUnverifiedCustomers()
	 */
	public function testCleanupUnverifiedCustomersWhenEnabledAndExpired(): void {
		Configure::write('Awyiss.Customers.Frontend.registration.deleteUnverifiedAccounts', true);
		Configure::write('Awyiss.Customers.Frontend.registration.verificationCodeValidity', 86400); // 24 hours

		$customersTable = $this->getTableLocator()->get('Customers');

		// Create unverified customer older than validity period
		$oldCustomer = $customersTable->newDefaultEntity([
			'email' => 'enabled-expired-old@example.com',
			'password' => 'password123',
			'passwordConfirm' => 'password123',
			'verified' => false,
			'active' => false,
			'createdOn' => DateTime::now()->subDays(2),
		]);
		$customersTable->saveOrFail($oldCustomer, ['audit' => ['skip' => true]]);

		// Create unverified customer within validity period
		$recentCustomer = $customersTable->newDefaultEntity([
			'email' => 'enabled-expired-recent@example.com',
			'password' => 'password123',
			'passwordConfirm' => 'password123',
			'verified' => false,
			'active' => false,
			'createdOn' => DateTime::now()->subHours(12),
		]);
		$customersTable->saveOrFail($recentCustomer, ['audit' => ['skip' => true]]);

		// Create verified customer older than validity period
		$verifiedCustomer = $customersTable->newDefaultEntity([
			'email' => 'enabled-expired-verified@example.com',
			'password' => 'password123',
			'passwordConfirm' => 'password123',
			'verified' => true,
			'active' => true,
			'createdOn' => DateTime::now()->subDays(2),
		]);
		$customersTable->saveOrFail($verifiedCustomer, ['audit' => ['skip' => true]]);

		$this->listener->cleanupUnverifiedCustomers();

		// Old unverified customer should be deleted
		$this->assertNull($customersTable->find()->where(['email' => 'enabled-expired-old@example.com'])->first());

		// Recent unverified customer should still exist
		$this->assertNotNull($customersTable->find()->where(['email' => 'enabled-expired-recent@example.com'])->first());

		// Verified customer should still exist
		$this->assertNotNull($customersTable->find()->where(['email' => 'enabled-expired-verified@example.com'])->first());
	}


	/**
	 * Test cleanup when feature is disabled
	 *
	 * @return void
	 * @see \Awyiss\Event\Global\CustomersListener::cleanupUnverifiedCustomers()
	 */
	public function testCleanupUnverifiedCustomersWhenDisabled(): void {
		Configure::write('Awyiss.Customers.Frontend.registration.deleteUnverifiedAccounts', false);
		Configure::write('Awyiss.Customers.Frontend.registration.verificationCodeValidity', 86400);

		$customersTable = $this->getTableLocator()->get('Customers');

		// Create unverified customer older than validity period
		$oldCustomer = $customersTable->newDefaultEntity([
			'email' => 'disabled-old@example.com',
			'password' => 'password123',
			'passwordConfirm' => 'password123',
			'verified' => false,
			'active' => false,
			'createdOn' => DateTime::now()->subDays(2),
		]);
		$customersTable->saveOrFail($oldCustomer, ['audit' => ['skip' => true]]);

		$this->listener->cleanupUnverifiedCustomers();

		// Customer should still exist because cleanup is disabled
		$this->assertNotNull($customersTable->find()->where(['email' => 'disabled-old@example.com'])->first());
	}


	/**
	 * Test cleanup when verification code validity is set to 0
	 *
	 * @return void
	 * @see \Awyiss\Event\Global\CustomersListener::cleanupUnverifiedCustomers()
	 */
	public function testCleanupUnverifiedCustomersWhenValidityIsZero(): void {
		Configure::write('Awyiss.Customers.Frontend.registration.deleteUnverifiedAccounts', true);
		Configure::write('Awyiss.Customers.Frontend.registration.verificationCodeValidity', 0);

		$customersTable = $this->getTableLocator()->get('Customers');

		// Create unverified customer older than validity period
		$oldCustomer = $customersTable->newDefaultEntity([
			'email' => 'validity-zero-old@example.com',
			'password' => 'password123',
			'passwordConfirm' => 'password123',
			'verified' => false,
			'active' => false,
			'createdOn' => DateTime::now()->subDays(2),
		]);
		$customersTable->saveOrFail($oldCustomer, ['audit' => ['skip' => true]]);

		$this->listener->cleanupUnverifiedCustomers();

		// Customer should still exist because validity is 0 (disabled)
		$this->assertNotNull($customersTable->find()->where(['email' => 'validity-zero-old@example.com'])->first());
	}


	/**
	 * Test cleanup uses default values when config is not set
	 *
	 * @return void
	 * @see \Awyiss\Event\Global\CustomersListener::cleanupUnverifiedCustomers()
	 */
	public function testCleanupUnverifiedCustomersWithDefaultConfig(): void {
		// Don't set any config, should use defaults (enabled: true, validity: 86400)

		$customersTable = $this->getTableLocator()->get('Customers');

		// Create unverified customer older than default validity period (24 hours)
		$oldCustomer = $customersTable->newDefaultEntity([
			'email' => 'default-config-old@example.com',
			'password' => 'password123',
			'passwordConfirm' => 'password123',
			'verified' => false,
			'active' => false,
			'createdOn' => DateTime::now()->subDays(2),
		]);
		$customersTable->saveOrFail($oldCustomer, ['audit' => ['skip' => true]]);

		$this->listener->cleanupUnverifiedCustomers();

		// Customer should be deleted using default settings
		$this->assertNull($customersTable->find()->where(['email' => 'default-config-old@example.com'])->first());
	}


	/**
	 * Test cleanup with custom validity period
	 *
	 * @return void
	 * @see \Awyiss\Event\Global\CustomersListener::cleanupUnverifiedCustomers()
	 */
	public function testCleanupUnverifiedCustomersWithCustomValidityPeriod(): void {
		Configure::write('Awyiss.Customers.Frontend.registration.deleteUnverifiedAccounts', true);
		Configure::write('Awyiss.Customers.Frontend.registration.verificationCodeValidity', 3600); // 1 hour

		$customersTable = $this->getTableLocator()->get('Customers');

		// Create unverified customer 2 hours old (should be deleted)
		$twoHoursOld = $customersTable->newDefaultEntity([
			'email' => 'custom-validity-twohours@example.com',
			'password' => 'password123',
			'passwordConfirm' => 'password123',
			'verified' => false,
			'active' => false,
			'createdOn' => DateTime::now()->subHours(2),
		]);
		$customersTable->saveOrFail($twoHoursOld, ['audit' => ['skip' => true]]);

		// Create unverified customer 30 minutes old (should remain)
		$thirtyMinutesOld = $customersTable->newDefaultEntity([
			'email' => 'custom-validity-thirtymin@example.com',
			'password' => 'password123',
			'passwordConfirm' => 'password123',
			'verified' => false,
			'active' => false,
			'createdOn' => DateTime::now()->subMinutes(30),
		]);
		$customersTable->saveOrFail($thirtyMinutesOld, ['audit' => ['skip' => true]]);

		$this->listener->cleanupUnverifiedCustomers();

		// 2 hours old should be deleted
		$this->assertNull($customersTable->find()->where(['email' => 'custom-validity-twohours@example.com'])->first());

		// 30 minutes old should still exist
		$this->assertNotNull($customersTable->find()->where(['email' => 'custom-validity-thirtymin@example.com'])->first());
	}


	/**
	 * Test cleanup when both configs are disabled
	 *
	 * @return void
	 * @see \Awyiss\Event\Global\CustomersListener::cleanupUnverifiedCustomers()
	 */
	public function testCleanupUnverifiedCustomersWhenBothConfigsDisabled(): void {
		Configure::write('Awyiss.Customers.Frontend.registration.deleteUnverifiedAccounts', false);
		Configure::write('Awyiss.Customers.Frontend.registration.verificationCodeValidity', 0);

		$customersTable = $this->getTableLocator()->get('Customers');

		// Create unverified customer
		$customer = $customersTable->newDefaultEntity([
			'email' => 'both-disabled-test@example.com',
			'password' => 'password123',
			'passwordConfirm' => 'password123',
			'verified' => false,
			'active' => false,
			'createdOn' => DateTime::now()->subDays(30),
		]);
		$customersTable->saveOrFail($customer, ['audit' => ['skip' => true]]);

		$this->listener->cleanupUnverifiedCustomers();

		// Customer should still exist
		$this->assertNotNull($customersTable->find()->where(['email' => 'both-disabled-test@example.com'])->first());
	}


	/**
	 * Test cleanup when enabled but no expired customers exist
	 *
	 * @return void
	 * @see \Awyiss\Event\Global\CustomersListener::cleanupUnverifiedCustomers()
	 */
	public function testCleanupUnverifiedCustomersWhenNoExpiredCustomers(): void {
		Configure::write('Awyiss.Customers.Frontend.registration.deleteUnverifiedAccounts', true);
		Configure::write('Awyiss.Customers.Frontend.registration.verificationCodeValidity', 86400);

		$customersTable = $this->getTableLocator()->get('Customers');

		// Create only recent unverified customers
		$customer1 = $customersTable->newDefaultEntity([
			'email' => 'no-expired-recent1@example.com',
			'password' => 'password123',
			'passwordConfirm' => 'password123',
			'verified' => false,
			'active' => false,
			'createdOn' => DateTime::now()->subHours(12),
		]);
		$customersTable->saveOrFail($customer1, ['audit' => ['skip' => true]]);

		$customer2 = $customersTable->newDefaultEntity([
			'email' => 'no-expired-recent2@example.com',
			'password' => 'password123',
			'passwordConfirm' => 'password123',
			'verified' => false,
			'active' => false,
			'createdOn' => DateTime::now()->subHours(6),
		]);
		$customersTable->saveOrFail($customer2, ['audit' => ['skip' => true]]);

		$initialCount = $customersTable->find()->where(['verified' => false])->count();

		$this->listener->cleanupUnverifiedCustomers();

		$finalCount = $customersTable->find()->where(['verified' => false])->count();

		// No customers should be deleted
		$this->assertSame($initialCount, $finalCount);
	}


	/**
	 * Test cleanup deletes multiple expired customers
	 *
	 * @return void
	 * @see \Awyiss\Event\Global\CustomersListener::cleanupUnverifiedCustomers()
	 */
	public function testCleanupUnverifiedCustomersDeletesMultiple(): void {
		Configure::write('Awyiss.Customers.Frontend.registration.deleteUnverifiedAccounts', true);
		Configure::write('Awyiss.Customers.Frontend.registration.verificationCodeValidity', 86400);

		$customersTable = $this->getTableLocator()->get('Customers');

		// Create multiple expired unverified customers
		$expiredCustomers = [];
		for ($i = 1; $i <= 5; $i++) {
			$customer = $customersTable->newDefaultEntity([
				'email' => 'deletes-multiple-expired' . $i . '@example.com',
				'password' => 'password123',
				'passwordConfirm' => 'password123',
				'verified' => false,
				'active' => false,
			]);

			$customer->createdOn = DateTime::now()->subDays(2);

			$expiredCustomers[] = $customer;
		}
		$customersTable->saveMany($expiredCustomers, ['audit' => ['skip' => true]]);

		// Create one recent customer
		$recentCustomer = $customersTable->newDefaultEntity([
			'email' => 'deletes-multiple-recent@example.com',
			'password' => 'password123',
			'passwordConfirm' => 'password123',
			'verified' => false,
			'active' => false,
			'createdOn' => DateTime::now()->subHours(12),
		]);
		$customersTable->saveOrFail($recentCustomer, ['audit' => ['skip' => true]]);

		$this->listener->cleanupUnverifiedCustomers();

		// All expired customers should be deleted
		for ($i = 1; $i <= 5; $i++) {
			$this->assertNull($customersTable->find()->where(['email' => 'deletes-multiple-expired' . $i . '@example.com'])->first());
		}

		// Recent customer should still exist
		$this->assertNotNull($customersTable->find()->where(['email' => 'deletes-multiple-recent@example.com'])->first());
	}
}
