<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Model\Table;


use Awyiss\Model\Entity\UsergroupsUser;
use Awyiss\Model\Table\UsergroupsUsersTable;
use Awyiss\ORM\Association\BelongsTo;
use Awyiss\Test\TestSuite\TestCase;
use Awyiss\Validation\Validator;
use Cake\Datasource\FactoryLocator;


/**
 * UsergroupsUsersTable Test Case
 *
 * @see \Awyiss\Model\Table\UsergroupsUsersTable
 */
class UsergroupsUsersTableTest extends TestCase {
	/**
	 * @var \Awyiss\Model\Table\UsergroupsUsersTable
	 */
	protected UsergroupsUsersTable $usergroupsUsersTable;


	/**
	 * @inheritDoc
	 */
	protected function setUp(): void {
		parent::setUp();

		/** @noinspection PhpFieldAssignmentTypeMismatchInspection */
		$this->usergroupsUsersTable = FactoryLocator::get('Table')->get('UsergroupsUsers');
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\UsergroupsUsersTable::ATTRIBUTABLE
	 */
	public function testAttributableConstant(): void {
		$this->assertFalse($this->usergroupsUsersTable::ATTRIBUTABLE);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\UsergroupsUsersTable::TABLE
	 */
	public function testTableConstant(): void {
		$this->assertEquals('usergroups_users', $this->usergroupsUsersTable::TABLE);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\UsergroupsUsersTable::initializeAssociations()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testInitializeAssociations(): void {
		$this->assertCount(3, $this->usergroupsUsersTable->associations()->keys());

		$this->assertTrue($this->usergroupsUsersTable->hasAssociation('Usergroups'));
		$usergroupsAssociation = $this->usergroupsUsersTable->getAssociation('Usergroups');
		$this->assertInstanceOf(BelongsTo::class, $usergroupsAssociation);
		$this->assertSame('INNER', $usergroupsAssociation->getJoinType());

		$this->assertTrue($this->usergroupsUsersTable->hasAssociation('Users'));
		$usersAssociation = $this->usergroupsUsersTable->getAssociation('Users');
		$this->assertInstanceOf(BelongsTo::class, $usersAssociation);
		$this->assertSame('INNER', $usersAssociation->getJoinType());

		// MediaAssignments is defined, but we don't care about it for this table
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\UsergroupsUsersTable::validationDefault()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testValidationDefault(): void {
		$validator = new Validator();
		$result = $this->usergroupsUsersTable->validationDefault($validator);

		$this->assertInstanceOf(Validator::class, $result);
		$this->assertSame('usergroups_users', $result->getI18nDomain());

		// Test required fields
		$this->assertTrue($result->hasField('id'));
		$this->assertTrue($result->hasField('usergroupId'));
		$this->assertTrue($result->hasField('userId'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\UsergroupsUsersTable::validationDefault()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testEntityValidationSuccess(): void {
		$data = [
			'usergroupId' => 1,
			'userId' => 1,
		];

		$entity = $this->usergroupsUsersTable->newDefaultEntity($data);
		$errors = $entity->getErrors();

		$this->assertEmpty($errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\UsergroupsUsersTable::validationDefault()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testEntityValidationInvalidTypes(): void {
		$data = [
			'id' => 'not_an_integer',
			'usergroupId' => 'not_an_integer',
			'userId' => 'not_an_integer',
		];

		$entity = $this->usergroupsUsersTable->newDefaultEntity();
		$this->usergroupsUsersTable->patchEntity($entity, $data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('id', $errors);
		$this->assertArrayHasKey('usergroupId', $errors);
		$this->assertArrayHasKey('userId', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\UsergroupsUsersTable::validationDefault()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testEntityValidationFieldLength(): void {
		$data = [
			'id' => 123456789123, // exceeds 11 char limit
			'usergroupId' => 123456789123, // exceeds 11 char limit
			'userId' => 123456789123, // exceeds 11 char limit
		];

		$entity = $this->usergroupsUsersTable->newDefaultEntity();
		$this->usergroupsUsersTable->patchEntity($entity, $data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('id', $errors);
		$this->assertArrayHasKey('usergroupId', $errors);
		$this->assertArrayHasKey('userId', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\UsergroupsUsersTable::buildRules()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testBuildRulesValid(): void {
		$data = [
			'usergroupId' => 1, // Existing usergroup
			'userId' => 1, // Existing user
		];

		$entity = $this->usergroupsUsersTable->newDefaultEntity();
		$this->usergroupsUsersTable->patchEntity($entity, $data);

		$result = $this->usergroupsUsersTable->checkRules($entity);
		$this->assertTrue($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\UsergroupsUsersTable::buildRules()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testBuildRulesInvalidUsergroup(): void {
		$data = [
			'usergroupId' => 99999, // Non-existing usergroup
			'userId' => 1, // Existing user
		];

		$entity = $this->usergroupsUsersTable->newDefaultEntity();
		$this->usergroupsUsersTable->patchEntity($entity, $data);

		$result = $this->usergroupsUsersTable->checkRules($entity);

		$this->assertFalse($result);

		$errors = $entity->getErrors();
		$this->assertArrayHasKey('usergroupId', $errors);
		$this->assertArrayHasKey('usergroupExists', $errors['usergroupId']);
		$this->assertSame('usergroups_users::error_usergroup_exists', $errors['usergroupId']['usergroupExists']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\UsergroupsUsersTable::buildRules()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testBuildRulesInvalidUser(): void {
		$data = [
			'usergroupId' => 1, // Existing usergroup
			'userId' => 99999, // Non-existing user
		];

		$entity = $this->usergroupsUsersTable->newDefaultEntity();
		$this->usergroupsUsersTable->patchEntity($entity, $data);

		$result = $this->usergroupsUsersTable->checkRules($entity);

		$this->assertFalse($result);

		$errors = $entity->getErrors();
		$this->assertArrayHasKey('userId', $errors);
		$this->assertArrayHasKey('userExists', $errors['userId']);
		$this->assertSame('usergroups_users::error_user_exists', $errors['userId']['userExists']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\UsergroupsUsersTable::newDefaultEntity()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testNewDefaultEntity(): void {
		/** @var \Awyiss\Model\Entity\UsergroupsUser $entity */
		$entity = $this->usergroupsUsersTable->newDefaultEntity();

		$this->assertInstanceOf(UsergroupsUser::class, $entity);
		$this->assertTrue($entity->isNew());

		// Test default values
		$this->assertNull($entity->usergroupId);
		$this->assertNull($entity->userId);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\UsergroupsUsersTable::newDefaultEntity()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testNewDefaultEntityWithData(): void {
		$additionalData = [
			'usergroupId' => 2,
			'userId' => 3,
		];

		$entity = $this->usergroupsUsersTable->newDefaultEntity($additionalData);

		$this->assertInstanceOf(UsergroupsUser::class, $entity);
		$this->assertTrue($entity->isNew());

		$this->assertSame(2, $entity->usergroupId);
		$this->assertSame(3, $entity->userId);
	}
}
