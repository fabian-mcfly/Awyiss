<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Model\Table;


use Awyiss\Model\Entity\ContentArea;
use Awyiss\Model\Table\ContentAreasTable;
use Awyiss\ORM\Association\BelongsTo;
use Awyiss\ORM\Association\BelongsToMany;
use Awyiss\ORM\Association\HasMany;
use Awyiss\ORM\Association\HasOne;
use Awyiss\Test\TestSuite\TestCase;
use Awyiss\Validation\Validator;
use Cake\Datasource\FactoryLocator;


/**
 * ContentAreasTable Test Case
 *
 * @see \Awyiss\Model\Table\ContentAreasTable
 */
class ContentAreasTableTest extends TestCase {
	/**
	 * @var \Awyiss\Model\Table\ContentAreasTable
	 */
	protected ContentAreasTable $contentAreasTable;


	/**
	 * @inheritDoc
	 */
	protected function setUp(): void {
		parent::setUp();

		/** @noinspection PhpFieldAssignmentTypeMismatchInspection */
		$this->contentAreasTable = FactoryLocator::get('Table')->get('ContentAreas');
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\ContentAreasTable::ATTRIBUTABLE
	 */
	public function testAttributableConstant(): void {
		$this->assertFalse($this->contentAreasTable::ATTRIBUTABLE);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\ContentAreasTable::TABLE
	 */
	public function testTableConstant(): void {
		$this->assertEquals('content_areas', $this->contentAreasTable::TABLE);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\ContentAreasTable::initializeAssociations()
	 */
	public function testInitializeAssociations(): void {
		$this->assertCount(8, $this->contentAreasTable->associations()->keys());

		// Test ContentTemplates association (BelongsToMany)
		$this->assertTrue($this->contentAreasTable->hasAssociation('ContentTemplates'));
		$contentTemplatesAssociation = $this->contentAreasTable->getAssociation('ContentTemplates');
		$this->assertInstanceOf(BelongsToMany::class, $contentTemplatesAssociation);
		$this->assertSame('ContentTemplateContentAreas', $contentTemplatesAssociation->getThrough());
		$this->assertSame('replace', $contentTemplatesAssociation->getSaveStrategy());

		// Test PageTemplates association (BelongsToMany)
		$this->assertTrue($this->contentAreasTable->hasAssociation('PageTemplates'));
		$pageTemplatesAssociation = $this->contentAreasTable->getAssociation('PageTemplates');
		$this->assertInstanceOf(BelongsToMany::class, $pageTemplatesAssociation);
		$this->assertSame('PageTemplateContentAreas', $pageTemplatesAssociation->getThrough());
		$this->assertSame(['systemOrder' => 'ASC'], $pageTemplatesAssociation->getSort());

		// 'MediaAssignments' must also exist
		$this->assertTrue($this->contentAreasTable->hasAssociation('MediaAssignments'));
		$mediaAssignmentsAssociation = $this->contentAreasTable->getAssociation('MediaAssignments');
		$this->assertInstanceOf(HasMany::class, $mediaAssignmentsAssociation);
		$this->assertTrue($mediaAssignmentsAssociation->getCascadeCallbacks());
		$this->assertTrue($mediaAssignmentsAssociation->getDependent());

		// Test user tracking associations
		$this->assertTrue($this->contentAreasTable->hasAssociation('CreatedByUser'));
		$createdByUserAssociation = $this->contentAreasTable->getAssociation('CreatedByUser');
		$this->assertInstanceOf(BelongsTo::class, $createdByUserAssociation);
		$this->assertFalse($createdByUserAssociation->getCascadeCallbacks());
		$this->assertFalse($createdByUserAssociation->getDependent());

		$this->assertTrue($this->contentAreasTable->hasAssociation('ChangedByUser'));
		$changedByUserAssociation = $this->contentAreasTable->getAssociation('ChangedByUser');
		$this->assertInstanceOf(BelongsTo::class, $changedByUserAssociation);
		$this->assertFalse($changedByUserAssociation->getCascadeCallbacks());
		$this->assertFalse($changedByUserAssociation->getDependent());

		$this->assertTrue($this->contentAreasTable->hasAssociation('DeletedByUser'));
		$deletedByUserAssociation = $this->contentAreasTable->getAssociation('DeletedByUser');
		$this->assertInstanceOf(BelongsTo::class, $deletedByUserAssociation);
		$this->assertFalse($deletedByUserAssociation->getCascadeCallbacks());
		$this->assertFalse($deletedByUserAssociation->getDependent());

		// 'ContentAreas_title_translation' must also exist
		$this->assertTrue($this->contentAreasTable->hasAssociation('ContentAreas_title_translation'));
		$titleTranslationAssociation = $this->contentAreasTable->getAssociation('ContentAreas_title_translation');
		$this->assertInstanceOf(HasOne::class, $titleTranslationAssociation);
		$this->assertFalse($titleTranslationAssociation->getCascadeCallbacks());
		$this->assertFalse($titleTranslationAssociation->getDependent());

		// 'I18n' must also exist
		$this->assertTrue($this->contentAreasTable->hasAssociation('I18n'));
		$i18nAssociation = $this->contentAreasTable->getAssociation('I18n');
		$this->assertInstanceOf(HasMany::class, $i18nAssociation);
		$this->assertFalse($i18nAssociation->getCascadeCallbacks());
		$this->assertTrue($i18nAssociation->getDependent());
		$this->assertEquals('append', $i18nAssociation->getSaveStrategy());
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\ContentAreasTable::validationDefault()
	 */
	public function testValidationDefault(): void {
		$validator = new Validator();
		$result = $this->contentAreasTable->validationDefault($validator);

		$this->assertInstanceOf(Validator::class, $result);
		$this->assertSame('ContentAreas', $result->getI18nDomain());

		// Test required fields
		$this->assertTrue($result->hasField('title'));
		$this->assertSame('create', $result->field('title')->isPresenceRequired());

		$this->assertTrue($result->hasField('identifier'));
		$this->assertSame('create', $result->field('identifier')->isPresenceRequired());

		// Test other fields
		$this->assertTrue($result->hasField('active'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\ContentAreasTable::validationDefault()
	 */
	public function testEntityValidationSuccess(): void {
		$data = [
			'title' => 'Test Content Area',
			'identifier' => 'testContentArea',
			'active' => true,
		];

		$entity = $this->contentAreasTable->newEntity($data);
		$errors = $entity->getErrors();

		$this->assertEmpty($errors, 'Valid data should not produce validation errors');
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\ContentAreasTable::validationDefault()
	 */
	public function testEntityValidationMissingRequired(): void {
		$data = [
			'active' => true,
		];

		$entity = $this->contentAreasTable->newEntity($data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('title', $errors);
		$this->assertArrayHasKey('identifier', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\ContentAreasTable::validationDefault()
	 */
	public function testEntityValidationInvalidTypes(): void {
		$data = [
			'title' => true,
			'identifier' => true,
			'active' => 'not_a_boolean',
		];

		$entity = $this->contentAreasTable->newEntity($data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('title', $errors);
		$this->assertArrayHasKey('identifier', $errors);
		$this->assertArrayHasKey('active', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\ContentAreasTable::validationDefault()
	 */
	public function testEntityValidationFieldLength(): void {
		$data = [
			'title' => str_repeat('a', 101), // exceeds 100 char limit
			'identifier' => str_repeat('b', 51), // exceeds 50 char limit
		];

		$entity = $this->contentAreasTable->newEntity($data, ['guard' => false]);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('title', $errors);
		$this->assertArrayHasKey('identifier', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\ContentAreasTable::validationDefault()
	 */
	public function testEntityValidationNotBlank(): void {
		$data = [
			'title' => '   ', // only whitespace
			'identifier' => '   ', // only whitespace
		];

		$entity = $this->contentAreasTable->newEntity($data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('title', $errors);
		$this->assertArrayHasKey('notBlank', $errors['title']);

		$this->assertArrayHasKey('identifier', $errors);
		$this->assertArrayHasKey('notBlank', $errors['identifier']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\ContentAreasTable::buildRules()
	 */
	public function testBuildRulesIdentifierUniqueValid(): void {
		$entity = $this->contentAreasTable->newEntity([
			'title' => 'Unique Content Area',
			'identifier' => 'uniqueContentArea',
			'active' => true,
		]);

		$result = $this->contentAreasTable->checkRules($entity);
		$this->assertTrue($result);

		// Check that the identifier is unique
		$this->assertEmpty($entity->getErrors());
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\ContentAreasTable::buildRules()
	 */
	public function testBuildRulesIdentifierUniqueInvalid(): void {
		$entity = $this->contentAreasTable->get(1);

		$entity->unset('id');
		$entity->setNew(true); // Mark as new to simulate a new entity

		$result = $this->contentAreasTable->checkRules($entity);

		$this->assertFalse($result, 'Second entity with duplicate identifier should fail');

		$errors = $entity->getErrors();
		$this->assertArrayHasKey('identifier', $errors);
		$this->assertArrayHasKey('identifierUnique', $errors['identifier']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\ContentAreasTable::newDefaultEntity()
	 */
	public function testNewDefaultEntity(): void {
		/** @var \Awyiss\Model\Entity\ContentArea $entity */
		$entity = $this->contentAreasTable->newDefaultEntity();

		$this->assertInstanceOf(ContentArea::class, $entity);
		$this->assertTrue($entity->isNew());

		// Check default values
		$this->assertNull($entity->id);
		$this->assertNull($entity->title);
		$this->assertNull($entity->identifier);
		$this->assertTrue($entity->active);
		$this->assertFalse($entity->deleted);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\ContentAreasTable::newDefaultEntity()
	 */
	public function testNewDefaultEntityWithData(): void {
		$additionalData = [
			'title' => 'Custom Content Area',
			'identifier' => 'customContentArea',
			'active' => false,
		];

		/** @var \Awyiss\Model\Entity\ContentArea $entity */
		$entity = $this->contentAreasTable->newDefaultEntity($additionalData);

		$this->assertInstanceOf(ContentArea::class, $entity);
		$this->assertTrue($entity->isNew());

		// Check custom values
		$this->assertSame('Custom Content Area', $entity->title);
		$this->assertSame('customContentArea', $entity->identifier);
		$this->assertFalse($entity->active);
		$this->assertFalse($entity->deleted); // Should remain default
	}
}
