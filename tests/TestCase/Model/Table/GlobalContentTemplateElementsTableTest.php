<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Model\Table;


use Awyiss\Model\Entity\GlobalContentTemplateElement;
use Awyiss\Model\Table\GlobalContentTemplateElementsTable;
use Awyiss\ORM\Association\BelongsTo;
use Awyiss\ORM\Association\HasMany;
use Awyiss\ORM\Association\HasOne;
use Awyiss\Test\TestSuite\TestCase;
use Awyiss\Utility\Content\BootstrapColumn;
use Awyiss\Validation\Validator;
use Cake\Datasource\FactoryLocator;


/**
 * GlobalContentTemplateElementsTable Test Case
 *
 * @see \Awyiss\Model\Table\GlobalContentTemplateElementsTable
 */
class GlobalContentTemplateElementsTableTest extends TestCase {
	/**
	 * @var \Awyiss\Model\Table\GlobalContentTemplateElementsTable
	 */
	protected GlobalContentTemplateElementsTable $globalContentTemplateElementsTable;


	/**
	 * @inheritDoc
	 */
	protected function setUp(): void {
		parent::setUp();

		/** @noinspection PhpFieldAssignmentTypeMismatchInspection */
		$this->globalContentTemplateElementsTable = FactoryLocator::get('Table')->get('GlobalContentTemplateElements');
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\GlobalContentTemplateElementsTable::ATTRIBUTABLE
	 */
	public function testAttributableConstant(): void {
		$this->assertFalse($this->globalContentTemplateElementsTable::ATTRIBUTABLE);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\GlobalContentTemplateElementsTable::TABLE
	 */
	public function testTableConstant(): void {
		$this->assertEquals('global_content_template_elements', $this->globalContentTemplateElementsTable::TABLE);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\GlobalContentTemplateElementsTable::initializeAssociations()
	 */
	public function testInitializeAssociations(): void {
		$this->assertCount(4, $this->globalContentTemplateElementsTable->associations()->keys());

		$this->assertTrue($this->globalContentTemplateElementsTable->hasAssociation('GlobalContentTemplates'));
		$globalContentTemplatesAssociation = $this->globalContentTemplateElementsTable->getAssociation('GlobalContentTemplates');
		$this->assertInstanceOf(BelongsTo::class, $globalContentTemplatesAssociation);
		$this->assertEquals('INNER', $globalContentTemplatesAssociation->getJoinType());
		$this->assertFalse($globalContentTemplatesAssociation->getCascadeCallbacks());
		$this->assertFalse($globalContentTemplatesAssociation->getDependent());

		// 'MediaAssignments' must also exist
		$this->assertTrue($this->globalContentTemplateElementsTable->hasAssociation('MediaAssignments'));
		$mediaAssignmentsAssociation = $this->globalContentTemplateElementsTable->getAssociation('MediaAssignments');
		$this->assertInstanceOf(HasMany::class, $mediaAssignmentsAssociation);
		$this->assertTrue($mediaAssignmentsAssociation->getCascadeCallbacks());
		$this->assertTrue($mediaAssignmentsAssociation->getDependent());
		$this->assertEquals('replace', $mediaAssignmentsAssociation->getSaveStrategy());

		// 'GlobalContentTemplateElements_title_translation' must also exist
		$this->assertTrue($this->globalContentTemplateElementsTable->hasAssociation('GlobalContentTemplateElements_title_translation'));
		$titleTranslationAssociation = $this->globalContentTemplateElementsTable->getAssociation('GlobalContentTemplateElements_title_translation');
		$this->assertInstanceOf(HasOne::class, $titleTranslationAssociation);
		$this->assertFalse($titleTranslationAssociation->getCascadeCallbacks());
		$this->assertFalse($titleTranslationAssociation->getDependent());

		// 'I18n' must also exist
		$this->assertTrue($this->globalContentTemplateElementsTable->hasAssociation('I18n'));
		$i18nAssociation = $this->globalContentTemplateElementsTable->getAssociation('I18n');
		$this->assertInstanceOf(HasMany::class, $i18nAssociation);
		$this->assertFalse($i18nAssociation->getCascadeCallbacks());
		$this->assertTrue($i18nAssociation->getDependent());
		$this->assertEquals('append', $i18nAssociation->getSaveStrategy());
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\GlobalContentTemplateElementsTable::getColumnSpans()
	 */
	public function testGetColumnSpans(): void {
		$columnSpans = $this->globalContentTemplateElementsTable->getColumnSpans();

		$this->assertIsArray($columnSpans);
		$this->assertCount(12, $columnSpans);
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

		foreach ($columnSpans as $key => $value) {
			$this->assertIsString($key);
			$this->assertInstanceOf(BootstrapColumn::class, $value);
		}
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\GlobalContentTemplateElementsTable::validationDefault()
	 */
	public function testValidationDefault(): void {
		$validator = new Validator();
		$result = $this->globalContentTemplateElementsTable->validationDefault($validator);

		$this->assertInstanceOf(Validator::class, $result);
		$this->assertSame('global_content_template_elements', $result->getI18nDomain());

		// Test required fields
		$this->assertTrue($result->hasField('identifier'));
		$this->assertSame('create', $result->field('identifier')->isPresenceRequired());

		$this->assertTrue($result->hasField('fieldset'));
		$this->assertSame('create', $result->field('fieldset')->isPresenceRequired());

		// Test other fields
		$this->assertTrue($result->hasField('id'));
		$this->assertTrue($result->hasField('globalContentTemplateId'));
		$this->assertTrue($result->hasField('title'));
		$this->assertTrue($result->hasField('columnSpan'));
		$this->assertTrue($result->hasField('required'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\GlobalContentTemplateElementsTable::validationDefault()
	 */
	public function testEntityValidationSuccess(): void {
		$data = [
			'globalContentTemplateId' => 1,
			'identifier' => 'test_element',
			'title' => 'Test Element',
			'fieldset' => 'general',
			'columnSpan' => '5/12',
			'required' => true,
		];

		$entity = $this->globalContentTemplateElementsTable->newEntity($data);
		$errors = $entity->getErrors();

		$this->assertEmpty($errors, 'Valid data should not produce validation errors');
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\GlobalContentTemplateElementsTable::validationDefault()
	 */
	public function testEntityValidationMissingRequired(): void {
		$data = [
			'globalContentTemplateId' => 1,
			'title' => 'Test Element',
		];

		$entity = $this->globalContentTemplateElementsTable->newEntity($data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('identifier', $errors);
		$this->assertArrayHasKey('fieldset', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\GlobalContentTemplateElementsTable::validationDefault()
	 */
	public function testEntityValidationInvalidTypes(): void {
		$data = [
			'id' => 'not_an_integer',
			'globalContentTemplateId' => 'not_an_integer',
			'identifier' => true,
			'title' => true,
			'fieldset' => true,
			'required' => 'not_a_boolean',
		];

		$entity = $this->globalContentTemplateElementsTable->newEntity($data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('id', $errors);
		$this->assertArrayHasKey('globalContentTemplateId', $errors);
		$this->assertArrayHasKey('identifier', $errors);
		$this->assertArrayHasKey('title', $errors);
		$this->assertArrayHasKey('fieldset', $errors);
		$this->assertArrayHasKey('required', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\GlobalContentTemplateElementsTable::validationDefault()
	 */
	public function testEntityValidationFieldLength(): void {
		$data = [
			'id' => 123456789123, // exceeds 11 char limit
			'globalContentTemplateId' => 123456789123, // exceeds 11 char limit
			'identifier' => str_repeat('a', 62), // exceeds 61 char limit
			'title' => str_repeat('b', 101), // exceeds 100 char limit
			'fieldset' => str_repeat('c', 51), // exceeds 50 char limit
		];

		$entity = $this->globalContentTemplateElementsTable->newEntity($data, ['guard' => false]);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('id', $errors);
		$this->assertArrayHasKey('globalContentTemplateId', $errors);
		$this->assertArrayHasKey('identifier', $errors);
		$this->assertArrayHasKey('title', $errors);
		$this->assertArrayHasKey('fieldset', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\GlobalContentTemplateElementsTable::validationDefault()
	 */
	public function testEntityValidationColumnSpanInList(): void {
		// Test invalid column span
		$invalidData = [
			'globalContentTemplateId' => 1,
			'identifier' => 'test_element',
			'fieldset' => 'general',
			'columnSpan' => 'invalid_span',
		];

		$invalidEntity = $this->globalContentTemplateElementsTable->newEntity($invalidData);
		$invalidErrors = $invalidEntity->getErrors();

		$this->assertArrayHasKey('columnSpan', $invalidErrors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\GlobalContentTemplateElementsTable::validationDefault()
	 */
	public function testEntityValidationEmptyTitle(): void {
		$data = [
			'globalContentTemplateId' => 1,
			'identifier' => 'test_element',
			'fieldset' => 'general',
			'title' => '',
		];

		$entity = $this->globalContentTemplateElementsTable->newEntity($data);
		$errors = $entity->getErrors();

		// Title is allowed to be empty
		$this->assertArrayNotHasKey('title', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\GlobalContentTemplateElementsTable::buildRules()
	 */
	public function testBuildRulesGlobalContentTemplateExists(): void {
		// Test with existing global content template
		$validData = [
			'globalContentTemplateId' => 1,
			'identifier' => 'test_element',
			'fieldset' => 'general',
		];

		$validEntity = $this->globalContentTemplateElementsTable->newEntity($validData);
		$validResult = $this->globalContentTemplateElementsTable->checkRules($validEntity);
		$this->assertTrue($validResult);

		// Test with non-existing global content template
		$invalidData = [
			'globalContentTemplateId' => 99999,
			'identifier' => 'test_element',
			'fieldset' => 'general',
		];

		$invalidEntity = $this->globalContentTemplateElementsTable->newEntity($invalidData);
		$invalidResult = $this->globalContentTemplateElementsTable->checkRules($invalidEntity);
		$this->assertFalse($invalidResult);

		$errors = $invalidEntity->getErrors();
		$this->assertArrayHasKey('globalContentTemplateId', $errors);
		$this->assertArrayHasKey('globalContentTemplateExists', $errors['globalContentTemplateId']);
		$this->assertEquals('global_content_template_elements::error_global_content_template_exists', $errors['globalContentTemplateId']['globalContentTemplateExists']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\GlobalContentTemplateElementsTable::newDefaultEntity()
	 */
	public function testNewDefaultEntity(): void {
		$entity = $this->globalContentTemplateElementsTable->newDefaultEntity();

		$this->assertInstanceOf(GlobalContentTemplateElement::class, $entity);
		$this->assertTrue($entity->isNew());

		$this->assertNull($entity->title);
		$this->assertSame('', $entity->fieldset);
		$this->assertSame('12/12', $entity->columnSpan);
		$this->assertFalse($entity->required);
		$this->assertSame(0, $entity->systemOrder);
		$this->assertNull($entity->globalContentTemplateId);
		$this->assertNull($entity->identifier);

		$this->assertIsArray($entity->column);
		$this->assertCount(1, $entity->column);
		$this->assertArrayHasKey('span', $entity->column);
		$this->assertInstanceOf(BootstrapColumn::class, $entity->column['span']);
		$this->assertSame('12/12', $entity->column['span']->getFraction());
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\GlobalContentTemplateElementsTable::newDefaultEntity()
	 */
	public function testNewDefaultEntityWithData(): void {
		$additionalData = [
			'required' => true,
			'globalContentTemplateId' => 1,
			'identifier' => 'custom_element',
			'title' => 'Custom Element',
			'fieldset' => 'content',
			'systemOrder' => 5,
			'columnSpan' => '2/12',
		];

		$entity = $this->globalContentTemplateElementsTable->newDefaultEntity($additionalData);

		$this->assertInstanceOf(GlobalContentTemplateElement::class, $entity);
		$this->assertTrue($entity->isNew());

		$this->assertSame('Custom Element', $entity->title);
		$this->assertSame('content', $entity->fieldset);
		$this->assertSame('2/12', $entity->columnSpan);
		$this->assertTrue($entity->required);
		$this->assertSame(5, $entity->systemOrder);
		$this->assertSame(1, $entity->globalContentTemplateId);
		$this->assertSame('custom_element', $entity->identifier);

		$this->assertIsArray($entity->column);
		$this->assertCount(1, $entity->column);
		$this->assertArrayHasKey('span', $entity->column);
		$this->assertInstanceOf(BootstrapColumn::class, $entity->column['span']);
		$this->assertSame('2/12', $entity->column['span']->getFraction());
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\GlobalContentTemplateElementsTable::$audit
	 */
	public function testAuditBehavior(): void {
		$this->assertTrue($this->globalContentTemplateElementsTable->hasBehavior('Audit'));

		$enabled = $this->globalContentTemplateElementsTable->getBehavior('Audit')->getConfig('enabled');

		$this->assertFalse($enabled);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\GlobalContentTemplateElementsTable::$systemOrder
	 */
	public function testSystemOrderBehavior(): void {
		$this->assertTrue($this->globalContentTemplateElementsTable->hasBehavior('SystemOrder'));

		$config = $this->globalContentTemplateElementsTable->getBehavior('SystemOrder')->getConfig();

		$this->assertTrue($config['enabled']);
		$this->assertSame(['globalContentTemplateId', 'fieldset'], $config['relatedColumns']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\GlobalContentTemplateElementsTable::$translate
	 */
	public function testTranslateBehavior(): void {
		$this->assertTrue($this->globalContentTemplateElementsTable->hasBehavior('Translate'));

		$config = $this->globalContentTemplateElementsTable->getBehavior('Translate')->getConfig();

		// Auto-realm
		$this->assertNull($config['realm'] ?? null);

		$this->assertIsArray($config['fields']);
		$this->assertSame(['title'], $config['fields']);
	}
}
