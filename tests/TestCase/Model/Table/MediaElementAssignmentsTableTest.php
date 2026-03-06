<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Model\Table;


use Awyiss\Model\Entity\MediaElementAssignment;
use Awyiss\Model\Table\MediaElementAssignmentsTable;
use Awyiss\ORM\Association\BelongsTo;
use Awyiss\ORM\Association\HasMany;
use Awyiss\Test\TestSuite\TestCase;
use Awyiss\Validation\Validator;
use Cake\Datasource\FactoryLocator;


/**
 * MediaElementAssignmentsTable Test Case
 *
 * @see \Awyiss\Model\Table\MediaElementAssignmentsTable
 */
class MediaElementAssignmentsTableTest extends TestCase {
	/**
	 * @var \Awyiss\Model\Table\MediaElementAssignmentsTable
	 */
	protected MediaElementAssignmentsTable $mediaElementAssignmentsTable;


	/**
	 * @inheritDoc
	 */
	protected function setUp(): void {
		parent::setUp();

		/** @noinspection PhpFieldAssignmentTypeMismatchInspection */
		$this->mediaElementAssignmentsTable = FactoryLocator::get('Table')->get('MediaElementAssignments');
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\MediaElementAssignmentsTable::ATTRIBUTABLE
	 */
	public function testAttributableConstant(): void {
		$this->assertFalse($this->mediaElementAssignmentsTable::ATTRIBUTABLE);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\MediaElementAssignmentsTable::TABLE
	 */
	public function testTableConstant(): void {
		$this->assertEquals('media_element_assignments', $this->mediaElementAssignmentsTable::TABLE);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\MediaElementAssignmentsTable::initializeAssociations()
	 */
	public function testInitializeAssociations(): void {
		$this->assertCount(2, $this->mediaElementAssignmentsTable->associations()->keys());

		// Test MediaAssignments association (HasMany)
		$this->assertTrue($this->mediaElementAssignmentsTable->hasAssociation('MediaAssignments'));
		$mediaAssignmentsAssociation = $this->mediaElementAssignmentsTable->getAssociation('MediaAssignments');
		$this->assertInstanceOf(HasMany::class, $mediaAssignmentsAssociation);
		$this->assertTrue($mediaAssignmentsAssociation->getCascadeCallbacks());
		$this->assertTrue($mediaAssignmentsAssociation->getDependent());
		$this->assertEquals('replace', $mediaAssignmentsAssociation->getSaveStrategy());
		$this->assertSame(['mediaElementId', 'scope'], $mediaAssignmentsAssociation->getBindingKey());
		$this->assertSame(['mediaElementId', 'scope'], $mediaAssignmentsAssociation->getForeignKey());

		// Test MediaElements association (BelongsTo)
		$this->assertTrue($this->mediaElementAssignmentsTable->hasAssociation('MediaElements'));
		$mediaElementsAssociation = $this->mediaElementAssignmentsTable->getAssociation('MediaElements');
		$this->assertInstanceOf(BelongsTo::class, $mediaElementsAssociation);
		$this->assertFalse($mediaElementsAssociation->getCascadeCallbacks());
		$this->assertFalse($mediaElementsAssociation->getDependent());
		$this->assertSame('INNER', $mediaElementsAssociation->getJoinType());
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\MediaElementAssignmentsTable::validationDefault()
	 */
	public function testValidationDefault(): void {
		$validator = new Validator();
		$result = $this->mediaElementAssignmentsTable->validationDefault($validator);

		$this->assertInstanceOf(Validator::class, $result);
		$this->assertSame('MediaElementAssignments', $result->getI18nDomain());

		// Test fields exist
		$this->assertTrue($result->hasField('id'));
		$this->assertTrue($result->hasField('mediaElementId'));
		$this->assertTrue($result->hasField('scope'));
		$this->assertTrue($result->hasField('foreignKey'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\MediaElementAssignmentsTable::validationDefault()
	 */
	public function testEntityValidationSuccess(): void {
		$data = [
			'mediaElementId' => 2,
			'scope' => 'GlobalContentTemplates',
			'foreignKey' => 1,
		];

		$entity = $this->mediaElementAssignmentsTable->newEntity($data);
		$errors = $entity->getErrors();

		$this->assertEmpty($errors, 'Valid data should not produce validation errors');
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\MediaElementAssignmentsTable::validationDefault()
	 */
	public function testEntityValidationSuccessWithNullForeignKey(): void {
		$data = [
			'mediaElementId' => 3,
			'scope' => 'Cars',
			'foreignKey' => null,
		];

		$entity = $this->mediaElementAssignmentsTable->newEntity($data);
		$errors = $entity->getErrors();

		$this->assertEmpty($errors, 'Valid data with null foreign key should not produce validation errors');
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\MediaElementAssignmentsTable::validationDefault()
	 */
	public function testEntityValidationMissingRequired(): void {
		$data = [
			'foreignKey' => 1,
		];

		$entity = $this->mediaElementAssignmentsTable->newEntity($data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('scope', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\MediaElementAssignmentsTable::validationDefault()
	 */
	public function testEntityValidationInvalidTypes(): void {
		$data = [
			'id' => 'not_an_integer',
			'mediaElementId' => 'not_an_integer',
			'scope' => true,
			'foreignKey' => 'not_an_integer',
		];

		$entity = $this->mediaElementAssignmentsTable->newEntity($data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('id', $errors);
		$this->assertArrayHasKey('mediaElementId', $errors);
		$this->assertArrayHasKey('scope', $errors);
		$this->assertArrayHasKey('foreignKey', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\MediaElementAssignmentsTable::validationDefault()
	 */
	public function testEntityValidationFieldLength(): void {
		$data = [
			'id' => 123456789123, // exceeds 11 char limit
			'mediaElementId' => 123456789123, // exceeds 11 char limit
			'scope' => str_repeat('a', 51), // exceeds 50 char limit
			'foreignKey' => 123456789123, // exceeds 11 char limit
		];

		$entity = $this->mediaElementAssignmentsTable->newEntity($data, ['guard' => false]);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('id', $errors);
		$this->assertArrayHasKey('mediaElementId', $errors);
		$this->assertArrayHasKey('scope', $errors);
		$this->assertArrayHasKey('foreignKey', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\MediaElementAssignmentsTable::validationDefault()
	 */
	public function testEntityValidationNotBlank(): void {
		$data = [
			'mediaElementId' => 2,
			'scope' => '   ', // only whitespace
		];

		$entity = $this->mediaElementAssignmentsTable->newEntity($data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('scope', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\MediaElementAssignmentsTable::validationDefault()
	 */
	public function testEntityValidationForeignKeyAllowEmpty(): void {
		$data = [
			'mediaElementId' => 2,
			'scope' => 'GlobalContentTemplates',
			'foreignKey' => null, // Should be allowed
		];

		$entity = $this->mediaElementAssignmentsTable->newEntity($data);
		$errors = $entity->getErrors();

		$this->assertArrayNotHasKey('foreignKey', $errors, 'foreignKey should allow empty values');
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\MediaElementAssignmentsTable::buildRules()
	 */
	public function testBuildRulesMediaElementExists(): void {
		// Test with existing media element
		$data = [
			'mediaElementId' => 3,
			'scope' => 'GlobalContentTemplates',
			'foreignKey' => 1,
		];

		$entity = $this->mediaElementAssignmentsTable->newEntity($data);
		$result = $this->mediaElementAssignmentsTable->checkRules($entity);
		$this->assertTrue($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\MediaElementAssignmentsTable::buildRules()
	 */
	public function testBuildRulesMediaElementNotExists(): void {
		// Test with non-existing media element
		$data = [
			'mediaElementId' => 99999,
			'scope' => 'GlobalContentTemplates',
			'foreignKey' => 1,
		];

		$entity = $this->mediaElementAssignmentsTable->newEntity($data);
		$result = $this->mediaElementAssignmentsTable->checkRules($entity);
		$this->assertFalse($result);

		$errors = $entity->getErrors();
		$this->assertArrayHasKey('mediaElementId', $errors);
		$this->assertArrayHasKey('mediaElementExists', $errors['mediaElementId']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\MediaElementAssignmentsTable::buildRules()
	 */
	public function testBuildRulesForeignKeyExistsValidScope(): void {
		// Test with valid assignable scope
		$data = [
			'mediaElementId' => 3,
			'scope' => 'GlobalContentTemplates',
			'foreignKey' => 1,
		];

		$entity = $this->mediaElementAssignmentsTable->newEntity($data);
		$result = $this->mediaElementAssignmentsTable->checkRules($entity);
		$this->assertTrue($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\MediaElementAssignmentsTable::buildRules()
	 */
	public function testBuildRulesForeignKeyExistsInvalidScope(): void {
		// Test with invalid scope
		$data = [
			'mediaElementId' => 2,
			'scope' => 'InvalidScope',
			'foreignKey' => 1,
		];

		$entity = $this->mediaElementAssignmentsTable->newEntity($data);
		$result = $this->mediaElementAssignmentsTable->checkRules($entity);
		$this->assertFalse($result);

		$errors = $entity->getErrors();
		$this->assertArrayHasKey('foreignKey', $errors);
		$this->assertArrayHasKey('foreignKeyExists', $errors['foreignKey']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\MediaElementAssignmentsTable::buildRules()
	 */
	public function testBuildRulesForeignKeyExistsEntityLevelNotAllowed(): void {
		// Test entity level assignment on scope that doesn't allow entity level
		$data = [
			'mediaElementId' => 2,
			'scope' => 'Cars',
			'foreignKey' => 1,
		];

		$entity = $this->mediaElementAssignmentsTable->newEntity($data);
		$result = $this->mediaElementAssignmentsTable->checkRules($entity);
		$this->assertFalse($result);

		$errors = $entity->getErrors();
		$this->assertArrayHasKey('foreignKey', $errors);
		$this->assertArrayHasKey('foreignKeyExists', $errors['foreignKey']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\MediaElementAssignmentsTable::buildRules()
	 */
	public function testBuildRulesForeignKeyExistsModelLevelValid(): void {
		// Test valid model level assignment (null foreign key)
		$data = [
			'mediaElementId' => 2,
			'scope' => 'Cars',
			'foreignKey' => null,
		];

		$entity = $this->mediaElementAssignmentsTable->newEntity($data);
		$result = $this->mediaElementAssignmentsTable->checkRules($entity);
		$this->assertTrue($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\MediaElementAssignmentsTable::buildRules()
	 */
	public function testBuildRulesForeignKeyExistsInvalidEntity(): void {
		// Test with non-existing entity
		$data = [
			'mediaElementId' => 2,
			'scope' => 'GlobalContentTemplates',
			'foreignKey' => 99999,
		];

		$entity = $this->mediaElementAssignmentsTable->newEntity($data);
		$result = $this->mediaElementAssignmentsTable->checkRules($entity);
		$this->assertFalse($result);

		$errors = $entity->getErrors();
		$this->assertArrayHasKey('foreignKey', $errors);
		$this->assertArrayHasKey('foreignKeyExists', $errors['foreignKey']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\MediaElementAssignmentsTable::buildRules()
	 */
	public function testBuildRulesMediaElementUniqueForScope(): void {
		/** @var \Awyiss\Model\Entity\MediaElementAssignment $entity */
		$entity = $this->mediaElementAssignmentsTable->get(2); // From seed data
		$entity->unset('id'); // Clear ID to create a new entity
		$entity->setNew(true);

		$saved2 = $this->mediaElementAssignmentsTable->checkRules($entity);
		$this->assertFalse($saved2, 'Second entity should fail due to duplicate combination');

		$errors = $entity->getErrors();
		$this->assertArrayHasKey('foreignKey', $errors);
		$this->assertArrayHasKey('mediaElementUniqueForScope', $errors['foreignKey']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\MediaElementAssignmentsTable::buildRules()
	 */
	public function testBuildRulesMediaElementUniqueForScopeWithNulls(): void {
		// Test that multiple nulls are not allowed (allowMultipleNulls => false)
		/** @var \Awyiss\Model\Entity\MediaElementAssignment $entity */
		$entity = $this->mediaElementAssignmentsTable->get(3); // From seed data (has null foreign key)
		$entity->unset('id'); // Clear ID to create a new entity
		$entity->setNew(true);

		$saved2 = $this->mediaElementAssignmentsTable->checkRules($entity);
		$this->assertFalse($saved2, 'Second entity should fail due to duplicate null combination');

		$errors = $entity->getErrors();
		$this->assertArrayHasKey('foreignKey', $errors);
		$this->assertArrayHasKey('mediaElementUniqueForScope', $errors['foreignKey']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\MediaElementAssignmentsTable::newDefaultEntity()
	 */
	public function testNewDefaultEntity(): void {
		/** @var \Awyiss\Model\Entity\MediaElementAssignment $entity */
		$entity = $this->mediaElementAssignmentsTable->newDefaultEntity();

		$this->assertInstanceOf(MediaElementAssignment::class, $entity);
		$this->assertTrue($entity->isNew());

		// Check default values
		$this->assertNull($entity->mediaElementId);
		$this->assertNull($entity->scope);
		$this->assertNull($entity->foreignKey);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\MediaElementAssignmentsTable::newDefaultEntity()
	 */
	public function testNewDefaultEntityWithData(): void {
		$additionalData = [
			'mediaElementId' => 4,
			'scope' => 'ContentTemplates',
			'foreignKey' => 2,
		];

		/** @var \Awyiss\Model\Entity\MediaElementAssignment $entity */
		$entity = $this->mediaElementAssignmentsTable->newDefaultEntity($additionalData);

		$this->assertInstanceOf(MediaElementAssignment::class, $entity);
		$this->assertTrue($entity->isNew());

		// Check custom values
		$this->assertSame(4, $entity->mediaElementId);
		$this->assertSame('ContentTemplates', $entity->scope);
		$this->assertSame(2, $entity->foreignKey);
	}
}
