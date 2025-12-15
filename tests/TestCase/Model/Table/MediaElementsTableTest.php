<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Model\Table;


use Awyiss\Model\Entity\MediaElement;
use Awyiss\Model\Table\MediaElementsTable;
use Awyiss\ORM\Association\BelongsTo;
use Awyiss\ORM\Association\HasMany;
use Awyiss\ORM\Association\HasOne;
use Awyiss\Test\TestSuite\TestCase;
use Awyiss\Utility\Content\BootstrapColumn;
use Awyiss\Validation\Validator;
use Cake\Datasource\FactoryLocator;
use Cake\Datasource\RulesChecker;


/**
 * MediaElementsTable Test Case
 *
 * @see \Awyiss\Model\Table\MediaElementsTable
 */
class MediaElementsTableTest extends TestCase {
	/**
	 * @var \Awyiss\Model\Table\MediaElementsTable
	 */
	protected MediaElementsTable $mediaElementsTable;


	/**
	 * @inheritDoc
	 */
	protected function setUp(): void {
		parent::setUp();

		/** @noinspection PhpFieldAssignmentTypeMismatchInspection */
		$this->mediaElementsTable = FactoryLocator::get('Table')->get('MediaElements');
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\MediaElementsTable::ATTRIBUTABLE
	 */
	public function testAttributableConstant(): void {
		$this->assertFalse($this->mediaElementsTable::ATTRIBUTABLE);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\MediaElementsTable::TABLE
	 */
	public function testTableConstant(): void {
		$this->assertEquals('media_elements', $this->mediaElementsTable::TABLE);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\MediaElementsTable::initializeAssociations()
	 */
	public function testInitializeAssociations(): void {
		$this->assertCount(8, $this->mediaElementsTable->associations()->keys());

		// Test MediaAssignments association (HasMany)
		$this->assertTrue($this->mediaElementsTable->hasAssociation('MediaAssignments'));
		$mediaAssignmentsAssociation = $this->mediaElementsTable->getAssociation('MediaAssignments');
		$this->assertInstanceOf(HasMany::class, $mediaAssignmentsAssociation);
		$this->assertTrue($mediaAssignmentsAssociation->getCascadeCallbacks());
		$this->assertTrue($mediaAssignmentsAssociation->getDependent());
		$this->assertEquals('replace', $mediaAssignmentsAssociation->getSaveStrategy());

		// Test MediaElementAssignments association (HasMany)
		$this->assertTrue($this->mediaElementsTable->hasAssociation('MediaElementAssignments'));
		$mediaElementAssignmentsAssociation = $this->mediaElementsTable->getAssociation('MediaElementAssignments');
		$this->assertInstanceOf(HasMany::class, $mediaElementAssignmentsAssociation);
		$this->assertTrue($mediaElementAssignmentsAssociation->getCascadeCallbacks());
		$this->assertTrue($mediaElementAssignmentsAssociation->getDependent());
		$this->assertEquals('replace', $mediaElementAssignmentsAssociation->getSaveStrategy());

		// Test MediaElementSelectors association (HasMany)
		$this->assertTrue($this->mediaElementsTable->hasAssociation('MediaElementSelectors'));
		$mediaElementSelectorsAssociation = $this->mediaElementsTable->getAssociation('MediaElementSelectors');
		$this->assertInstanceOf(HasMany::class, $mediaElementSelectorsAssociation);
		$this->assertTrue($mediaElementSelectorsAssociation->getCascadeCallbacks());
		$this->assertTrue($mediaElementSelectorsAssociation->getDependent());
		$this->assertEquals('replace', $mediaElementSelectorsAssociation->getSaveStrategy());

		// Test user tracking associations
		$this->assertTrue($this->mediaElementsTable->hasAssociation('CreatedByUser'));
		$createdByUserAssociation = $this->mediaElementsTable->getAssociation('CreatedByUser');
		$this->assertInstanceOf(BelongsTo::class, $createdByUserAssociation);
		$this->assertFalse($createdByUserAssociation->getCascadeCallbacks());
		$this->assertFalse($createdByUserAssociation->getDependent());

		$this->assertTrue($this->mediaElementsTable->hasAssociation('ChangedByUser'));
		$changedByUserAssociation = $this->mediaElementsTable->getAssociation('ChangedByUser');
		$this->assertInstanceOf(BelongsTo::class, $changedByUserAssociation);
		$this->assertFalse($changedByUserAssociation->getCascadeCallbacks());
		$this->assertFalse($changedByUserAssociation->getDependent());

		$this->assertTrue($this->mediaElementsTable->hasAssociation('DeletedByUser'));
		$deletedByUserAssociation = $this->mediaElementsTable->getAssociation('DeletedByUser');
		$this->assertInstanceOf(BelongsTo::class, $deletedByUserAssociation);
		$this->assertFalse($deletedByUserAssociation->getCascadeCallbacks());
		$this->assertFalse($deletedByUserAssociation->getDependent());

		// Test translation associations
		$this->assertTrue($this->mediaElementsTable->hasAssociation('MediaElements_title_translation'));
		$titleTranslationAssociation = $this->mediaElementsTable->getAssociation('MediaElements_title_translation');
		$this->assertInstanceOf(HasOne::class, $titleTranslationAssociation);
		$this->assertFalse($titleTranslationAssociation->getCascadeCallbacks());
		$this->assertFalse($titleTranslationAssociation->getDependent());

		$this->assertTrue($this->mediaElementsTable->hasAssociation('I18n'));
		$i18nAssociation = $this->mediaElementsTable->getAssociation('I18n');
		$this->assertInstanceOf(HasMany::class, $i18nAssociation);
		$this->assertFalse($i18nAssociation->getCascadeCallbacks());
		$this->assertTrue($i18nAssociation->getDependent());
		$this->assertEquals('append', $i18nAssociation->getSaveStrategy());
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\MediaElementsTable::getColumnSpans()
	 */
	public function testGetColumnSpans(): void {
		$columnSpans = $this->mediaElementsTable->getColumnSpans();

		$this->assertIsArray($columnSpans);
		$this->assertSame([
			'12/12',
			'1/12',
			'2/12',
			'3/12',
			'4/12',
			'5/12',
			'6/12',
			'7/12',
			'8/12',
			'9/12',
			'10/12',
			'11/12',
		], array_keys($columnSpans));

		// Test that all values are valid column span objects/values
		foreach ($columnSpans as $key => $value) {
			$this->assertIsString($key);
			$this->assertInstanceOf(BootstrapColumn::class, $value);
		}
	}


	/**
	 * @testWith ["cars", false, true]
	 *           ["content_templates", true, false]
	 *           ["employees", false, true]
	 *           ["employers", false, true]
	 *           ["page_templates", true, false]
	 *           ["global_content_templates", true, false]
	 * @param string $model
	 * @param bool $entityLevel
	 * @param bool $modelLevel
	 * @return void
	 * @see \Awyiss\Model\Table\MediaElementsTable::getAssignableModels()
	 * @throws \ReflectionException
	 */
	public function testGetAssignableModels(string $model, bool $entityLevel, bool $modelLevel): void {
		$models = $this->mediaElementsTable->getAssignableModels();

		$this->assertIsArray($models);
		$this->assertArrayHasKey($model, $models);
		$modelData = $models[ $model ];

		$this->assertIsArray($modelData);

		// Test required keys
		$this->assertArrayHasKey('entityLevel', $modelData);
		$this->assertArrayHasKey('modelLevel', $modelData);
		$this->assertArrayHasKey('label', $modelData);
		$this->assertArrayHasKey('entities', $modelData);

		// Test data types
		$this->assertSame($entityLevel, $modelData['entityLevel']);
		$this->assertSame($modelLevel, $modelData['modelLevel']);
	}


	/**
	 * @testWith ["cars", false]
	 *           ["content_templates", true]
	 *           ["employees", false]
	 *           ["employers", false]
	 *           ["page_templates", true]
	 *           ["global_content_templates", true]
	 * @param string $model
	 * @param bool $hasEntities
	 * @return void
	 * @see \Awyiss\Model\Table\MediaElementsTable::getAssignableModels()
	 * @throws \ReflectionException
	 */
	public function testGetAssignableModelsWithEntities(string $model, bool $hasEntities): void {
		$modelsWithEntities = $this->mediaElementsTable->getAssignableModels(true);

		$this->assertIsArray($modelsWithEntities);
		$this->assertArrayHasKey($model, $modelsWithEntities);
		$modelData = $modelsWithEntities[ $model ];

		$this->assertIsArray($modelData);
		$this->assertArrayHasKey('entities', $modelData);
		$this->assertSame($hasEntities, !empty($modelData['entities']));
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\MediaElementsTable::validationDefault()
	 */
	public function testValidationDefault(): void {
		$validator = new Validator();
		$result = $this->mediaElementsTable->validationDefault($validator);

		$this->assertInstanceOf(Validator::class, $result);
		$this->assertSame('media_elements', $result->getI18nDomain());

		// Test required fields
		$this->assertTrue($result->hasField('title'));
		$this->assertSame('create', $result->field('title')->isPresenceRequired());

		// Test other fields
		$this->assertTrue($result->hasField('id'));
		$this->assertTrue($result->hasField('identifier'));
		$this->assertTrue($result->hasField('columnSpan'));
		$this->assertTrue($result->hasField('systemOrder'));
		$this->assertTrue($result->hasField('active'));
		$this->assertTrue($result->hasField('deleted'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\MediaElementsTable::validationDefault()
	 */
	public function testEntityValidationSuccess(): void {
		$data = [
			'title' => 'Test Media Element',
			'identifier' => 'test_media_element',
			'columnSpan' => '12/12',
			'internal' => false,
			'systemOrder' => 1,
			'active' => true,
			'deleted' => false,
		];

		$entity = $this->mediaElementsTable->newDefaultEntity();
		$this->mediaElementsTable->patchEntity($entity, $data);
		$errors = $entity->getErrors();

		$this->assertEmpty($errors, 'Valid data should not produce validation errors');
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\MediaElementsTable::validationDefault()
	 */
	public function testEntityValidationMissingRequired(): void {
		$data = [
			'identifier' => 'test_element',
		];

		$entity = $this->mediaElementsTable->newDefaultEntity();
		$this->mediaElementsTable->patchEntity($entity, $data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('title', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\MediaElementsTable::validationDefault()
	 */
	public function testEntityValidationInvalidTypes(): void {
		$data = [
			'id' => 'not_an_integer',
			'title' => true,
			'identifier' => true,
			'systemOrder' => 'not_an_integer',
			'active' => 'not_a_boolean',
			'deleted' => 'not_a_boolean',
		];

		$entity = $this->mediaElementsTable->newDefaultEntity();
		$this->mediaElementsTable->patchEntity($entity, $data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('id', $errors);
		$this->assertArrayHasKey('title', $errors);
		$this->assertArrayHasKey('identifier', $errors);
		$this->assertArrayHasKey('systemOrder', $errors);
		$this->assertArrayHasKey('active', $errors);
		$this->assertArrayHasKey('deleted', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\MediaElementsTable::validationDefault()
	 */
	public function testEntityValidationFieldLength(): void {
		$data = [
			'id' => 123456789123, // exceeds 11 char limit
			'title' => str_repeat('a', 101), // exceeds 100 char limit
			'identifier' => str_repeat('b', 51), // exceeds 50 char limit
			'systemOrder' => 123456789123, // exceeds 11 char limit
		];

		$entity = $this->mediaElementsTable->newDefaultEntity();
		$this->mediaElementsTable->patchEntity($entity, $data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('id', $errors);
		$this->assertArrayHasKey('title', $errors);
		$this->assertArrayHasKey('identifier', $errors);
		$this->assertArrayHasKey('systemOrder', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\MediaElementsTable::validationDefault()
	 */
	public function testEntityValidationNotBlank(): void {
		$data = [
			'title' => '   ', // only whitespace
			'identifier' => '   ', // only whitespace
		];

		$entity = $this->mediaElementsTable->newDefaultEntity();
		$this->mediaElementsTable->patchEntity($entity, $data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('title', $errors);
		$this->assertArrayHasKey('identifier', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\MediaElementsTable::validationDefault()
	 */
	public function testEntityValidationColumnSpanInList(): void {
		$columnSpans = $this->mediaElementsTable->getColumnSpans();
		$validColumnSpan = array_key_first($columnSpans);

		$data = [
			'title' => 'Test Element',
			'identifier' => 'test_element',
			'columnSpan' => $validColumnSpan,
		];

		$entity = $this->mediaElementsTable->newDefaultEntity();
		$this->mediaElementsTable->patchEntity($entity, $data);
		$errors = $entity->getErrors();

		$this->assertArrayNotHasKey('columnSpan', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\MediaElementsTable::validationDefault()
	 */
	public function testEntityValidationColumnSpanNotInList(): void {
		$data = [
			'title' => 'Test Element',
			'identifier' => 'test_element',
			'columnSpan' => 'invalid_column_span',
		];

		$entity = $this->mediaElementsTable->newDefaultEntity();
		$this->mediaElementsTable->patchEntity($entity, $data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('columnSpan', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\MediaElementsTable::buildRules()
	 */
	public function testBuildRulesIdentifierUnique(): void {
		$entity = $this->mediaElementsTable->get(1);

		$entity->set('id', 10);
		$entity->setNew(true);

		$result = $this->mediaElementsTable->checkRules($entity);
		$this->assertFalse($result);

		$errors = $entity->getErrors();
		$this->assertArrayHasKey('identifier', $errors);
		$this->assertArrayHasKey('identifierUnique', $errors['identifier']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\MediaElementsTable::buildRules()
	 */
	public function testBuildRulesNotDefaultElementDeletion(): void {
		// Test that default elements (id < 10) cannot be deleted
		$entity = $this->mediaElementsTable->get(1);

		$result = $this->mediaElementsTable->checkRules($entity, RulesChecker::DELETE);
		$this->assertFalse($result, 'Default element deletion should fail');

		$errors = $entity->getErrors();
		$this->assertArrayHasKey('_general', $errors);
		$this->assertArrayHasKey('notDefaultElementDeletion', $errors['_general']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\MediaElementsTable::buildRules()
	 */
	public function testBuildRulesAllowNonDefaultElementDeletion(): void {
		// Test that non-default elements (id >= 10) can be deleted
		$entity = $this->mediaElementsTable->get(1);
		$entity->set('id', 15);
		$entity->setNew(false);

		$result = $this->mediaElementsTable->checkRules($entity);
		$this->assertTrue($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\MediaElementsTable::newDefaultEntity()
	 */
	public function testNewDefaultEntity(): void {
		/** @var \Awyiss\Model\Entity\MediaElement $entity */
		$entity = $this->mediaElementsTable->newDefaultEntity();

		$this->assertInstanceOf(MediaElement::class, $entity);
		$this->assertTrue($entity->isNew());

		// Check default values
		$this->assertNull($entity->title);
		$this->assertNull($entity->identifier);
		$this->assertSame('12/12', $entity->columnSpan);
		$this->assertFalse($entity->internal);
		$this->assertSame(0, $entity->systemOrder);
		$this->assertTrue($entity->active);
		$this->assertFalse($entity->deleted);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\MediaElementsTable::newDefaultEntity()
	 */
	public function testNewDefaultEntityWithData(): void {
		$additionalData = [
			'title' => 'Custom Media Element',
			'identifier' => 'custom_element',
			'columnSpan' => '6/12',
			'internal' => true,
			'systemOrder' => 5,
			'active' => false,
		];

		/** @var \Awyiss\Model\Entity\MediaElement $entity */
		$entity = $this->mediaElementsTable->newDefaultEntity($additionalData);

		$this->assertInstanceOf(MediaElement::class, $entity);
		$this->assertTrue($entity->isNew());

		// Check custom values
		$this->assertSame('Custom Media Element', $entity->title);
		$this->assertSame('custom_element', $entity->identifier);
		$this->assertSame('6/12', $entity->columnSpan);
		$this->assertTrue($entity->internal);
		$this->assertSame(5, $entity->systemOrder);
		$this->assertFalse($entity->active);
		$this->assertFalse($entity->deleted); // Should remain default
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\MediaElementsTable::$systemOrder
	 */
	public function testSystemOrderBehavior(): void {
		$this->assertTrue($this->mediaElementsTable->hasBehavior('SystemOrder'));

		$config = $this->mediaElementsTable->getBehavior('SystemOrder')->getConfig();

		$this->assertSame(['internal'], $config['relatedColumns']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\MediaElementsTable::$translate
	 */
	public function testTranslateBehavior(): void {
		$this->assertTrue($this->mediaElementsTable->hasBehavior('Translate'));

		$config = $this->mediaElementsTable->getBehavior('Translate')->getConfig();

		$this->assertNull($config['realm'] ?? null);

		$this->assertIsArray($config['fields']);
		$this->assertSame(['title'], $config['fields']);
	}
}
