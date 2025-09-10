<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Model\Table;


use Awyiss\Annotation\MediaElementAssignable;
use Awyiss\Model\Entity\WidgetTemplate;
use Awyiss\Model\Table\WidgetTemplatesTable;
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
 * WidgetTemplatesTable Test Case
 *
 * @see \Awyiss\Model\Table\WidgetTemplatesTable
 */
class WidgetTemplatesTableTest extends TestCase {
	/**
	 * @var \Awyiss\Model\Table\WidgetTemplatesTable
	 */
	protected WidgetTemplatesTable $widgetTemplatesTable;


	/**
	 * @inheritDoc
	 */
	protected function setUp(): void {
		parent::setUp();

		/** @noinspection PhpFieldAssignmentTypeMismatchInspection */
		$this->widgetTemplatesTable = FactoryLocator::get('Table')->get('WidgetTemplates');
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\WidgetTemplateElementsTable::ATTRIBUTABLE
	 */
	public function testAttributableConstant(): void {
		/** @noinspection PhpUnitAssertTrueWithIncompatibleTypeArgumentInspection */
		$this->assertTrue($this->widgetTemplatesTable::ATTRIBUTABLE);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\WidgetTemplatesTable::TABLE
	 */
	public function testTableConstant(): void {
		$this->assertEquals('widget_templates', $this->widgetTemplatesTable::TABLE);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\PageTemplatesTable
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testMediaElementAssignableAttribute(): void {
		$reflection = new ReflectionClass(WidgetTemplatesTable::class);
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
	 * @see \Awyiss\Model\Table\WidgetTemplatesTable::initializeAssociations()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testInitializeAssociations(): void {
		$this->assertCount(9, $this->widgetTemplatesTable->associations()->keys());

		$this->assertTrue($this->widgetTemplatesTable->hasAssociation('Widgets'));
		$widgetsAssociation = $this->widgetTemplatesTable->getAssociation('Widgets');
		$this->assertInstanceOf(HasMany::class, $widgetsAssociation);
		$this->assertTrue($widgetsAssociation->getCascadeCallbacks());
		$this->assertTrue($widgetsAssociation->getDependent());

		$this->assertTrue($this->widgetTemplatesTable->hasAssociation('WidgetTemplateElements'));
		$elementsAssociation = $this->widgetTemplatesTable->getAssociation('WidgetTemplateElements');
		$this->assertInstanceOf(HasMany::class, $elementsAssociation);
		$this->assertTrue($elementsAssociation->getCascadeCallbacks());
		$this->assertTrue($elementsAssociation->getDependent());
		$this->assertEquals('replace', $elementsAssociation->getSaveStrategy());

		// 'CreatedByUser' must also exist
		$this->assertTrue($this->widgetTemplatesTable->hasAssociation('CreatedByUser'));
		$createdByUserAssociation = $this->widgetTemplatesTable->getAssociation('CreatedByUser');
		$this->assertInstanceOf(BelongsTo::class, $createdByUserAssociation);
		$this->assertFalse($createdByUserAssociation->getCascadeCallbacks());
		$this->assertFalse($createdByUserAssociation->getDependent());

		// 'ChangedByUser' must also exist
		$this->assertTrue($this->widgetTemplatesTable->hasAssociation('ChangedByUser'));
		$changedByUserAssociation = $this->widgetTemplatesTable->getAssociation('ChangedByUser');
		$this->assertInstanceOf(BelongsTo::class, $changedByUserAssociation);
		$this->assertFalse($changedByUserAssociation->getCascadeCallbacks());
		$this->assertFalse($changedByUserAssociation->getDependent());

		// 'DeletedByUser' must also exist
		$this->assertTrue($this->widgetTemplatesTable->hasAssociation('DeletedByUser'));
		$deletedByUserAssociation = $this->widgetTemplatesTable->getAssociation('DeletedByUser');
		$this->assertInstanceOf(BelongsTo::class, $deletedByUserAssociation);
		$this->assertFalse($deletedByUserAssociation->getCascadeCallbacks());
		$this->assertFalse($deletedByUserAssociation->getDependent());

		// 'MediaAssignments' must also exist
		$this->assertTrue($this->widgetTemplatesTable->hasAssociation('MediaAssignments'));
		$mediaAssignmentsAssociation = $this->widgetTemplatesTable->getAssociation('MediaAssignments');
		$this->assertInstanceOf(HasMany::class, $mediaAssignmentsAssociation);
		$this->assertTrue($mediaAssignmentsAssociation->getCascadeCallbacks());
		$this->assertTrue($mediaAssignmentsAssociation->getDependent());

		// 'MediaElementAssignments' must also exist
		$this->assertTrue($this->widgetTemplatesTable->hasAssociation('MediaElementAssignments'));
		$mediaElementAssignmentsAssociation = $this->widgetTemplatesTable->getAssociation('MediaElementAssignments');
		$this->assertInstanceOf(HasMany::class, $mediaElementAssignmentsAssociation);
		$this->assertTrue($mediaElementAssignmentsAssociation->getCascadeCallbacks());
		$this->assertTrue($mediaElementAssignmentsAssociation->getDependent());

		// 'WidgetTemplates_title_translation' must also exist
		$this->assertTrue($this->widgetTemplatesTable->hasAssociation('WidgetTemplates_title_translation'));
		$titleTranslationAssociation = $this->widgetTemplatesTable->getAssociation('WidgetTemplates_title_translation');
		$this->assertInstanceOf(HasOne::class, $titleTranslationAssociation);
		$this->assertFalse($titleTranslationAssociation->getCascadeCallbacks());
		$this->assertFalse($titleTranslationAssociation->getDependent());

		// 'I18n' must also exist
		$this->assertTrue($this->widgetTemplatesTable->hasAssociation('I18n'));
		$i18nAssociation = $this->widgetTemplatesTable->getAssociation('I18n');
		$this->assertInstanceOf(HasMany::class, $i18nAssociation);
		$this->assertFalse($i18nAssociation->getCascadeCallbacks());
		$this->assertTrue($i18nAssociation->getDependent());
		$this->assertEquals('append', $i18nAssociation->getSaveStrategy());
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\WidgetTemplatesTable::findWithUsages()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testFindWithUsages(): void {
		$query = $this->widgetTemplatesTable->find('withUsages');

		/** @noinspection PhpConditionAlreadyCheckedInspection */
		$this->assertInstanceOf(SelectQuery::class, $query);

		// Test that the query includes the expected fields
		$select = $query->clause('select');
		$this->assertContains('used_for_widgets', array_keys($select));
		$this->assertInstanceOf(AggregateExpression::class, $select['used_for_widgets']);

		// Test that the query includes group by
		$this->assertTrue($query->isAutoFieldsEnabled());
		$this->assertSame(['WidgetTemplates.id'], $query->clause('group'));

		// Test that the query has a left join with Widgets
		$matching = $query->getEagerLoader()->getMatching();
		$this->assertArrayHasKey('Widgets', $matching);
		$this->assertArrayHasKey('queryBuilder', $matching['Widgets']);
		/** @var \Cake\ORM\Query\SelectQuery $query */
		$query = $matching['Widgets']['queryBuilder']($query);

		$this->assertSame(['attributes' => ['skip' => true]], $query->getOptions());
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\WidgetTemplatesTable::getAssignedWidgetAttributes()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testGetAssignedWidgetAttributes(): void {
		/** @var \Awyiss\Model\Entity\WidgetTemplate $widgetTemplate */
		$widgetTemplate = $this->widgetTemplatesTable->newEntity([
			'title' => 'Test Template',
			'fileName' => 'test_template',
			'widgetTemplateElements' => [
				[
					'identifier' => 'attributes.free_text',
				],
				[
					'identifier' => 'attributes.free_text_inactive',
				],
				[
					'identifier' => 'title',
				],
			],
		]);

		$assignedAttributes = $this->widgetTemplatesTable->getAssignedWidgetAttributes($widgetTemplate);

		$this->assertSame(['free_text'], $assignedAttributes);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\WidgetTemplatesTable::getAssignedWidgetAttributes()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testGetAssignedWidgetAttributesWithMissingElements(): void {
		/** @var \Awyiss\Model\Entity\WidgetTemplate $widgetTemplate */
		$widgetTemplate = $this->widgetTemplatesTable->get(1);

		$this->assertEmpty($widgetTemplate->widgetTemplateElements);

		$assignedAttributes = $this->widgetTemplatesTable->getAssignedWidgetAttributes($widgetTemplate);

		$this->assertNotEmpty($widgetTemplate->widgetTemplateElements);
		$this->assertSame(['free_text'], $assignedAttributes);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\WidgetTemplatesTable::getAvailableFieldsets()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testGetAvailableFieldsets(): void {
		$fieldsets = $this->widgetTemplatesTable->getAvailableFieldsets();

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
	 * @see \Awyiss\Model\Table\WidgetTemplatesTable::getAvailableWidgetElements()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testGetAvailableWidgetElements(): void {
		$elements = $this->widgetTemplatesTable->getAvailableWidgetElements();

		$this->assertIsArray($elements);

		foreach ($elements as $key => $value) {
			$this->assertIsString($key);
			$this->assertIsBool($value);
		}
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\WidgetTemplatesTable::getAvailableWidgetAttributes()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testGetAvailableWidgetAttributes(): void {
		$attributes = $this->widgetTemplatesTable->getAvailableWidgetAttributes();

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
	 * @see \Awyiss\Model\Table\WidgetTemplatesTable::getAvailableWidgetAttributes()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testGetAvailableWidgetAttributesIncludeInactive(): void {
		$activeOnly = $this->widgetTemplatesTable->getAvailableWidgetAttributes();
		$withInactive = $this->widgetTemplatesTable->getAvailableWidgetAttributes(true);

		$this->assertIsArray($activeOnly);
		$this->assertIsArray($withInactive);

		$this->assertCount(2, $activeOnly);
		$this->assertCount(3, $withInactive);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\WidgetTemplatesTable::validationDefault()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testValidationDefault(): void {
		$validator = new Validator();
		$result = $this->widgetTemplatesTable->validationDefault($validator);

		$this->assertInstanceOf(Validator::class, $result);
		$this->assertSame('widget_templates', $result->getI18nDomain());

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
	 * @see \Awyiss\Model\Table\WidgetTemplatesTable::validationDefault()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testEntityValidationSuccess(): void {
		$data = [
			'title' => 'Test Template',
			'fileName' => 'test_template',
			'systemOrder' => 1,
			'active' => true,
			'deleted' => false,
		];

		$entity = $this->widgetTemplatesTable->newEntity($data);
		$errors = $entity->getErrors();

		$this->assertEmpty($errors, 'Valid data should not produce validation errors');
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\WidgetTemplatesTable::validationDefault()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testEntityValidationMissingRequired(): void {
		$data = [
			'systemOrder' => 1,
		];

		$entity = $this->widgetTemplatesTable->newEntity($data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('title', $errors);
		$this->assertArrayHasKey('fileName', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\WidgetTemplatesTable::validationDefault()
	 * @noinspection PhpVariableNamingConventionInspection
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

		$entity = $this->widgetTemplatesTable->newEntity($data);
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
	 * @see \Awyiss\Model\Table\WidgetTemplatesTable::validationDefault()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testEntityValidationFieldLength(): void {
		$data = [
			'id' => 123456789123, // exceeds 11 char limit
			'title' => str_repeat('a', 101), // exceeds 100 char limit
			'fileName' => str_repeat('b', 101), // exceeds 100 char limit
		];

		$entity = $this->widgetTemplatesTable->newEntity($data, ['guard' => false]);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('id', $errors);
		$this->assertArrayHasKey('title', $errors);
		$this->assertArrayHasKey('fileName', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\WidgetTemplatesTable::buildRules()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testBuildRulesFileNameUnique(): void {
		/** @var \Awyiss\Model\Entity\WidgetTemplate $entity1 */
		$entity1 = $this->widgetTemplatesTable->get(1);

		// Try to create second entity with same fileName
		$entity2 = unserialize(serialize($entity1));
		$entity2->unset('id'); // Clear ID to create a new entity
		$entity2->setNew(true);

		$saved2 = $this->widgetTemplatesTable->checkRules($entity2);
		$this->assertFalse($saved2, 'Second entity should fail due to duplicate fileName');

		$errors = $entity2->getErrors();
		$this->assertArrayHasKey('fileName', $errors);
		$this->assertArrayHasKey('fileNameUnique', $errors['fileName']);
		$this->assertEquals('widget_templates::error_file_name_unique', $errors['fileName']['fileNameUnique']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\WidgetTemplatesTable::buildRules()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testBuildRulesAssignedWidgetTemplateElementsExist(): void {
		/** @var \Awyiss\Model\Entity\WidgetTemplate $entity */
		$entity = $this->widgetTemplatesTable->get(1, contain: ['WidgetTemplateElements']);

		// Add a non-existing element identifier
		$entity->widgetTemplateElements[8]->identifier = 'non_existing_element';

		$saved = $this->widgetTemplatesTable->checkRules($entity);
		$this->assertFalse($saved, 'Entity should fail due to non-existing widget template element');

		$errors = $entity->getErrors();
		$this->assertArrayHasKey('widgetTemplateElements', $errors);
		$this->assertArrayHasKey('validWidgetElements', $errors['widgetTemplateElements']);
		$this->assertEquals('widget_templates::error_valid_widget_elements', $errors['widgetTemplateElements']['validWidgetElements']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\WidgetTemplatesTable::buildRules()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testBuildRulesNoLinkedWidgets(): void {
		/** @var \Awyiss\Model\Entity\WidgetTemplate $entity */
		$entity = $this->widgetTemplatesTable->get(1);

		$saved = $this->widgetTemplatesTable->checkRules($entity, RulesChecker::DELETE);
		$this->assertFalse($saved);

		$errors = $entity->getErrors();
		$this->assertArrayHasKey('_general', $errors);
		$this->assertArrayHasKey('noLinkedWidgets', $errors['_general']);
		$this->assertEquals('widget_templates::error_linked_widgets', $errors['_general']['noLinkedWidgets']);

		/** @var \Awyiss\Model\Entity\WidgetTemplate $entity */
		$entity = $this->widgetTemplatesTable->get(2);

		$saved = $this->widgetTemplatesTable->checkRules($entity, RulesChecker::DELETE);
		$this->assertTrue($saved);

		$errors = $entity->getErrors();
		$this->assertEmpty($errors, 'Entity with no linked widgets should not have errors');
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\WidgetTemplatesTable::buildRules()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testNewDefaultEntity(): void {
		$entity = $this->widgetTemplatesTable->newDefaultEntity();

		$this->assertInstanceOf(WidgetTemplate::class, $entity);
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
	 * @see \Awyiss\Model\Table\WidgetTemplatesTable::newDefaultEntity()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testNewDefaultEntityWithData(): void {
		$additionalData = [
			'active' => false,
			'title' => 'Custom Title',
			'fileName' => 'custom_file',
			'systemOrder' => 5,
		];

		$entity = $this->widgetTemplatesTable->newDefaultEntity($additionalData);

		$this->assertInstanceOf(WidgetTemplate::class, $entity);
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
	 * @see \Awyiss\Model\Table\WidgetTemplatesTable::$translate
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testSystemOrderBehavior(): void {
		$this->assertTrue($this->widgetTemplatesTable->hasBehavior('SystemOrder'));

		$config = $this->widgetTemplatesTable->getBehavior('SystemOrder')->getConfig();

		$this->assertTrue($config['enabled']);
		$this->assertSame([], $config['relatedColumns']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\WidgetTemplatesTable::$translate
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testTranslateBehavior(): void {
		$this->assertTrue($this->widgetTemplatesTable->hasBehavior('Translate'));

		$config = $this->widgetTemplatesTable->getBehavior('Translate')->getConfig();

		// Auto-realm
		$this->assertNull($config['realm'] ?? null);

		$this->assertIsArray($config['fields']);
		$this->assertSame(['title'], $config['fields']);
	}
}
