<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Model\Table;


use Awyiss\Authorization\Permission\PermissionAccess;
use Awyiss\Model\Entity\UsergroupPermission;
use Awyiss\Model\Table\UsergroupPermissionsTable;
use Awyiss\ORM\Association\BelongsTo;
use Awyiss\Test\TestSuite\TestCase;
use Awyiss\Validation\Validator;
use Cake\Datasource\FactoryLocator;


/**
 * UsergroupPermissionsTable Test Case
 *
 * @see \Awyiss\Model\Table\UsergroupPermissionsTable
 */
class UsergroupPermissionsTableTest extends TestCase {
	/**
	 * @var \Awyiss\Model\Table\UsergroupPermissionsTable
	 */
	protected UsergroupPermissionsTable $usergroupPermissionsTable;


	/**
	 * @inheritDoc
	 */
	protected function setUp(): void {
		parent::setUp();

		/** @noinspection PhpFieldAssignmentTypeMismatchInspection */
		$this->usergroupPermissionsTable = FactoryLocator::get('Table')->get('UsergroupPermissions');
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\UsergroupPermissionsTable::ATTRIBUTABLE
	 */
	public function testAttributableConstant(): void {
		$this->assertFalse($this->usergroupPermissionsTable::ATTRIBUTABLE);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\UsergroupPermissionsTable::TABLE
	 */
	public function testTableConstant(): void {
		$this->assertEquals('usergroup_permissions', $this->usergroupPermissionsTable::TABLE);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\UsergroupPermissionsTable::initializeAssociations()
	 */
	public function testInitializeAssociations(): void {
		$this->assertCount(2, $this->usergroupPermissionsTable->associations()->keys());

		$this->assertTrue($this->usergroupPermissionsTable->hasAssociation('Usergroups'));
		$usergroupsAssociation = $this->usergroupPermissionsTable->getAssociation('Usergroups');
		$this->assertInstanceOf(BelongsTo::class, $usergroupsAssociation);
		$this->assertSame('INNER', $usergroupsAssociation->getJoinType());

		// MediaAssignments is also defined, but we don't care about it for this table
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\UsergroupPermissionsTable::validationDefault()
	 */
	public function testValidationDefault(): void {
		$validator = new Validator();
		$result = $this->usergroupPermissionsTable->validationDefault($validator);

		$this->assertInstanceOf(Validator::class, $result);
		$this->assertSame('UsergroupPermissions', $result->getI18nDomain());

		// Test required fields
		$this->assertTrue($result->hasField('scope'));
		$this->assertSame('create', $result->field('scope')->isPresenceRequired());

		$this->assertTrue($result->hasField('identifier'));
		$this->assertSame('create', $result->field('identifier')->isPresenceRequired());

		// Test other fields
		$this->assertTrue($result->hasField('id'));
		$this->assertTrue($result->hasField('usergroupId'));
		$this->assertTrue($result->hasField('access'));
		$this->assertTrue($result->hasField('settings'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\UsergroupPermissionsTable::validationDefault()
	 */
	public function testEntityValidationSuccess(): void {
		$data = [
			'usergroupId' => 1,
			'scope' => 'TestScope',
			'identifier' => 'testPermission',
			'access' => PermissionAccess::Granted,
			'settings' => ['key' => 'value'],
		];

		$entity = $this->usergroupPermissionsTable->newDefaultEntity($data);
		$errors = $entity->getErrors();

		$this->assertEmpty($errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\UsergroupPermissionsTable::validationDefault()
	 */
	public function testEntityValidationMissingRequired(): void {
		$data = [
			'usergroupId' => 1,
		];

		$entity = $this->usergroupPermissionsTable->newDefaultEntity();
		$this->usergroupPermissionsTable->patchEntity($entity, $data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('scope', $errors);
		$this->assertArrayHasKey('_required', $errors['scope']);
		$this->assertSame('UsergroupPermissions::error_required', $errors['scope']['_required']);

		$this->assertArrayHasKey('identifier', $errors);
		$this->assertArrayHasKey('_required', $errors['identifier']);
		$this->assertSame('UsergroupPermissions::error_required', $errors['identifier']['_required']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\UsergroupPermissionsTable::validationDefault()
	 */
	public function testEntityValidationInvalidTypes(): void {
		$data = [
			'id' => 'not_an_integer',
			'usergroupId' => 'not_an_integer',
			'scope' => true,
			'identifier' => true,
			'access' => 'not_an_integer',
			'settings' => 'not_an_array',
		];

		$entity = $this->usergroupPermissionsTable->newDefaultEntity();
		$this->usergroupPermissionsTable->patchEntity($entity, $data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('id', $errors);
		$this->assertArrayHasKey('usergroupId', $errors);
		$this->assertArrayHasKey('scope', $errors);
		$this->assertArrayHasKey('identifier', $errors);
		$this->assertArrayHasKey('access', $errors);
		$this->assertArrayHasKey('settings', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\UsergroupPermissionsTable::validationDefault()
	 */
	public function testEntityValidationFieldLength(): void {
		$data = [
			'id' => 123456789123, // exceeds 11 char limit
			'usergroupId' => 123456789123, // exceeds 11 char limit
			'scope' => str_repeat('a', 51), // exceeds 50 char limit
			'identifier' => str_repeat('b', 51), // exceeds 50 char limit
			'access' => 12, // exceeds 1 char limit
		];

		$entity = $this->usergroupPermissionsTable->newDefaultEntity();
		$this->usergroupPermissionsTable->patchEntity($entity, $data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('id', $errors);
		$this->assertArrayHasKey('usergroupId', $errors);
		$this->assertArrayHasKey('scope', $errors);
		$this->assertArrayHasKey('identifier', $errors);
		$this->assertArrayHasKey('access', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\UsergroupPermissionsTable::validationDefault()
	 */
	public function testEntityValidationEmptyScope(): void {
		$data = [
			'scope' => '',
			'identifier' => 'testPermission',
		];

		$entity = $this->usergroupPermissionsTable->newDefaultEntity();
		$this->usergroupPermissionsTable->patchEntity($entity, $data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('scope', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\UsergroupPermissionsTable::validationDefault()
	 */
	public function testEntityValidationBlankScope(): void {
		$data = [
			'scope' => '   ', // Only whitespace
			'identifier' => 'testPermission',
		];

		$entity = $this->usergroupPermissionsTable->newDefaultEntity();
		$this->usergroupPermissionsTable->patchEntity($entity, $data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('scope', $errors);
		$this->assertArrayHasKey('_empty', $errors['scope']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\UsergroupPermissionsTable::validationDefault()
	 */
	public function testEntityValidationEmptyIdentifier(): void {
		$data = [
			'scope' => 'TestScope',
			'identifier' => '',
		];

		$entity = $this->usergroupPermissionsTable->newDefaultEntity();
		$this->usergroupPermissionsTable->patchEntity($entity, $data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('identifier', $errors);
		$this->assertArrayHasKey('_empty', $errors['identifier']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\UsergroupPermissionsTable::validationDefault()
	 */
	public function testEntityValidationBlankIdentifier(): void {
		$data = [
			'scope' => 'TestScope',
			'identifier' => '   ', // Only whitespace
		];

		$entity = $this->usergroupPermissionsTable->newDefaultEntity();
		$this->usergroupPermissionsTable->patchEntity($entity, $data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('identifier', $errors);
		$this->assertArrayHasKey('_empty', $errors['identifier']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\UsergroupPermissionsTable::validationDefault()
	 */
	public function testEntityValidationNullIdentifier(): void {
		$data = [
			'scope' => 'TestScope',
			'identifier' => null,
		];

		$entity = $this->usergroupPermissionsTable->newDefaultEntity();
		$this->usergroupPermissionsTable->patchEntity($entity, $data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('identifier', $errors);
		$this->assertArrayHasKey('_empty', $errors['identifier']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\UsergroupPermissionsTable::validationDefault()
	 */
	public function testEntityValidationInvalidAccess(): void {
		$data = [
			'scope' => 'TestScope',
			'identifier' => 'testPermission',
			'access' => '3',
		];

		$entity = $this->usergroupPermissionsTable->newDefaultEntity();

		$this->usergroupPermissionsTable->patchEntity($entity, $data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('access', $errors);
		$this->assertArrayHasKey('enum', $errors['access']);
		$this->assertSame('UsergroupPermissions::error_enum', $errors['access']['enum']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\UsergroupPermissionsTable::validationDefault()
	 */
	public function testEntityValidationSettingsEmpty(): void {
		$data = [
			'scope' => 'TestScope',
			'identifier' => 'testPermission',
			'settings' => [],
		];

		$entity = $this->usergroupPermissionsTable->newDefaultEntity();
		$this->usergroupPermissionsTable->patchEntity($entity, $data);
		$errors = $entity->getErrors();

		$this->assertArrayNotHasKey('settings', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\UsergroupPermissionsTable::validationDefault()
	 */
	public function testEntityValidationSettingsExceedsMaxLength(): void {
		$largeSettings = array_fill(0, 1000, str_repeat('x', 100)); // Create very large array

		$data = [
			'scope' => 'TestScope',
			'identifier' => 'testPermission',
			'settings' => $largeSettings,
		];

		$entity = $this->usergroupPermissionsTable->newDefaultEntity();
		$this->usergroupPermissionsTable->patchEntity($entity, $data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('settings', $errors);
		$this->assertArrayHasKey('maxLengthBytes', $errors['settings']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\UsergroupPermissionsTable::buildRules()
	 */
	public function testBuildRulesValidUsergroup(): void {
		$data = [
			'usergroupId' => 1, // Existing usergroup
			'scope' => 'TestScope',
			'identifier' => 'testPermission',
			'access' => PermissionAccess::Granted,
		];

		$entity = $this->usergroupPermissionsTable->newDefaultEntity();
		$this->usergroupPermissionsTable->patchEntity($entity, $data);

		$result = $this->usergroupPermissionsTable->checkRules($entity);
		$this->assertTrue($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\UsergroupPermissionsTable::buildRules()
	 */
	public function testBuildRulesInvalidUsergroup(): void {
		$data = [
			'usergroupId' => 99999, // Non-existing usergroup
			'scope' => 'TestScope',
			'identifier' => 'testPermission',
			'access' => PermissionAccess::Granted,
		];

		$entity = $this->usergroupPermissionsTable->newDefaultEntity();
		$this->usergroupPermissionsTable->patchEntity($entity, $data);

		$result = $this->usergroupPermissionsTable->checkRules($entity);

		$this->assertFalse($result);

		$errors = $entity->getErrors();
		$this->assertArrayHasKey('usergroupId', $errors);
		$this->assertArrayHasKey('usergroupExists', $errors['usergroupId']);
		$this->assertSame('UsergroupPermissions::error_usergroup_exists', $errors['usergroupId']['usergroupExists']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\UsergroupPermissionsTable::buildRules()
	 */
	public function testBuildRulesValidAccess() {
		$data = [
			'usergroupId' => 1,
			'scope' => 'TestScope',
			'identifier' => 'testPermission',
			'access' => 1, // Patching entity will convert to enum
		];

		$entity = $this->usergroupPermissionsTable->newDefaultEntity();
		$this->usergroupPermissionsTable->patchEntity($entity, $data);

		$this->assertSame(PermissionAccess::Granted, $entity->access);

		$result = $this->usergroupPermissionsTable->checkRules($entity);

		$this->assertTrue($result);

		$entity->access = PermissionAccess::Denied;

		$result = $this->usergroupPermissionsTable->checkRules($entity);

		$this->assertTrue($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\UsergroupPermissionsTable::buildRules()
	 */
	public function testBuildRulesInvalidAccess() {
		$data = [
			'usergroupId' => 1,
			'scope' => 'TestScope',
			'identifier' => 'testPermission',
			'access' => 'invalid', // Patching entity will convert to enum but fail here
		];

		$entity = $this->usergroupPermissionsTable->newDefaultEntity();
		$this->usergroupPermissionsTable->patchEntity($entity, $data);

		$result = $this->usergroupPermissionsTable->checkRules($entity);

		$this->assertTrue($result);

		$this->assertSame(PermissionAccess::Denied, $entity->access);

		$entity->access = 0; // Setting a value directly will not convert to enum

		$result = $this->usergroupPermissionsTable->checkRules($entity);

		$this->assertFalse($result);

		$errors = $entity->getErrors();
		$this->assertArrayHasKey('access', $errors);
		$this->assertArrayHasKey('validAccess', $errors['access']);
		$this->assertSame('UsergroupPermissions::error_valid_access', $errors['access']['validAccess']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\UsergroupPermissionsTable::newDefaultEntity()
	 */
	public function testNewDefaultEntity(): void {
		/** @var \Awyiss\Model\Entity\UsergroupPermission $entity */
		$entity = $this->usergroupPermissionsTable->newDefaultEntity();

		$this->assertInstanceOf(UsergroupPermission::class, $entity);
		$this->assertTrue($entity->isNew());

		// Test default values
		$this->assertNull($entity->usergroupId);
		$this->assertNull($entity->scope);
		$this->assertNull($entity->identifier);
		$this->assertSame(PermissionAccess::Denied, $entity->access);
		$this->assertNull($entity->settings);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\UsergroupPermissionsTable::newDefaultEntity()
	 */
	public function testNewDefaultEntityWithData(): void {
		$additionalData = [
			'usergroupId' => 2,
			'scope' => 'CustomScope',
			'identifier' => 'customPermission',
			'access' => PermissionAccess::Granted,
			'settings' => ['customKey' => 'custom_value'],
		];

		$entity = $this->usergroupPermissionsTable->newDefaultEntity($additionalData);

		$this->assertInstanceOf(UsergroupPermission::class, $entity);
		$this->assertTrue($entity->isNew());

		$this->assertSame(2, $entity->usergroupId);
		$this->assertSame('CustomScope', $entity->scope);
		$this->assertSame('customPermission', $entity->identifier);
		$this->assertSame(PermissionAccess::Granted, $entity->access);
		$this->assertSame(['customKey' => 'custom_value'], $entity->settings);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\UsergroupPermissionsTable::newDefaultEntity()
	 */
	public function testNewDefaultEntityWithIntegerAccess(): void {
		$additionalData = [
			'usergroupId' => 2,
			'scope' => 'CustomScope',
			'identifier' => 'customPermission',
			'access' => 1,
			'settings' => ['customKey' => 'custom_value'],
		];

		$entity = $this->usergroupPermissionsTable->newDefaultEntity($additionalData);

		$this->assertInstanceOf(UsergroupPermission::class, $entity);
		$this->assertSame(PermissionAccess::Granted, $entity->access);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\UsergroupPermissionsTable::initializeSchema()
	 */
	public function testInitializeSchemaAccessColumn(): void {
		$schema = $this->usergroupPermissionsTable->getSchema();

		// Test that access column is configured as an enum type
		$this->assertSame('enum-awyiss-authorization-permission-permissionaccess', $schema->getColumnType('access'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\UsergroupPermissionsTable::initializeSchema()
	 */
	public function testInitializeSchemaSettingsColumn(): void {
		$schema = $this->usergroupPermissionsTable->getSchema();

		// Test that settings column is configured as JSON type
		$this->assertSame('json', $schema->getColumnType('settings'));
	}
}
