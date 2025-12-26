<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Model\Table;


use Awyiss\Model\Entity\User;
use Awyiss\Model\Table\UsersTable;
use Awyiss\ORM\Association\BelongsTo;
use Awyiss\ORM\Association\BelongsToMany;
use Awyiss\ORM\Association\HasMany;
use Awyiss\ORM\Association\HasOne;
use Awyiss\Test\TestSuite\TestCase;
use Awyiss\Validation\Validator;
use Cake\Collection\CollectionInterface;
use Cake\Datasource\FactoryLocator;
use Cake\I18n\DateTime;


/**
 * UsersTable Test Case
 *
 * @see \Awyiss\Model\Table\UsersTable
 */
class UsersTableTest extends TestCase {
	/**
	 * @var \Awyiss\Model\Table\UsersTable
	 */
	protected UsersTable $usersTable;


	/**
	 * @inheritDoc
	 */
	protected function setUp(): void {
		parent::setUp();

		/** @noinspection PhpFieldAssignmentTypeMismatchInspection */
		$this->usersTable = FactoryLocator::get('Table')->get('Users');
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\UsergroupsUsersTable::ATTRIBUTABLE
	 */
	public function testAttributableConstant(): void {
		/** @noinspection PhpUnitAssertTrueWithIncompatibleTypeArgumentInspection */
		$this->assertTrue($this->usersTable::ATTRIBUTABLE);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\UsersTable::TABLE
	 */
	public function testTableConstant(): void {
		$this->assertEquals('users', UsersTable::TABLE);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\UsersTable::initializeAssociations()
	 */
	public function testInitializeAssociations(): void {
		$this->assertCount(8, $this->usersTable->associations()->keys());

		$this->assertTrue($this->usersTable->hasAssociation('Usergroups'));
		$usergroupsAssociation = $this->usersTable->getAssociation('Usergroups');
		$this->assertInstanceOf(BelongsToMany::class, $usergroupsAssociation);
		$this->assertFalse($usergroupsAssociation->getCascadeCallbacks());
		$this->assertTrue($usergroupsAssociation->getDependent());

		$this->assertTrue($this->usersTable->hasAssociation('UserConfiguration'));
		$userConfigurationAssociation = $this->usersTable->getAssociation('UserConfiguration');
		$this->assertInstanceOf(HasMany::class, $userConfigurationAssociation);
		$this->assertTrue($userConfigurationAssociation->getCascadeCallbacks());
		$this->assertTrue($userConfigurationAssociation->getDependent());

		// 'CustomerGroupAccessSettings' must also exist
		$this->assertTrue($this->usersTable->hasAssociation('CustomerGroupAccessSettings'));
		$customerGroupAccessSettingsAssociation = $this->usersTable->getAssociation('CustomerGroupAccessSettings');
		$this->assertInstanceOf(HasOne::class, $customerGroupAccessSettingsAssociation);
		$this->assertTrue($customerGroupAccessSettingsAssociation->getCascadeCallbacks());
		$this->assertTrue($customerGroupAccessSettingsAssociation->getDependent());

		// 'CustomerGroupAssignments' must also exist
		$this->assertTrue($this->usersTable->hasAssociation('CustomerGroupAssignments'));
		$customerGroupAssignmentsAssociation = $this->usersTable->getAssociation('CustomerGroupAssignments');
		$this->assertInstanceOf(HasMany::class, $customerGroupAssignmentsAssociation);
		$this->assertTrue($customerGroupAssignmentsAssociation->getCascadeCallbacks());
		$this->assertTrue($customerGroupAssignmentsAssociation->getDependent());

		// 'MediaAssignments' must also exist
		$this->assertTrue($this->usersTable->hasAssociation('MediaAssignments'));
		$mediaAssignmentsAssociation = $this->usersTable->getAssociation('MediaAssignments');
		$this->assertInstanceOf(HasMany::class, $mediaAssignmentsAssociation);
		$this->assertTrue($mediaAssignmentsAssociation->getCascadeCallbacks());
		$this->assertTrue($mediaAssignmentsAssociation->getDependent());

		// 'CreatedByUser' must also exist
		$this->assertTrue($this->usersTable->hasAssociation('CreatedByUser'));
		$createdByUserAssociation = $this->usersTable->getAssociation('CreatedByUser');
		$this->assertInstanceOf(BelongsTo::class, $createdByUserAssociation);
		$this->assertFalse($createdByUserAssociation->getCascadeCallbacks());
		$this->assertFalse($createdByUserAssociation->getDependent());

		// 'ChangedByUser' must also exist
		$this->assertTrue($this->usersTable->hasAssociation('ChangedByUser'));
		$changedByUserAssociation = $this->usersTable->getAssociation('ChangedByUser');
		$this->assertInstanceOf(BelongsTo::class, $changedByUserAssociation);
		$this->assertFalse($changedByUserAssociation->getCascadeCallbacks());
		$this->assertFalse($changedByUserAssociation->getDependent());

		// 'DeletedByUser' must also exist
		$this->assertTrue($this->usersTable->hasAssociation('DeletedByUser'));
		$deletedByUserAssociation = $this->usersTable->getAssociation('DeletedByUser');
		$this->assertInstanceOf(BelongsTo::class, $deletedByUserAssociation);
		$this->assertFalse($deletedByUserAssociation->getCascadeCallbacks());
		$this->assertFalse($deletedByUserAssociation->getDependent());
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\UsersTable::findActive()
	 * @throws \Exception
	 */
	public function testFindActive(): void {
		$user5 = $this->usersTable->newDefaultEntity();
		$this->usersTable->patchEntity(
			$user5,
			[
				'username' => 'awyiss-failed-logins-in-last-10-minutes',
				'password' => 'awyiss1234',
				'passwordConfirm' => 'awyiss1234',
				'active' => 1,
			],
		);

		$user6 = $this->usersTable->newDefaultEntity();
		$this->usersTable->patchEntity(
			$user6,
			[
				'username' => 'awyiss-failed-logins-before-10-minutes',
				'password' => 'awyiss1234',
				'passwordConfirm' => 'awyiss1234',
				'active' => 1,
			],
		);

		$this->usersTable->saveMany([$user5, $user6], ['audit' => ['skip' => true]]);

		$user5->lastLogin = new DateTime()->subMinutes(5)->format('Y-m-d H:i:s');
		$user5->failedAttempts = 5;
		$this->usersTable->save($user5, ['audit' => ['skip' => true]]);

		$user6->lastLogin = new DateTime()->subMinutes(40)->format('Y-m-d H:i:s');
		$user6->failedAttempts = 5;
		$this->usersTable->save($user6, ['audit' => ['skip' => true]]);

		$all = $this->usersTable->find()->all();

		$this->assertInstanceOf(CollectionInterface::class, $all);
		$this->assertCount(6, $all);

		$active = $this->usersTable->find('active')->all();

		$this->assertInstanceOf(CollectionInterface::class, $active);
		$this->assertCount(4, $active);

		$userNames = $active->extract('username')->toArray();
		$this->assertSame([
			'awyiss',
			'awyiss-undecided-access',
			'awyiss-no-access',
			'awyiss-failed-logins-before-10-minutes',
		], $userNames);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\UsersTable::validationDefault()
	 */
	public function testValidationDefault(): void {
		$validator = new Validator();
		$result = $this->usersTable->validationDefault($validator);

		$this->assertInstanceOf(Validator::class, $result);
		$this->assertSame('users', $result->getI18nDomain());

		// Test required fields
		$this->assertTrue($result->hasField('username'));
		$this->assertSame('create', $result->field('username')->isPresenceRequired());

		$this->assertTrue($result->hasField('password'));
		$this->assertSame('create', $result->field('password')->isPresenceRequired());

		// Test other fields
		$this->assertTrue($result->hasField('id'));
		$this->assertTrue($result->hasField('failedAttempts'));
		$this->assertTrue($result->hasField('lastLogin'));
		$this->assertTrue($result->hasField('firstname'));
		$this->assertTrue($result->hasField('lastname'));
		$this->assertTrue($result->hasField('email'));
		$this->assertTrue($result->hasField('active'));
		$this->assertTrue($result->hasField('deleted'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\UsersTable::validationDefault()
	 */
	public function testEntityValidationSuccess(): void {
		$data = [
			'username' => 'testuser',
			'password' => 'password123',
			'password_confirm' => 'password123',
			'firstname' => 'Test',
			'lastname' => 'User',
			'email' => 'test@example.com',
			'active' => true,
			'deleted' => false,
			'failedAttempts' => 0,
			'lastLogin' => DateTime::now(),
		];

		$entity = $this->usersTable->newDefaultEntity($data);
		$errors = $entity->getErrors();

		$this->assertEmpty($errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\UsersTable::validationDefault()
	 */
	public function testEntityValidationMissingRequired(): void {
		$data = [
			'firstname' => 'Test',
		];

		$entity = $this->usersTable->newDefaultEntity();
		$this->usersTable->patchEntity($entity, $data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('username', $errors);
		$this->assertArrayHasKey('_required', $errors['username']);
		$this->assertSame('users::error_required', $errors['username']['_required']);

		$this->assertArrayHasKey('password', $errors);
		$this->assertArrayHasKey('_required', $errors['password']);
		$this->assertSame('users::error_required', $errors['password']['_required']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\UsersTable::validationDefault()
	 */
	public function testEntityValidationInvalidTypes(): void {
		$data = [
			'id' => 'not_an_integer',
			'username' => true,
			'password' => true,
			'failedAttempts' => 'not_an_integer',
			'lastLogin' => 'not_a_datetime',
			'firstname' => true,
			'lastname' => true,
			'email' => true,
			'active' => 'not_a_boolean',
			'deleted' => 'not_a_boolean',
		];

		$entity = $this->usersTable->newDefaultEntity();
		$this->usersTable->patchEntity($entity, $data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('id', $errors);
		$this->assertArrayHasKey('username', $errors);
		$this->assertArrayHasKey('password', $errors);
		$this->assertArrayHasKey('failedAttempts', $errors);
		$this->assertArrayHasKey('lastLogin', $errors);
		$this->assertArrayHasKey('firstname', $errors);
		$this->assertArrayHasKey('lastname', $errors);
		$this->assertArrayHasKey('email', $errors);
		$this->assertArrayHasKey('active', $errors);
		$this->assertArrayHasKey('deleted', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\UsersTable::validationDefault()
	 */
	public function testEntityValidationFieldLength(): void {
		$data = [
			'id' => 123456789123, // exceeds 11 char limit
			'username' => str_repeat('a', 51), // exceeds 50 char limit
			'password' => 'short', // below 8 char minimum
			'firstname' => str_repeat('b', 51), // exceeds 50 char limit
			'lastname' => str_repeat('c', 51), // exceeds 50 char limit
			'email' => str_repeat('d', 51), // exceeds 50 char limit
			'failedAttempts' => 12, // exceeds 1 char limit
		];

		$entity = $this->usersTable->newDefaultEntity();
		$this->usersTable->patchEntity($entity, $data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('id', $errors);
		$this->assertArrayHasKey('username', $errors);
		$this->assertArrayHasKey('password', $errors);
		$this->assertArrayHasKey('firstname', $errors);
		$this->assertArrayHasKey('lastname', $errors);
		$this->assertArrayHasKey('email', $errors);
		$this->assertArrayHasKey('failedAttempts', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\UsersTable::validationDefault()
	 */
	public function testEntityValidationPasswordTooLong(): void {
		$data = [
			'username' => 'testuser',
			'password' => str_repeat('a', 101), // exceeds 100 char limit
			'password_confirm' => str_repeat('a', 101),
		];

		$entity = $this->usersTable->newDefaultEntity();
		$this->usersTable->patchEntity($entity, $data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('password', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\UsersTable::validationDefault()
	 */
	public function testEntityValidationPasswordMismatch(): void {
		$data = [
			'username' => 'testuser',
			'password' => 'password123',
			'password_confirm' => 'differentpassword',
		];

		$entity = $this->usersTable->newDefaultEntity();
		$this->usersTable->patchEntity($entity, $data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('password', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\UsersTable::validationDefault()
	 */
	public function testEntityValidationPasswordEmptyOnUpdate(): void {
		// Password should be allowed to be empty on update
		$data = [
			'username' => 'testuser',
			'password' => '',
		];

		$entity = $this->usersTable->newDefaultEntity();
		$entity->setNew(false); // Simulate update
		$this->usersTable->patchEntity($entity, $data);
		$errors = $entity->getErrors();

		$this->assertArrayNotHasKey('password', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\UsersTable::validationDefault()
	 */
	public function testEntityValidationInvalidEmail(): void {
		$data = [
			'username' => 'testuser',
			'password' => 'password123',
			'password_confirm' => 'password123',
			'email' => 'invalid-email',
		];

		$entity = $this->usersTable->newDefaultEntity();
		$this->usersTable->patchEntity($entity, $data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('email', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\UsersTable::buildRules()
	 */
	public function testBuildRulesUniqueUsername(): void {
		$data = [
			'username' => 'uniqueuser',
			'password' => 'password123',
			'password_confirm' => 'password123',
		];

		$entity = $this->usersTable->newDefaultEntity();
		$this->usersTable->patchEntity($entity, $data);

		$result = $this->usersTable->checkRules($entity);
		$this->assertTrue($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\UsersTable::buildRules()
	 */
	public function testBuildRulesExistingUsername(): void {
		// Try to create another user with the same username, even if the existing user is inactive
		$data = [
			'username' => 'awyiss-inactive',
			'password' => 'password456',
			'password_confirm' => 'password456',
		];

		$entity = $this->usersTable->newDefaultEntity();
		$this->usersTable->patchEntity($entity, $data);

		$result = $this->usersTable->checkRules($entity);

		$this->assertFalse($result);

		$errors = $entity->getErrors();
		$this->assertArrayHasKey('username', $errors);
		$this->assertArrayHasKey('usernameUnique', $errors['username']);
		$this->assertSame('users::error_username_unique', $errors['username']['usernameUnique']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\UsersTable::buildRules()
	 */
	public function testBuildRulesUniqueEmail(): void {
		// First create a user with an email
		$data = [
			'username' => 'user1',
			'password' => 'password123',
			'password_confirm' => 'password123',
			'email' => 'unique@example.com',
		];

		$entity = $this->usersTable->newDefaultEntity();
		$this->usersTable->patchEntity($entity, $data);

		$result = $this->usersTable->checkRules($entity);
		$this->assertTrue($result);
	}

	/**
	 * @return void
	 * @see \Awyiss\Model\Table\UsersTable::buildRules()
	 */
	public function testBuildRulesExistingEmail(): void {
		// Try to create another user with the same email
		$data = [
			'username' => 'user2',
			'password' => 'password456',
			'password_confirm' => 'password456',
			'email' => 'hello@2f.media',
		];

		$entity = $this->usersTable->newDefaultEntity();
		$this->usersTable->patchEntity($entity, $data);

		$result = $this->usersTable->checkRules($entity);

		$this->assertFalse($result);

		$errors = $entity->getErrors();
		$this->assertArrayHasKey('email', $errors);
		$this->assertArrayHasKey('emailUnique', $errors['email']);
		$this->assertSame('users::error_email_unique', $errors['email']['emailUnique']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\UsersTable::buildRules()
	 */
	public function testBuildRulesUpdateWithSameTitle(): void {
		// Get an existing user
		$existingUser = $this->usersTable->find()->where(['email' => 'hello@2f.media'])->first();
		$this->assertNotNull($existingUser);

		// Update with the same title should be allowed
		$this->usersTable->patchEntity($existingUser, ['email' => 'hello@2f.media']);

		$result = $this->usersTable->checkRules($existingUser);
		$this->assertTrue($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\UsersTable::buildRules()
	 */
	public function testBuildRulesMultipleNullEmails(): void {
		// Should allow multiple users with null emails
		$data = [
			'username' => 'user1',
			'password' => 'password123',
			'password_confirm' => 'password123',
			'email' => null,
		];

		$entity = $this->usersTable->newDefaultEntity();
		$this->usersTable->patchEntity($entity, $data);

		$result = $this->usersTable->checkRules($entity);

		$this->assertTrue($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\UsersTable::newDefaultEntity()
	 */
	public function testNewDefaultEntity(): void {
		/** @var \Awyiss\Model\Entity\User $entity */
		$entity = $this->usersTable->newDefaultEntity();

		$this->assertInstanceOf(User::class, $entity);
		$this->assertTrue($entity->isNew());

		// Test default values
		$this->assertNull($entity->username);
		$this->assertNull($entity->password);
		$this->assertSame(0, $entity->failedAttempts);
		$this->assertNull($entity->lastLogin);
		$this->assertNull($entity->firstname);
		$this->assertNull($entity->lastname);
		$this->assertNull($entity->email);
		$this->assertTrue($entity->active);
		$this->assertFalse($entity->deleted);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\UsersTable::newDefaultEntity()
	 */
	public function testNewDefaultEntityWithData(): void {
		$additionalData = [
			'username' => 'customuser',
			'password' => 'custompassword',
			'firstname' => 'Custom',
			'lastname' => 'User',
			'email' => 'custom@example.com',
			'active' => false,
			'failedAttempts' => 3,
			'lastLogin' => new DateTime('2023-10-01 12:00:00'),
		];

		$entity = $this->usersTable->newDefaultEntity($additionalData);

		$this->assertInstanceOf(User::class, $entity);
		$this->assertTrue($entity->isNew());

		$this->assertSame('customuser', $entity->username);
		$this->assertSame('custompassword', $entity->password);
		$this->assertSame('Custom', $entity->firstname);
		$this->assertSame('User', $entity->lastname);
		$this->assertSame('custom@example.com', $entity->email);
		$this->assertFalse($entity->active);
		$this->assertSame(3, $entity->failedAttempts);
		$this->assertFalse($entity->deleted);

		$this->assertInstanceOf(DateTime::class, $entity->lastLogin);
		$this->assertSame('2023-10-01 12:00:00', $entity->lastLogin->format('Y-m-d H:i:s'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\UsersTable::$categories
	 */
	public function testCategoriesBehavior(): void {
		$this->assertTrue($this->usersTable->hasBehavior('Categories'));

		$config = $this->usersTable->getBehavior('Categories')->getConfig();

		$this->assertTrue($config['allowAggregation']);
		$this->assertTrue($config['allowUnassigned']);
		$this->assertTrue($config['enabled']);
		$this->assertSame('Usergroups', $config['associationName']);
		$this->assertSame('usergroup', $config['identifier']);
	}
}
