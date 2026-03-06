<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Model\Table;


use Awyiss\Annotation\MediaElementAssignable;
use Awyiss\Model\Entity\GlobalContentTemplate;
use Awyiss\Model\Table\GlobalContentTemplatesTable;
use Awyiss\ORM\Association\BelongsTo;
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
 * GlobalContentTemplatesTable Test Case
 *
 * @see \Awyiss\Model\Table\GlobalContentTemplatesTable
 */
class GlobalContentTemplatesTableTest extends TestCase {
	/**
	 * @var \Awyiss\Model\Table\GlobalContentTemplatesTable
	 */
	protected GlobalContentTemplatesTable $globalContentTemplatesTable;


	/**
	 * @inheritDoc
	 */
	protected function setUp(): void {
		parent::setUp();

		/** @noinspection PhpFieldAssignmentTypeMismatchInspection */
		$this->globalContentTemplatesTable = FactoryLocator::get('Table')->get('GlobalContentTemplates');
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\GlobalContentTemplateElementsTable::ATTRIBUTABLE
	 */
	public function testAttributableConstant(): void {
		/** @noinspection PhpUnitAssertTrueWithIncompatibleTypeArgumentInspection */
		$this->assertTrue($this->globalContentTemplatesTable::ATTRIBUTABLE);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\GlobalContentTemplatesTable::TABLE
	 */
	public function testTableConstant(): void {
		$this->assertEquals('global_content_templates', $this->globalContentTemplatesTable::TABLE);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\PageTemplatesTable
	 */
	public function testMediaElementAssignableAttribute(): void {
		$reflection = new ReflectionClass(GlobalContentTemplatesTable::class);
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
	 * @see \Awyiss\Model\Table\GlobalContentTemplatesTable::initializeAssociations()
	 */
	public function testInitializeAssociations(): void {
		$this->assertCount(9, $this->globalContentTemplatesTable->associations()->keys());

		$this->assertTrue($this->globalContentTemplatesTable->hasAssociation('GlobalContents'));
		$globalContentsAssociation = $this->globalContentTemplatesTable->getAssociation('GlobalContents');
		$this->assertInstanceOf(HasMany::class, $globalContentsAssociation);
		$this->assertTrue($globalContentsAssociation->getCascadeCallbacks());
		$this->assertTrue($globalContentsAssociation->getDependent());

		$this->assertTrue($this->globalContentTemplatesTable->hasAssociation('GlobalContentTemplateElements'));
		$elementsAssociation = $this->globalContentTemplatesTable->getAssociation('GlobalContentTemplateElements');
		$this->assertInstanceOf(HasMany::class, $elementsAssociation);
		$this->assertTrue($elementsAssociation->getCascadeCallbacks());
		$this->assertTrue($elementsAssociation->getDependent());
		$this->assertEquals('replace', $elementsAssociation->getSaveStrategy());

		// 'CreatedByUser' must also exist
		$this->assertTrue($this->globalContentTemplatesTable->hasAssociation('CreatedByUser'));
		$createdByUserAssociation = $this->globalContentTemplatesTable->getAssociation('CreatedByUser');
		$this->assertInstanceOf(BelongsTo::class, $createdByUserAssociation);
		$this->assertFalse($createdByUserAssociation->getCascadeCallbacks());
		$this->assertFalse($createdByUserAssociation->getDependent());

		// 'ChangedByUser' must also exist
		$this->assertTrue($this->globalContentTemplatesTable->hasAssociation('ChangedByUser'));
		$changedByUserAssociation = $this->globalContentTemplatesTable->getAssociation('ChangedByUser');
		$this->assertInstanceOf(BelongsTo::class, $changedByUserAssociation);
		$this->assertFalse($changedByUserAssociation->getCascadeCallbacks());
		$this->assertFalse($changedByUserAssociation->getDependent());

		// 'DeletedByUser' must also exist
		$this->assertTrue($this->globalContentTemplatesTable->hasAssociation('DeletedByUser'));
		$deletedByUserAssociation = $this->globalContentTemplatesTable->getAssociation('DeletedByUser');
		$this->assertInstanceOf(BelongsTo::class, $deletedByUserAssociation);
		$this->assertFalse($deletedByUserAssociation->getCascadeCallbacks());
		$this->assertFalse($deletedByUserAssociation->getDependent());

		// 'MediaAssignments' must also exist
		$this->assertTrue($this->globalContentTemplatesTable->hasAssociation('MediaAssignments'));
		$mediaAssignmentsAssociation = $this->globalContentTemplatesTable->getAssociation('MediaAssignments');
		$this->assertInstanceOf(HasMany::class, $mediaAssignmentsAssociation);
		$this->assertTrue($mediaAssignmentsAssociation->getCascadeCallbacks());
		$this->assertTrue($mediaAssignmentsAssociation->getDependent());

		// 'MediaElementAssignments' must also exist
		$this->assertTrue($this->globalContentTemplatesTable->hasAssociation('MediaElementAssignments'));
		$mediaElementAssignmentsAssociation = $this->globalContentTemplatesTable->getAssociation('MediaElementAssignments');
		$this->assertInstanceOf(HasMany::class, $mediaElementAssignmentsAssociation);
		$this->assertTrue($mediaElementAssignmentsAssociation->getCascadeCallbacks());
		$this->assertTrue($mediaElementAssignmentsAssociation->getDependent());

		// 'GlobalContentTemplates_title_translation' must also exist
		$this->assertTrue($this->globalContentTemplatesTable->hasAssociation('GlobalContentTemplates_title_translation'));
		$titleTranslationAssociation = $this->globalContentTemplatesTable->getAssociation('GlobalContentTemplates_title_translation');
		$this->assertInstanceOf(HasOne::class, $titleTranslationAssociation);
		$this->assertFalse($titleTranslationAssociation->getCascadeCallbacks());
		$this->assertFalse($titleTranslationAssociation->getDependent());

		// 'I18n' must also exist
		$this->assertTrue($this->globalContentTemplatesTable->hasAssociation('I18n'));
		$i18nAssociation = $this->globalContentTemplatesTable->getAssociation('I18n');
		$this->assertInstanceOf(HasMany::class, $i18nAssociation);
		$this->assertFalse($i18nAssociation->getCascadeCallbacks());
		$this->assertTrue($i18nAssociation->getDependent());
		$this->assertEquals('append', $i18nAssociation->getSaveStrategy());
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\GlobalContentTemplatesTable::findWithUsages()
	 */
	public function testFindWithUsages(): void {
		$query = $this->globalContentTemplatesTable->find('withUsages');

		/** @noinspection PhpConditionAlreadyCheckedInspection */
		$this->assertInstanceOf(SelectQuery::class, $query);

		// Test that the query includes the expected fields
		$select = $query->clause('select');
		$this->assertContains('usedForGlobalContents', array_keys($select));
		$this->assertInstanceOf(AggregateExpression::class, $select['usedForGlobalContents']);

		// Test that the query includes group by
		$this->assertTrue($query->isAutoFieldsEnabled());
		$this->assertSame(['GlobalContentTemplates.id'], $query->clause('group'));

		// Test that the query has a left join with GlobalContents
		$matching = $query->getEagerLoader()->getMatching();
		$this->assertArrayHasKey('GlobalContents', $matching);
		$this->assertArrayHasKey('queryBuilder', $matching['GlobalContents']);
		/** @var \Cake\ORM\Query\SelectQuery $query */
		$query = $matching['GlobalContents']['queryBuilder']($query);

		$this->assertSame(['attributes' => ['skip' => true]], $query->getOptions());
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\GlobalContentTemplatesTable::getAssignedGlobalContentAttributes()
	 */
	public function testGetAssignedGlobalContentAttributes(): void {
		/** @var \Awyiss\Model\Entity\GlobalContentTemplate $globalContentTemplate */
		$globalContentTemplate = $this->globalContentTemplatesTable->newEntity([
			'title' => 'Test Template',
			'fileName' => 'test_template',
			'globalContentTemplateElements' => [
				[
					'identifier' => 'attributes.freeText',
				],
				[
					'identifier' => 'attributes.freeText_inactive',
				],
				[
					'identifier' => 'title',
				],
			],
		]);

		$assignedAttributes = $this->globalContentTemplatesTable->getAssignedGlobalContentAttributes($globalContentTemplate);

		$this->assertSame(['freeText'], $assignedAttributes);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\GlobalContentTemplatesTable::getAssignedGlobalContentAttributes()
	 */
	public function testGetAssignedGlobalContentAttributesWithMissingElements(): void {
		/** @var \Awyiss\Model\Entity\GlobalContentTemplate $globalContentTemplate */
		$globalContentTemplate = $this->globalContentTemplatesTable->get(1);

		$this->assertEmpty($globalContentTemplate->globalContentTemplateElements);

		$assignedAttributes = $this->globalContentTemplatesTable->getAssignedGlobalContentAttributes($globalContentTemplate);

		$this->assertNotEmpty($globalContentTemplate->globalContentTemplateElements);
		$this->assertSame(['freeText'], $assignedAttributes);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\GlobalContentTemplatesTable::getAvailableFieldsets()
	 */
	public function testGetAvailableFieldsets(): void {
		$fieldsets = $this->globalContentTemplatesTable->getAvailableFieldsets();

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
	 * @see \Awyiss\Model\Table\GlobalContentTemplatesTable::getAvailableGlobalContentElements()
	 */
	public function testGetAvailableGlobalContentElements(): void {
		$elements = $this->globalContentTemplatesTable->getAvailableGlobalContentElements();

		$this->assertIsArray($elements);

		foreach ($elements as $key => $value) {
			$this->assertIsString($key);
			$this->assertIsBool($value);
		}
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\GlobalContentTemplatesTable::getAvailableGlobalContentAttributes()
	 */
	public function testGetAvailableGlobalContentAttributes(): void {
		$attributes = $this->globalContentTemplatesTable->getAvailableGlobalContentAttributes();

		$this->assertIsArray($attributes);

		// Test that each attribute has the expected structure
		foreach ($attributes as $identifier => $attribute) {
			$this->assertSame([
				'title',
				'label',
				'identifier',
				'active',
				'type',
				'inputType',
			], array_keys($attribute));

			$this->assertSame($identifier, $attribute['identifier']);
		}
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\GlobalContentTemplatesTable::getAvailableGlobalContentAttributes()
	 */
	public function testGetAvailableGlobalContentAttributesIncludeInactive(): void {
		$activeOnly = $this->globalContentTemplatesTable->getAvailableGlobalContentAttributes();
		$withInactive = $this->globalContentTemplatesTable->getAvailableGlobalContentAttributes(true);

		$this->assertIsArray($activeOnly);
		$this->assertIsArray($withInactive);

		$this->assertCount(2, $activeOnly);
		$this->assertCount(3, $withInactive);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\GlobalContentTemplatesTable::validationDefault()
	 */
	public function testValidationDefault(): void {
		$validator = new Validator();
		$result = $this->globalContentTemplatesTable->validationDefault($validator);

		$this->assertInstanceOf(Validator::class, $result);
		$this->assertSame('GlobalContentTemplates', $result->getI18nDomain());

		// Test required fields
		$this->assertTrue($result->hasField('title'));
		$this->assertSame('create', $result->field('title')->isPresenceRequired());

		$this->assertTrue($result->hasField('fileName'));
		$this->assertSame('create', $result->field('fileName')->isPresenceRequired());

		$this->assertTrue($result->hasField('id'));
		$this->assertTrue($result->hasField('systemOrder'));
		$this->assertTrue($result->hasField('active'));
		$this->assertTrue($result->hasField('deleted'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\GlobalContentTemplatesTable::validationDefault()
	 */
	public function testEntityValidationSuccess(): void {
		$data = [
			'title' => 'Test Template',
			'fileName' => 'test_template',
			'systemOrder' => 1,
			'active' => true,
			'deleted' => false,
		];

		$entity = $this->globalContentTemplatesTable->newEntity($data);
		$errors = $entity->getErrors();

		$this->assertEmpty($errors, 'Valid data should not produce validation errors');
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\GlobalContentTemplatesTable::validationDefault()
	 */
	public function testEntityValidationMissingRequired(): void {
		$data = [
			'systemOrder' => 1,
		];

		$entity = $this->globalContentTemplatesTable->newEntity($data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('title', $errors);
		$this->assertArrayHasKey('fileName', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\GlobalContentTemplatesTable::validationDefault()
	 */
	public function testEntityValidationInvalidTypes(): void {
		$data = [
			'title' => true,
			'fileName' => true,
			'id' => 'not_an_integer',
			'systemOrder' => 'not_an_integer',
			'active' => 'not_a_boolean',
			'deleted' => 'not_a_boolean',
		];

		$entity = $this->globalContentTemplatesTable->newEntity($data);
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
	 * @see \Awyiss\Model\Table\GlobalContentTemplatesTable::validationDefault()
	 */
	public function testEntityValidationFieldLength(): void {
		$data = [
			'id' => 123456789123, // exceeds 11 char limit
			'title' => str_repeat('a', 101), // exceeds 100 char limit
			'fileName' => str_repeat('b', 101), // exceeds 100 char limit
		];

		$entity = $this->globalContentTemplatesTable->newEntity($data, ['guard' => false]);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('id', $errors);
		$this->assertArrayHasKey('title', $errors);
		$this->assertArrayHasKey('fileName', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\GlobalContentTemplatesTable::buildRules()
	 */
	public function testBuildRulesFileNameUnique(): void {
		/** @var \Awyiss\Model\Entity\GlobalContentTemplate $entity1 */
		$entity1 = $this->globalContentTemplatesTable->get(1);

		// Try to create second entity with same fileName
		$entity2 = unserialize(serialize($entity1));
		$entity2->unset('id'); // Clear ID to create a new entity
		$entity2->setNew(true);

		$saved2 = $this->globalContentTemplatesTable->checkRules($entity2);
		$this->assertFalse($saved2, 'Second entity should fail due to duplicate fileName');

		$errors = $entity2->getErrors();
		$this->assertArrayHasKey('fileName', $errors);
		$this->assertArrayHasKey('fileNameUnique', $errors['fileName']);
		$this->assertEquals('global_content_templates::error_file_name_unique', $errors['fileName']['fileNameUnique']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\GlobalContentTemplatesTable::buildRules()
	 */
	public function testBuildRulesAssignedGlobalContentTemplateElementsExist(): void {
		/** @var \Awyiss\Model\Entity\GlobalContentTemplate $entity */
		$entity = $this->globalContentTemplatesTable->get(1, contain: ['GlobalContentTemplateElements']);

		// Add a non-existing element identifier
		$entity->globalContentTemplateElements[8]->identifier = 'non_existing_element';

		$saved = $this->globalContentTemplatesTable->checkRules($entity);
		$this->assertFalse($saved, 'Entity should fail due to non-existing global content template element');

		$errors = $entity->getErrors();
		$this->assertArrayHasKey('globalContentTemplateElements', $errors);
		$this->assertArrayHasKey('validGlobalContentElements', $errors['globalContentTemplateElements']);
		$this->assertEquals('global_content_templates::error_valid_global_content_elements', $errors['globalContentTemplateElements']['validGlobalContentElements']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\GlobalContentTemplatesTable::buildRules()
	 */
	public function testBuildRulesNoLinkedGlobalContents(): void {
		/** @var \Awyiss\Model\Entity\GlobalContentTemplate $entity */
		$entity = $this->globalContentTemplatesTable->get(1);

		$saved = $this->globalContentTemplatesTable->checkRules($entity, RulesChecker::DELETE);
		$this->assertFalse($saved);

		$errors = $entity->getErrors();
		$this->assertArrayHasKey('_general', $errors);
		$this->assertArrayHasKey('noLinkedGlobalContents', $errors['_general']);
		$this->assertEquals('global_content_templates::error_linked_global_contents', $errors['_general']['noLinkedGlobalContents']);

		/** @var \Awyiss\Model\Entity\GlobalContentTemplate $entity */
		$entity = $this->globalContentTemplatesTable->get(2);

		$saved = $this->globalContentTemplatesTable->checkRules($entity, RulesChecker::DELETE);
		$this->assertTrue($saved);

		$errors = $entity->getErrors();
		$this->assertEmpty($errors, 'Entity with no linked global contents should not have errors');
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\GlobalContentTemplatesTable::buildRules()
	 */
	public function testNewDefaultEntity(): void {
		$entity = $this->globalContentTemplatesTable->newDefaultEntity();

		$this->assertInstanceOf(GlobalContentTemplate::class, $entity);
		$this->assertTrue($entity->isNew());

		$this->assertTrue($entity->active);
		$this->assertFalse($entity->deleted);
		$this->assertTrue($entity->inContentRow);
		$this->assertSame(0, $entity->systemOrder);
		$this->assertNull($entity->title);
		$this->assertNull($entity->fileName);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\GlobalContentTemplatesTable::newDefaultEntity()
	 */
	public function testNewDefaultEntityWithData(): void {
		$additionalData = [
			'active' => false,
			'title' => 'Custom Title',
			'fileName' => 'custom_file',
			'systemOrder' => 5,
		];

		$entity = $this->globalContentTemplatesTable->newDefaultEntity($additionalData);

		$this->assertInstanceOf(GlobalContentTemplate::class, $entity);
		$this->assertTrue($entity->isNew());

		$this->assertFalse($entity->active);
		$this->assertFalse($entity->deleted);
		$this->assertTrue($entity->inContentRow);
		$this->assertSame(5, $entity->systemOrder);
		$this->assertSame('Custom Title', $entity->title);
		$this->assertSame('custom_file', $entity->fileName);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\GlobalContentTemplatesTable::$translate
	 */
	public function testSystemOrderBehavior(): void {
		$this->assertTrue($this->globalContentTemplatesTable->hasBehavior('SystemOrder'));

		$config = $this->globalContentTemplatesTable->getBehavior('SystemOrder')->getConfig();

		$this->assertTrue($config['enabled']);
		$this->assertSame([], $config['relatedColumns']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\GlobalContentTemplatesTable::$translate
	 */
	public function testTranslateBehavior(): void {
		$this->assertTrue($this->globalContentTemplatesTable->hasBehavior('Translate'));

		$config = $this->globalContentTemplatesTable->getBehavior('Translate')->getConfig();

		// Auto-realm
		$this->assertNull($config['realm'] ?? null);

		$this->assertIsArray($config['fields']);
		$this->assertSame(['title'], $config['fields']);
	}
}
