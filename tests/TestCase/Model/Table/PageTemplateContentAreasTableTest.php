<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Model\Table;


use Awyiss\Model\Entity\PageTemplateContentArea;
use Awyiss\Model\Table\PageTemplateContentAreasTable;
use Awyiss\ORM\Association\BelongsTo;
use Awyiss\Test\TestSuite\TestCase;
use Awyiss\Validation\Validator;
use Cake\Datasource\FactoryLocator;


/**
 * PageTemplateContentAreasTable Test Case
 *
 * @see \Awyiss\Model\Table\PageTemplateContentAreasTable
 */
class PageTemplateContentAreasTableTest extends TestCase {
	/**
	 * @var \Awyiss\Model\Table\PageTemplateContentAreasTable
	 */
	protected PageTemplateContentAreasTable $pageTemplateContentAreasTable;


	/**
	 * @inheritDoc
	 */
	protected function setUp(): void {
		parent::setUp();

		/** @noinspection PhpFieldAssignmentTypeMismatchInspection */
		$this->pageTemplateContentAreasTable = FactoryLocator::get('Table')->get('PageTemplateContentAreas');
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\PageTemplateContentAreasTable::ATTRIBUTABLE
	 */
	public function testAttributableConstant(): void {
		$this->assertFalse($this->pageTemplateContentAreasTable::ATTRIBUTABLE);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\PageTemplateContentAreasTable::TABLE
	 */
	public function testTableConstant(): void {
		$this->assertEquals('page_template_content_areas', $this->pageTemplateContentAreasTable::TABLE);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\PageTemplateContentAreasTable::initializeAssociations()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testInitializeAssociations(): void {
		$this->assertCount(3, $this->pageTemplateContentAreasTable->associations()->keys());

		// Test ContentAreas association (BelongsTo)
		$this->assertTrue($this->pageTemplateContentAreasTable->hasAssociation('ContentAreas'));
		$contentAreasAssociation = $this->pageTemplateContentAreasTable->getAssociation('ContentAreas');
		$this->assertInstanceOf(BelongsTo::class, $contentAreasAssociation);
		$this->assertFalse($contentAreasAssociation->getCascadeCallbacks());
		$this->assertFalse($contentAreasAssociation->getDependent());

		// Test PageTemplates association (BelongsTo)
		$this->assertTrue($this->pageTemplateContentAreasTable->hasAssociation('PageTemplates'));
		$pageTemplatesAssociation = $this->pageTemplateContentAreasTable->getAssociation('PageTemplates');
		$this->assertInstanceOf(BelongsTo::class, $pageTemplatesAssociation);
		$this->assertFalse($pageTemplatesAssociation->getCascadeCallbacks());
		$this->assertFalse($pageTemplatesAssociation->getDependent());

		// MediaAssignments is defined, but we don't care about it for this table
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\PageTemplateContentAreasTable::validationDefault()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testValidationDefault(): void {
		$validator = new Validator();
		$result = $this->pageTemplateContentAreasTable->validationDefault($validator);

		$this->assertInstanceOf(Validator::class, $result);
		$this->assertSame('page_template_content_areas', $result->getI18nDomain());

		// Test fields exist
		$this->assertTrue($result->hasField('id'));
		$this->assertTrue($result->hasField('pageTemplateId'));
		$this->assertTrue($result->hasField('contentAreaId'));
		$this->assertTrue($result->hasField('systemOrder'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\PageTemplateContentAreasTable::validationDefault()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testEntityValidationSuccess(): void {
		$data = [
			'pageTemplateId' => 1,
			'contentAreaId' => 1,
			'systemOrder' => 1,
		];

		$entity = $this->pageTemplateContentAreasTable->newEntity($data);
		$errors = $entity->getErrors();

		$this->assertEmpty($errors, 'Valid data should not produce validation errors');
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\PageTemplateContentAreasTable::validationDefault()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testEntityValidationInvalidTypes(): void {
		$data = [
			'id' => 'not_an_integer',
			'pageTemplateId' => 'not_an_integer',
			'contentAreaId' => 'not_an_integer',
			'systemOrder' => 'not_an_integer',
		];

		$entity = $this->pageTemplateContentAreasTable->newEntity($data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('id', $errors);
		$this->assertArrayHasKey('pageTemplateId', $errors);
		$this->assertArrayHasKey('contentAreaId', $errors);
		$this->assertArrayHasKey('systemOrder', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\PageTemplateContentAreasTable::validationDefault()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testEntityValidationFieldLength(): void {
		$data = [
			'id' => 123456789123, // exceeds 11 char limit
			'pageTemplateId' => 123456789123, // exceeds 11 char limit
			'contentAreaId' => 123456789123, // exceeds 11 char limit
			'systemOrder' => 123456789123, // exceeds 11 char limit
		];

		$entity = $this->pageTemplateContentAreasTable->newEntity($data, ['guard' => false]);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('id', $errors);
		$this->assertArrayHasKey('pageTemplateId', $errors);
		$this->assertArrayHasKey('contentAreaId', $errors);
		$this->assertArrayHasKey('systemOrder', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\PageTemplateContentAreasTable::buildRules()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testBuildRulesPageTemplateExistsValid(): void {
		// Test with existing page template
		$validData = [
			'pageTemplateId' => 1,
			'contentAreaId' => 1,
			'systemOrder' => 1,
		];

		$validEntity = $this->pageTemplateContentAreasTable->newEntity($validData);
		$validResult = $this->pageTemplateContentAreasTable->checkRules($validEntity);
		$this->assertTrue($validResult);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\PageTemplateContentAreasTable::buildRules()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testBuildRulesPageTemplateExistsInvalid(): void {
		// Test with non-existing page template
		$invalidData = [
			'pageTemplateId' => 99999,
			'contentAreaId' => 1,
			'systemOrder' => 1,
		];

		$invalidEntity = $this->pageTemplateContentAreasTable->newEntity($invalidData);
		$invalidResult = $this->pageTemplateContentAreasTable->checkRules($invalidEntity);
		$this->assertFalse($invalidResult);

		$errors = $invalidEntity->getErrors();
		$this->assertArrayHasKey('pageTemplateId', $errors);
		$this->assertArrayHasKey('pageTemplateExists', $errors['pageTemplateId']);
		$this->assertEquals('page_template_content_areas::error_page_template_exists', $errors['pageTemplateId']['pageTemplateExists']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\PageTemplateContentAreasTable::buildRules()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testBuildRulesContentAreaExistsValid(): void {
		// Test with existing content area
		$validData = [
			'pageTemplateId' => 1,
			'contentAreaId' => 1,
			'systemOrder' => 1,
		];

		$validEntity = $this->pageTemplateContentAreasTable->newEntity($validData);
		$validResult = $this->pageTemplateContentAreasTable->checkRules($validEntity);
		$this->assertTrue($validResult);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\PageTemplateContentAreasTable::buildRules()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testBuildRulesContentAreaExistsInvalid(): void {
		// Test with non-existing content area
		$invalidData = [
			'pageTemplateId' => 1,
			'contentAreaId' => 99999,
			'systemOrder' => 1,
		];

		$invalidEntity = $this->pageTemplateContentAreasTable->newEntity($invalidData);
		$invalidResult = $this->pageTemplateContentAreasTable->checkRules($invalidEntity);
		$this->assertFalse($invalidResult);

		$errors = $invalidEntity->getErrors();
		$this->assertArrayHasKey('contentAreaId', $errors);
		$this->assertArrayHasKey('contentAreaExists', $errors['contentAreaId']);
		$this->assertEquals('page_template_content_areas::error_content_area_exists', $errors['contentAreaId']['contentAreaExists']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\PageTemplateContentAreasTable::newDefaultEntity()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testNewDefaultEntity(): void {
		$entity = $this->pageTemplateContentAreasTable->newDefaultEntity();

		$this->assertInstanceOf(PageTemplateContentArea::class, $entity);
		$this->assertTrue($entity->isNew());

		// Test default values
		$this->assertSame(0, $entity->systemOrder);
		$this->assertNull($entity->pageTemplateId);
		$this->assertNull($entity->contentAreaId);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\PageTemplateContentAreasTable::newDefaultEntity()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testNewDefaultEntityWithData(): void {
		$additionalData = [
			'pageTemplateId' => 2,
			'contentAreaId' => 3,
			'systemOrder' => 5,
		];

		$entity = $this->pageTemplateContentAreasTable->newDefaultEntity($additionalData);

		$this->assertInstanceOf(PageTemplateContentArea::class, $entity);
		$this->assertTrue($entity->isNew());

		$this->assertSame(2, $entity->pageTemplateId);
		$this->assertSame(3, $entity->contentAreaId);
		$this->assertSame(5, $entity->systemOrder);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\PageTemplateContentAreasTable::$audit
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testAuditBehavior(): void {
		$this->assertTrue($this->pageTemplateContentAreasTable->hasBehavior('Audit'));

		$config = $this->pageTemplateContentAreasTable->getBehavior('Audit')->getConfig();

		$this->assertFalse($config['enabled']);
	}
}
