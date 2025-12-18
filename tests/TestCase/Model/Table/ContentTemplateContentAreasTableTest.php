<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Model\Table;


use Awyiss\Model\Entity\ContentTemplateContentArea;
use Awyiss\Model\Table\ContentTemplateContentAreasTable;
use Awyiss\ORM\Association\BelongsTo;
use Awyiss\Test\TestSuite\TestCase;
use Awyiss\Validation\Validator;
use Cake\Datasource\FactoryLocator;


/**
 * ContentTemplateContentAreasTable Test Case
 *
 * @see \Awyiss\Model\Table\ContentTemplateContentAreasTable
 */
class ContentTemplateContentAreasTableTest extends TestCase {
	/**
	 * @var \Awyiss\Model\Table\ContentTemplateContentAreasTable
	 */
	protected ContentTemplateContentAreasTable $contentTemplateContentAreasTable;


	/**
	 * @inheritDoc
	 */
	protected function setUp(): void {
		parent::setUp();

		/** @noinspection PhpFieldAssignmentTypeMismatchInspection */
		$this->contentTemplateContentAreasTable = FactoryLocator::get('Table')->get('ContentTemplateContentAreas');
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\ContentTemplateContentAreasTable::ATTRIBUTABLE
	 */
	public function testAttributableConstant(): void {
		$this->assertFalse($this->contentTemplateContentAreasTable::ATTRIBUTABLE);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\ContentTemplateContentAreasTable::TABLE
	 */
	public function testTableConstant(): void {
		$this->assertEquals('content_template_content_areas', $this->contentTemplateContentAreasTable::TABLE);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\ContentTemplateContentAreasTable::initializeAssociations()
	 */
	public function testInitializeAssociations(): void {
		$this->assertCount(4, $this->contentTemplateContentAreasTable->associations()->keys());

		// Test ContentAreas association (BelongsTo)
		$this->assertTrue($this->contentTemplateContentAreasTable->hasAssociation('ContentAreas'));
		$contentAreasAssociation = $this->contentTemplateContentAreasTable->getAssociation('ContentAreas');
		$this->assertInstanceOf(BelongsTo::class, $contentAreasAssociation);
		$this->assertFalse($contentAreasAssociation->getCascadeCallbacks());
		$this->assertFalse($contentAreasAssociation->getDependent());

		// Test ContentTemplates association (BelongsTo)
		$this->assertTrue($this->contentTemplateContentAreasTable->hasAssociation('ContentTemplates'));
		$contentTemplatesAssociation = $this->contentTemplateContentAreasTable->getAssociation('ContentTemplates');
		$this->assertInstanceOf(BelongsTo::class, $contentTemplatesAssociation);
		$this->assertFalse($contentTemplatesAssociation->getCascadeCallbacks());
		$this->assertFalse($contentTemplatesAssociation->getDependent());

		// Test PageTemplates association (BelongsTo)
		$this->assertTrue($this->contentTemplateContentAreasTable->hasAssociation('PageTemplates'));
		$pageTemplatesAssociation = $this->contentTemplateContentAreasTable->getAssociation('PageTemplates');
		$this->assertInstanceOf(BelongsTo::class, $pageTemplatesAssociation);
		$this->assertFalse($pageTemplatesAssociation->getCascadeCallbacks());
		$this->assertFalse($pageTemplatesAssociation->getDependent());

		// MediaAssignments is defined, but we don't care about it for this table
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\ContentTemplateContentAreasTable::validationDefault()
	 */
	public function testValidationDefault(): void {
		$validator = new Validator();
		$result = $this->contentTemplateContentAreasTable->validationDefault($validator);

		$this->assertInstanceOf(Validator::class, $result);
		$this->assertSame('content_template_content_areas', $result->getI18nDomain());

		// Test fields exist
		$this->assertTrue($result->hasField('id'));
		$this->assertTrue($result->hasField('contentTemplateId'));
		$this->assertTrue($result->hasField('contentAreaId'));
		$this->assertTrue($result->hasField('pageTemplateId'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\ContentTemplateContentAreasTable::validationDefault()
	 */
	public function testEntityValidationSuccess(): void {
		$data = [
			'contentTemplateId' => 1,
			'contentAreaId' => 1,
			'pageTemplateId' => 1,
		];

		$entity = $this->contentTemplateContentAreasTable->newEntity($data);
		$errors = $entity->getErrors();

		$this->assertEmpty($errors, 'Valid data should not produce validation errors');
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\ContentTemplateContentAreasTable::validationDefault()
	 */
	public function testEntityValidationInvalidTypes(): void {
		$data = [
			'id' => 'not_an_integer',
			'contentTemplateId' => 'not_an_integer',
			'contentAreaId' => 'not_an_integer',
			'pageTemplateId' => 'not_an_integer',
		];

		$entity = $this->contentTemplateContentAreasTable->newEntity($data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('id', $errors);
		$this->assertArrayHasKey('contentTemplateId', $errors);
		$this->assertArrayHasKey('contentAreaId', $errors);
		$this->assertArrayHasKey('pageTemplateId', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\ContentTemplateContentAreasTable::validationDefault()
	 */
	public function testEntityValidationFieldLength(): void {
		$data = [
			'id' => 123456789123, // exceeds 11 char limit
			'contentTemplateId' => 123456789123, // exceeds 11 char limit
			'contentAreaId' => 123456789123, // exceeds 11 char limit
			'pageTemplateId' => 123456789123, // exceeds 11 char limit
		];

		$entity = $this->contentTemplateContentAreasTable->newEntity($data, ['guard' => false]);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('id', $errors);
		$this->assertArrayHasKey('contentTemplateId', $errors);
		$this->assertArrayHasKey('contentAreaId', $errors);
		$this->assertArrayHasKey('pageTemplateId', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\ContentTemplateContentAreasTable::validationDefault()
	 */
	public function testEntityValidationContentTemplateRequiredWithoutPageTemplate(): void {
		// When page_template_id is empty, content_template_id is required
		$data = [
			'contentAreaId' => 1,
		];

		$entity = $this->contentTemplateContentAreasTable->newEntity($data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('contentTemplateId', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\ContentTemplateContentAreasTable::validationDefault()
	 */
	public function testEntityValidationPageTemplateRequiredWithoutContentTemplate(): void {
		// When content_template_id is empty, page_template_id is required
		$data = [
			'contentAreaId' => 1,
		];

		$entity = $this->contentTemplateContentAreasTable->newEntity($data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('pageTemplateId', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\ContentTemplateContentAreasTable::validationDefault()
	 */
	public function testEntityValidationContentTemplateNotRequiredWithPageTemplate(): void {
		// When page_template_id is present, content_template_id is not required
		$data = [
			'pageTemplateId' => 1,
			'contentAreaId' => 1,
		];

		$entity = $this->contentTemplateContentAreasTable->newEntity($data);
		$errors = $entity->getErrors();

		$this->assertArrayNotHasKey('contentTemplateId', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\ContentTemplateContentAreasTable::validationDefault()
	 */
	public function testEntityValidationPageTemplateNotRequiredWithContentTemplate(): void {
		// When content_template_id is present, page_template_id is not required
		$data = [
			'contentTemplateId' => 1,
			'contentAreaId' => 1,
		];

		$entity = $this->contentTemplateContentAreasTable->newEntity($data);
		$errors = $entity->getErrors();

		$this->assertArrayNotHasKey('pageTemplateId', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\ContentTemplateContentAreasTable::buildRules()
	 */
	public function testBuildRulesContentTemplateExistsValid(): void {
		// Test with existing content template
		$validData = [
			'contentTemplateId' => 1,
			'contentAreaId' => 1,
			'pageTemplateId' => 1,
		];

		$validEntity = $this->contentTemplateContentAreasTable->newEntity($validData);
		$validResult = $this->contentTemplateContentAreasTable->checkRules($validEntity);
		$this->assertTrue($validResult);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\ContentTemplateContentAreasTable::buildRules()
	 */
	public function testBuildRulesContentTemplateExistsInvalid(): void {
		// Test with non-existing content template
		$invalidData = [
			'contentTemplateId' => 99999,
			'contentAreaId' => 1,
			'pageTemplateId' => 1,
		];

		$invalidEntity = $this->contentTemplateContentAreasTable->newEntity($invalidData);
		$invalidResult = $this->contentTemplateContentAreasTable->checkRules($invalidEntity);
		$this->assertFalse($invalidResult);

		$errors = $invalidEntity->getErrors();
		$this->assertArrayHasKey('contentTemplateId', $errors);
		$this->assertArrayHasKey('contentTemplateExists', $errors['contentTemplateId']);
		$this->assertEquals('content_template_content_areas::error_content_template_exists', $errors['contentTemplateId']['contentTemplateExists']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\ContentTemplateContentAreasTable::buildRules()
	 */
	public function testBuildRulesContentAreaExistsValid(): void {
		// Test with existing content area
		$validData = [
			'contentTemplateId' => 1,
			'contentAreaId' => 1,
			'pageTemplateId' => 1,
		];

		$validEntity = $this->contentTemplateContentAreasTable->newEntity($validData);
		$validResult = $this->contentTemplateContentAreasTable->checkRules($validEntity);
		$this->assertTrue($validResult);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\ContentTemplateContentAreasTable::buildRules()
	 */
	public function testBuildRulesContentAreaExistsInvalid(): void {
		// Test with non-existing content area
		$invalidData = [
			'contentTemplateId' => 1,
			'contentAreaId' => 99999,
			'pageTemplateId' => 1,
		];

		$invalidEntity = $this->contentTemplateContentAreasTable->newEntity($invalidData);
		$invalidResult = $this->contentTemplateContentAreasTable->checkRules($invalidEntity);
		$this->assertFalse($invalidResult);

		$errors = $invalidEntity->getErrors();
		$this->assertArrayHasKey('contentAreaId', $errors);
		$this->assertArrayHasKey('contentAreaExists', $errors['contentAreaId']);
		$this->assertEquals('content_template_content_areas::error_content_area_exists', $errors['contentAreaId']['contentAreaExists']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\ContentTemplateContentAreasTable::buildRules()
	 */
	public function testBuildRulesPageTemplateExistsValid(): void {
		// Test with existing page template
		$validData = [
			'contentTemplateId' => 1,
			'contentAreaId' => 1,
			'pageTemplateId' => 1,
		];

		$validEntity = $this->contentTemplateContentAreasTable->newEntity($validData);
		$validResult = $this->contentTemplateContentAreasTable->checkRules($validEntity);
		$this->assertTrue($validResult);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\ContentTemplateContentAreasTable::buildRules()
	 */
	public function testBuildRulesPageTemplateExistsInvalid(): void {
		// Test with non-existing page template
		$invalidData = [
			'contentTemplateId' => 1,
			'contentAreaId' => 1,
			'pageTemplateId' => 99999,
		];

		$invalidEntity = $this->contentTemplateContentAreasTable->newEntity($invalidData);
		$invalidResult = $this->contentTemplateContentAreasTable->checkRules($invalidEntity);
		$this->assertFalse($invalidResult);

		$errors = $invalidEntity->getErrors();
		$this->assertArrayHasKey('pageTemplateId', $errors);
		$this->assertArrayHasKey('pageTemplateExists', $errors['pageTemplateId']);
		$this->assertEquals('content_template_content_areas::error_page_template_exists', $errors['pageTemplateId']['pageTemplateExists']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\ContentTemplateContentAreasTable::buildRules()
	 */
	public function testBuildRulesPageTemplateContentAreaExistsValid(): void {
		// Test with existing page template content area combination
		$validData = [
			'contentTemplateId' => 1,
			'contentAreaId' => 1,
			'pageTemplateId' => 1,
		];

		$validEntity = $this->contentTemplateContentAreasTable->newEntity($validData);
		$validResult = $this->contentTemplateContentAreasTable->checkRules($validEntity);
		$this->assertTrue($validResult);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\ContentTemplateContentAreasTable::buildRules()
	 */
	public function testBuildRulesPageTemplateContentAreaExistsInvalid(): void {
		// Test with non-existing page template content area combination
		$invalidData = [
			'contentTemplateId' => 1,
			'contentAreaId' => 2, // This combination doesn't exist in the seed data
			'pageTemplateId' => 3,
		];

		$invalidEntity = $this->contentTemplateContentAreasTable->newEntity($invalidData);
		$invalidResult = $this->contentTemplateContentAreasTable->checkRules($invalidEntity);
		$this->assertFalse($invalidResult);

		$errors = $invalidEntity->getErrors();
		$this->assertArrayHasKey('_general', $errors);
		$this->assertArrayHasKey('contentTemplateContentAreas', $errors['_general']);
		$this->assertEquals('content_template_content_areas::error_page_template_content_area_exists', $errors['_general']['contentTemplateContentAreas']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\ContentTemplateContentAreasTable::newDefaultEntity()
	 */
	public function testNewDefaultEntity(): void {
		$entity = $this->contentTemplateContentAreasTable->newDefaultEntity();

		$this->assertInstanceOf(ContentTemplateContentArea::class, $entity);
		$this->assertTrue($entity->isNew());

		// Test default values
		$this->assertNull($entity->contentTemplateId);
		$this->assertNull($entity->contentAreaId);
		$this->assertNull($entity->pageTemplateId);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\ContentTemplateContentAreasTable::newDefaultEntity()
	 */
	public function testNewDefaultEntityWithData(): void {
		$additionalData = [
			'contentTemplateId' => 2,
			'contentAreaId' => 3,
			'pageTemplateId' => 5,
		];

		$entity = $this->contentTemplateContentAreasTable->newDefaultEntity($additionalData);

		$this->assertInstanceOf(ContentTemplateContentArea::class, $entity);
		$this->assertTrue($entity->isNew());

		$this->assertSame(2, $entity->contentTemplateId);
		$this->assertSame(3, $entity->contentAreaId);
		$this->assertSame(5, $entity->pageTemplateId);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\ContentTemplateContentAreasTable::$audit
	 */
	public function testAuditBehavior(): void {
		$this->assertTrue($this->contentTemplateContentAreasTable->hasBehavior('Audit'));

		$config = $this->contentTemplateContentAreasTable->getBehavior('Audit')->getConfig();

		$this->assertFalse($config['enabled']);
	}
}
