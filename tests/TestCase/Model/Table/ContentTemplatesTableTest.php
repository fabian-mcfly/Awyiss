<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Model\Table;


use Awyiss\Annotation\MediaElementAssignable;
use Awyiss\Model\Entity\ContentTemplate;
use Awyiss\Model\Table\ContentTemplatesTable;
use Awyiss\ORM\Association\BelongsTo;
use Awyiss\ORM\Association\BelongsToMany;
use Awyiss\ORM\Association\HasMany;
use Awyiss\ORM\Association\HasOne;
use Awyiss\Test\TestSuite\TestCase;
use Awyiss\Validation\Validator;
use Cake\Database\Expression\AggregateExpression;
use Cake\Datasource\FactoryLocator;
use Cake\Datasource\RulesChecker;
use Cake\ORM\Query\SelectQuery;
use ReflectionClass;


/**
 * ContentTemplatesTable Test Case
 *
 * @see \Awyiss\Model\Table\ContentTemplatesTable
 */
class ContentTemplatesTableTest extends TestCase {
	/**
	 * @var \Awyiss\Model\Table\ContentTemplatesTable
	 */
	protected ContentTemplatesTable $contentTemplatesTable;


	/**
	 * @inheritDoc
	 */
	protected function setUp(): void {
		parent::setUp();

		/** @noinspection PhpFieldAssignmentTypeMismatchInspection */
		$this->contentTemplatesTable = FactoryLocator::get('Table')->get('ContentTemplates');
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\ContentTemplatesTable::ATTRIBUTABLE
	 */
	public function testAttributableConstant(): void {
		/** @noinspection PhpUnitAssertTrueWithIncompatibleTypeArgumentInspection */
		$this->assertTrue($this->contentTemplatesTable::ATTRIBUTABLE);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\ContentTemplatesTable::TABLE
	 */
	public function testTableConstant(): void {
		$this->assertEquals('content_templates', $this->contentTemplatesTable::TABLE);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\ContentTemplatesTable
	 */
	public function testMediaElementAssignableAttribute(): void {
		$reflection = new ReflectionClass(ContentTemplatesTable::class);
		$attributes = $reflection->getAttributes(MediaElementAssignable::class);

		$this->assertCount(1, $attributes);

		$attribute = $attributes[0];
		$this->assertSame(MediaElementAssignable::class, $attribute->getName());

		$instance = $attribute->newInstance();
		$this->assertInstanceOf(MediaElementAssignable::class, $instance);
		$this->assertSame(MediaElementAssignable::ENTITY_LEVEL, $instance->level);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\ContentTemplatesTable::initializeAssociations()
	 */
	public function testInitializeAssociations(): void {
		$this->assertCount(11, $this->contentTemplatesTable->associations()->keys());

		// Test Contents association (HasMany)
		$this->assertTrue($this->contentTemplatesTable->hasAssociation('Contents'));
		$contentsAssociation = $this->contentTemplatesTable->getAssociation('Contents');
		$this->assertInstanceOf(HasMany::class, $contentsAssociation);
		$this->assertTrue($contentsAssociation->getCascadeCallbacks());
		$this->assertTrue($contentsAssociation->getDependent());

		// Test ContentAreas association (BelongsToMany)
		$this->assertTrue($this->contentTemplatesTable->hasAssociation('ContentAreas'));
		$contentAreasAssociation = $this->contentTemplatesTable->getAssociation('ContentAreas');
		$this->assertInstanceOf(BelongsToMany::class, $contentAreasAssociation);
		$this->assertSame('ContentTemplateContentAreas', $contentAreasAssociation->getThrough());

		// Test ContentTemplateElements association (HasMany)
		$this->assertTrue($this->contentTemplatesTable->hasAssociation('ContentTemplateElements'));
		$contentTemplateElementsAssociation = $this->contentTemplatesTable->getAssociation('ContentTemplateElements');
		$this->assertInstanceOf(HasMany::class, $contentTemplateElementsAssociation);
		$this->assertTrue($contentTemplateElementsAssociation->getCascadeCallbacks());
		$this->assertTrue($contentTemplateElementsAssociation->getDependent());
		$this->assertSame('replace', $contentTemplateElementsAssociation->getSaveStrategy());

		// Test PageTemplates association (BelongsToMany)
		$this->assertTrue($this->contentTemplatesTable->hasAssociation('PageTemplates'));
		$pageTemplatesAssociation = $this->contentTemplatesTable->getAssociation('PageTemplates');
		$this->assertInstanceOf(BelongsToMany::class, $pageTemplatesAssociation);
		$this->assertSame('ContentTemplateContentAreas', $pageTemplatesAssociation->getThrough());

		// Test MediaAssignments association (HasMany)
		$this->assertTrue($this->contentTemplatesTable->hasAssociation('MediaAssignments'));
		$mediaAssignmentsAssociation = $this->contentTemplatesTable->getAssociation('MediaAssignments');
		$this->assertInstanceOf(HasMany::class, $mediaAssignmentsAssociation);
		$this->assertTrue($mediaAssignmentsAssociation->getCascadeCallbacks());
		$this->assertTrue($mediaAssignmentsAssociation->getDependent());

		// Test MediaElementAssignments association (HasMany)
		$this->assertTrue($this->contentTemplatesTable->hasAssociation('MediaElementAssignments'));
		$mediaElementAssignmentsAssociation = $this->contentTemplatesTable->getAssociation('MediaElementAssignments');
		$this->assertInstanceOf(HasMany::class, $mediaElementAssignmentsAssociation);
		$this->assertTrue($mediaElementAssignmentsAssociation->getCascadeCallbacks());
		$this->assertTrue($mediaElementAssignmentsAssociation->getDependent());

		// Test user tracking associations
		$this->assertTrue($this->contentTemplatesTable->hasAssociation('CreatedByUser'));
		$createdByUserAssociation = $this->contentTemplatesTable->getAssociation('CreatedByUser');
		$this->assertInstanceOf(BelongsTo::class, $createdByUserAssociation);
		$this->assertFalse($createdByUserAssociation->getCascadeCallbacks());
		$this->assertFalse($createdByUserAssociation->getDependent());

		$this->assertTrue($this->contentTemplatesTable->hasAssociation('ChangedByUser'));
		$changedByUserAssociation = $this->contentTemplatesTable->getAssociation('ChangedByUser');
		$this->assertInstanceOf(BelongsTo::class, $changedByUserAssociation);
		$this->assertFalse($changedByUserAssociation->getCascadeCallbacks());
		$this->assertFalse($changedByUserAssociation->getDependent());

		$this->assertTrue($this->contentTemplatesTable->hasAssociation('DeletedByUser'));
		$deletedByUserAssociation = $this->contentTemplatesTable->getAssociation('DeletedByUser');
		$this->assertInstanceOf(BelongsTo::class, $deletedByUserAssociation);
		$this->assertFalse($deletedByUserAssociation->getCascadeCallbacks());
		$this->assertFalse($deletedByUserAssociation->getDependent());

		// Test ContentTemplates_title_translation association (HasOne)
		$this->assertTrue($this->contentTemplatesTable->hasAssociation('ContentTemplates_title_translation'));
		$titleTranslationAssociation = $this->contentTemplatesTable->getAssociation('ContentTemplates_title_translation');
		$this->assertInstanceOf(HasOne::class, $titleTranslationAssociation);
		$this->assertFalse($titleTranslationAssociation->getCascadeCallbacks());
		$this->assertFalse($titleTranslationAssociation->getDependent());

		// Test I18n association (HasMany)
		$this->assertTrue($this->contentTemplatesTable->hasAssociation('I18n'));
		$i18nAssociation = $this->contentTemplatesTable->getAssociation('I18n');
		$this->assertInstanceOf(HasMany::class, $i18nAssociation);
		$this->assertFalse($i18nAssociation->getCascadeCallbacks());
		$this->assertTrue($i18nAssociation->getDependent());
		$this->assertEquals('append', $i18nAssociation->getSaveStrategy());
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\ContentTemplatesTable::findWithUsages()
	 */
	public function testFindWithUsages(): void {
		$query = $this->contentTemplatesTable->find('withUsages');

		/** @noinspection PhpConditionAlreadyCheckedInspection */
		$this->assertInstanceOf(SelectQuery::class, $query);

		// Test that the query includes the expected fields
		$select = $query->clause('select');
		$this->assertContains('used_for_contents', array_keys($select));
		$this->assertInstanceOf(AggregateExpression::class, $select['used_for_contents']);

		// Test that the query includes group by
		$this->assertTrue($query->isAutoFieldsEnabled());
		$this->assertSame(['ContentTemplates.id'], $query->clause('group'));

		// Test that the query has a left join with Contents
		$matching = $query->getEagerLoader()->getMatching();
		$this->assertArrayHasKey('Contents', $matching);
		$this->assertArrayHasKey('queryBuilder', $matching['Contents']);
		/** @var \Cake\ORM\Query\SelectQuery $query */
		$query = $matching['Contents']['queryBuilder']($query);

		$this->assertSame(['attributes' => ['skip' => true]], $query->getOptions());
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\ContentTemplatesTable::getAvailableContentElements()
	 */
	public function testGetAvailableContentElements(): void {
		$elements = $this->contentTemplatesTable->getAvailableContentElements();

		$this->assertIsArray($elements);
		$this->assertCount(21, $elements);

		// Test required elements
		$requiredElements = [
			'active',
			'content_template_id',
			'language_shortcode',
			'page_id',
			'content_area_id',
			'system_order',
		];

		foreach ($requiredElements as $element) {
			$this->assertArrayHasKey($element, $elements);
			$this->assertFalse($elements[ $element ]);
		}

		// Test optional elements
		$optionalElements = [
			'parent_id',
			'css_class',
			'column_width',
			'column_indent',
			'column_last',
			'column_rtl',
			'title',
			'title_tag',
			'subtitle',
			'subtitle_tag',
			'text',
			'link',
			'duplicate_of',
			'form_id',
			'survey_id',
		];

		foreach ($optionalElements as $element) {
			$this->assertArrayHasKey($element, $elements);
			$this->assertTrue($elements[ $element ]);
		}
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\ContentTemplatesTable::getAvailableFieldsets()
	 */
	public function testGetAvailableFieldsets(): void {
		$fieldsets = $this->contentTemplatesTable->getAvailableFieldsets();

		$this->assertIsArray($fieldsets);
		$this->assertCount(8, $fieldsets);
		$this->assertContains('presentation', $fieldsets);
		$this->assertContains('conditions', $fieldsets);
		$this->assertContains('general', $fieldsets);
		$this->assertContains('content', $fieldsets);
		$this->assertContains('media', $fieldsets);
		$this->assertContains('attributes', $fieldsets);
		$this->assertContains('data', $fieldsets);
		$this->assertContains('publication', $fieldsets);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\ContentTemplatesTable::getAssignedContentAttributes()
	 */
	public function testGetAssignedContentAttributes(): void {
		/** @var \Awyiss\Model\Entity\ContentTemplate $contentTemplate */
		$contentTemplate = $this->contentTemplatesTable->get(2);

		$contentTemplate->contentTemplateElements = [
			['id' => 1, 'identifier' => 'attributes.free_text'],
			['id' => 2, 'identifier' => 'teaser'],
			['id' => 3, 'identifier' => 'attributes.background_color'],
			['id' => 4, 'identifier' => 'title'],
		];

		$assignedAttributes = $this->contentTemplatesTable->getAssignedContentAttributes($contentTemplate);

		$this->assertIsArray($assignedAttributes);
		$this->assertSame(['background_color'], $assignedAttributes);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\ContentTemplatesTable::getAssignedContentAttributes()
	 */
	public function testGetAssignedContentAttributesWithMissingElements(): void {
		/** @var \Awyiss\Model\Entity\ContentTemplate $contentTemplate */
		$contentTemplate = $this->contentTemplatesTable->get(2);

		$this->assertEmpty($contentTemplate->contentTemplateElements);

		$assignedAttributes = $this->contentTemplatesTable->getAssignedContentAttributes($contentTemplate);

		$this->assertNotEmpty($contentTemplate->contentTemplateElements);
		$this->assertIsArray($assignedAttributes);
		$this->assertSame(['background_color'], $assignedAttributes);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\ContentTemplatesTable::getAvailableContentAttributes()
	 */
	public function testGetAvailableContentAttributes(): void {
		$attributes = $this->contentTemplatesTable->getAvailableContentAttributes();

		$this->assertIsArray($attributes);

		// Test that each attribute has the expected structure
		foreach ($attributes as $attribute) {
			$this->assertSame([
				'title',
				'label',
				'identifier',
				'active',
				'type',
				'inputType',
			], array_keys($attribute));
		}
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\ContentTemplatesTable::getAvailableContentAttributes()
	 */
	public function testGetAvailableContentAttributesIncludeInactive(): void {
		$activeOnly = $this->contentTemplatesTable->getAvailableContentAttributes();
		$withInactive = $this->contentTemplatesTable->getAvailableContentAttributes(true);

		$this->assertIsArray($activeOnly);
		$this->assertIsArray($withInactive);

		$this->assertCount(1, $activeOnly);
		$this->assertCount(2, $withInactive);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\ContentTemplatesTable::validationDefault()
	 */
	public function testValidationDefault(): void {
		$validator = new Validator();
		$result = $this->contentTemplatesTable->validationDefault($validator);

		$this->assertInstanceOf(Validator::class, $result);
		$this->assertSame('content_templates', $result->getI18nDomain());

		// Test required fields
		$this->assertTrue($result->hasField('title'));
		$this->assertSame('create', $result->field('title')->isPresenceRequired());

		$this->assertTrue($result->hasField('fileName'));
		$this->assertSame('create', $result->field('fileName')->isPresenceRequired());

		// Test other fields
		$this->assertTrue($result->hasField('id'));
		$this->assertTrue($result->hasField('systemOrder'));
		$this->assertTrue($result->hasField('active'));
		$this->assertTrue($result->hasField('deleted'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\ContentTemplatesTable::validationDefault()
	 */
	public function testEntityValidationSuccess(): void {
		$data = [
			'title' => 'Test Content Template',
			'fileName' => 'test_template',
			'systemOrder' => 1,
			'active' => true,
			'deleted' => false,
		];

		$entity = $this->contentTemplatesTable->newEntity($data);
		$errors = $entity->getErrors();

		$this->assertEmpty($errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\ContentTemplatesTable::validationDefault()
	 */
	public function testEntityValidationMissingRequired(): void {
		$data = [
			'systemOrder' => 1,
		];

		$entity = $this->contentTemplatesTable->newEntity($data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('title', $errors);
		$this->assertArrayHasKey('fileName', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\ContentTemplatesTable::validationDefault()
	 */
	public function testEntityValidationInvalidTypes(): void {
		$data = [
			'id' => 'not_an_integer',
			'title' => true,
			'fileName' => true,
			'systemOrder' => 'not_an_integer',
			'active' => 'not_a_boolean',
			'deleted' => 'not_a_boolean',
		];

		$entity = $this->contentTemplatesTable->newEntity($data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('id', $errors);
		$this->assertArrayHasKey('title', $errors);
		$this->assertArrayHasKey('fileName', $errors);
		$this->assertArrayHasKey('systemOrder', $errors);
		$this->assertArrayHasKey('active', $errors);
		$this->assertArrayHasKey('deleted', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\ContentTemplatesTable::validationDefault()
	 */
	public function testEntityValidationFieldLength(): void {
		$data = [
			'id' => 123456789123, // exceeds 11 char limit
			'title' => str_repeat('a', 101), // exceeds 100 char limit
			'fileName' => str_repeat('b', 101), // exceeds 100 char limit
		];

		$entity = $this->contentTemplatesTable->newEntity($data, ['guard' => false]);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('id', $errors);
		$this->assertArrayHasKey('title', $errors);
		$this->assertArrayHasKey('fileName', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\ContentTemplatesTable::validationDefault()
	 */
	public function testEntityValidationNotBlank(): void {
		$data = [
			'title' => '   ', // only whitespace
			'fileName' => '   ', // only whitespace
		];

		$entity = $this->contentTemplatesTable->newEntity($data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('title', $errors);
		$this->assertArrayHasKey('fileName', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\ContentTemplatesTable::validationDefault()
	 */
	public function testEntityValidationNotEmptyString(): void {
		$data = [
			'title' => '',
			'fileName' => '',
		];

		$entity = $this->contentTemplatesTable->newEntity($data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('title', $errors);
		$this->assertArrayHasKey('fileName', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\ContentTemplatesTable::validationDefault()
	 */
	public function testEntityValidationFileNameAscii(): void {
		$data = [
			'title' => 'Test Template',
			'fileName' => 'tëst_fîlé_nämé', // non-ASCII characters
		];

		$entity = $this->contentTemplatesTable->newEntity($data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('fileName', $errors);
		$this->assertArrayHasKey('ascii', $errors['fileName']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\ContentTemplatesTable::buildRules()
	 */
	public function testBuildRulesFileNameUniqueValid(): void {
		$data = [
			'title' => 'Test Template',
			'fileName' => 'unique_test_template',
		];

		$entity = $this->contentTemplatesTable->newEntity($data);
		$result = $this->contentTemplatesTable->checkRules($entity);

		$this->assertTrue($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\ContentTemplatesTable::buildRules()
	 */
	public function testBuildRulesFileNameUniqueInvalid(): void {
		$data = [
			'title' => 'Test Template',
			'fileName' => 'standard', // This fileName already exists in fixtures
		];

		$entity = $this->contentTemplatesTable->newEntity($data);
		$result = $this->contentTemplatesTable->checkRules($entity);

		$this->assertFalse($result);

		$errors = $entity->getErrors();
		$this->assertArrayHasKey('fileName', $errors);
		$this->assertArrayHasKey('fileNameUnique', $errors['fileName']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\ContentTemplatesTable::buildRules()
	 */
	public function testBuildRulesValidContentElementsValid(): void {
		$data = [
			'title' => 'Test Template',
			'fileName' => 'test_valid_elements',
			'contentTemplateElements' => [
				[
					'identifier' => 'title',
				],
				[
					'identifier' => 'text',
				],
				[
					'identifier' => 'attributes.background_color',
				],
			],
		];

		$entity = $this->contentTemplatesTable->newEntity($data);
		$result = $this->contentTemplatesTable->checkRules($entity);

		$this->assertTrue($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\ContentTemplatesTable::buildRules()
	 */
	public function testBuildRulesValidContentElementsInvalidElement(): void {
		$data = [
			'title' => 'Test Template',
			'fileName' => 'test_invalid_elements',
			'contentTemplateElements' => [
				[
					'identifier' => 'title',
				],
				[
					'identifier' => 'non_existing_element',
				],
			],
		];

		$entity = $this->contentTemplatesTable->newEntity($data);
		$result = $this->contentTemplatesTable->checkRules($entity);

		$this->assertFalse($result);

		$errors = $entity->getErrors();
		$this->assertArrayHasKey('contentTemplateElements', $errors);
		$this->assertArrayHasKey('validContentElements', $errors['contentTemplateElements']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\ContentTemplatesTable::buildRules()
	 */
	public function testBuildRulesValidContentElementsInvalidAttribute(): void {
		$data = [
			'title' => 'Test Template',
			'fileName' => 'test_invalid_attribute',
			'contentTemplateElements' => [
				[
					'identifier' => 'title',
				],
				[
					'identifier' => 'attributes.non_existing_attribute',
				],
			],
		];

		$entity = $this->contentTemplatesTable->newEntity($data);
		$result = $this->contentTemplatesTable->checkRules($entity);

		$this->assertFalse($result);

		$errors = $entity->getErrors();
		$this->assertArrayHasKey('contentTemplateElements', $errors);
		$this->assertArrayHasKey('validContentElements', $errors['contentTemplateElements']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\ContentTemplatesTable::buildRules()
	 */
	public function testBuildDeleteRulesNoLinkedContentsValid(): void {
		$data = [
			'title' => 'Test Template',
			'fileName' => 'test_no_contents',
		];

		$entity = $this->contentTemplatesTable->newEntity($data);
		$entity->set('id', 9999);
		$entity->setNew(false);

		$result = $this->contentTemplatesTable->checkRules($entity, RulesChecker::DELETE);
		$this->assertTrue($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\ContentTemplatesTable::buildRules()
	 */
	public function testBuildDeleteRulesNoLinkedContentsInvalid(): void {
		$contentTemplate = $this->contentTemplatesTable->get(1); // Content Template that has linked contents

		$result = $this->contentTemplatesTable->checkRules($contentTemplate, RulesChecker::DELETE);
		$this->assertFalse($result);

		$errors = $contentTemplate->getErrors();
		$this->assertArrayHasKey('_general', $errors);
		$this->assertArrayHasKey('noLinkedContents', $errors['_general']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\ContentTemplatesTable::newDefaultEntity()
	 */
	public function testNewDefaultEntity(): void {
		$entity = $this->contentTemplatesTable->newDefaultEntity();

		$this->assertInstanceOf(ContentTemplate::class, $entity);
		$this->assertTrue($entity->isNew());

		// Check default values
		$this->assertNull($entity->title);
		$this->assertNull($entity->fileName);
		$this->assertTrue($entity->active);
		$this->assertFalse($entity->deleted);
		$this->assertTrue($entity->inContentRow);
		$this->assertSame(0, $entity->systemOrder);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\ContentTemplatesTable::newDefaultEntity()
	 */
	public function testNewDefaultEntityWithData(): void {
		$additionalData = [
			'title' => 'Custom Content Template',
			'fileName' => 'custom_template',
			'active' => false,
			'systemOrder' => 5,
		];

		$entity = $this->contentTemplatesTable->newDefaultEntity($additionalData);

		$this->assertInstanceOf(ContentTemplate::class, $entity);
		$this->assertTrue($entity->isNew());

		// Check custom values
		$this->assertSame('Custom Content Template', $entity->title);
		$this->assertSame('custom_template', $entity->fileName);
		$this->assertFalse($entity->active);
		$this->assertSame(5, $entity->systemOrder);

		// Check that defaults are preserved
		$this->assertFalse($entity->deleted);
		$this->assertTrue($entity->inContentRow);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\ContentTemplatesTable::$systemOrder
	 */
	public function testSystemOrderBehavior(): void {
		$this->assertTrue($this->contentTemplatesTable->hasBehavior('SystemOrder'));

		$config = $this->contentTemplatesTable->getBehavior('SystemOrder')->getConfig();

		$this->assertTrue($config['enabled']);
		$this->assertSame([], $config['relatedColumns']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\ContentTemplatesTable::$translate
	 */
	public function testTranslateBehavior(): void {
		$this->assertTrue($this->contentTemplatesTable->hasBehavior('Translate'));

		$config = $this->contentTemplatesTable->getBehavior('Translate')->getConfig();

		$this->assertNull($config['realm'] ?? null);

		$this->assertIsArray($config['fields']);
		$this->assertSame(['title'], $config['fields']);
	}
}
