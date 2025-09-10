<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Model\Table;


use Awyiss\Model\Entity\ContentTemplateElement;
use Awyiss\Model\Table\ContentTemplateElementsTable;
use Awyiss\ORM\Association\BelongsTo;
use Awyiss\ORM\Association\HasMany;
use Awyiss\ORM\Association\HasOne;
use Awyiss\Test\TestSuite\TestCase;
use Awyiss\Utility\Content\BootstrapColumn;
use Awyiss\Validation\Validator;
use Cake\Datasource\FactoryLocator;


/**
 * ContentTemplateElementsTable Test Case
 *
 * @see \Awyiss\Model\Table\ContentTemplateElementsTable
 */
class ContentTemplateElementsTableTest extends TestCase {
	/**
	 * @var \Awyiss\Model\Table\ContentTemplateElementsTable
	 */
	protected ContentTemplateElementsTable $contentTemplateElementsTable;


	/**
	 * @inheritDoc
	 */
	protected function setUp(): void {
		parent::setUp();

		/** @noinspection PhpFieldAssignmentTypeMismatchInspection */
		$this->contentTemplateElementsTable = FactoryLocator::get('Table')->get('ContentTemplateElements');
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\ContentTemplateElementsTable::ATTRIBUTABLE
	 */
	public function testAttributableConstant(): void {
		$this->assertFalse($this->contentTemplateElementsTable::ATTRIBUTABLE);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\ContentTemplateElementsTable::TABLE
	 */
	public function testTableConstant(): void {
		$this->assertEquals('content_template_elements', $this->contentTemplateElementsTable::TABLE);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\ContentTemplateElementsTable::initializeAssociations()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testInitializeAssociations(): void {
		$this->assertCount(4, $this->contentTemplateElementsTable->associations()->keys());

		$this->assertTrue($this->contentTemplateElementsTable->hasAssociation('ContentTemplates'));
		$contentTemplatesAssociation = $this->contentTemplateElementsTable->getAssociation('ContentTemplates');
		$this->assertInstanceOf(BelongsTo::class, $contentTemplatesAssociation);
		$this->assertEquals('INNER', $contentTemplatesAssociation->getJoinType());
		$this->assertFalse($contentTemplatesAssociation->getCascadeCallbacks());
		$this->assertFalse($contentTemplatesAssociation->getDependent());

		// Test MediaAssignments association (HasMany)
		$this->assertTrue($this->contentTemplateElementsTable->hasAssociation('MediaAssignments'));
		$mediaAssignmentsAssociation = $this->contentTemplateElementsTable->getAssociation('MediaAssignments');
		$this->assertInstanceOf(HasMany::class, $mediaAssignmentsAssociation);
		$this->assertTrue($mediaAssignmentsAssociation->getCascadeCallbacks());
		$this->assertTrue($mediaAssignmentsAssociation->getDependent());

		// 'ContentTemplateElements_title_translation' must also exist
		$this->assertTrue($this->contentTemplateElementsTable->hasAssociation('ContentTemplateElements_title_translation'));
		$titleTranslationAssociation = $this->contentTemplateElementsTable->getAssociation('ContentTemplateElements_title_translation');
		$this->assertInstanceOf(HasOne::class, $titleTranslationAssociation);
		$this->assertFalse($titleTranslationAssociation->getCascadeCallbacks());
		$this->assertFalse($titleTranslationAssociation->getDependent());

		// 'I18n' must also exist
		$this->assertTrue($this->contentTemplateElementsTable->hasAssociation('I18n'));
		$i18nAssociation = $this->contentTemplateElementsTable->getAssociation('I18n');
		$this->assertInstanceOf(HasMany::class, $i18nAssociation);
		$this->assertFalse($i18nAssociation->getCascadeCallbacks());
		$this->assertTrue($i18nAssociation->getDependent());
		$this->assertEquals('append', $i18nAssociation->getSaveStrategy());
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\ContentTemplateElementsTable::getColumnSpans()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testGetColumnSpans(): void {
		$columnSpans = $this->contentTemplateElementsTable->getColumnSpans();

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
	 * @see \Awyiss\Model\Table\ContentTemplateElementsTable::validationDefault()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testValidationDefault(): void {
		$validator = new Validator();
		$result = $this->contentTemplateElementsTable->validationDefault($validator);

		$this->assertInstanceOf(Validator::class, $result);
		$this->assertSame('content_template_elements', $result->getI18nDomain());

		// Test required fields
		$this->assertTrue($result->hasField('identifier'));
		$this->assertSame('create', $result->field('identifier')->isPresenceRequired());

		$this->assertTrue($result->hasField('fieldset'));
		$this->assertSame('create', $result->field('fieldset')->isPresenceRequired());

		// Test other fields
		$this->assertTrue($result->hasField('id'));
		$this->assertTrue($result->hasField('contentTemplateId'));
		$this->assertTrue($result->hasField('title'));
		$this->assertTrue($result->hasField('columnSpan'));
		$this->assertTrue($result->hasField('required'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\ContentTemplateElementsTable::validationDefault()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testEntityValidationSuccess(): void {
		$data = [
			'contentTemplateId' => 1,
			'identifier' => 'test_element',
			'title' => 'Test Element',
			'fieldset' => 'general',
			'columnSpan' => '5/12',
			'required' => true,
		];

		$entity = $this->contentTemplateElementsTable->newEntity($data);
		$errors = $entity->getErrors();

		$this->assertEmpty($errors, 'Valid data should not produce validation errors');
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\ContentTemplateElementsTable::validationDefault()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testEntityValidationMissingRequired(): void {
		$data = [
			'contentTemplateId' => 1,
			'title' => 'Test Element',
		];

		$entity = $this->contentTemplateElementsTable->newEntity($data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('identifier', $errors);
		$this->assertArrayHasKey('fieldset', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\ContentTemplateElementsTable::validationDefault()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testEntityValidationInvalidTypes(): void {
		$data = [
			'id' => 'not_an_integer',
			'contentTemplateId' => 'not_an_integer',
			'identifier' => true,
			'title' => true,
			'fieldset' => true,
			'required' => 'not_a_boolean',
		];

		$entity = $this->contentTemplateElementsTable->newEntity($data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('id', $errors);
		$this->assertArrayHasKey('contentTemplateId', $errors);
		$this->assertArrayHasKey('identifier', $errors);
		$this->assertArrayHasKey('title', $errors);
		$this->assertArrayHasKey('fieldset', $errors);
		$this->assertArrayHasKey('required', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\ContentTemplateElementsTable::validationDefault()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testEntityValidationFieldLength(): void {
		$data = [
			'id' => 123456789123, // exceeds 11 char limit
			'contentTemplateId' => 123456789123, // exceeds 11 char limit
			'identifier' => str_repeat('a', 62), // exceeds 61 char limit
			'title' => str_repeat('b', 101), // exceeds 100 char limit
			'fieldset' => str_repeat('c', 51), // exceeds 50 char limit
		];

		$entity = $this->contentTemplateElementsTable->newEntity($data, ['guard' => false]);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('id', $errors);
		$this->assertArrayHasKey('contentTemplateId', $errors);
		$this->assertArrayHasKey('identifier', $errors);
		$this->assertArrayHasKey('title', $errors);
		$this->assertArrayHasKey('fieldset', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\ContentTemplateElementsTable::validationDefault()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testEntityValidationColumnSpanInList(): void {
		// Test invalid column span
		$invalidData = [
			'contentTemplateId' => 1,
			'identifier' => 'test_element',
			'fieldset' => 'general',
			'columnSpan' => 'invalid_span',
		];

		$invalidEntity = $this->contentTemplateElementsTable->newEntity($invalidData);
		$invalidErrors = $invalidEntity->getErrors();

		$this->assertArrayHasKey('columnSpan', $invalidErrors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\ContentTemplateElementsTable::validationDefault()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testEntityValidationEmptyTitle(): void {
		$data = [
			'contentTemplateId' => 1,
			'identifier' => 'test_element',
			'fieldset' => 'general',
			'title' => '',
		];

		$entity = $this->contentTemplateElementsTable->newEntity($data);
		$errors = $entity->getErrors();

		// Title is allowed to be empty
		$this->assertArrayNotHasKey('title', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\ContentTemplateElementsTable::buildRules()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testBuildRulesContentTemplateExists(): void {
		// Test with existing content template
		$validData = [
			'contentTemplateId' => 1,
			'identifier' => 'test_element',
			'fieldset' => 'general',
		];

		$validEntity = $this->contentTemplateElementsTable->newEntity($validData);
		$validResult = $this->contentTemplateElementsTable->checkRules($validEntity);
		$this->assertTrue($validResult);

		// Test with non-existing content template
		$invalidData = [
			'contentTemplateId' => 99999,
			'identifier' => 'test_element',
			'fieldset' => 'general',
		];

		$invalidEntity = $this->contentTemplateElementsTable->newEntity($invalidData);
		$invalidResult = $this->contentTemplateElementsTable->checkRules($invalidEntity);
		$this->assertFalse($invalidResult);

		$errors = $invalidEntity->getErrors();
		$this->assertArrayHasKey('contentTemplateId', $errors);
		$this->assertArrayHasKey('contentTemplateExists', $errors['contentTemplateId']);
		$this->assertEquals('content_template_elements::error_content_template_exists', $errors['contentTemplateId']['contentTemplateExists']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\ContentTemplateElementsTable::newDefaultEntity()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testNewDefaultEntity(): void {
		$entity = $this->contentTemplateElementsTable->newDefaultEntity();

		$this->assertInstanceOf(ContentTemplateElement::class, $entity);
		$this->assertTrue($entity->isNew());

		$this->assertNull($entity->title);
		$this->assertSame('', $entity->fieldset);
		$this->assertSame('12/12', $entity->columnSpan);
		$this->assertFalse($entity->required);
		$this->assertSame(0, $entity->systemOrder);
		$this->assertNull($entity->contentTemplateId);
		$this->assertNull($entity->identifier);

		$this->assertIsArray($entity->column);
		$this->assertCount(1, $entity->column);
		$this->assertArrayHasKey('span', $entity->column);
		$this->assertInstanceOf(BootstrapColumn::class, $entity->column['span']);
		$this->assertSame('12/12', $entity->column['span']->getFraction());
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\ContentTemplateElementsTable::newDefaultEntity()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testNewDefaultEntityWithData(): void {
		$additionalData = [
			'required' => true,
			'contentTemplateId' => 1,
			'identifier' => 'custom_element',
			'title' => 'Custom Element',
			'fieldset' => 'content',
			'systemOrder' => 5,
			'columnSpan' => '2/12',
		];

		$entity = $this->contentTemplateElementsTable->newDefaultEntity($additionalData);

		$this->assertInstanceOf(ContentTemplateElement::class, $entity);
		$this->assertTrue($entity->isNew());

		$this->assertSame('Custom Element', $entity->title);
		$this->assertSame('content', $entity->fieldset);
		$this->assertSame('2/12', $entity->columnSpan);
		$this->assertTrue($entity->required);
		$this->assertSame(5, $entity->systemOrder);
		$this->assertSame(1, $entity->contentTemplateId);
		$this->assertSame('custom_element', $entity->identifier);

		$this->assertIsArray($entity->column);
		$this->assertCount(1, $entity->column);
		$this->assertArrayHasKey('span', $entity->column);
		$this->assertInstanceOf(BootstrapColumn::class, $entity->column['span']);
		$this->assertSame('2/12', $entity->column['span']->getFraction());
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\ContentTemplateElementsTable::$audit
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testAuditBehavior(): void {
		$this->assertTrue($this->contentTemplateElementsTable->hasBehavior('Audit'));

		$enabled = $this->contentTemplateElementsTable->getBehavior('Audit')->getConfig('enabled');

		$this->assertFalse($enabled);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\ContentTemplateElementsTable::$systemOrder
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testSystemOrderBehavior(): void {
		$this->assertTrue($this->contentTemplateElementsTable->hasBehavior('SystemOrder'));

		$config = $this->contentTemplateElementsTable->getBehavior('SystemOrder')->getConfig();

		$this->assertTrue($config['enabled']);
		$this->assertSame(['contentTemplateId', 'fieldset'], $config['relatedColumns']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\ContentTemplateElementsTable::$translate
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testTranslateBehavior(): void {
		$this->assertTrue($this->contentTemplateElementsTable->hasBehavior('Translate'));

		$config = $this->contentTemplateElementsTable->getBehavior('Translate')->getConfig();

		// Auto-realm
		$this->assertNull($config['realm'] ?? null);

		$this->assertIsArray($config['fields']);
		$this->assertSame(['title'], $config['fields']);
	}
}
