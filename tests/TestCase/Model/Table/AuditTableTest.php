<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Model\Table;


use Awyiss\Model\Entity\Audit;
use Awyiss\Model\Table\AuditTable;
use Awyiss\ORM\Association\BelongsTo;
use Awyiss\Test\TestSuite\TestCase;
use Awyiss\Validation\Validator;
use Cake\Datasource\FactoryLocator;
use Cake\I18n\DateTime;


/**
 * AuditTable Test Case
 *
 * @see \Awyiss\Model\Table\AuditTable
 */
class AuditTableTest extends TestCase {
	/**
	 * @var \Awyiss\Model\Table\AuditTable
	 */
	protected AuditTable $auditTable;


	/**
	 * @inheritDoc
	 */
	protected function setUp(): void {
		parent::setUp();

		/** @noinspection PhpFieldAssignmentTypeMismatchInspection */
		$this->auditTable = FactoryLocator::get('Table')->get('Audit');
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\AuditTable::ATTRIBUTABLE
	 */
	public function testAttributableConstant(): void {
		$this->assertFalse($this->auditTable::ATTRIBUTABLE);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\AuditTable::TABLE
	 */
	public function testTableConstant(): void {
		$this->assertEquals('audit', $this->auditTable::TABLE);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\AuditTable::$audit
	 */
	public function testAuditBehaviorDisabled(): void {
		$this->assertTrue($this->auditTable->hasBehavior('Audit'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\AuditTable::initializeAssociations()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testInitializeAssociations(): void {
		$this->assertCount(2, $this->auditTable->associations()->keys());

		// 'Users' must exist
		$this->assertTrue($this->auditTable->hasAssociation('Users'));
		$usersAssociation = $this->auditTable->getAssociation('Users');
		$this->assertInstanceOf(BelongsTo::class, $usersAssociation);
		$this->assertFalse($usersAssociation->getCascadeCallbacks());
		$this->assertFalse($usersAssociation->getDependent());
		$this->assertEquals('created_by', $usersAssociation->getForeignKey());

		// 'CreatedByUser' must exist
		$this->assertTrue($this->auditTable->hasAssociation('CreatedByUser'));
		$createdByUserAssociation = $this->auditTable->getAssociation('CreatedByUser');
		$this->assertInstanceOf(BelongsTo::class, $createdByUserAssociation);
		$this->assertFalse($createdByUserAssociation->getCascadeCallbacks());
		$this->assertFalse($createdByUserAssociation->getDependent());
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\AuditTable::validationDefault()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testValidationDefault(): void {
		$validator = new Validator();
		$result = $this->auditTable->validationDefault($validator);

		$this->assertInstanceOf(Validator::class, $result);
		$this->assertSame('audit', $result->getI18nDomain());

		// Test required fields
		$this->assertTrue($result->hasField('scope'));
		$this->assertSame('create', $result->field('scope')->isPresenceRequired());

		$this->assertTrue($result->hasField('foreignKey'));
		$this->assertSame('create', $result->field('foreignKey')->isPresenceRequired());

		$this->assertTrue($result->hasField('transactionId'));
		$this->assertSame('create', $result->field('transactionId')->isPresenceRequired());

		$this->assertTrue($result->hasField('type'));
		$this->assertSame('create', $result->field('type')->isPresenceRequired());

		$this->assertTrue($result->hasField('createdOn'));
		$this->assertSame('create', $result->field('createdOn')->isPresenceRequired());

		$this->assertTrue($result->hasField('createdBy'));
		$this->assertSame('create', $result->field('createdBy')->isPresenceRequired());

		// Test other fields
		$this->assertTrue($result->hasField('id'));
		$this->assertTrue($result->hasField('dataOld'));
		$this->assertTrue($result->hasField('dataNew'));
		$this->assertTrue($result->hasField('diff'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\AuditTable::validationDefault()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testEntityValidationSuccess(): void {
		$data = [
			'scope' => 'pages',
			'foreignKey' => 123,
			'transactionId' => 'abc-123-def-456',
			'type' => 'u',
			'dataOld' => '{"title": "Old Title"}',
			'dataNew' => '{"title": "New Title"}',
			'diff' => ['title' => ['old' => 'Old Title', 'new' => 'New Title']],
			'createdBy' => 1,
			'createdOn' => new DateTime('now'),
		];

		$entity = $this->auditTable->newEntity($data);
		$errors = $entity->getErrors();

		$this->assertEmpty($errors, 'Valid data should not produce validation errors');
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\AuditTable::validationDefault()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testEntityValidationMissingRequired(): void {
		$data = [
			'dataOld' => '{"title": "Old Title"}',
		];

		$entity = $this->auditTable->newEntity($data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('scope', $errors);
		$this->assertArrayHasKey('foreignKey', $errors);
		$this->assertArrayHasKey('transactionId', $errors);
		$this->assertArrayHasKey('type', $errors);
		$this->assertArrayHasKey('createdOn', $errors);
		$this->assertArrayHasKey('createdBy', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\AuditTable::validationDefault()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testEntityValidationInvalidTypes(): void {
		$data = [
			'id' => 'not_an_integer',
			'scope' => true,
			'foreignKey' => 'not_an_integer',
			'transactionId' => true,
			'type' => true,
			'dataOld' => true,
			'dataNew' => true,
			'diff' => 'not_an_array',
			'createdBy' => 'not_an_integer',
			'createdOn' => 'not_a_datetime',
		];

		$entity = $this->auditTable->newEntity($data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('id', $errors);
		$this->assertArrayHasKey('scope', $errors);
		$this->assertArrayHasKey('foreignKey', $errors);
		$this->assertArrayHasKey('transactionId', $errors);
		$this->assertArrayHasKey('type', $errors);
		$this->assertArrayHasKey('dataOld', $errors);
		$this->assertArrayHasKey('dataNew', $errors);
		$this->assertArrayHasKey('diff', $errors);
		$this->assertArrayHasKey('createdBy', $errors);
		$this->assertArrayHasKey('createdOn', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\AuditTable::validationDefault()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testEntityValidationFieldLength(): void {
		$data = [
			'id' => 123456789123, // exceeds 11 char limit
			'scope' => str_repeat('a', 51), // exceeds 50 char limit
			'transactionId' => str_repeat('b', 37), // exceeds 36 char limit
			'dataOld' => str_repeat('c', 65536), // exceeds 65535 byte limit
			'dataNew' => str_repeat('d', 65536), // exceeds 65535 byte limit
		];

		$entity = $this->auditTable->newEntity($data, ['guard' => false]);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('id', $errors);
		$this->assertArrayHasKey('scope', $errors);
		$this->assertArrayHasKey('transactionId', $errors);
		$this->assertArrayHasKey('dataOld', $errors);
		$this->assertArrayHasKey('dataNew', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\AuditTable::validationDefault()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testEntityValidationNotBlank(): void {
		$data = [
			'scope' => '   ', // only whitespace
			'transactionId' => '   ', // only whitespace
			'type' => '   ', // only whitespace
			'foreignKey' => 123,
			'createdBy' => 1,
			'createdOn' => new DateTime('now'),
		];

		$entity = $this->auditTable->newEntity($data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('scope', $errors);
		$this->assertArrayHasKey('transactionId', $errors);
		$this->assertArrayHasKey('type', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\AuditTable::validationDefault()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testEntityValidationTypeInList(): void {
		// Test valid types
		$validTypes = ['u', 'd'];
		foreach ($validTypes as $type) {
			$data = [
				'scope' => 'pages',
				'foreignKey' => 123,
				'transactionId' => 'abc-123-def-456',
				'type' => $type,
				'createdBy' => 1,
				'createdOn' => new DateTime('now'),
			];

			$entity = $this->auditTable->newEntity($data);
			$errors = $entity->getErrors();

			$this->assertArrayNotHasKey('type', $errors);
		}

		// Test invalid type
		$data = [
			'scope' => 'pages',
			'foreignKey' => 123,
			'transactionId' => 'abc-123-def-456',
			'type' => 'invalid_type',
			'createdBy' => 1,
			'createdOn' => new DateTime('now'),
		];

		$entity = $this->auditTable->newEntity($data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('type', $errors);
		$this->assertArrayHasKey('inList', $errors['type']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\AuditTable::validationDefault()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testEntityValidationDiffMaxLengthBytes(): void {
		// Create a very large diff array that exceeds 16777215 bytes when JSON encoded
		$largeArray = array_fill(0, 50000, str_repeat('x', 1000));

		$data = [
			'scope' => 'pages',
			'foreignKey' => 123,
			'transactionId' => 'abc-123-def-456',
			'type' => 'u',
			'diff' => $largeArray,
			'createdBy' => 1,
			'createdOn' => new DateTime('now'),
		];

		$entity = $this->auditTable->newEntity($data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('diff', $errors);
		$this->assertArrayHasKey('maxLengthBytes', $errors['diff']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\AuditTable::validationDefault()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testEntityValidationValidDiffArray(): void {
		// Test with valid diff array
		$data = [
			'scope' => 'pages',
			'foreignKey' => 123,
			'transactionId' => 'abc-123-def-456',
			'type' => 'u',
			'diff' => [
				'title' => ['old' => 'Old Title', 'new' => 'New Title'],
				'content' => ['old' => 'Old Content', 'new' => 'New Content'],
			],
			'createdBy' => 1,
			'createdOn' => new DateTime('now'),
		];

		$entity = $this->auditTable->newEntity($data);
		$errors = $entity->getErrors();

		$this->assertArrayNotHasKey('diff', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\AuditTable::validationDefault()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testEntityValidationAllowEmptyOptionalFields(): void {
		$data = [
			'scope' => 'pages',
			'foreignKey' => 123,
			'transactionId' => 'abc-123-def-456',
			'type' => 'u',
			'dataOld' => null,
			'dataNew' => null,
			'diff' => null,
			'createdBy' => 1,
			'createdOn' => new DateTime('now'),
		];

		$entity = $this->auditTable->newEntity($data);
		$errors = $entity->getErrors();

		$this->assertArrayNotHasKey('dataOld', $errors);
		$this->assertArrayNotHasKey('dataNew', $errors);
		$this->assertArrayNotHasKey('diff', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\AuditTable::newDefaultEntity()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testNewDefaultEntity(): void {
		/** @var \Awyiss\Model\Entity\Audit $entity */
		$entity = $this->auditTable->newDefaultEntity();

		$this->assertInstanceOf(Audit::class, $entity);
		$this->assertTrue($entity->isNew());

		// Check default values
		$this->assertNull($entity->scope);
		$this->assertNull($entity->foreignKey);
		$this->assertNull($entity->transactionId);
		$this->assertNull($entity->type);
		$this->assertNull($entity->dataOld);
		$this->assertNull($entity->dataNew);
		$this->assertNull($entity->diff);
		$this->assertNull($entity->createdBy);
		$this->assertNull($entity->createdOn);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\AuditTable::newDefaultEntity()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testNewDefaultEntityWithData(): void {
		$additionalData = [
			'scope' => 'test_scope',
			'foreignKey' => 456,
			'transactionId' => 'test-transaction-id',
			'type' => 'd',
			'dataOld' => '{"title": "Test Title"}',
			'dataNew' => null,
			'diff' => ['title' => ['old' => 'Test Title', 'new' => null]],
			'createdBy' => 2,
			'createdOn' => new DateTime('2023-01-01 12:00:00'),
		];

		/** @var \Awyiss\Model\Entity\Audit $entity */
		$entity = $this->auditTable->newDefaultEntity($additionalData);

		$this->assertInstanceOf(Audit::class, $entity);
		$this->assertTrue($entity->isNew());

		// Check custom values
		$this->assertSame('test_scope', $entity->scope);
		$this->assertSame(456, $entity->foreignKey);
		$this->assertSame('test-transaction-id', $entity->transactionId);
		$this->assertSame('d', $entity->type);
		$this->assertSame('{"title": "Test Title"}', $entity->dataOld);
		$this->assertNull($entity->dataNew);
		$this->assertSame(['title' => ['old' => 'Test Title', 'new' => null]], $entity->diff);
		$this->assertSame(2, $entity->createdBy);
		$this->assertEquals(new DateTime('2023-01-01 12:00:00'), $entity->createdOn);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\AuditTable::initializeSchema()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testInitializeSchemaDataColumn(): void {
		$schema = $this->auditTable->getSchema();
		// Test that diff column is configured as JSON type
		$this->assertSame('json', $schema->getColumnType('diff'));
	}
}
