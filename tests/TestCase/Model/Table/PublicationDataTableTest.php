<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Model\Table;


use Awyiss\Model\Entity\PublicationData;
use Awyiss\Model\Enum\PublicationDataType;
use Awyiss\Model\Table\PublicationDataTable;
use Awyiss\Test\TestSuite\TestCase;
use Awyiss\Validation\Validator;
use Cake\Datasource\FactoryLocator;


/**
 * PublicationDataTable Test Case
 *
 * @see \Awyiss\Model\Table\PublicationDataTable
 */
class PublicationDataTableTest extends TestCase {
	/**
	 * @var \Awyiss\Model\Table\PublicationDataTable
	 */
	protected PublicationDataTable $publicationDataTable;


	/**
	 * @inheritDoc
	 */
	protected function setUp(): void {
		parent::setUp();

		/** @noinspection PhpFieldAssignmentTypeMismatchInspection */
		$this->publicationDataTable = FactoryLocator::get('Table')->get('PublicationData');
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\PublicationDataTable::ATTRIBUTABLE
	 */
	public function testAttributableConstant(): void {
		$this->assertFalse($this->publicationDataTable::ATTRIBUTABLE);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\PublicationDataTable::TABLE
	 */
	public function testTableConstant(): void {
		$this->assertEquals('publication_data', $this->publicationDataTable::TABLE);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\PublicationDataTable::initializeAssociations()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testInitializeAssociations(): void {
		$this->assertCount(0, $this->publicationDataTable->associations()->keys());
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\PublicationDataTable::validationDefault()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testValidationDefault(): void {
		$validator = new Validator();
		$result = $this->publicationDataTable->validationDefault($validator);

		$this->assertInstanceOf(Validator::class, $result);
		$this->assertSame('publication_data', $result->getI18nDomain());

		// Test required fields
		$this->assertTrue($result->hasField('foreignKey'));
		$this->assertTrue($result->hasField('type'));

		// Test other fields
		$this->assertTrue($result->hasField('scope'));
		$this->assertTrue($result->hasField('dateTime'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\PublicationDataTable::validationDefault()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testEntityValidationSuccess(): void {
		$data = [
			'scope' => 'test_scope',
			'foreignKey' => 123,
			'type' => 'start',
			'dateTime' => '2024-01-15 10:30:00',
		];

		$entity = $this->publicationDataTable->newDefaultEntity($data);
		$errors = $entity->getErrors();

		$this->assertEmpty($errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\PublicationDataTable::validationDefault()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testEntityValidationMissingRequired(): void {
		$entity = $this->publicationDataTable->newDefaultEntity();
		$this->publicationDataTable->patchEntity($entity, []);
		$errors = $entity->getErrors();

		$this->assertArrayNotHasKey('foreignKey', $errors);

		$this->assertArrayHasKey('scope', $errors);
		$this->assertArrayHasKey('_required', $errors['scope']);
		$this->assertSame('publication_data::error_required', $errors['scope']['_required']);

		$this->assertArrayHasKey('type', $errors);
		$this->assertArrayHasKey('_required', $errors['type']);
		$this->assertSame('publication_data::error_required', $errors['type']['_required']);

		$this->assertArrayNotHasKey('dateTime', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\PublicationDataTable::validationDefault()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testEntityValidationInvalidTypes(): void {
		$data = [
			'scope' => true,
			'foreignKey' => 'not_an_integer',
			'type' => true,
			'dateTime' => 'not_a_datetime',
		];

		$entity = $this->publicationDataTable->newDefaultEntity();
		$this->publicationDataTable->patchEntity($entity, $data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('scope', $errors);
		$this->assertArrayHasKey('foreignKey', $errors);

		$this->assertArrayHasKey('type', $errors);
		$this->assertArrayHasKey('enum', $errors['type']);
		$this->assertSame('publication_data::error_enum', $errors['type']['enum']);

		$this->assertArrayHasKey('dateTime', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\PublicationDataTable::validationDefault()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testEntityValidationFieldLength(): void {
		$data = [
			'scope' => str_repeat('a', 51), // exceeds 50 char limit
			'foreignKey' => 123456789123, // exceeds 11 char limit
			'type' => str_repeat('b', 21), // exceeds 20 char limit
		];

		$entity = $this->publicationDataTable->newDefaultEntity();
		$this->publicationDataTable->patchEntity($entity, $data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('scope', $errors);
		$this->assertArrayHasKey('foreignKey', $errors);
		$this->assertArrayHasKey('type', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\PublicationDataTable::validationDefault()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testEntityValidationBlankFields(): void {
		$data = [
			'scope' => '   ', // Only whitespace
			'foreignKey' => 123,
			'type' => 'end',
		];

		$entity = $this->publicationDataTable->newDefaultEntity();
		$this->publicationDataTable->patchEntity($entity, $data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('scope', $errors);
		$this->assertArrayHasKey('notBlank', $errors['scope']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\PublicationDataTable::validationDefault()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testEntityValidationValidDateTime(): void {
		$data = [
			'scope' => 'test_scope',
			'foreignKey' => 123,
			'type' => 'start',
			'dateTime' => '2024-12-25 15:30:45',
		];

		$entity = $this->publicationDataTable->newDefaultEntity();
		$this->publicationDataTable->patchEntity($entity, $data);
		$errors = $entity->getErrors();

		$this->assertArrayNotHasKey('dateTime', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\PublicationDataTable::validationDefault()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function _testEntityValidationInvalidDateTime(): void {
		$data = [
			'scope' => 'test_scope',
			'foreignKey' => 123,
			'type' => 'start',
			'dateTime' => 'invalid-date-format',
		];

		$entity = $this->publicationDataTable->newDefaultEntity();
		$this->publicationDataTable->patchEntity($entity, $data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('dateTime', $errors);
		$this->assertArrayHasKey('dateTime', $errors['dateTime']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\PublicationDataTable::validationDefault()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testEntityValidationNullDateTime(): void {
		$data = [
			'scope' => 'test_scope',
			'foreignKey' => 123,
			'type' => 'end',
			'dateTime' => null, // Null should be allowed for dateTime
		];

		$entity = $this->publicationDataTable->newDefaultEntity();
		$this->publicationDataTable->patchEntity($entity, $data);
		$errors = $entity->getErrors();

		$this->assertArrayNotHasKey('dateTime', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\PublicationDataTable::buildRules()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testBuildRulesValidType(): void {
		$data = [
			'type' => 'start', // Patching entity will convert to enum
			'scope' => 'test_scope',
		];

		$entity = $this->publicationDataTable->newDefaultEntity();

		$this->publicationDataTable->patchEntity($entity, $data);

		$this->assertSame(PublicationDataType::Start, $entity->type);

		$result = $this->publicationDataTable->checkRules($entity);

		$this->assertTrue($result);

		$entity->type = PublicationDataType::End;

		$result = $this->publicationDataTable->checkRules($entity);

		$this->assertTrue($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\PublicationDataTable::buildRules()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testBuildRulesInvalidType(): void {
		$data = [
			'type' => 'invalid', // Patching entity will convert to enum but fail here
			'scope' => 'test_scope',
		];

		$entity = $this->publicationDataTable->newDefaultEntity();

		$this->publicationDataTable->patchEntity($entity, $data);

		$this->assertNull($entity->type);

		$result = $this->publicationDataTable->checkRules($entity);

		$this->assertFalse($result);

		$entity->type = 'invalid';

		$result = $this->publicationDataTable->checkRules($entity);

		$this->assertFalse($result);

		$errors = $entity->getErrors();
		$this->assertArrayHasKey('type', $errors);
		$this->assertArrayHasKey('validType', $errors['type']);
		$this->assertSame('publication_data::error_valid_type', $errors['type']['validType']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\PublicationDataTable::newDefaultEntity()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testNewDefaultEntity(): void {
		/** @var \Awyiss\Model\Entity\PublicationData $entity */
		$entity = $this->publicationDataTable->newDefaultEntity();

		$this->assertInstanceOf(PublicationData::class, $entity);
		$this->assertTrue($entity->isNew());

		// Test default values
		$this->assertNull($entity->scope);
		$this->assertNull($entity->foreignKey);
		$this->assertNull($entity->type);
		$this->assertNull($entity->dateTime);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\PublicationDataTable::newDefaultEntity()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testNewDefaultEntityWithData(): void {
		$additionalData = [
			'scope' => 'custom_scope',
			'foreignKey' => 456,
			'type' => 'end',
			'dateTime' => '2024-06-15 14:20:30',
		];

		$entity = $this->publicationDataTable->newDefaultEntity($additionalData);

		$this->assertInstanceOf(PublicationData::class, $entity);
		$this->assertTrue($entity->isNew());

		$this->assertSame('custom_scope', $entity->scope);
		$this->assertSame(456, $entity->foreignKey);
		$this->assertSame(PublicationDataType::End, $entity->type);
		$this->assertEquals('2024-06-15 14:20:30', $entity->dateTime->format('Y-m-d H:i:s'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\PublicationDataTable::initializeSchema()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testInitializeSchemaTypeColumn(): void {
		$schema = $this->publicationDataTable->getSchema();

		// Test that type column is configured as an enum type
		$this->assertSame('enum-awyiss-model-enum-publicationdatatype', $schema->getColumnType('type'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\PublicationDataTable::$audit
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testAuditBehaviorDisabled(): void {
		$this->assertTrue($this->publicationDataTable->hasBehavior('Audit'));

		$config = $this->publicationDataTable->getBehavior('Audit')->getConfig();

		$this->assertArrayHasKey('enabled', $config);
		$this->assertFalse($config['enabled']);
	}
}
