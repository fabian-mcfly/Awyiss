<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Model\Table;


use Awyiss\Model\Entity\CustomerGroupAccessSetting;
use Awyiss\Model\Enum\CustomerGroupAccessType;
use Awyiss\Model\Table\CustomerGroupAccessSettingsTable;
use Awyiss\ORM\Association\BelongsTo;
use Awyiss\Test\TestSuite\TestCase;
use Awyiss\Validation\Validator;
use Cake\Datasource\FactoryLocator;


/**
 * CustomerGroupAccessSettingsTable Test Case
 *
 * @see \Awyiss\Model\Table\CustomerGroupAccessSettingsTable
 */
class CustomerGroupAccessSettingsTableTest extends TestCase {
	/**
	 * @var \Awyiss\Model\Table\CustomerGroupAccessSettingsTable
	 */
	protected CustomerGroupAccessSettingsTable $customerGroupAccessSettingsTable;


	/**
	 * @inheritDoc
	 */
	protected function setUp(): void {
		parent::setUp();

		/** @noinspection PhpFieldAssignmentTypeMismatchInspection */
		$this->customerGroupAccessSettingsTable = FactoryLocator::get('Table')->get('CustomerGroupAccessSettings');
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\CustomerGroupAccessSettingsTable::ATTRIBUTABLE
	 */
	public function testAttributableConstant(): void {
		$this->assertFalse($this->customerGroupAccessSettingsTable::ATTRIBUTABLE);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\CustomerGroupAccessSettingsTable::TABLE
	 */
	public function testTableConstant(): void {
		$this->assertEquals('customer_group_access_settings', $this->customerGroupAccessSettingsTable::TABLE);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\CustomerGroupAccessSettingsTable::initializeAssociations()
	 */
	public function testInitializeAssociations(): void {
		$this->assertCount(3, $this->customerGroupAccessSettingsTable->associations()->keys());

		// Test user tracking associations
		$this->assertTrue($this->customerGroupAccessSettingsTable->hasAssociation('CreatedByUser'));
		$createdByUserAssociation = $this->customerGroupAccessSettingsTable->getAssociation('CreatedByUser');
		$this->assertInstanceOf(BelongsTo::class, $createdByUserAssociation);
		$this->assertFalse($createdByUserAssociation->getCascadeCallbacks());
		$this->assertFalse($createdByUserAssociation->getDependent());

		$this->assertTrue($this->customerGroupAccessSettingsTable->hasAssociation('ChangedByUser'));
		$changedByUserAssociation = $this->customerGroupAccessSettingsTable->getAssociation('ChangedByUser');
		$this->assertInstanceOf(BelongsTo::class, $changedByUserAssociation);
		$this->assertFalse($changedByUserAssociation->getCascadeCallbacks());
		$this->assertFalse($changedByUserAssociation->getDependent());

		$this->assertTrue($this->customerGroupAccessSettingsTable->hasAssociation('DeletedByUser'));
		$deletedByUserAssociation = $this->customerGroupAccessSettingsTable->getAssociation('DeletedByUser');
		$this->assertInstanceOf(BelongsTo::class, $deletedByUserAssociation);
		$this->assertFalse($deletedByUserAssociation->getCascadeCallbacks());
		$this->assertFalse($deletedByUserAssociation->getDependent());
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\CustomerGroupAccessSettingsTable::validationDefault()
	 */
	public function testValidationDefault(): void {
		$validator = new Validator();
		$result = $this->customerGroupAccessSettingsTable->validationDefault($validator);

		$this->assertInstanceOf(Validator::class, $result);
		$this->assertSame('customer_group_access_settings', $result->getI18nDomain());

		// Test fields exist
		$this->assertTrue($result->hasField('id'));
		$this->assertTrue($result->hasField('scope'));
		$this->assertTrue($result->hasField('foreignKey'));
		$this->assertTrue($result->hasField('accessType'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\CustomerGroupAccessSettingsTable::validationDefault()
	 */
	public function testEntityValidationSuccessWithSpecificGroups(): void {
		$data = [
			'scope' => 'pages',
			'foreignKey' => 1,
			'accessType' => 'specific_groups',
		];

		$entity = $this->customerGroupAccessSettingsTable->newEntity($data);
		$errors = $entity->getErrors();

		$this->assertEmpty($errors, 'Valid data with specific_groups should not produce validation errors');
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\CustomerGroupAccessSettingsTable::validationDefault()
	 */
	public function testEntityValidationSuccessWithAllGroups(): void {
		$data = [
			'scope' => 'pages',
			'foreignKey' => 2,
			'accessType' => 'all_groups',
		];

		$entity = $this->customerGroupAccessSettingsTable->newEntity($data);
		$errors = $entity->getErrors();

		$this->assertEmpty($errors, 'Valid data with all_groups should not produce validation errors');
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\CustomerGroupAccessSettingsTable::validationDefault()
	 */
	public function testEntityValidationSuccessWithHideOnLogin(): void {
		$data = [
			'scope' => 'surveys',
			'foreignKey' => 1,
			'accessType' => 'hide_on_login',
		];

		$entity = $this->customerGroupAccessSettingsTable->newEntity($data);
		$errors = $entity->getErrors();

		$this->assertEmpty($errors, 'Valid data with hide_on_login should not produce validation errors');
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\CustomerGroupAccessSettingsTable::validationDefault()
	 */
	public function testEntityValidationSuccessWithNullForeignKey(): void {
		$data = [
			'scope' => 'pages',
			'foreignKey' => null,
			'accessType' => 'all_groups',
		];

		$entity = $this->customerGroupAccessSettingsTable->newEntity($data);
		$errors = $entity->getErrors();

		$this->assertEmpty($errors, 'Valid data with null foreign key should not produce validation errors');
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\CustomerGroupAccessSettingsTable::validationDefault()
	 */
	public function testEntityValidationMissingRequired(): void {
		$data = [
			'foreignKey' => 1,
		];

		$entity = $this->customerGroupAccessSettingsTable->newEntity($data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('scope', $errors);
		$this->assertArrayHasKey('accessType', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\CustomerGroupAccessSettingsTable::validationDefault()
	 */
	public function testEntityValidationInvalidTypes(): void {
		$data = [
			'id' => 'not_an_integer',
			'scope' => true,
			'foreignKey' => 'not_an_integer',
			'accessType' => true,
		];

		$entity = $this->customerGroupAccessSettingsTable->newEntity($data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('id', $errors);
		$this->assertArrayHasKey('scope', $errors);
		$this->assertArrayHasKey('foreignKey', $errors);
		$this->assertArrayHasKey('accessType', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\CustomerGroupAccessSettingsTable::validationDefault()
	 */
	public function testEntityValidationFieldLength(): void {
		$data = [
			'id' => 123456789123, // exceeds 11 char limit
			'scope' => str_repeat('a', 51), // exceeds 50 char limit
			'foreignKey' => 123456789123, // exceeds 11 char limit
			'accessType' => 'specific_groups',
		];

		$entity = $this->customerGroupAccessSettingsTable->newEntity($data, ['guard' => false]);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('id', $errors);
		$this->assertArrayHasKey('scope', $errors);
		$this->assertArrayHasKey('foreignKey', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\CustomerGroupAccessSettingsTable::validationDefault()
	 */
	public function testEntityValidationNotBlank(): void {
		$data = [
			'scope' => '   ', // only whitespace
			'accessType' => 'specific_groups',
		];

		$entity = $this->customerGroupAccessSettingsTable->newEntity($data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('scope', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\CustomerGroupAccessSettingsTable::validationDefault()
	 */
	public function testEntityValidationForeignKeyAllowEmpty(): void {
		$data = [
			'scope' => 'pages',
			'foreignKey' => null, // Should be allowed
			'accessType' => 'all_groups',
		];

		$entity = $this->customerGroupAccessSettingsTable->newEntity($data);
		$errors = $entity->getErrors();

		$this->assertArrayNotHasKey('foreignKey', $errors, 'foreignKey should allow empty values');
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\CustomerGroupAccessSettingsTable::validationDefault()
	 */
	public function testEntityValidationInvalidAccessType(): void {
		$data = [
			'scope' => 'pages',
			'foreignKey' => 1,
			'accessType' => 'invalid_type',
		];

		$entity = $this->customerGroupAccessSettingsTable->newEntity($data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('accessType', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\CustomerGroupAccessSettingsTable::buildRules()
	 */
	public function testBuildRulesValidAccessType(): void {
		$data = [
			'scope' => 'pages',
			'foreignKey' => 1,
			'accessType' => CustomerGroupAccessType::SpecificGroups,
		];

		$entity = $this->customerGroupAccessSettingsTable->newEntity($data);
		$result = $this->customerGroupAccessSettingsTable->checkRules($entity);
		$this->assertTrue($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\CustomerGroupAccessSettingsTable::buildRules()
	 */
	public function testBuildRulesAllValidAccessTypes(): void {
		$validTypes = [
			CustomerGroupAccessType::AllGroups,
			CustomerGroupAccessType::HideOnLogin,
			CustomerGroupAccessType::SpecificGroups,
		];

		foreach ($validTypes as $type) {
			$data = [
				'scope' => 'pages',
				'foreignKey' => 1,
				'accessType' => $type,
			];

			$entity = $this->customerGroupAccessSettingsTable->newEntity($data);
			$result = $this->customerGroupAccessSettingsTable->checkRules($entity);
			$this->assertTrue($result, "Access type {$type->value} should be valid");
		}
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\CustomerGroupAccessSettingsTable::newDefaultEntity()
	 */
	public function testNewDefaultEntity(): void {
		/** @var \Awyiss\Model\Entity\CustomerGroupAccessSetting $entity */
		$entity = $this->customerGroupAccessSettingsTable->newDefaultEntity();

		$this->assertInstanceOf(CustomerGroupAccessSetting::class, $entity);
		$this->assertTrue($entity->isNew());

		// Check default values
		$this->assertNull($entity->scope);
		$this->assertNull($entity->foreignKey);
		$this->assertNull($entity->accessType);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\CustomerGroupAccessSettingsTable::newDefaultEntity()
	 */
	public function testNewDefaultEntityWithData(): void {
		$additionalData = [
			'scope' => 'surveys',
			'foreignKey' => 2,
			'accessType' => 'all_groups',
		];

		/** @var \Awyiss\Model\Entity\CustomerGroupAccessSetting $entity */
		$entity = $this->customerGroupAccessSettingsTable->newDefaultEntity($additionalData);

		$this->assertInstanceOf(CustomerGroupAccessSetting::class, $entity);
		$this->assertTrue($entity->isNew());

		// Check custom values
		$this->assertSame('surveys', $entity->scope);
		$this->assertSame(2, $entity->foreignKey);
		$this->assertSame(CustomerGroupAccessType::AllGroups, $entity->accessType);
	}
}
