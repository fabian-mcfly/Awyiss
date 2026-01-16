<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Model\Table;


use Awyiss\Model\Entity\Customer;
use Awyiss\Model\Table\CustomersTable;
use Awyiss\ORM\Association\BelongsTo;
use Awyiss\ORM\Association\BelongsToMany;
use Awyiss\ORM\Association\HasMany;
use Awyiss\Test\TestSuite\TestCase;
use Awyiss\Validation\Validator;
use Cake\Collection\CollectionInterface;
use Cake\Datasource\FactoryLocator;
use Cake\I18n\DateTime;


/**
 * CustomersTable Test Case
 *
 * @see \Awyiss\Model\Table\CustomersTable
 */
class CustomersTableTest extends TestCase {
	/**
	 * @var \Awyiss\Model\Table\CustomersTable
	 */
	protected CustomersTable $customersTable;


	/**
	 * @inheritDoc
	 */
	protected function setUp(): void {
		parent::setUp();

		/** @noinspection PhpFieldAssignmentTypeMismatchInspection */
		$this->customersTable = FactoryLocator::get('Table')->get('Customers');
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\CustomersTable::ATTRIBUTABLE
	 */
	public function testAttributableConstant(): void {
		/** @noinspection PhpUnitAssertTrueWithIncompatibleTypeArgumentInspection */
		$this->assertTrue($this->customersTable::ATTRIBUTABLE);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\CustomersTable::TABLE
	 */
	public function testTableConstant(): void {
		$this->assertEquals('customers', CustomersTable::TABLE);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\CustomersTable::initializeAssociations()
	 */
	public function testInitializeAssociations(): void {
		$this->assertCount(5, $this->customersTable->associations()->keys());

		// Test MediaAssignments association (HasMany)
		$this->assertTrue($this->customersTable->hasAssociation('MediaAssignments'));
		$mediaAssignmentsAssociation = $this->customersTable->getAssociation('MediaAssignments');
		$this->assertInstanceOf(HasMany::class, $mediaAssignmentsAssociation);
		$this->assertTrue($mediaAssignmentsAssociation->getCascadeCallbacks());
		$this->assertTrue($mediaAssignmentsAssociation->getDependent());

		$this->assertTrue($this->customersTable->hasAssociation('CustomerGroups'));
		$customerGroupsAssociation = $this->customersTable->getAssociation('CustomerGroups');
		$this->assertInstanceOf(BelongsToMany::class, $customerGroupsAssociation);
		$this->assertTrue($customerGroupsAssociation->getCascadeCallbacks());
		$this->assertTrue($customerGroupsAssociation->getDependent());

		// 'CreatedByUser' must also exist
		$this->assertTrue($this->customersTable->hasAssociation('CreatedByUser'));
		$createdByUserAssociation = $this->customersTable->getAssociation('CreatedByUser');
		$this->assertInstanceOf(BelongsTo::class, $createdByUserAssociation);
		$this->assertFalse($createdByUserAssociation->getCascadeCallbacks());
		$this->assertFalse($createdByUserAssociation->getDependent());

		// 'ChangedByUser' must also exist
		$this->assertTrue($this->customersTable->hasAssociation('ChangedByUser'));
		$changedByUserAssociation = $this->customersTable->getAssociation('ChangedByUser');
		$this->assertInstanceOf(BelongsTo::class, $changedByUserAssociation);
		$this->assertFalse($changedByUserAssociation->getCascadeCallbacks());
		$this->assertFalse($changedByUserAssociation->getDependent());

		// 'DeletedByUser' must also exist
		$this->assertTrue($this->customersTable->hasAssociation('DeletedByUser'));
		$deletedByUserAssociation = $this->customersTable->getAssociation('DeletedByUser');
		$this->assertInstanceOf(BelongsTo::class, $deletedByUserAssociation);
		$this->assertFalse($deletedByUserAssociation->getCascadeCallbacks());
		$this->assertFalse($deletedByUserAssociation->getDependent());
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\CustomersTable::findActive()
	 * @throws \Exception
	 */
	public function testFindActive(): void {
		$customer5 = $this->customersTable->newDefaultEntity();
		$this->customersTable->patchEntity(
			$customer5,
			[
				'email' => 'failed-logins-in-last-10-minutes@example.com',
				'password' => 'awyiss1234',
				'passwordConfirm' => 'awyiss1234',
				'verified' => 1,
				'active' => 1,
			],
		);

		$customer6 = $this->customersTable->newDefaultEntity();
		$this->customersTable->patchEntity(
			$customer6,
			[
				'email' => 'failed-logins-before-10-minutes@example.com',
				'password' => 'awyiss1234',
				'passwordConfirm' => 'awyiss1234',
				'verified' => 1,
				'active' => 1,
			],
		);

		$this->customersTable->saveMany([$customer5, $customer6], ['audit' => ['skip' => true]]);

		$customer5->lastLogin = new DateTime()->subMinutes(5)->format('Y-m-d H:i:s');
		$customer5->failedAttempts = 5;
		$this->customersTable->save($customer5, ['audit' => ['skip' => true]]);

		$customer6->lastLogin = new DateTime()->subMinutes(40)->format('Y-m-d H:i:s');
		$customer6->failedAttempts = 5;
		$this->customersTable->save($customer6, ['audit' => ['skip' => true]]);

		$all = $this->customersTable->find()->all();

		$this->assertInstanceOf(CollectionInterface::class, $all);
		$this->assertGreaterThanOrEqual(2, $all->count());

		$active = $this->customersTable->find('active')->all();

		$this->assertInstanceOf(CollectionInterface::class, $active);
		$this->assertGreaterThanOrEqual(1, $active->count());
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\CustomersTable::validationDefault()
	 */
	public function testValidationDefault(): void {
		$validator = new Validator();
		$result = $this->customersTable->validationDefault($validator);

		$this->assertInstanceOf(Validator::class, $result);
		$this->assertSame('customers', $result->getI18nDomain());

		// Test required fields
		$this->assertTrue($result->hasField('email'));
		$this->assertSame('create', $result->field('email')->isPresenceRequired());

		// Test other fields
		$this->assertTrue($result->hasField('id'));
		$this->assertTrue($result->hasField('failedAttempts'));
		$this->assertTrue($result->hasField('lastLogin'));
		$this->assertTrue($result->hasField('firstname'));
		$this->assertTrue($result->hasField('lastname'));
		$this->assertTrue($result->hasField('verified'));
		$this->assertTrue($result->hasField('verificationCode'));
		$this->assertTrue($result->hasField('active'));
		$this->assertTrue($result->hasField('deleted'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\CustomersTable::validationDefault()
	 */
	public function testEntityValidationSuccess(): void {
		$data = [
			'email' => 'testcustomer@example.com',
			'password' => 'password123',
			'password_confirm' => 'password123',
			'firstname' => 'Test',
			'lastname' => 'Customer',
			'active' => true,
			'deleted' => false,
			'verified' => false,
			'failedAttempts' => 0,
			'lastLogin' => DateTime::now(),
		];

		$entity = $this->customersTable->newDefaultEntity($data);
		$errors = $entity->getErrors();

		$this->assertEmpty($errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\CustomersTable::validationDefault()
	 */
	public function testEntityValidationMissingRequired(): void {
		$data = [
			'firstname' => 'Test',
		];

		$entity = $this->customersTable->newDefaultEntity();
		$this->customersTable->patchEntity($entity, $data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('email', $errors);
		$this->assertArrayHasKey('_required', $errors['email']);
		$this->assertSame('customers::error_required', $errors['email']['_required']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\CustomersTable::validationDefault()
	 */
	public function testEntityValidationInvalidTypes(): void {
		$data = [
			'id' => 'not_an_integer',
			'email' => true,
			'password' => true,
			'failedAttempts' => 'not_an_integer',
			'lastLogin' => 'not_a_datetime',
			'firstname' => true,
			'lastname' => true,
			'verified' => 'not_a_boolean',
			'verificationCode' => false,
			'active' => 'not_a_boolean',
			'deleted' => 'not_a_boolean',
		];

		$entity = $this->customersTable->newDefaultEntity();
		$this->customersTable->patchEntity($entity, $data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('id', $errors);
		$this->assertArrayHasKey('email', $errors);
		$this->assertArrayHasKey('password', $errors);
		$this->assertArrayHasKey('failedAttempts', $errors);
		$this->assertArrayHasKey('lastLogin', $errors);
		$this->assertArrayHasKey('firstname', $errors);
		$this->assertArrayHasKey('lastname', $errors);
		$this->assertArrayHasKey('verified', $errors);
		$this->assertArrayHasKey('verificationCode', $errors);
		$this->assertArrayHasKey('active', $errors);
		$this->assertArrayHasKey('deleted', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\CustomersTable::validationDefault()
	 */
	public function testEntityValidationFieldLength(): void {
		$data = [
			'id' => 123456789123, // exceeds 11 char limit
			'password' => 'short', // below 8 char minimum
			'firstname' => str_repeat('b', 256), // exceeds 255 char limit
			'lastname' => str_repeat('c', 256), // exceeds 255 char limit
			'failedAttempts' => 12, // exceeds 1 char limit
			'verificationCode' => str_repeat('d', 65), // exceeds 64 char limit
		];

		$entity = $this->customersTable->newDefaultEntity();
		$this->customersTable->patchEntity($entity, $data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('id', $errors);
		$this->assertArrayHasKey('password', $errors);
		$this->assertArrayHasKey('firstname', $errors);
		$this->assertArrayHasKey('lastname', $errors);
		$this->assertArrayHasKey('failedAttempts', $errors);
		$this->assertArrayHasKey('verificationCode', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\CustomersTable::validationDefault()
	 */
	public function testEntityValidationPasswordTooLong(): void {
		$data = [
			'email' => 'testcustomer@example.com',
			'password' => str_repeat('a', 101), // exceeds 100 char limit
			'password_confirm' => str_repeat('a', 101),
		];

		$entity = $this->customersTable->newDefaultEntity();
		$this->customersTable->patchEntity($entity, $data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('password', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\CustomersTable::validationDefault()
	 */
	public function testEntityValidationPasswordMismatch(): void {
		$data = [
			'email' => 'testcustomer@example.com',
			'password' => 'password123',
			'password_confirm' => 'differentpassword',
		];

		$entity = $this->customersTable->newDefaultEntity();
		$this->customersTable->patchEntity($entity, $data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('password', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\CustomersTable::validationDefault()
	 */
	public function testEntityValidationPasswordEmptyOnUpdate(): void {
		// Password should be allowed to be empty on update
		$data = [
			'email' => 'testcustomer@example.com',
			'password' => '',
		];

		$entity = $this->customersTable->newDefaultEntity();
		$entity->setNew(false); // Simulate update
		$this->customersTable->patchEntity($entity, $data);
		$errors = $entity->getErrors();

		$this->assertArrayNotHasKey('password', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\CustomersTable::validationDefault()
	 */
	public function testEntityValidationInvalidEmail(): void {
		$data = [
			'email' => 'invalid-email',
			'password' => 'password123',
			'password_confirm' => 'password123',
		];

		$entity = $this->customersTable->newDefaultEntity();
		$this->customersTable->patchEntity($entity, $data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('email', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\CustomersTable::buildRules()
	 */
	public function testBuildRulesUniqueEmail(): void {
		$data = [
			'email' => 'unique-customer@example.com',
			'password' => 'password123',
			'password_confirm' => 'password123',
		];

		$entity = $this->customersTable->newDefaultEntity();
		$this->customersTable->patchEntity($entity, $data);

		$result = $this->customersTable->checkRules($entity);
		$this->assertTrue($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\CustomersTable::buildRules()
	 */
	public function testBuildRulesExistingEmail(): void {
		// Create first customer
		$data1 = [
			'email' => 'existing@example.com',
			'password' => 'password123',
			'password_confirm' => 'password123',
		];

		$entity1 = $this->customersTable->newDefaultEntity();
		$this->customersTable->patchEntity($entity1, $data1);
		$this->customersTable->saveOrFail($entity1, ['audit' => ['skip' => true]]);

		// Try to create another customer with the same email
		$data2 = [
			'email' => 'existing@example.com',
			'password' => 'password456',
			'password_confirm' => 'password456',
		];

		$entity2 = $this->customersTable->newDefaultEntity();
		$this->customersTable->patchEntity($entity2, $data2);

		$result = $this->customersTable->checkRules($entity2);

		$this->assertFalse($result);

		$errors = $entity2->getErrors();
		$this->assertArrayHasKey('email', $errors);
		$this->assertArrayHasKey('emailUnique', $errors['email']);
		$this->assertSame('customers::error_email_unique', $errors['email']['emailUnique']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\CustomersTable::buildRules()
	 */
	public function testBuildRulesUpdateWithSameEmail(): void {
		// Create a customer
		$data = [
			'email' => 'update-test@example.com',
			'password' => 'password123',
			'password_confirm' => 'password123',
		];

		$entity = $this->customersTable->newDefaultEntity();
		$this->customersTable->patchEntity($entity, $data);
		$this->customersTable->saveOrFail($entity, ['audit' => ['skip' => true]]);

		// Update with the same email should be allowed
		$this->customersTable->patchEntity($entity, ['email' => 'update-test@example.com']);

		$result = $this->customersTable->checkRules($entity);
		$this->assertTrue($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\CustomersTable::newDefaultEntity()
	 */
	public function testNewDefaultEntity(): void {
		/** @var \Awyiss\Model\Entity\Customer $entity */
		$entity = $this->customersTable->newDefaultEntity();

		$this->assertInstanceOf(Customer::class, $entity);
		$this->assertTrue($entity->isNew());

		// Test default values
		$this->assertNull($entity->email);
		$this->assertNull($entity->password);
		$this->assertSame(0, $entity->failedAttempts);
		$this->assertNull($entity->lastLogin);
		$this->assertNull($entity->firstname);
		$this->assertNull($entity->lastname);
		$this->assertFalse($entity->verified);
		$this->assertNull($entity->verifiedOn);
		$this->assertTrue($entity->active);
		$this->assertFalse($entity->deleted);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\CustomersTable::newDefaultEntity()
	 */
	public function testNewDefaultEntityWithData(): void {
		$additionalData = [
			'email' => 'custom@example.com',
			'password' => 'custompassword',
			'firstname' => 'Custom',
			'lastname' => 'Customer',
			'active' => false,
			'verified' => true,
			'verifiedOn' => new DateTime('2023-10-01 12:00:00'),
			'failedAttempts' => 3,
			'lastLogin' => new DateTime('2023-10-01 12:00:00'),
		];

		$entity = $this->customersTable->newDefaultEntity($additionalData);

		$this->assertInstanceOf(Customer::class, $entity);
		$this->assertTrue($entity->isNew());

		$this->assertSame('custom@example.com', $entity->email);
		$this->assertSame('custompassword', $entity->password);
		$this->assertSame('Custom', $entity->firstname);
		$this->assertSame('Customer', $entity->lastname);
		$this->assertFalse($entity->active);
		$this->assertTrue($entity->verified);
		$this->assertSame(3, $entity->failedAttempts);
		$this->assertFalse($entity->deleted);

		$this->assertInstanceOf(DateTime::class, $entity->verifiedOn);
		$this->assertSame('2023-10-01 12:00:00', $entity->verifiedOn->format('Y-m-d H:i:s'));

		$this->assertInstanceOf(DateTime::class, $entity->lastLogin);
		$this->assertSame('2023-10-01 12:00:00', $entity->lastLogin->format('Y-m-d H:i:s'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\CustomersTable::$categories
	 */
	public function testCategoriesBehavior(): void {
		$this->assertTrue($this->customersTable->hasBehavior('Categories'));

		$config = $this->customersTable->getBehavior('Categories')->getConfig();

		$this->assertTrue($config['allowUnassigned']);
		$this->assertTrue($config['enabled']);
		$this->assertSame('CustomerGroups', $config['associationName']);
		$this->assertSame('customerGroup', $config['identifier']);
	}

	/**
	 * @return void
	 * @see \Awyiss\Model\Table\CustomersTable::validationRegistration()
	 */
	public function testValidationRegistration(): void {
		$validator = new Validator();
		$result = $this->customersTable->validationRegistration($validator);

		$this->assertInstanceOf(Validator::class, $result);
		$this->assertSame('customers', $result->getI18nDomain());

		// Test required fields for registration
		$this->assertTrue($result->hasField('email'));
		$this->assertTrue($result->hasField('password'));
		$this->assertTrue($result->hasField('password_confirm'));

		// Test other fields exist
		$this->assertTrue($result->hasField('id'));
		$this->assertTrue($result->hasField('firstname'));
		$this->assertTrue($result->hasField('lastname'));
		$this->assertTrue($result->hasField('verified'));
		$this->assertTrue($result->hasField('verificationCode'));
		$this->assertTrue($result->hasField('verifiedOn'));
		$this->assertTrue($result->hasField('passwordResetCode'));
		$this->assertTrue($result->hasField('passwordResetOn'));
		$this->assertTrue($result->hasField('active'));
		$this->assertTrue($result->hasField('deleted'));
	}

	/**
	 * @return void
	 * @see \Awyiss\Model\Table\CustomersTable::validationRegistration()
	 */
	public function testRegistrationValidationSuccess(): void {
		$data = [
			'email' => 'registration@example.com',
			'password' => 'password123',
			'password_confirm' => 'password123',
			'firstname' => 'Test',
			'lastname' => 'User',
		];

		$entity = $this->customersTable->newDefaultEntity();
		$this->customersTable->patchEntity($entity, $data, [
			'validate' => 'registration',
		]);
		$errors = $entity->getErrors();

		$this->assertEmpty($errors);
	}

	/**
	 * @return void
	 * @see \Awyiss\Model\Table\CustomersTable::validationRegistration()
	 */
	public function testRegistrationValidationMissingEmail(): void {
		$data = [
			'password' => 'password123',
			'password_confirm' => 'password123',
		];

		$entity = $this->customersTable->newDefaultEntity();
		$this->customersTable->patchEntity($entity, $data, [
			'validate' => 'registration',
		]);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('email', $errors);
		$this->assertArrayHasKey('_required', $errors['email']);
		$this->assertSame('customers::error_required', $errors['email']['_required']);
	}

	/**
	 * @return void
	 * @see \Awyiss\Model\Table\CustomersTable::validationRegistration()
	 */
	public function testRegistrationValidationMissingPassword(): void {
		$data = [
			'email' => 'registration@example.com',
			'password_confirm' => 'password123',
		];

		$entity = $this->customersTable->newDefaultEntity();
		$this->customersTable->patchEntity($entity, $data, [
			'validate' => 'registration',
		]);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('password', $errors);
		$this->assertArrayHasKey('_required', $errors['password']);
		$this->assertSame('customers::error_required', $errors['password']['_required']);
	}

	/**
	 * @return void
	 * @see \Awyiss\Model\Table\CustomersTable::validationRegistration()
	 */
	public function testRegistrationValidationMissingPasswordConfirm(): void {
		$data = [
			'email' => 'registration@example.com',
			'password' => 'password123',
		];

		$entity = $this->customersTable->newDefaultEntity();
		$this->customersTable->patchEntity($entity, $data, [
			'validate' => 'registration',
		]);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('password_confirm', $errors);
		$this->assertArrayHasKey('_required', $errors['password_confirm']);
		$this->assertSame('customers::error_required', $errors['password_confirm']['_required']);
	}

	/**
	 * @return void
	 * @see \Awyiss\Model\Table\CustomersTable::validationRegistration()
	 */
	public function testRegistrationValidationEmptyEmail(): void {
		$data = [
			'email' => '',
			'password' => 'password123',
			'password_confirm' => 'password123',
		];

		$entity = $this->customersTable->newDefaultEntity();
		$this->customersTable->patchEntity($entity, $data, [
			'validate' => 'registration',
		]);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('email', $errors);
	}

	/**
	 * @return void
	 * @see \Awyiss\Model\Table\CustomersTable::validationRegistration()
	 */
	public function testRegistrationValidationEmptyPassword(): void {
		$data = [
			'email' => 'registration@example.com',
			'password' => '',
			'password_confirm' => '',
		];

		$entity = $this->customersTable->newDefaultEntity();
		$this->customersTable->patchEntity($entity, $data, [
			'validate' => 'registration',
		]);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('password', $errors);
	}

	/**
	 * @return void
	 * @see \Awyiss\Model\Table\CustomersTable::validationRegistration()
	 */
	public function testRegistrationValidationInvalidEmail(): void {
		$data = [
			'email' => 'invalid-email',
			'password' => 'password123',
			'password_confirm' => 'password123',
		];

		$entity = $this->customersTable->newDefaultEntity();
		$this->customersTable->patchEntity($entity, $data, [
			'validate' => 'registration',
		]);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('email', $errors);
		$this->assertArrayHasKey('email', $errors['email']);
	}

	/**
	 * @return void
	 * @see \Awyiss\Model\Table\CustomersTable::validationRegistration()
	 */
	public function testRegistrationValidationPasswordTooShort(): void {
		$data = [
			'email' => 'registration@example.com',
			'password' => 'short',
			'password_confirm' => 'short',
		];

		$entity = $this->customersTable->newDefaultEntity();
		$this->customersTable->patchEntity($entity, $data, [
			'validate' => 'registration',
		]);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('password', $errors);
		$this->assertArrayHasKey('minLength', $errors['password']);
	}

	/**
	 * @return void
	 * @see \Awyiss\Model\Table\CustomersTable::validationRegistration()
	 */
	public function testRegistrationValidationPasswordTooLong(): void {
		$data = [
			'email' => 'registration@example.com',
			'password' => str_repeat('a', 101),
			'password_confirm' => str_repeat('a', 101),
		];

		$entity = $this->customersTable->newDefaultEntity();
		$this->customersTable->patchEntity($entity, $data, [
			'validate' => 'registration',
		]);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('password', $errors);
		$this->assertArrayHasKey('maxLength', $errors['password']);
	}

	/**
	 * @return void
	 * @see \Awyiss\Model\Table\CustomersTable::validationRegistration()
	 */
	public function testRegistrationValidationPasswordMismatch(): void {
		$data = [
			'email' => 'registration@example.com',
			'password' => 'password123',
			'password_confirm' => 'differentpassword',
		];

		$entity = $this->customersTable->newDefaultEntity();
		$this->customersTable->patchEntity($entity, $data, [
			'validate' => 'registration',
		]);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('password', $errors);
		$this->assertArrayHasKey('compareWith', $errors['password']);
	}

	/**
	 * @return void
	 * @see \Awyiss\Model\Table\CustomersTable::validationRegistration()
	 */
	public function testRegistrationValidationEmailTooLong(): void {
		$data = [
			'email' => str_repeat('a', 245) . '@example.com',
			'password' => 'password123',
			'password_confirm' => 'password123',
		];

		$entity = $this->customersTable->newDefaultEntity();
		$this->customersTable->patchEntity($entity, $data, [
			'validate' => 'registration',
		]);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('email', $errors);
		$this->assertArrayHasKey('maxLength', $errors['email']);
	}

	/**
	 * @return void
	 * @see \Awyiss\Model\Table\CustomersTable::validationRegistration()
	 */
	public function testRegistrationValidationFirstnameTooLong(): void {
		$data = [
			'email' => 'registration@example.com',
			'password' => 'password123',
			'password_confirm' => 'password123',
			'firstname' => str_repeat('a', 256),
		];

		$entity = $this->customersTable->newDefaultEntity();
		$this->customersTable->patchEntity($entity, $data, [
			'validate' => 'registration',
		]);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('firstname', $errors);
		$this->assertArrayHasKey('maxLength', $errors['firstname']);
	}

	/**
	 * @return void
	 * @see \Awyiss\Model\Table\CustomersTable::validationRegistration()
	 */
	public function testRegistrationValidationLastnameTooLong(): void {
		$data = [
			'email' => 'registration@example.com',
			'password' => 'password123',
			'password_confirm' => 'password123',
			'lastname' => str_repeat('a', 256),
		];

		$entity = $this->customersTable->newDefaultEntity();
		$this->customersTable->patchEntity($entity, $data, [
			'validate' => 'registration',
		]);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('lastname', $errors);
		$this->assertArrayHasKey('maxLength', $errors['lastname']);
	}

	/**
	 * @return void
	 * @see \Awyiss\Model\Table\CustomersTable::validationRegistration()
	 */
	public function testRegistrationValidationOptionalFirstnameLastname(): void {
		$data = [
			'email' => 'registration@example.com',
			'password' => 'password123',
			'password_confirm' => 'password123',
			'firstname' => '',
			'lastname' => '',
		];

		$entity = $this->customersTable->newDefaultEntity();
		$this->customersTable->patchEntity($entity, $data, [
			'validate' => 'registration',
		]);
		$errors = $entity->getErrors();

		$this->assertArrayNotHasKey('firstname', $errors);
		$this->assertArrayNotHasKey('lastname', $errors);
	}

	/**
	 * @return void
	 * @see \Awyiss\Model\Table\CustomersTable::validationRegistration()
	 */
	public function testRegistrationValidationVerificationCodeLength(): void {
		$data = [
			'email' => 'registration@example.com',
			'password' => 'password123',
			'password_confirm' => 'password123',
			'verificationCode' => str_repeat('a', 65),
		];

		$entity = $this->customersTable->newDefaultEntity();
		$this->customersTable->patchEntity($entity, $data, [
			'validate' => 'registration',
		]);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('verificationCode', $errors);
		$this->assertArrayHasKey('maxLength', $errors['verificationCode']);
	}

	/**
	 * @return void
	 * @see \Awyiss\Model\Table\CustomersTable::validationRegistration()
	 */
	public function testRegistrationValidationPasswordResetCodeLength(): void {
		$data = [
			'email' => 'registration@example.com',
			'password' => 'password123',
			'password_confirm' => 'password123',
			'passwordResetCode' => str_repeat('a', 65),
		];

		$entity = $this->customersTable->newDefaultEntity();
		$this->customersTable->patchEntity($entity, $data, [
			'validate' => 'registration',
		]);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('passwordResetCode', $errors);
		$this->assertArrayHasKey('maxLength', $errors['passwordResetCode']);
	}

	/**
	 * @return void
	 * @see \Awyiss\Model\Table\CustomersTable::validationRegistration()
	 */
	public function testRegistrationValidationInvalidVerifiedOn(): void {
		$data = [
			'email' => 'registration@example.com',
			'password' => 'password123',
			'passwordConfirm' => 'password123',
			'verifiedOn' => 'not_a_datetime',
		];

		$entity = $this->customersTable->newDefaultEntity();
		$this->customersTable->patchEntity($entity, $data, [
			'validate' => 'registration',
		]);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('verifiedOn', $errors);
		$this->assertArrayHasKey('dateTime', $errors['verifiedOn']);
	}

	/**
	 * @return void
	 * @see \Awyiss\Model\Table\CustomersTable::validationRegistration()
	 */
	public function testRegistrationValidationInvalidPasswordResetOn(): void {
		$data = [
			'email' => 'registration@example.com',
			'password' => 'password123',
			'password_confirm' => 'password123',
			'passwordResetOn' => 'not_a_datetime',
		];

		$entity = $this->customersTable->newDefaultEntity();
		$this->customersTable->patchEntity($entity, $data, [
			'validate' => 'registration',
		]);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('passwordResetOn', $errors);
		$this->assertArrayHasKey('dateTime', $errors['passwordResetOn']);
	}

	/**
	 * @return void
	 * @see \Awyiss\Model\Table\CustomersTable::validationRegistration()
	 */
	public function testRegistrationValidationInvalidTypeBoolean(): void {
		$data = [
			'email' => 'registration@example.com',
			'password' => 'password123',
			'password_confirm' => 'password123',
			'verified' => 'not_a_boolean',
			'active' => 'not_a_boolean',
			'deleted' => 'not_a_boolean',
		];

		$entity = $this->customersTable->newDefaultEntity();
		$this->customersTable->patchEntity($entity, $data, [
			'validate' => 'registration',
		]);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('verified', $errors);
		$this->assertArrayHasKey('active', $errors);
		$this->assertArrayHasKey('deleted', $errors);
	}
}
