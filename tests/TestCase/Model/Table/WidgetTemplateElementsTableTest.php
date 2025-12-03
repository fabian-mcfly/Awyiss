<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Model\Table;


use Awyiss\Model\Entity\WidgetTemplateElement;
use Awyiss\Model\Table\WidgetTemplateElementsTable;
use Awyiss\ORM\Association\BelongsTo;
use Awyiss\ORM\Association\HasMany;
use Awyiss\ORM\Association\HasOne;
use Awyiss\Test\TestSuite\TestCase;
use Awyiss\Utility\Content\BootstrapColumn;
use Awyiss\Validation\Validator;
use Cake\Datasource\FactoryLocator;


/**
 * WidgetTemplateElementsTable Test Case
 *
 * @see \Awyiss\Model\Table\WidgetTemplateElementsTable
 */
class WidgetTemplateElementsTableTest extends TestCase {
	/**
	 * @var \Awyiss\Model\Table\WidgetTemplateElementsTable
	 */
	protected WidgetTemplateElementsTable $widgetTemplateElementsTable;


	/**
	 * @inheritDoc
	 */
	protected function setUp(): void {
		parent::setUp();

		/** @noinspection PhpFieldAssignmentTypeMismatchInspection */
		$this->widgetTemplateElementsTable = FactoryLocator::get('Table')->get('WidgetTemplateElements');
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\WidgetTemplateElementsTable::ATTRIBUTABLE
	 */
	public function testAttributableConstant(): void {
		$this->assertFalse($this->widgetTemplateElementsTable::ATTRIBUTABLE);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\WidgetTemplateElementsTable::TABLE
	 */
	public function testTableConstant(): void {
		$this->assertEquals('widget_template_elements', $this->widgetTemplateElementsTable::TABLE);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\WidgetTemplateElementsTable::initializeAssociations()
	 */
	public function testInitializeAssociations(): void {
		$this->assertCount(4, $this->widgetTemplateElementsTable->associations()->keys());

		$this->assertTrue($this->widgetTemplateElementsTable->hasAssociation('WidgetTemplates'));
		$widgetTemplatesAssociation = $this->widgetTemplateElementsTable->getAssociation('WidgetTemplates');
		$this->assertInstanceOf(BelongsTo::class, $widgetTemplatesAssociation);
		$this->assertEquals('INNER', $widgetTemplatesAssociation->getJoinType());
		$this->assertFalse($widgetTemplatesAssociation->getCascadeCallbacks());
		$this->assertFalse($widgetTemplatesAssociation->getDependent());

		// 'MediaAssignments' must also exist
		$this->assertTrue($this->widgetTemplateElementsTable->hasAssociation('MediaAssignments'));
		$mediaAssignmentsAssociation = $this->widgetTemplateElementsTable->getAssociation('MediaAssignments');
		$this->assertInstanceOf(HasMany::class, $mediaAssignmentsAssociation);
		$this->assertTrue($mediaAssignmentsAssociation->getCascadeCallbacks());
		$this->assertTrue($mediaAssignmentsAssociation->getDependent());
		$this->assertEquals('replace', $mediaAssignmentsAssociation->getSaveStrategy());

		// 'WidgetTemplateElements_title_translation' must also exist
		$this->assertTrue($this->widgetTemplateElementsTable->hasAssociation('WidgetTemplateElements_title_translation'));
		$titleTranslationAssociation = $this->widgetTemplateElementsTable->getAssociation('WidgetTemplateElements_title_translation');
		$this->assertInstanceOf(HasOne::class, $titleTranslationAssociation);
		$this->assertFalse($titleTranslationAssociation->getCascadeCallbacks());
		$this->assertFalse($titleTranslationAssociation->getDependent());

		// 'I18n' must also exist
		$this->assertTrue($this->widgetTemplateElementsTable->hasAssociation('I18n'));
		$i18nAssociation = $this->widgetTemplateElementsTable->getAssociation('I18n');
		$this->assertInstanceOf(HasMany::class, $i18nAssociation);
		$this->assertFalse($i18nAssociation->getCascadeCallbacks());
		$this->assertTrue($i18nAssociation->getDependent());
		$this->assertEquals('append', $i18nAssociation->getSaveStrategy());
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\WidgetTemplateElementsTable::getColumnSpans()
	 */
	public function testGetColumnSpans(): void {
		$columnSpans = $this->widgetTemplateElementsTable->getColumnSpans();

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
	 * @see \Awyiss\Model\Table\WidgetTemplateElementsTable::validationDefault()
	 */
	public function testValidationDefault(): void {
		$validator = new Validator();
		$result = $this->widgetTemplateElementsTable->validationDefault($validator);

		$this->assertInstanceOf(Validator::class, $result);
		$this->assertSame('widget_template_elements', $result->getI18nDomain());

		// Test required fields
		$this->assertTrue($result->hasField('identifier'));
		$this->assertSame('create', $result->field('identifier')->isPresenceRequired());

		$this->assertTrue($result->hasField('fieldset'));
		$this->assertSame('create', $result->field('fieldset')->isPresenceRequired());

		// Test other fields
		$this->assertTrue($result->hasField('id'));
		$this->assertTrue($result->hasField('widgetTemplateId'));
		$this->assertTrue($result->hasField('title'));
		$this->assertTrue($result->hasField('columnSpan'));
		$this->assertTrue($result->hasField('required'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\WidgetTemplateElementsTable::validationDefault()
	 */
	public function testEntityValidationSuccess(): void {
		$data = [
			'widgetTemplateId' => 1,
			'identifier' => 'test_element',
			'title' => 'Test Element',
			'fieldset' => 'general',
			'columnSpan' => '5/12',
			'required' => true,
		];

		$entity = $this->widgetTemplateElementsTable->newEntity($data);
		$errors = $entity->getErrors();

		$this->assertEmpty($errors, 'Valid data should not produce validation errors');
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\WidgetTemplateElementsTable::validationDefault()
	 */
	public function testEntityValidationMissingRequired(): void {
		$data = [
			'widgetTemplateId' => 1,
			'title' => 'Test Element',
		];

		$entity = $this->widgetTemplateElementsTable->newEntity($data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('identifier', $errors);
		$this->assertArrayHasKey('fieldset', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\WidgetTemplateElementsTable::validationDefault()
	 */
	public function testEntityValidationInvalidTypes(): void {
		$data = [
			'id' => 'not_an_integer',
			'widgetTemplateId' => 'not_an_integer',
			'identifier' => true,
			'title' => true,
			'fieldset' => true,
			'required' => 'not_a_boolean',
		];

		$entity = $this->widgetTemplateElementsTable->newEntity($data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('id', $errors);
		$this->assertArrayHasKey('widgetTemplateId', $errors);
		$this->assertArrayHasKey('identifier', $errors);
		$this->assertArrayHasKey('title', $errors);
		$this->assertArrayHasKey('fieldset', $errors);
		$this->assertArrayHasKey('required', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\WidgetTemplateElementsTable::validationDefault()
	 */
	public function testEntityValidationFieldLength(): void {
		$data = [
			'id' => 123456789123, // exceeds 11 char limit
			'widgetTemplateId' => 123456789123, // exceeds 11 char limit
			'identifier' => str_repeat('a', 62), // exceeds 61 char limit
			'title' => str_repeat('b', 101), // exceeds 100 char limit
			'fieldset' => str_repeat('c', 51), // exceeds 50 char limit
		];

		$entity = $this->widgetTemplateElementsTable->newEntity($data, ['guard' => false]);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('id', $errors);
		$this->assertArrayHasKey('widgetTemplateId', $errors);
		$this->assertArrayHasKey('identifier', $errors);
		$this->assertArrayHasKey('title', $errors);
		$this->assertArrayHasKey('fieldset', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\WidgetTemplateElementsTable::validationDefault()
	 */
	public function testEntityValidationColumnSpanInList(): void {
		// Test invalid column span
		$invalidData = [
			'widgetTemplateId' => 1,
			'identifier' => 'test_element',
			'fieldset' => 'general',
			'columnSpan' => 'invalid_span',
		];

		$invalidEntity = $this->widgetTemplateElementsTable->newEntity($invalidData);
		$invalidErrors = $invalidEntity->getErrors();

		$this->assertArrayHasKey('columnSpan', $invalidErrors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\WidgetTemplateElementsTable::validationDefault()
	 */
	public function testEntityValidationEmptyTitle(): void {
		$data = [
			'widgetTemplateId' => 1,
			'identifier' => 'test_element',
			'fieldset' => 'general',
			'title' => '',
		];

		$entity = $this->widgetTemplateElementsTable->newEntity($data);
		$errors = $entity->getErrors();

		// Title is allowed to be empty
		$this->assertArrayNotHasKey('title', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\WidgetTemplateElementsTable::buildRules()
	 */
	public function testBuildRulesWidgetTemplateExists(): void {
		// Test with existing widget template
		$validData = [
			'widgetTemplateId' => 1,
			'identifier' => 'test_element',
			'fieldset' => 'general',
		];

		$validEntity = $this->widgetTemplateElementsTable->newEntity($validData);
		$validResult = $this->widgetTemplateElementsTable->checkRules($validEntity);
		$this->assertTrue($validResult);

		// Test with non-existing widget template
		$invalidData = [
			'widgetTemplateId' => 99999,
			'identifier' => 'test_element',
			'fieldset' => 'general',
		];

		$invalidEntity = $this->widgetTemplateElementsTable->newEntity($invalidData);
		$invalidResult = $this->widgetTemplateElementsTable->checkRules($invalidEntity);
		$this->assertFalse($invalidResult);

		$errors = $invalidEntity->getErrors();
		$this->assertArrayHasKey('widgetTemplateId', $errors);
		$this->assertArrayHasKey('widgetTemplateExists', $errors['widgetTemplateId']);
		$this->assertEquals('widget_template_elements::error_widget_template_exists', $errors['widgetTemplateId']['widgetTemplateExists']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\WidgetTemplateElementsTable::newDefaultEntity()
	 */
	public function testNewDefaultEntity(): void {
		$entity = $this->widgetTemplateElementsTable->newDefaultEntity();

		$this->assertInstanceOf(WidgetTemplateElement::class, $entity);
		$this->assertTrue($entity->isNew());

		$this->assertNull($entity->title);
		$this->assertSame('', $entity->fieldset);
		$this->assertSame('12/12', $entity->columnSpan);
		$this->assertFalse($entity->required);
		$this->assertSame(0, $entity->systemOrder);
		$this->assertNull($entity->widgetTemplateId);
		$this->assertNull($entity->identifier);

		$this->assertIsArray($entity->column);
		$this->assertCount(1, $entity->column);
		$this->assertArrayHasKey('span', $entity->column);
		$this->assertInstanceOf(BootstrapColumn::class, $entity->column['span']);
		$this->assertSame('12/12', $entity->column['span']->getFraction());
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\WidgetTemplateElementsTable::newDefaultEntity()
	 */
	public function testNewDefaultEntityWithData(): void {
		$additionalData = [
			'required' => true,
			'widgetTemplateId' => 1,
			'identifier' => 'custom_element',
			'title' => 'Custom Element',
			'fieldset' => 'content',
			'systemOrder' => 5,
			'columnSpan' => '2/12',
		];

		$entity = $this->widgetTemplateElementsTable->newDefaultEntity($additionalData);

		$this->assertInstanceOf(WidgetTemplateElement::class, $entity);
		$this->assertTrue($entity->isNew());

		$this->assertSame('Custom Element', $entity->title);
		$this->assertSame('content', $entity->fieldset);
		$this->assertSame('2/12', $entity->columnSpan);
		$this->assertTrue($entity->required);
		$this->assertSame(5, $entity->systemOrder);
		$this->assertSame(1, $entity->widgetTemplateId);
		$this->assertSame('custom_element', $entity->identifier);

		$this->assertIsArray($entity->column);
		$this->assertCount(1, $entity->column);
		$this->assertArrayHasKey('span', $entity->column);
		$this->assertInstanceOf(BootstrapColumn::class, $entity->column['span']);
		$this->assertSame('2/12', $entity->column['span']->getFraction());
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\WidgetTemplateElementsTable::$audit
	 */
	public function testAuditBehavior(): void {
		$this->assertTrue($this->widgetTemplateElementsTable->hasBehavior('Audit'));

		$enabled = $this->widgetTemplateElementsTable->getBehavior('Audit')->getConfig('enabled');

		$this->assertFalse($enabled);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\WidgetTemplateElementsTable::$systemOrder
	 */
	public function testSystemOrderBehavior(): void {
		$this->assertTrue($this->widgetTemplateElementsTable->hasBehavior('SystemOrder'));

		$config = $this->widgetTemplateElementsTable->getBehavior('SystemOrder')->getConfig();

		$this->assertTrue($config['enabled']);
		$this->assertSame(['widgetTemplateId', 'fieldset'], $config['relatedColumns']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\WidgetTemplateElementsTable::$translate
	 */
	public function testTranslateBehavior(): void {
		$this->assertTrue($this->widgetTemplateElementsTable->hasBehavior('Translate'));

		$config = $this->widgetTemplateElementsTable->getBehavior('Translate')->getConfig();

		// Auto-realm
		$this->assertNull($config['realm'] ?? null);

		$this->assertIsArray($config['fields']);
		$this->assertSame(['title'], $config['fields']);
	}
}
