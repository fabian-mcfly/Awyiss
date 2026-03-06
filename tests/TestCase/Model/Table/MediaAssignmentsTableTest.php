<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Model\Table;


use Awyiss\Model\Entity\MediaAssignment;
use Awyiss\Model\Table\MediaAssignmentsTable;
use Awyiss\ORM\Association\BelongsTo;
use Awyiss\Test\TestSuite\TestCase;
use Awyiss\Validation\Validator;
use Cake\Datasource\FactoryLocator;


/**
 * MediaAssignmentsTable Test Case
 *
 * @see \Awyiss\Model\Table\MediaAssignmentsTable
 */
class MediaAssignmentsTableTest extends TestCase {
	/**
	 * @var \Awyiss\Model\Table\MediaAssignmentsTable
	 */
	protected MediaAssignmentsTable $mediaAssignmentsTable;


	/**
	 * @inheritDoc
	 */
	protected function setUp(): void {
		parent::setUp();

		/** @noinspection PhpFieldAssignmentTypeMismatchInspection */
		$this->mediaAssignmentsTable = FactoryLocator::get('Table')->get('MediaAssignments');
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\MediaAssignmentsTable::ATTRIBUTABLE
	 */
	public function testAttributableConstant(): void {
		$this->assertFalse($this->mediaAssignmentsTable::ATTRIBUTABLE);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\MediaAssignmentsTable::TABLE
	 */
	public function testTableConstant(): void {
		$this->assertEquals('media_assignments', $this->mediaAssignmentsTable::TABLE);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\MediaAssignmentsTable::initializeAssociations()
	 */
	public function testInitializeAssociations(): void {
		$this->assertCount(8, $this->mediaAssignmentsTable->associations()->keys());

		// Test MediaElements association (BelongsTo)
		$this->assertTrue($this->mediaAssignmentsTable->hasAssociation('MediaElements'));
		$mediaElementsAssociation = $this->mediaAssignmentsTable->getAssociation('MediaElements');
		$this->assertInstanceOf(BelongsTo::class, $mediaElementsAssociation);
		$this->assertFalse($mediaElementsAssociation->getCascadeCallbacks());
		$this->assertFalse($mediaElementsAssociation->getDependent());
		$this->assertSame('mediaElementId', $mediaElementsAssociation->getForeignKey());
		$this->assertSame('INNER', $mediaElementsAssociation->getJoinType());

		// Test MediaElementAssignments association (BelongsTo)
		$this->assertTrue($this->mediaAssignmentsTable->hasAssociation('MediaElementAssignments'));
		$mediaElementAssignmentsAssociation = $this->mediaAssignmentsTable->getAssociation('MediaElementAssignments');
		$this->assertInstanceOf(BelongsTo::class, $mediaElementAssignmentsAssociation);
		$this->assertFalse($mediaElementAssignmentsAssociation->getCascadeCallbacks());
		$this->assertFalse($mediaElementAssignmentsAssociation->getDependent());
		$this->assertSame('mediaElementId', $mediaElementAssignmentsAssociation->getBindingKey());
		$this->assertSame('mediaElementId', $mediaElementAssignmentsAssociation->getForeignKey());
		$this->assertSame('INNER', $mediaElementAssignmentsAssociation->getJoinType());

		// Test MediaElementSelectors association (BelongsTo)
		$this->assertTrue($this->mediaAssignmentsTable->hasAssociation('MediaElementSelectors'));
		$mediaElementSelectorsAssociation = $this->mediaAssignmentsTable->getAssociation('MediaElementSelectors');
		$this->assertInstanceOf(BelongsTo::class, $mediaElementSelectorsAssociation);
		$this->assertFalse($mediaElementSelectorsAssociation->getCascadeCallbacks());
		$this->assertFalse($mediaElementSelectorsAssociation->getDependent());
		$this->assertSame(['mediaElementId', 'identifier'], $mediaElementSelectorsAssociation->getBindingKey());
		$this->assertSame(['mediaElementId', 'mediaElementSelectorIdentifier'], $mediaElementSelectorsAssociation->getForeignKey());
		$this->assertSame('INNER', $mediaElementSelectorsAssociation->getJoinType());

		// Test Media association (BelongsTo)
		$this->assertTrue($this->mediaAssignmentsTable->hasAssociation('Media'));
		$mediaAssociation = $this->mediaAssignmentsTable->getAssociation('Media');
		$this->assertInstanceOf(BelongsTo::class, $mediaAssociation);
		$this->assertFalse($mediaAssociation->getCascadeCallbacks());
		$this->assertFalse($mediaAssociation->getDependent());
		$this->assertSame('mediaId', $mediaAssociation->getForeignKey());

		// Test MediaFolders association (BelongsTo)
		$this->assertTrue($this->mediaAssignmentsTable->hasAssociation('MediaFolders'));
		$mediaFoldersAssociation = $this->mediaAssignmentsTable->getAssociation('MediaFolders');
		$this->assertInstanceOf(BelongsTo::class, $mediaFoldersAssociation);
		$this->assertFalse($mediaFoldersAssociation->getCascadeCallbacks());
		$this->assertFalse($mediaFoldersAssociation->getDependent());
		$this->assertSame('mediaFolderId', $mediaFoldersAssociation->getForeignKey());

		// Test user tracking associations
		$this->assertTrue($this->mediaAssignmentsTable->hasAssociation('CreatedByUser'));
		$createdByUserAssociation = $this->mediaAssignmentsTable->getAssociation('CreatedByUser');
		$this->assertInstanceOf(BelongsTo::class, $createdByUserAssociation);
		$this->assertFalse($createdByUserAssociation->getCascadeCallbacks());
		$this->assertFalse($createdByUserAssociation->getDependent());

		$this->assertTrue($this->mediaAssignmentsTable->hasAssociation('ChangedByUser'));
		$changedByUserAssociation = $this->mediaAssignmentsTable->getAssociation('ChangedByUser');
		$this->assertInstanceOf(BelongsTo::class, $changedByUserAssociation);
		$this->assertFalse($changedByUserAssociation->getCascadeCallbacks());
		$this->assertFalse($changedByUserAssociation->getDependent());

		$this->assertTrue($this->mediaAssignmentsTable->hasAssociation('DeletedByUser'));
		$deletedByUserAssociation = $this->mediaAssignmentsTable->getAssociation('DeletedByUser');
		$this->assertInstanceOf(BelongsTo::class, $deletedByUserAssociation);
		$this->assertFalse($deletedByUserAssociation->getCascadeCallbacks());
		$this->assertFalse($deletedByUserAssociation->getDependent());
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\MediaAssignmentsTable::validationDefault()
	 */
	public function testValidationDefault(): void {
		$validator = new Validator();
		$result = $this->mediaAssignmentsTable->validationDefault($validator);

		$this->assertInstanceOf(Validator::class, $result);
		$this->assertSame('MediaAssignments', $result->getI18nDomain());

		// Test fields exist
		$this->assertTrue($result->hasField('id'));
		$this->assertTrue($result->hasField('mediaElementId'));
		$this->assertTrue($result->hasField('mediaElementSelectorIdentifier'));
		$this->assertTrue($result->hasField('mediaId'));
		$this->assertTrue($result->hasField('mediaFolderId'));
		$this->assertTrue($result->hasField('scope'));
		$this->assertTrue($result->hasField('foreignKey'));
		$this->assertTrue($result->hasField('systemOrder'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\MediaAssignmentsTable::validationDefault()
	 */
	public function testEntityValidationSuccessWithMedia(): void {
		$data = [
			'mediaElementId' => 2,
			'mediaElementSelectorIdentifier' => 'media',
			'mediaId' => 1,
			'scope' => 'GlobalContents',
			'foreignKey' => 1,
			'systemOrder' => 1,
		];

		$entity = $this->mediaAssignmentsTable->newEntity($data);
		$errors = $entity->getErrors();

		$this->assertEmpty($errors, 'Valid data with media should not produce validation errors');
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\MediaAssignmentsTable::validationDefault()
	 */
	public function testEntityValidationSuccessWithMediaFolder(): void {
		$data = [
			'mediaElementId' => 2,
			'mediaElementSelectorIdentifier' => 'media',
			'mediaFolderId' => 1,
			'scope' => 'GlobalContents',
			'foreignKey' => 1,
			'systemOrder' => 1,
		];

		$entity = $this->mediaAssignmentsTable->newEntity($data);
		$errors = $entity->getErrors();

		$this->assertEmpty($errors, 'Valid data with media folder should not produce validation errors');
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\MediaAssignmentsTable::validationDefault()
	 */
	public function testEntityValidationMissingRequired(): void {
		$data = [
			'systemOrder' => 1,
		];

		$entity = $this->mediaAssignmentsTable->newEntity($data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('mediaElementId', $errors);
		$this->assertArrayHasKey('mediaElementSelectorIdentifier', $errors);
		$this->assertArrayHasKey('scope', $errors);

		$this->assertTrue(
			isset($errors['mediaId']) || isset($errors['mediaFolderId']),
			'Should require either mediaId or mediaFolderId'
		);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\MediaAssignmentsTable::validationDefault()
	 */
	public function testEntityValidationMediaOrMediaFolderRequired(): void {
		// Test that mediaId is required when mediaFolderId is empty
		$data = [
			'mediaElementId' => 2,
			'mediaElementSelectorIdentifier' => 'media',
			'scope' => 'GlobalContents',
		];

		$entity = $this->mediaAssignmentsTable->newEntity($data);
		$errors = $entity->getErrors();

		$this->assertTrue(
			isset($errors['mediaId']) || isset($errors['mediaFolderId']),
			'Should require either mediaId or mediaFolderId'
		);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\MediaAssignmentsTable::validationDefault()
	 */
	public function testEntityValidationInvalidTypes(): void {
		$data = [
			'id' => 'not_an_integer',
			'mediaElementId' => 'not_an_integer',
			'mediaElementSelectorIdentifier' => true,
			'mediaId' => 'not_an_integer',
			'mediaFolderId' => 'not_an_integer',
			'scope' => true,
			'foreignKey' => 'not_an_integer',
			'systemOrder' => 'not_an_integer',
		];

		$entity = $this->mediaAssignmentsTable->newEntity($data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('id', $errors);
		$this->assertArrayHasKey('mediaElementId', $errors);
		$this->assertArrayHasKey('mediaElementSelectorIdentifier', $errors);
		$this->assertArrayHasKey('mediaId', $errors);
		$this->assertArrayHasKey('mediaFolderId', $errors);
		$this->assertArrayHasKey('scope', $errors);
		$this->assertArrayHasKey('foreignKey', $errors);
		$this->assertArrayHasKey('systemOrder', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\MediaAssignmentsTable::validationDefault()
	 */
	public function testEntityValidationFieldLength(): void {
		$data = [
			'id' => 123456789123, // exceeds 11 char limit
			'mediaElementId' => 123456789123, // exceeds 11 char limit
			'mediaElementSelectorIdentifier' => str_repeat('a', 51), // exceeds 50 char limit
			'mediaId' => 123456789123, // exceeds 11 char limit
			'mediaFolderId' => 123456789123, // exceeds 11 char limit
			'scope' => str_repeat('b', 51), // exceeds 50 char limit
			'foreignKey' => 123456789123, // exceeds 11 char limit
			'systemOrder' => 123456789123, // exceeds 11 char limit
		];

		$entity = $this->mediaAssignmentsTable->newEntity($data, ['guard' => false]);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('id', $errors);
		$this->assertArrayHasKey('mediaElementId', $errors);
		$this->assertArrayHasKey('mediaElementSelectorIdentifier', $errors);
		$this->assertArrayHasKey('mediaId', $errors);
		$this->assertArrayHasKey('mediaFolderId', $errors);
		$this->assertArrayHasKey('scope', $errors);
		$this->assertArrayHasKey('foreignKey', $errors);
		$this->assertArrayHasKey('systemOrder', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\MediaAssignmentsTable::validationDefault()
	 */
	public function testEntityValidationNotBlank(): void {
		$data = [
			'mediaElementId' => 2,
			'mediaElementSelectorIdentifier' => '   ', // only whitespace
			'mediaId' => 1,
			'scope' => '   ', // only whitespace
		];

		$entity = $this->mediaAssignmentsTable->newEntity($data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('mediaElementSelectorIdentifier', $errors);
		$this->assertArrayHasKey('scope', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\MediaAssignmentsTable::validationDefault()
	 */
	public function testEntityValidationForeignKeyAllowEmpty(): void {
		$data = [
			'mediaElementId' => 2,
			'mediaElementSelectorIdentifier' => 'media',
			'mediaId' => 1,
			'scope' => 'GlobalContents',
			'foreignKey' => null, // Should be allowed
			'systemOrder' => 1,
		];

		$entity = $this->mediaAssignmentsTable->newEntity($data);
		$errors = $entity->getErrors();

		$this->assertArrayNotHasKey('foreignKey', $errors, 'foreignKey should allow empty values');
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\MediaAssignmentsTable::buildRules()
	 */
	public function testBuildRulesMediaElementExists(): void {
		// Test with existing media element
		$data = [
			'mediaElementId' => 2,
			'mediaElementSelectorIdentifier' => 'media',
			'mediaId' => 1,
			'scope' => 'GlobalContents',
			'foreignKey' => 1,
		];

		$entity = $this->mediaAssignmentsTable->newEntity($data);
		$result = $this->mediaAssignmentsTable->checkRules($entity);
		$this->assertTrue($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\MediaAssignmentsTable::buildRules()
	 */
	public function testBuildRulesMediaElementNotExists(): void {
		// Test with non-existing media element
		$data = [
			'mediaElementId' => 99999,
			'mediaElementSelectorIdentifier' => 'media',
			'mediaId' => 1,
			'scope' => 'GlobalContents',
			'foreignKey' => 1,
		];

		$entity = $this->mediaAssignmentsTable->newEntity($data);
		$result = $this->mediaAssignmentsTable->checkRules($entity);
		$this->assertFalse($result);

		$errors = $entity->getErrors();
		$this->assertArrayHasKey('mediaElementId', $errors);
		$this->assertArrayHasKey('mediaElementExists', $errors['mediaElementId']);
		$this->assertSame('media_assignments::error_media_element_exists', $errors['mediaElementId']['mediaElementExists']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\MediaAssignmentsTable::buildRules()
	 */
	public function testBuildRulesMediaExists(): void {
		// Test with existing media
		$data = [
			'mediaElementId' => 2,
			'mediaElementSelectorIdentifier' => 'media',
			'mediaId' => 1,
			'scope' => 'GlobalContents',
			'foreignKey' => 1,
		];

		$entity = $this->mediaAssignmentsTable->newEntity($data);
		$result = $this->mediaAssignmentsTable->checkRules($entity);
		$this->assertTrue($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\MediaAssignmentsTable::buildRules()
	 */
	public function testBuildRulesMediaNotExists(): void {
		// Test with non-existing media
		$data = [
			'mediaElementId' => 2,
			'mediaElementSelectorIdentifier' => 'media',
			'mediaId' => 99999,
			'scope' => 'GlobalContents',
			'foreignKey' => 1,
		];

		$entity = $this->mediaAssignmentsTable->newEntity($data);
		$result = $this->mediaAssignmentsTable->checkRules($entity);
		$this->assertFalse($result);

		$errors = $entity->getErrors();
		$this->assertArrayHasKey('mediaId', $errors);
		$this->assertArrayHasKey('mediaExists', $errors['mediaId']);
		$this->assertSame('media_assignments::error_media_exists', $errors['mediaId']['mediaExists']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\MediaAssignmentsTable::buildRules()
	 */
	public function testBuildRulesMediaExistsWithNullValue(): void {
		// Test with null media (should pass when mediaFolderId is provided)
		$data = [
			'mediaElementId' => 2,
			'mediaElementSelectorIdentifier' => 'media',
			'mediaId' => null,
			'mediaFolderId' => 1,
			'scope' => 'GlobalContents',
			'foreignKey' => 1,
		];

		$entity = $this->mediaAssignmentsTable->newEntity($data);
		$result = $this->mediaAssignmentsTable->checkRules($entity);
		$this->assertTrue($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\MediaAssignmentsTable::buildRules()
	 */
	public function testBuildRulesMediaFolderExists(): void {
		// Test with existing media
		$data = [
			'mediaElementId' => 2,
			'mediaElementSelectorIdentifier' => 'media',
			'mediaFolderId' => 1,
			'scope' => 'GlobalContents',
			'foreignKey' => 1,
		];

		$entity = $this->mediaAssignmentsTable->newEntity($data);
		$result = $this->mediaAssignmentsTable->checkRules($entity);
		$this->assertTrue($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\MediaAssignmentsTable::buildRules()
	 */
	public function testBuildRulesMediaFolderNotExists(): void {
		// Test with non-existing media
		$data = [
			'mediaElementId' => 2,
			'mediaElementSelectorIdentifier' => 'media',
			'mediaFolderId' => 99999,
			'scope' => 'GlobalContents',
			'foreignKey' => 1,
		];

		$entity = $this->mediaAssignmentsTable->newEntity($data);
		$result = $this->mediaAssignmentsTable->checkRules($entity);
		$this->assertFalse($result);

		$errors = $entity->getErrors();
		$this->assertArrayHasKey('mediaFolderId', $errors);
		$this->assertArrayHasKey('mediaFolderExists', $errors['mediaFolderId']);
		$this->assertSame('media_assignments::error_media_folder_exists', $errors['mediaFolderId']['mediaFolderExists']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\MediaAssignmentsTable::buildRules()
	 */
	public function testBuildRulesMediaFolderExistsWithNullValue(): void {
		// Test with null media (should pass when mediaFolderId is provided)
		$data = [
			'mediaElementId' => 2,
			'mediaElementSelectorIdentifier' => 'media',
			'mediaId' => 1,
			'mediaFolderId' => null,
			'scope' => 'GlobalContents',
			'foreignKey' => 1,
		];

		$entity = $this->mediaAssignmentsTable->newEntity($data);
		$result = $this->mediaAssignmentsTable->checkRules($entity);
		$this->assertTrue($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\MediaAssignmentsTable::newDefaultEntity()
	 */
	public function testNewDefaultEntity(): void {
		/** @var \Awyiss\Model\Entity\MediaAssignment $entity */
		$entity = $this->mediaAssignmentsTable->newDefaultEntity();

		$this->assertInstanceOf(MediaAssignment::class, $entity);
		$this->assertTrue($entity->isNew());

		// Check default values
		$this->assertNull($entity->mediaElementId);
		$this->assertNull($entity->mediaElementSelectorIdentifier);
		$this->assertNull($entity->mediaId);
		$this->assertNull($entity->mediaFolderId);
		$this->assertNull($entity->scope);
		$this->assertNull($entity->foreignKey);
		$this->assertSame(0, $entity->systemOrder);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\MediaAssignmentsTable::newDefaultEntity()
	 */
	public function testNewDefaultEntityWithData(): void {
		$additionalData = [
			'mediaElementId' => 3,
			'mediaElementSelectorIdentifier' => 'titleMedia',
			'mediaId' => 2,
			'scope' => 'Pages',
			'foreignKey' => 5,
			'systemOrder' => 3,
		];

		/** @var \Awyiss\Model\Entity\MediaAssignment $entity */
		$entity = $this->mediaAssignmentsTable->newDefaultEntity($additionalData);

		$this->assertInstanceOf(MediaAssignment::class, $entity);
		$this->assertTrue($entity->isNew());

		// Check custom values
		$this->assertSame(3, $entity->mediaElementId);
		$this->assertSame('titleMedia', $entity->mediaElementSelectorIdentifier);
		$this->assertSame(2, $entity->mediaId);
		$this->assertNull($entity->mediaFolderId); // Should remain default
		$this->assertSame('Pages', $entity->scope);
		$this->assertSame(5, $entity->foreignKey);
		$this->assertSame(3, $entity->systemOrder);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\MediaAssignmentsTable::$systemOrder
	 */
	public function testSystemOrderBehavior(): void {
		$this->assertTrue($this->mediaAssignmentsTable->hasBehavior('SystemOrder'));

		$config = $this->mediaAssignmentsTable->getBehavior('SystemOrder')->getConfig();

		$this->assertSame(['mediaElementId', 'mediaElementSelectorIdentifier', 'scope', 'foreignKey'], $config['relatedColumns']);
	}
}
