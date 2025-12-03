<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Model\Table;


use Awyiss\Model\Entity\MediaElementSelector;
use Awyiss\Model\Table\MediaElementSelectorsTable;
use Awyiss\ORM\Association\BelongsTo;
use Awyiss\ORM\Association\HasMany;
use Awyiss\ORM\Association\HasOne;
use Awyiss\Test\TestSuite\TestCase;
use Awyiss\Utility\Content\BootstrapColumn;
use Awyiss\Validation\Validator;
use Cake\Datasource\FactoryLocator;


/**
 * MediaElementSelectorsTable Test Case
 *
 * @see \Awyiss\Model\Table\MediaElementSelectorsTable
 */
class MediaElementSelectorsTableTest extends TestCase {
	/**
	 * @var \Awyiss\Model\Table\MediaElementSelectorsTable
	 */
	protected MediaElementSelectorsTable $mediaElementSelectorsTable;


	/**
	 * @inheritDoc
	 */
	protected function setUp(): void {
		parent::setUp();

		/** @noinspection PhpFieldAssignmentTypeMismatchInspection */
		$this->mediaElementSelectorsTable = FactoryLocator::get('Table')->get('MediaElementSelectors');
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\MediaElementSelectorsTable::ATTRIBUTABLE
	 */
	public function testAttributableConstant(): void {
		$this->assertFalse($this->mediaElementSelectorsTable::ATTRIBUTABLE);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\MediaElementSelectorsTable::TABLE
	 */
	public function testTableConstant(): void {
		$this->assertEquals('media_element_selectors', $this->mediaElementSelectorsTable::TABLE);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\MediaElementSelectorsTable::initializeAssociations()
	 */
	public function testInitializeAssociations(): void {
		$this->assertCount(5, $this->mediaElementSelectorsTable->associations()->keys());

		// Test MediaAssignments association (HasMany)
		$this->assertTrue($this->mediaElementSelectorsTable->hasAssociation('MediaAssignments'));
		$mediaAssignmentsAssociation = $this->mediaElementSelectorsTable->getAssociation('MediaAssignments');
		$this->assertInstanceOf(HasMany::class, $mediaAssignmentsAssociation);
		$this->assertTrue($mediaAssignmentsAssociation->getCascadeCallbacks());
		$this->assertTrue($mediaAssignmentsAssociation->getDependent());
		$this->assertEquals('replace', $mediaAssignmentsAssociation->getSaveStrategy());
		$this->assertSame(['media_element_id', 'identifier'], $mediaAssignmentsAssociation->getBindingKey());
		$this->assertSame(['media_element_id', 'media_element_selector_identifier'], $mediaAssignmentsAssociation->getForeignKey());

		// Test MediaElements association (BelongsTo)
		$this->assertTrue($this->mediaElementSelectorsTable->hasAssociation('MediaElements'));
		$mediaElementsAssociation = $this->mediaElementSelectorsTable->getAssociation('MediaElements');
		$this->assertInstanceOf(BelongsTo::class, $mediaElementsAssociation);
		$this->assertFalse($mediaElementsAssociation->getCascadeCallbacks());
		$this->assertFalse($mediaElementsAssociation->getDependent());
		$this->assertSame('INNER', $mediaElementsAssociation->getJoinType());

		// Test MediaSelectors association (BelongsTo)
		$this->assertTrue($this->mediaElementSelectorsTable->hasAssociation('MediaSelectors'));
		$mediaSelectorsAssociation = $this->mediaElementSelectorsTable->getAssociation('MediaSelectors');
		$this->assertInstanceOf(BelongsTo::class, $mediaSelectorsAssociation);
		$this->assertFalse($mediaSelectorsAssociation->getCascadeCallbacks());
		$this->assertFalse($mediaSelectorsAssociation->getDependent());
		$this->assertSame('INNER', $mediaSelectorsAssociation->getJoinType());

		// Test translation associations
		$this->assertTrue($this->mediaElementSelectorsTable->hasAssociation('MediaElementSelectors_title_translation'));
		$titleTranslationAssociation = $this->mediaElementSelectorsTable->getAssociation('MediaElementSelectors_title_translation');
		$this->assertInstanceOf(HasOne::class, $titleTranslationAssociation);
		$this->assertFalse($titleTranslationAssociation->getCascadeCallbacks());
		$this->assertFalse($titleTranslationAssociation->getDependent());

		$this->assertTrue($this->mediaElementSelectorsTable->hasAssociation('I18n'));
		$i18nAssociation = $this->mediaElementSelectorsTable->getAssociation('I18n');
		$this->assertInstanceOf(HasMany::class, $i18nAssociation);
		$this->assertFalse($i18nAssociation->getCascadeCallbacks());
		$this->assertTrue($i18nAssociation->getDependent());
		$this->assertEquals('append', $i18nAssociation->getSaveStrategy());
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\MediaElementSelectorsTable::getColumnSpans()
	 */
	public function testGetColumnSpans(): void {
		$columnSpans = $this->mediaElementSelectorsTable->getColumnSpans();

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
	 * @return void
	 * @see \Awyiss\Model\Table\MediaElementSelectorsTable::validationDefault()
	 */
	public function testValidationDefault(): void {
		$validator = new Validator();
		$result = $this->mediaElementSelectorsTable->validationDefault($validator);

		$this->assertInstanceOf(Validator::class, $result);
		$this->assertSame('media_element_selectors', $result->getI18nDomain());

		// Test required fields
		$this->assertTrue($result->hasField('identifier'));
		$this->assertSame('create', $result->field('identifier')->isPresenceRequired());

		// Test other fields
		$this->assertTrue($result->hasField('id'));
		$this->assertTrue($result->hasField('mediaElementId'));
		$this->assertTrue($result->hasField('mediaSelectorId'));
		$this->assertTrue($result->hasField('title'));
		$this->assertTrue($result->hasField('columnSpan'));
		$this->assertTrue($result->hasField('required'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\MediaElementSelectorsTable::validationDefault()
	 */
	public function testEntityValidationSuccess(): void {
		$data = [
			'mediaElementId' => 2,
			'mediaSelectorId' => 1,
			'identifier' => 'test_selector',
			'title' => 'Test Media Element Selector',
			'columnSpan' => '12/12',
			'required' => false,
		];

		$entity = $this->mediaElementSelectorsTable->newEntity($data);
		$errors = $entity->getErrors();

		$this->assertEmpty($errors, 'Valid data should not produce validation errors');
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\MediaElementSelectorsTable::validationDefault()
	 */
	public function testEntityValidationMissingRequired(): void {
		$data = [
			'mediaElementId' => 2,
			'mediaSelectorId' => 1,
		];

		$entity = $this->mediaElementSelectorsTable->newEntity($data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('identifier', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\MediaElementSelectorsTable::validationDefault()
	 */
	public function testEntityValidationInvalidTypes(): void {
		$data = [
			'id' => 'not_an_integer',
			'mediaElementId' => 'not_an_integer',
			'mediaSelectorId' => 'not_an_integer',
			'identifier' => true,
			'title' => true,
			'required' => 'not_a_boolean',
		];

		$entity = $this->mediaElementSelectorsTable->newEntity($data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('id', $errors);
		$this->assertArrayHasKey('mediaElementId', $errors);
		$this->assertArrayHasKey('mediaSelectorId', $errors);
		$this->assertArrayHasKey('identifier', $errors);
		$this->assertArrayHasKey('title', $errors);
		$this->assertArrayHasKey('required', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\MediaElementSelectorsTable::validationDefault()
	 */
	public function testEntityValidationFieldLength(): void {
		$data = [
			'id' => 123456789123, // exceeds 11 char limit
			'mediaElementId' => 123456789123, // exceeds 11 char limit
			'mediaSelectorId' => 123456789123, // exceeds 11 char limit
			'identifier' => str_repeat('a', 51), // exceeds 50 char limit
			'title' => str_repeat('b', 101), // exceeds 100 char limit
		];

		$entity = $this->mediaElementSelectorsTable->newEntity($data, ['guard' => false]);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('id', $errors);
		$this->assertArrayHasKey('mediaElementId', $errors);
		$this->assertArrayHasKey('mediaSelectorId', $errors);
		$this->assertArrayHasKey('identifier', $errors);
		$this->assertArrayHasKey('title', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\MediaElementSelectorsTable::validationDefault()
	 */
	public function testEntityValidationNotBlank(): void {
		$data = [
			'mediaElementId' => 2,
			'mediaSelectorId' => 1,
			'identifier' => '   ', // only whitespace
			'title' => '   ', // only whitespace
		];

		$entity = $this->mediaElementSelectorsTable->newEntity($data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('identifier', $errors);
		$this->assertArrayHasKey('title', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\MediaElementSelectorsTable::validationDefault()
	 */
	public function testEntityValidationColumnSpanInList(): void {
		$columnSpans = $this->mediaElementSelectorsTable->getColumnSpans();
		$validColumnSpan = array_key_first($columnSpans);

		$data = [
			'mediaElementId' => 2,
			'mediaSelectorId' => 1,
			'identifier' => 'test_selector',
			'columnSpan' => $validColumnSpan,
		];

		$entity = $this->mediaElementSelectorsTable->newEntity($data);
		$errors = $entity->getErrors();

		$this->assertArrayNotHasKey('columnSpan', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\MediaElementSelectorsTable::validationDefault()
	 */
	public function testEntityValidationColumnSpanNotInList(): void {
		$data = [
			'mediaElementId' => 2,
			'mediaSelectorId' => 1,
			'identifier' => 'test_selector',
			'columnSpan' => 'invalid_column_span',
		];

		$entity = $this->mediaElementSelectorsTable->newEntity($data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('columnSpan', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\MediaElementSelectorsTable::buildRules()
	 */
	public function testBuildRulesMediaElementExists(): void {
		// Test with existing media element
		$data = [
			'mediaElementId' => 2,
			'mediaSelectorId' => 1,
			'identifier' => 'test_selector',
		];

		$entity = $this->mediaElementSelectorsTable->newEntity($data);
		$result = $this->mediaElementSelectorsTable->checkRules($entity);
		$this->assertTrue($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\MediaElementSelectorsTable::buildRules()
	 */
	public function testBuildRulesMediaElementNotExists(): void {
		// Test with non-existing media element
		$data = [
			'mediaElementId' => 99999,
			'mediaSelectorId' => 1,
			'identifier' => 'test_selector',
		];

		$entity = $this->mediaElementSelectorsTable->newEntity($data);
		$result = $this->mediaElementSelectorsTable->checkRules($entity);
		$this->assertFalse($result);

		$errors = $entity->getErrors();
		$this->assertArrayHasKey('mediaElementId', $errors);
		$this->assertArrayHasKey('mediaElementExists', $errors['mediaElementId']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\MediaElementSelectorsTable::buildRules()
	 */
	public function testBuildRulesMediaSelectorExists(): void {
		// Test with existing media selector
		$data = [
			'mediaElementId' => 2,
			'mediaSelectorId' => 1,
			'identifier' => 'test_selector',
		];

		$entity = $this->mediaElementSelectorsTable->newEntity($data);
		$result = $this->mediaElementSelectorsTable->checkRules($entity);
		$this->assertTrue($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\MediaElementSelectorsTable::buildRules()
	 */
	public function testBuildRulesMediaSelectorNotExists(): void {
		// Test with non-existing media selector
		$data = [
			'mediaElementId' => 2,
			'mediaSelectorId' => 99999,
			'identifier' => 'test_selector',
		];

		$entity = $this->mediaElementSelectorsTable->newEntity($data);
		$result = $this->mediaElementSelectorsTable->checkRules($entity);
		$this->assertFalse($result);

		$errors = $entity->getErrors();
		$this->assertArrayHasKey('mediaSelectorId', $errors);
		$this->assertArrayHasKey('mediaSelectorExists', $errors['mediaSelectorId']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\MediaElementSelectorsTable::buildRules()
	 */
	public function testBuildRulesIdentifierUniqueForMediaElement(): void {
		/** @var \Awyiss\Model\Entity\MediaElementSelector $entity */
		$entity = $this->mediaElementSelectorsTable->get(2);
		$entity->unset('id');
		$entity->setNew(true);

		$saved2 = $this->mediaElementSelectorsTable->checkRules($entity);
		$this->assertFalse($saved2, 'Second entity should fail due to duplicate identifier for same media element');

		$errors = $entity->getErrors();
		$this->assertArrayHasKey('identifier', $errors);
		$this->assertArrayHasKey('identifierUniqueForMediaElement', $errors['identifier']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\MediaElementSelectorsTable::newDefaultEntity()
	 */
	public function testNewDefaultEntity(): void {
		/** @var \Awyiss\Model\Entity\MediaElementSelector $entity */
		$entity = $this->mediaElementSelectorsTable->newDefaultEntity();

		$this->assertInstanceOf(MediaElementSelector::class, $entity);
		$this->assertTrue($entity->isNew());

		// Check default values
		$this->assertNull($entity->mediaElementId);
		$this->assertNull($entity->mediaSelectorId);
		$this->assertNull($entity->identifier);
		$this->assertNull($entity->title);
		$this->assertSame('12/12', $entity->columnSpan);
		$this->assertFalse($entity->required);
		$this->assertSame(0, $entity->systemOrder);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\MediaElementSelectorsTable::newDefaultEntity()
	 */
	public function testNewDefaultEntityWithData(): void {
		$additionalData = [
			'mediaElementId' => 3,
			'mediaSelectorId' => 2,
			'identifier' => 'custom_selector',
			'title' => 'Custom Media Element Selector',
			'columnSpan' => '6/12',
			'required' => true,
			'systemOrder' => 5,
		];

		/** @var \Awyiss\Model\Entity\MediaElementSelector $entity */
		$entity = $this->mediaElementSelectorsTable->newDefaultEntity($additionalData);

		$this->assertInstanceOf(MediaElementSelector::class, $entity);
		$this->assertTrue($entity->isNew());

		// Check custom values
		$this->assertSame(3, $entity->mediaElementId);
		$this->assertSame(2, $entity->mediaSelectorId);
		$this->assertSame('custom_selector', $entity->identifier);
		$this->assertSame('Custom Media Element Selector', $entity->title);
		$this->assertSame('6/12', $entity->columnSpan);
		$this->assertTrue($entity->required);
		$this->assertSame(5, $entity->systemOrder);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\MediaElementSelectorsTable::$audit
	 */
	public function testAuditBehavior(): void {
		$this->assertTrue($this->mediaElementSelectorsTable->hasBehavior('Audit'));

		$config = $this->mediaElementSelectorsTable->getBehavior('Audit')->getConfig();

		$this->assertArrayHasKey('enabled', $config);
		$this->assertFalse($config['enabled']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\MediaElementSelectorsTable::$translate
	 */
	public function testTranslateBehavior(): void {
		$this->assertTrue($this->mediaElementSelectorsTable->hasBehavior('Translate'));

		$config = $this->mediaElementSelectorsTable->getBehavior('Translate')->getConfig();

		// Auto-realm
		$this->assertNull($config['realm'] ?? null);

		$this->assertIsArray($config['fields']);
		$this->assertSame(['title'], $config['fields']);
	}
}
