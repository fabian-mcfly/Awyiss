<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Model\Table;


use Awyiss\Model\Entity\ThirdPartyConsent;
use Awyiss\Model\Table\ThirdPartyConsentsTable;
use Awyiss\Test\TestSuite\TestCase;
use Awyiss\Validation\Validator;
use Cake\Datasource\FactoryLocator;


/**
 * ThirdPartyConsentsTable Test Case
 *
 * @see \Awyiss\Model\Table\ThirdPartyConsentsTable
 */
class ThirdPartyConsentsTableTest extends TestCase {
	/**
	 * @var \Awyiss\Model\Table\ThirdPartyConsentsTable
	 */
	protected ThirdPartyConsentsTable $thirdPartyConsentsTable;


	/**
	 * @inheritDoc
	 */
	protected function setUp(): void {
		parent::setUp();

		/** @noinspection PhpFieldAssignmentTypeMismatchInspection */
		$this->thirdPartyConsentsTable = FactoryLocator::get('Table')->get('ThirdPartyConsents');
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\ThirdPartyConsentsTable::ATTRIBUTABLE
	 */
	public function testAttributableConstant(): void {
		$this->assertFalse($this->thirdPartyConsentsTable::ATTRIBUTABLE);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\ThirdPartyConsentsTable::TABLE
	 */
	public function testTableConstant(): void {
		$this->assertEquals('third_party_consents', $this->thirdPartyConsentsTable::TABLE);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\PublicationDataTable::initializeAssociations()
	 */
	public function testInitializeAssociations(): void {
		$this->assertCount(1, $this->thirdPartyConsentsTable->associations()->keys());

		// MediaAssignments is also defined, but we don't care about it for this table
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\ThirdPartyConsentsTable::validationDefault()
	 */
	public function testValidationDefault(): void {
		$validator = new Validator();
		$result = $this->thirdPartyConsentsTable->validationDefault($validator);

		$this->assertInstanceOf(Validator::class, $result);
		$this->assertSame('ThirdPartyConsents', $result->getI18nDomain());

		// Test required fields
		$this->assertTrue($result->hasField('consentId'));
		$this->assertSame('create', $result->field('consentId')->isPresenceRequired());

		$this->assertTrue($result->hasField('acceptType'));
		$this->assertSame('create', $result->field('acceptType')->isPresenceRequired());

		$this->assertTrue($result->hasField('acceptedCategories'));
		$this->assertSame('create', $result->field('acceptedCategories')->isPresenceRequired());

		$this->assertTrue($result->hasField('rejectedCategories'));
		$this->assertSame('create', $result->field('rejectedCategories')->isPresenceRequired());

		// Test other fields
		$this->assertTrue($result->hasField('id'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\ThirdPartyConsentsTable::validationDefault()
	 */
	public function testEntityValidationSuccess(): void {
		$data = [
			'consentId' => 'a1b2c3d4-e5f6-7890-abcd-ef1234567890',
			'acceptType' => 'explicit',
			'acceptedCategories' => ['necessary', 'analytics'],
			'rejectedCategories' => ['marketing', 'social'],
		];

		$entity = $this->thirdPartyConsentsTable->newDefaultEntity($data);
		$errors = $entity->getErrors();

		$this->assertEmpty($errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\ThirdPartyConsentsTable::validationDefault()
	 */
	public function testEntityValidationMissingRequired(): void {
		$data = [
			'acceptType' => 'explicit',
		];

		$entity = $this->thirdPartyConsentsTable->newDefaultEntity();
		$this->thirdPartyConsentsTable->patchEntity($entity, $data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('consentId', $errors);
		$this->assertArrayHasKey('_required', $errors['consentId']);
		$this->assertSame('third_party_consents::error_required', $errors['consentId']['_required']);

		$this->assertArrayHasKey('acceptedCategories', $errors);
		$this->assertArrayHasKey('_required', $errors['acceptedCategories']);
		$this->assertSame('third_party_consents::error_required', $errors['acceptedCategories']['_required']);

		$this->assertArrayHasKey('rejectedCategories', $errors);
		$this->assertArrayHasKey('_required', $errors['rejectedCategories']);
		$this->assertSame('third_party_consents::error_required', $errors['rejectedCategories']['_required']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\ThirdPartyConsentsTable::validationDefault()
	 */
	public function testEntityValidationInvalidTypes(): void {
		$data = [
			'id' => 'not_an_integer',
			'consentId' => true,
			'acceptType' => true,
			'acceptedCategories' => 'not_an_array',
			'rejectedCategories' => 'not_an_array',
		];

		$entity = $this->thirdPartyConsentsTable->newDefaultEntity();
		$this->thirdPartyConsentsTable->patchEntity($entity, $data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('id', $errors);
		$this->assertArrayHasKey('consentId', $errors);
		$this->assertArrayHasKey('acceptType', $errors);
		$this->assertArrayHasKey('acceptedCategories', $errors);
		$this->assertArrayHasKey('rejectedCategories', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\ThirdPartyConsentsTable::validationDefault()
	 */
	public function testEntityValidationFieldLength(): void {
		$data = [
			'id' => 123456789123, // exceeds 11 char limit
			'acceptType' => str_repeat('a', 51), // exceeds 50 char limit
		];

		$entity = $this->thirdPartyConsentsTable->newDefaultEntity();
		$this->thirdPartyConsentsTable->patchEntity($entity, $data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('id', $errors);
		$this->assertArrayHasKey('acceptType', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\ThirdPartyConsentsTable::validationDefault()
	 */
	public function testEntityValidationConsentIdEmpty(): void {
		$data = [
			'consentId' => '',
			'acceptType' => 'explicit',
			'acceptedCategories' => [],
			'rejectedCategories' => [],
		];

		$entity = $this->thirdPartyConsentsTable->newDefaultEntity();
		$this->thirdPartyConsentsTable->patchEntity($entity, $data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('consentId', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\ThirdPartyConsentsTable::validationDefault()
	 */
	public function testEntityValidationConsentIdExactLength(): void {
		$data = [
			'consentId' => 'a1b2c3d4-e5f6-7890-abcd-ef1234567890',
			'acceptType' => 'explicit',
			'acceptedCategories' => [],
			'rejectedCategories' => [],
		];

		$entity = $this->thirdPartyConsentsTable->newDefaultEntity();
		$this->thirdPartyConsentsTable->patchEntity($entity, $data);
		$errors = $entity->getErrors();

		$this->assertArrayNotHasKey('consentId', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\ThirdPartyConsentsTable::validationDefault()
	 */
	public function testEntityValidationConsentIdWrongLength(): void {
		$data = [
			'consentId' => 'too-short', // Not 36 characters
			'acceptType' => 'explicit',
			'acceptedCategories' => [],
			'rejectedCategories' => [],
		];

		$entity = $this->thirdPartyConsentsTable->newDefaultEntity();
		$this->thirdPartyConsentsTable->patchEntity($entity, $data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('consentId', $errors);
		$this->assertArrayHasKey('exactLength', $errors['consentId']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\ThirdPartyConsentsTable::validationDefault()
	 */
	public function testEntityValidationConsentIdTooLong(): void {
		$data = [
			'consentId' => str_repeat('a', 37), // Too long
			'acceptType' => 'explicit',
			'acceptedCategories' => [],
			'rejectedCategories' => [],
		];

		$entity = $this->thirdPartyConsentsTable->newDefaultEntity();
		$this->thirdPartyConsentsTable->patchEntity($entity, $data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('consentId', $errors);
		$this->assertArrayHasKey('exactLength', $errors['consentId']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\ThirdPartyConsentsTable::validationDefault()
	 */
	public function testEntityValidationConsentIdNonAscii(): void {
		$data = [
			'consentId' => 'a1b2c3d4-e5f6-7890-abcd-ef123456789ö', // Non-ASCII character
			'acceptType' => 'explicit',
			'acceptedCategories' => [],
			'rejectedCategories' => [],
		];

		$entity = $this->thirdPartyConsentsTable->newDefaultEntity();
		$this->thirdPartyConsentsTable->patchEntity($entity, $data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('consentId', $errors);
		$this->assertArrayHasKey('ascii', $errors['consentId']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\ThirdPartyConsentsTable::validationDefault()
	 */
	public function testEntityValidationAcceptTypeBlank(): void {
		$data = [
			'consentId' => 'a1b2c3d4-e5f6-7890-abcd-ef1234567890',
			'acceptType' => '   ', // Only whitespace
			'acceptedCategories' => [],
			'rejectedCategories' => [],
		];

		$entity = $this->thirdPartyConsentsTable->newDefaultEntity();
		$this->thirdPartyConsentsTable->patchEntity($entity, $data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('acceptType', $errors);
		$this->assertArrayHasKey('inList', $errors['acceptType']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\ThirdPartyConsentsTable::validationDefault()
	 */
	public function testEntityValidationAcceptedCategoriesEmpty(): void {
		$data = [
			'consentId' => 'a1b2c3d4-e5f6-7890-abcd-ef1234567890',
			'acceptType' => 'explicit',
			'acceptedCategories' => [],
			'rejectedCategories' => [],
		];

		$entity = $this->thirdPartyConsentsTable->newDefaultEntity();
		$this->thirdPartyConsentsTable->patchEntity($entity, $data);
		$errors = $entity->getErrors();

		$this->assertArrayNotHasKey('acceptedCategories', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\ThirdPartyConsentsTable::validationDefault()
	 */
	public function testEntityValidationAcceptedCategoriesMaxLength(): void {
		$largeCategories = array_fill(0, 1000, str_repeat('x', 100)); // Create very large array

		$data = [
			'consentId' => 'a1b2c3d4-e5f6-7890-abcd-ef1234567890',
			'acceptType' => 'explicit',
			'acceptedCategories' => $largeCategories,
			'rejectedCategories' => [],
		];

		$entity = $this->thirdPartyConsentsTable->newDefaultEntity();
		$this->thirdPartyConsentsTable->patchEntity($entity, $data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('acceptedCategories', $errors);
		$this->assertArrayHasKey('maxLengthBytes', $errors['acceptedCategories']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\ThirdPartyConsentsTable::validationDefault()
	 */
	public function testEntityValidationRejectedCategoriesEmpty(): void {
		$data = [
			'consentId' => 'a1b2c3d4-e5f6-7890-abcd-ef1234567890',
			'acceptType' => 'explicit',
			'acceptedCategories' => [],
			'rejectedCategories' => [],
		];

		$entity = $this->thirdPartyConsentsTable->newDefaultEntity();
		$this->thirdPartyConsentsTable->patchEntity($entity, $data);
		$errors = $entity->getErrors();

		$this->assertArrayNotHasKey('rejectedCategories', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\ThirdPartyConsentsTable::validationDefault()
	 */
	public function testEntityValidationRejectedCategoriesMaxLength(): void {
		$largeCategories = array_fill(0, 1000, str_repeat('y', 100)); // Create very large array

		$data = [
			'consentId' => 'a1b2c3d4-e5f6-7890-abcd-ef1234567890',
			'acceptType' => 'explicit',
			'acceptedCategories' => [],
			'rejectedCategories' => $largeCategories,
		];

		$entity = $this->thirdPartyConsentsTable->newDefaultEntity();
		$this->thirdPartyConsentsTable->patchEntity($entity, $data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('rejectedCategories', $errors);
		$this->assertArrayHasKey('maxLengthBytes', $errors['rejectedCategories']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\ThirdPartyConsentsTable::validationDefault()
	 */
	public function testEntityValidationValidConsentTypes(): void {
		$validTypes = ['all', 'custom', 'necessary'];

		foreach ($validTypes as $type) {
			$data = [
				'consentId' => 'a1b2c3d4-e5f6-7890-abcd-ef1234567890',
				'acceptType' => $type,
				'acceptedCategories' => [],
				'rejectedCategories' => [],
			];

			$entity = $this->thirdPartyConsentsTable->newDefaultEntity();
			$this->thirdPartyConsentsTable->patchEntity($entity, $data);
			$errors = $entity->getErrors();

			$this->assertArrayNotHasKey('acceptType', $errors);
		}
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\ThirdPartyConsentsTable::validationDefault()
	 */
	public function testEntityValidationInvalidConsentType(): void {
		$data = [
			'consentId' => 'a1b2c3d4-e5f6-7890-abcd-ef1234567890',
			'acceptType' => 'invalid-type', // Invalid consent type
			'acceptedCategories' => [],
			'rejectedCategories' => [],
		];

		$entity = $this->thirdPartyConsentsTable->newDefaultEntity();
		$this->thirdPartyConsentsTable->patchEntity($entity, $data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('acceptType', $errors);
		$this->assertArrayHasKey('inList', $errors['acceptType']);
		$this->assertSame('third_party_consents::error_in_list', $errors['acceptType']['inList']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\ThirdPartyConsentsTable::newDefaultEntity()
	 */
	public function testNewDefaultEntity(): void {
		/** @var \Awyiss\Model\Entity\ThirdPartyConsent $entity */
		$entity = $this->thirdPartyConsentsTable->newDefaultEntity();

		$this->assertInstanceOf(ThirdPartyConsent::class, $entity);
		$this->assertTrue($entity->isNew());

		// Test default values
		$this->assertNull($entity->consentId);
		$this->assertNull($entity->acceptType);
		$this->assertNull($entity->acceptedCategories);
		$this->assertNull($entity->rejectedCategories);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\ThirdPartyConsentsTable::newDefaultEntity()
	 */
	public function testNewDefaultEntityWithData(): void {
		$additionalData = [
			'consentId' => 'b2c3d4e5-f6g7-8901-bcde-fg2345678901',
			'acceptType' => 'implicit',
			'acceptedCategories' => ['necessary', 'preferences'],
			'rejectedCategories' => ['analytics', 'marketing'],
		];

		$entity = $this->thirdPartyConsentsTable->newDefaultEntity($additionalData);

		$this->assertInstanceOf(ThirdPartyConsent::class, $entity);
		$this->assertTrue($entity->isNew());

		$this->assertSame('b2c3d4e5-f6g7-8901-bcde-fg2345678901', $entity->consentId);
		$this->assertSame('implicit', $entity->acceptType);
		$this->assertSame(['necessary', 'preferences'], $entity->acceptedCategories);
		$this->assertSame(['analytics', 'marketing'], $entity->rejectedCategories);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\ThirdPartyConsentsTable::initializeSchema()
	 */
	public function testInitializeSchemaAcceptedCategoriesColumn(): void {
		$schema = $this->thirdPartyConsentsTable->getSchema();

		// Test that acceptedCategories column is configured as JSON type
		$this->assertSame('json', $schema->getColumnType('acceptedCategories'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\ThirdPartyConsentsTable::initializeSchema()
	 */
	public function testInitializeSchemaRejectedCategoriesColumn(): void {
		$schema = $this->thirdPartyConsentsTable->getSchema();

		// Test that rejectedCategories column is configured as JSON type
		$this->assertSame('json', $schema->getColumnType('rejectedCategories'));
	}
}
