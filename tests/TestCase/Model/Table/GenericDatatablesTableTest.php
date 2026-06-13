<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Model\Table;


use Awyiss\Annotation\MediaElementAssignable;
use Awyiss\Awyiss;
use Awyiss\Model\Table\GenericDatatablesTable;
use Awyiss\ORM\Association\BelongsTo;
use Awyiss\ORM\Association\HasMany;
use Awyiss\ORM\Association\HasOne;
use Awyiss\Test\TestSuite\TestCase;
use Awyiss\Validation\Validator;
use Cake\Core\Configure;
use Cake\Datasource\FactoryLocator;
use Customer\Model\Table\CarsTable;
use ReflectionClass;


/**
 * GenericDatatablesTable Test Case
 *
 * @see \Awyiss\Model\Table\GenericDatatablesTable
 */
class GenericDatatablesTableTest extends TestCase {
	/**
	 * @var \Customer\Model\Table\CarsTable
	 */
	protected CarsTable $carsTable;


	/**
	 * @inheritDoc
	 */
	protected function setUp(): void {
		parent::setUp();

		/** @noinspection PhpFieldAssignmentTypeMismatchInspection */
		$this->carsTable = FactoryLocator::get('Table')->get('Cars');
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\GenericDatatablesTable
	 * @see \Customer\Model\Table\CarsTable
	 */
	protected function testGenericDatatablesIsAbstract(): void {
		$reflection = new ReflectionClass(GenericDatatablesTable::class);
		$this->assertTrue($reflection->isAbstract());
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\GenericDatatablesTable
	 * @see \Customer\Model\Table\CarsTable
	 */
	protected function testExtendsGenericDatatablesTable(): void {
		/** @noinspection PhpConditionAlreadyCheckedInspection */
		$this->assertInstanceOf(GenericDatatablesTable::class, $this->carsTable);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\GenericDatatablesTable::ATTRIBUTABLE
	 */
	public function testAttributableConstant(): void {
		/** @noinspection PhpUnitAssertTrueWithIncompatibleTypeArgumentInspection */
		$this->assertTrue($this->carsTable::ATTRIBUTABLE);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\GenericDatatablesTable::TABLE
	 */
	public function testTableConstant(): void {
		$this->assertEquals('cars', $this->carsTable::TABLE);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\GenericDatatablesTable
	 */
	public function testMediaElementAssignableAttribute(): void {
		$reflection = new ReflectionClass(GenericDatatablesTable::class);
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
	 * @see \Awyiss\Model\Table\GenericDatatablesTable::initializeAssociations()
	 */
	public function testInitializeAssociations(): void {
		$this->assertCount(9, $this->carsTable->associations()->keys());

		$this->assertTrue($this->carsTable->hasAssociation('AttributesCars'));
		$attributesAssociation = $this->carsTable->getAssociation('AttributesCars');
		$this->assertInstanceOf(HasOne::class, $attributesAssociation);
		$this->assertTrue($attributesAssociation->getCascadeCallbacks());
		$this->assertTrue($attributesAssociation->getDependent());

		// Test Languages association (BelongsTo)
		$this->assertTrue($this->carsTable->hasAssociation('Languages'));
		$languagesAssociation = $this->carsTable->getAssociation('Languages');
		$this->assertInstanceOf(BelongsTo::class, $languagesAssociation);
		$this->assertFalse($languagesAssociation->getCascadeCallbacks());
		$this->assertFalse($languagesAssociation->getDependent());
		$this->assertSame('shortcode', $languagesAssociation->getBindingKey());
		$this->assertSame('languageShortcode', $languagesAssociation->getForeignKey());
		$this->assertSame(['realm' => Awyiss::REALM_FRONTEND], $languagesAssociation->getConditions());

		// 'MediaAssignments' must also exist
		$this->assertTrue($this->carsTable->hasAssociation('MediaAssignments'));
		$mediaAssignmentsAssociation = $this->carsTable->getAssociation('MediaAssignments');
		$this->assertInstanceOf(HasMany::class, $mediaAssignmentsAssociation);
		$this->assertTrue($mediaAssignmentsAssociation->getCascadeCallbacks());
		$this->assertTrue($mediaAssignmentsAssociation->getDependent());

		// Test MediaElementAssignments association (BelongsTo)
		$this->assertTrue($this->carsTable->hasAssociation('MediaElementAssignments'));
		$mediaElementAssignmentsAssociation = $this->carsTable->getAssociation('MediaElementAssignments');
		$this->assertInstanceOf(HasMany::class, $mediaElementAssignmentsAssociation);
		$this->assertTrue($mediaElementAssignmentsAssociation->getCascadeCallbacks());
		$this->assertFalse($mediaElementAssignmentsAssociation->getDependent());

		// 'ParentCars' must also exist (from parent table implementation)
		$this->assertTrue($this->carsTable->hasAssociation('ParentCars'));
		$parentCarsAssociation = $this->carsTable->getAssociation('ParentCars');
		$this->assertInstanceOf(BelongsTo::class, $parentCarsAssociation);
		$this->assertFalse($parentCarsAssociation->getCascadeCallbacks());
		$this->assertFalse($parentCarsAssociation->getDependent());

		// 'ChildCars' must also exist (from parent table implementation)
		$this->assertTrue($this->carsTable->hasAssociation('ChildCars'));
		$childCarsAssociation = $this->carsTable->getAssociation('ChildCars');
		$this->assertInstanceOf(HasMany::class, $childCarsAssociation);
		$this->assertTrue($childCarsAssociation->getCascadeCallbacks());
		$this->assertTrue($childCarsAssociation->getDependent());

		// Test user tracking associations
		$this->assertTrue($this->carsTable->hasAssociation('CreatedByUser'));
		$createdByUserAssociation = $this->carsTable->getAssociation('CreatedByUser');
		$this->assertInstanceOf(BelongsTo::class, $createdByUserAssociation);
		$this->assertFalse($createdByUserAssociation->getCascadeCallbacks());
		$this->assertFalse($createdByUserAssociation->getDependent());

		$this->assertTrue($this->carsTable->hasAssociation('ChangedByUser'));
		$changedByUserAssociation = $this->carsTable->getAssociation('ChangedByUser');
		$this->assertInstanceOf(BelongsTo::class, $changedByUserAssociation);
		$this->assertFalse($changedByUserAssociation->getCascadeCallbacks());
		$this->assertFalse($changedByUserAssociation->getDependent());

		$this->assertTrue($this->carsTable->hasAssociation('DeletedByUser'));
		$deletedByUserAssociation = $this->carsTable->getAssociation('DeletedByUser');
		$this->assertInstanceOf(BelongsTo::class, $deletedByUserAssociation);
		$this->assertFalse($deletedByUserAssociation->getCascadeCallbacks());
		$this->assertFalse($deletedByUserAssociation->getDependent());
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\GenericDatatablesTable::initializeAssociations()
	 */
	public function testInitializeAssociationsWithTranslatable(): void {
		Configure::write('Awyiss.Cars.Backend.translatable', true);

		$tableLocator = FactoryLocator::get('Table');
		$tableLocator->clear();

		$table = $tableLocator->get('Cars');

		// Test translation associations
		$this->assertTrue($table->hasAssociation('Cars_title_translation'));
		$titleTranslationAssociation = $table->getAssociation('Cars_title_translation');
		$this->assertInstanceOf(HasOne::class, $titleTranslationAssociation);
		$this->assertFalse($titleTranslationAssociation->getCascadeCallbacks());
		$this->assertFalse($titleTranslationAssociation->getDependent());

		$this->assertTrue($table->hasAssociation('I18n'));
		$i18nAssociation = $table->getAssociation('I18n');
		$this->assertInstanceOf(HasMany::class, $i18nAssociation);
		$this->assertFalse($i18nAssociation->getCascadeCallbacks());
		$this->assertTrue($i18nAssociation->getDependent());
		$this->assertEquals('append', $i18nAssociation->getSaveStrategy());
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\GenericDatatablesTable::validationDefault()
	 */
	public function testValidationDefaultWithSplitIntoLanguagesEnabled(): void {
		Configure::write('Awyiss.Cars.Backend.splitIntoLanguages', true);

		$tableLocator = FactoryLocator::get('Table');
		$tableLocator->clear();

		/** @var \Customer\Model\Table\CarsTable $table */
		$table = $tableLocator->get('Cars');

		$validator = new Validator();
		$result = $table->validationDefault($validator);

		$this->assertInstanceOf(Validator::class, $result);
		$this->assertSame('Cars', $result->getI18nDomain());

		// Test required fields when splitIntoLanguages is true
		$this->assertTrue($result->hasField('languageShortcode')); // Should be required
		$this->assertTrue($result->hasField('title'));

		// Test other fields
		$this->assertTrue($result->hasField('id'));
		$this->assertTrue($result->hasField('parentId'));
		$this->assertTrue($result->hasField('systemOrder'));
		$this->assertTrue($result->hasField('active'));
		$this->assertTrue($result->hasField('deleted'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\GenericDatatablesTable::validationDefault()
	 */
	public function testValidationDefaultWithSplitIntoLanguagesDisabled(): void {
		Configure::write('Awyiss.Cars.Backend.splitIntoLanguages', false);

		$tableLocator = FactoryLocator::get('Table');
		$tableLocator->clear();

		/** @var \Customer\Model\Table\CarsTable $table */
		$table = $tableLocator->get('Cars');

		$validator = new Validator();
		$result = $table->validationDefault($validator);

		$this->assertInstanceOf(Validator::class, $result);
		$this->assertSame('Cars', $result->getI18nDomain());

		// Test required fields when splitIntoLanguages is false
		$this->assertFalse($result->hasField('languageShortcode')); // Should not be required
		$this->assertTrue($result->hasField('title'));

		// Test other fields
		$this->assertTrue($result->hasField('id'));
		$this->assertTrue($result->hasField('parentId'));
		$this->assertTrue($result->hasField('systemOrder'));
		$this->assertTrue($result->hasField('active'));
		$this->assertTrue($result->hasField('deleted'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\GenericDatatablesTable::validationDefault()
	 */
	public function testEntityValidationSuccess(): void {
		$data = [
			'languageShortcode' => 'de',
			'title' => 'Test Generic Datatable',
			'parentId' => 1,
			'systemOrder' => 1,
			'active' => true,
			'deleted' => false,
		];

		$entity = $this->carsTable->newEntity($data);
		$errors = $entity->getErrors();

		$this->assertEmpty($errors, 'Valid data should not produce validation errors');
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\GenericDatatablesTable::validationDefault()
	 */
	public function testEntityValidationMissingRequired(): void {
		$data = [
			'systemOrder' => 1,
		];

		$entity = $this->carsTable->newEntity($data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('languageShortcode', $errors);
		$this->assertArrayHasKey('title', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\GenericDatatablesTable::validationDefault()
	 */
	public function testEntityValidationInvalidTypes(): void {
		$data = [
			'id' => 'not_an_integer',
			'parentId' => 'not_an_integer',
			'languageShortcode' => true,
			'title' => true,
			'systemOrder' => 'not_an_integer',
			'active' => 'not_a_boolean',
			'deleted' => 'not_a_boolean',
		];

		$entity = $this->carsTable->newEntity($data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('id', $errors);
		$this->assertArrayHasKey('parentId', $errors);
		$this->assertArrayHasKey('languageShortcode', $errors);
		$this->assertArrayHasKey('title', $errors);
		$this->assertArrayHasKey('systemOrder', $errors);
		$this->assertArrayHasKey('active', $errors);
		$this->assertArrayHasKey('deleted', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\GenericDatatablesTable::validationDefault()
	 */
	public function testEntityValidationFieldLength(): void {
		$data = [
			'id' => 123456789123, // exceeds 11 char limit
			'parentId' => 123456789123, // exceeds 11 char limit
			'languageShortcode' => 'de', // valid 2 char length
			'title' => str_repeat('a', 256), // exceeds 255 char limit
			'systemOrder' => 123456789123, // exceeds 11 char limit
		];

		$entity = $this->carsTable->newEntity($data, ['guard' => false]);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('id', $errors);
		$this->assertArrayHasKey('parentId', $errors);
		$this->assertArrayNotHasKey('languageShortcode', $errors); // Should be valid
		$this->assertArrayHasKey('title', $errors);
		$this->assertArrayHasKey('systemOrder', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\GenericDatatablesTable::validationDefault()
	 */
	public function testEntityValidationLanguageShortcodeExactLengthValid(): void {
		// Test valid language shortcodes
		$codes = ['de', 'en', 'fr', 'es'];
		foreach ($codes as $code) {
			$data = [
				'languageShortcode' => $code,
				'title' => 'Test',
			];

			$entity = $this->carsTable->newEntity($data);
			$errors = $entity->getErrors();
			$this->assertArrayNotHasKey('languageShortcode', $errors);
		}
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\GenericDatatablesTable::validationDefault()
	 */
	public function testEntityValidationLanguageShortcodeExactLengthInvalid(): void {
		// Test invalid language shortcodes
		$codes = ['d', 'deu', 'english', ''];
		foreach ($codes as $code) {
			$data = [
				'languageShortcode' => $code,
				'title' => 'Test',
			];

			$entity = $this->carsTable->newEntity($data);
			$errors = $entity->getErrors();
			$this->assertArrayHasKey('languageShortcode', $errors);
		}
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\GenericDatatablesTable::validationDefault()
	 */
	public function testEntityValidationNotBlank(): void {
		$data = [
			'languageShortcode' => 'de',
			'title' => '   ', // only whitespace
		];

		$entity = $this->carsTable->newEntity($data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('title', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\GenericDatatablesTable::buildRules()
	 */
	public function testBuildRulesLanguageExistsWithSplitIntoLanguagesEnabled(): void {
		Configure::write('Awyiss.Cars.Backend.splitIntoLanguages', true);

		$tableLocator = FactoryLocator::get('Table');
		$tableLocator->clear();

		/** @var \Customer\Model\Table\CarsTable $carsTable */
		$carsTable = $tableLocator->get('Cars');

		// Test with existing language when splitIntoLanguages is enabled (default)
		$data = [
			'languageShortcode' => 'de',
			'title' => 'Test Generic Datatable',
		];

		$entity = $carsTable->newEntity($data);
		$result = $carsTable->checkRules($entity);
		$this->assertTrue($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\GenericDatatablesTable::buildRules()
	 */
	public function testBuildRulesLanguageNotExistsWithSplitIntoLanguagesEnabled(): void {
		Configure::write('Awyiss.Cars.Backend.splitIntoLanguages', true);

		$tableLocator = FactoryLocator::get('Table');
		$tableLocator->clear();

		/** @var \Customer\Model\Table\CarsTable $carsTable */
		$carsTable = $tableLocator->get('Cars');

		// Test with non-existing language when splitIntoLanguages is enabled (default)
		$data = [
			'languageShortcode' => 'xx',
			'title' => 'Test Generic Datatable',
		];

		$entity = $carsTable->newEntity($data);
		$result = $carsTable->checkRules($entity);
		$this->assertFalse($result);

		$errors = $entity->getErrors();
		$this->assertArrayHasKey('languageShortcode', $errors);
		$this->assertArrayHasKey('languageExists', $errors['languageShortcode']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\GenericDatatablesTable::buildRules()
	 */
	public function testBuildRulesNoLanguageValidationWithSplitIntoLanguagesDisabled(): void {
		Configure::write('Awyiss.Cars.Backend.splitIntoLanguages', false);

		$tableLocator = FactoryLocator::get('Table');
		$tableLocator->clear();

		/** @var \Customer\Model\Table\CarsTable $carsTable */
		$carsTable = $tableLocator->get('Cars');

		// Test with non-existing language when splitIntoLanguages is disabled
		$data = [
			'languageShortcode' => 'foobar',
			'title' => 'Test Generic Datatable',
		];

		$entity = $carsTable->newEntity($data);
		$result = $carsTable->checkRules($entity);
		$this->assertTrue($result); // Should pass without language validation

		$errors = $entity->getErrors();
		$this->assertEmpty($errors); // No errors expected
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\GenericDatatablesTable::newDefaultEntity()
	 */
	public function testNewDefaultEntity(): void {
		$entity = $this->carsTable->newDefaultEntity();

		$this->assertTrue($entity->isNew());

		// Check default values
		$this->assertNull($entity->parentId);
		$this->assertNull($entity->languageShortcode);
		$this->assertNull($entity->title);
		$this->assertSame(0, $entity->systemOrder);
		$this->assertTrue($entity->active);
		$this->assertFalse($entity->deleted);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\GenericDatatablesTable::newDefaultEntity()
	 */
	public function testNewDefaultEntityWithData(): void {
		$additionalData = [
			'parentId' => 2,
			'languageShortcode' => 'en',
			'title' => 'Custom Generic Datatable',
			'systemOrder' => 5,
			'active' => false,
		];

		$entity = $this->carsTable->newDefaultEntity($additionalData);

		$this->assertTrue($entity->isNew());

		// Check custom values
		$this->assertSame(2, $entity->parentId);
		$this->assertSame('en', $entity->languageShortcode);
		$this->assertSame('Custom Generic Datatable', $entity->title);
		$this->assertSame(5, $entity->systemOrder);
		$this->assertFalse($entity->active);
		$this->assertFalse($entity->deleted); // Should remain default
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\GenericDatatablesTable::$nest
	 */
	public function testNestBehaviorWithNestableDisabledSplitIntoLanguagesEnabled(): void {
		Configure::write('Awyiss.Cars.Backend.nest.enabled', false);
		Configure::write('Awyiss.Cars.Backend.splitIntoLanguages', true);

		$tableLocator = FactoryLocator::get('Table');
		$tableLocator->clear();

		/** @var \Customer\Model\Table\CarsTable $table */
		$table = $tableLocator->get('Cars');

		$this->assertTrue($table->hasBehavior('Nest'));

		$config = $table->getBehavior('Nest')->getConfig();

		$this->assertFalse($config['enabled']);

		// Should contain languageShortcode when splitIntoLanguages is enabled
		$this->assertContains('languageShortcode', $config['relatedColumns']);
		// Should NOT contain parentId when nestable is disabled
		$this->assertNotContains('parentId', $config['relatedColumns']);
	}

	/**
	 * @return void
	 * @see \Awyiss\Model\Table\GenericDatatablesTable::$nest
	 */
	public function testNestBehaviorWithNestableEnabledSplitIntoLanguagesEnabled(): void {
		Configure::write('Awyiss.Cars.Backend.nest.enabled', true);
		Configure::write('Awyiss.Cars.Backend.splitIntoLanguages', true);

		$tableLocator = FactoryLocator::get('Table');
		$tableLocator->clear();

		/** @var \Customer\Model\Table\CarsTable $table */
		$table = $tableLocator->get('Cars');

		$this->assertTrue($table->hasBehavior('Nest'));

		$config = $table->getBehavior('Nest')->getConfig();

		$this->assertTrue($config['enabled']);

		// Should contain languageShortcode when splitIntoLanguages is enabled
		$this->assertContains('languageShortcode', $config['relatedColumns']);
		// Should still NOT contain parentId in nest config (parentId goes to systemOrder only)
		$this->assertNotContains('parentId', $config['relatedColumns']);
	}

	/**
	 * @return void
	 * @see \Awyiss\Model\Table\GenericDatatablesTable::$nest
	 */
	public function testNestBehaviorWithNestableDisabledSplitIntoLanguagesDisabled(): void {
		Configure::write('Awyiss.Cars.Backend.nest.enabled', false);
		Configure::write('Awyiss.Cars.Backend.splitIntoLanguages', false);

		$tableLocator = FactoryLocator::get('Table');
		$tableLocator->clear();

		/** @var \Customer\Model\Table\CarsTable $table */
		$table = $tableLocator->get('Cars');

		$this->assertTrue($table->hasBehavior('Nest'));

		$config = $table->getBehavior('Nest')->getConfig();

		$this->assertFalse($config['enabled']);

		// Should NOT contain languageShortcode when splitIntoLanguages is disabled
		$this->assertNotContains('languageShortcode', $config['relatedColumns']);
		// Should NOT contain parentId when nestable is disabled
		$this->assertNotContains('parentId', $config['relatedColumns']);
	}

	/**
	 * @return void
	 * @see \Awyiss\Model\Table\GenericDatatablesTable::$nest
	 */
	public function testNestBehaviorWithNestableEnabledSplitIntoLanguagesDisabled(): void {
		Configure::write('Awyiss.Cars.Backend.nest.enabled', true);
		Configure::write('Awyiss.Cars.Backend.splitIntoLanguages', false);

		$tableLocator = FactoryLocator::get('Table');
		$tableLocator->clear();

		/** @var \Customer\Model\Table\CarsTable $table */
		$table = $tableLocator->get('Cars');

		$this->assertTrue($table->hasBehavior('Nest'));

		$config = $table->getBehavior('Nest')->getConfig();

		$this->assertTrue($config['enabled']);

		// Should NOT contain languageShortcode when splitIntoLanguages is disabled
		$this->assertNotContains('languageShortcode', $config['relatedColumns']);
		// Should still NOT contain parentId in nest config (parentId goes to systemOrder only)
		$this->assertNotContains('parentId', $config['relatedColumns']);
	}

	/**
	 * @return void
	 * @see \Awyiss\Model\Table\GenericDatatablesTable::$systemOrder
	 */
	public function testSystemOrderBehaviorWithNestableDisabledSplitIntoLanguagesEnabled(): void {
		Configure::write('Awyiss.Cars.Backend.nest.enabled', false);
		Configure::write('Awyiss.Cars.Backend.splitIntoLanguages', true);

		$tableLocator = FactoryLocator::get('Table');
		$tableLocator->clear();

		/** @var \Customer\Model\Table\CarsTable $table */
		$table = $tableLocator->get('Cars');

		$this->assertTrue($table->hasBehavior('SystemOrder'));

		$config = $table->getBehavior('SystemOrder')->getConfig();

		$this->assertTrue($config['enabled']);

		// Should contain languageShortcode when splitIntoLanguages is enabled
		$this->assertContains('languageShortcode', $config['relatedColumns']);
		// Should NOT contain parentId when nestable is disabled
		$this->assertNotContains('parentId', $config['relatedColumns']);
	}

	/**
	 * @return void
	 * @see \Awyiss\Model\Table\GenericDatatablesTable::$systemOrder
	 */
	public function testSystemOrderBehaviorWithNestableEnabledSplitIntoLanguagesEnabled(): void {
		Configure::write('Awyiss.Cars.Backend.nest.enabled', true);
		Configure::write('Awyiss.Cars.Backend.splitIntoLanguages', true);

		$tableLocator = FactoryLocator::get('Table');
		$tableLocator->clear();

		/** @var \Customer\Model\Table\CarsTable $table */
		$table = $tableLocator->get('Cars');

		$this->assertTrue($table->hasBehavior('SystemOrder'));

		$config = $table->getBehavior('SystemOrder')->getConfig();

		$this->assertTrue($config['enabled']);

		// Should contain both when both options are enabled
		$this->assertContains('languageShortcode', $config['relatedColumns']);
		$this->assertContains('parentId', $config['relatedColumns']);
	}

	/**
	 * @return void
	 * @see \Awyiss\Model\Table\GenericDatatablesTable::$systemOrder
	 */
	public function testSystemOrderBehaviorWithNestableDisabledSplitIntoLanguagesDisabled(): void {
		Configure::write('Awyiss.Cars.Backend.nest.enabled', false);
		Configure::write('Awyiss.Cars.Backend.splitIntoLanguages', false);

		$tableLocator = FactoryLocator::get('Table');
		$tableLocator->clear();

		/** @var \Customer\Model\Table\CarsTable $table */
		$table = $tableLocator->get('Cars');

		$this->assertTrue($table->hasBehavior('SystemOrder'));

		$config = $table->getBehavior('SystemOrder')->getConfig();

		$this->assertTrue($config['enabled']);

		// Should contain neither when both options are disabled
		$this->assertNotContains('languageShortcode', $config['relatedColumns']);
		$this->assertNotContains('parentId', $config['relatedColumns']);
	}

	/**
	 * @return void
	 * @see \Awyiss\Model\Table\GenericDatatablesTable::$systemOrder
	 */
	public function testSystemOrderBehaviorWithNestableEnabledSplitIntoLanguagesDisabled(): void {
		Configure::write('Awyiss.Cars.Backend.nest.enabled', true);
		Configure::write('Awyiss.Cars.Backend.splitIntoLanguages', false);

		$tableLocator = FactoryLocator::get('Table');
		$tableLocator->clear();

		/** @var \Customer\Model\Table\CarsTable $table */
		$table = $tableLocator->get('Cars');

		$this->assertTrue($table->hasBehavior('SystemOrder'));

		$config = $table->getBehavior('SystemOrder')->getConfig();

		$this->assertTrue($config['enabled']);

		// Should NOT contain languageShortcode when splitIntoLanguages is disabled
		$this->assertNotContains('languageShortcode', $config['relatedColumns']);
		// Should contain parentId when nestable is enabled
		$this->assertContains('parentId', $config['relatedColumns']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\GenericDatatablesTable::$translate
	 */
	public function testTranslateBehaviorWithTranslatableEnabled(): void {
		Configure::write('Awyiss.Cars.Backend.translatable', true);

		$tableLocator = FactoryLocator::get('Table');
		$tableLocator->clear();

		/** @var \Customer\Model\Table\CarsTable $table */
		$table = $tableLocator->get('Cars');

		$this->assertTrue($table->hasBehavior('Translate'));

		$config = $table->getBehavior('Translate')->getConfig();

		$this->assertSame(Awyiss::REALM_FRONTEND, $config['realm']);
		$this->assertIsArray($config['fields']);
		$this->assertContains('title', $config['fields']); // Should contain title when translatable is enabled
	}

	/**
	 * @return void
	 * @see \Awyiss\Model\Table\GenericDatatablesTable::$translate
	 */
	public function testTranslateBehaviorWithTranslatableDisabled(): void {
		Configure::write('Awyiss.Cars.Backend.translatable', false);

		$tableLocator = FactoryLocator::get('Table');
		$tableLocator->clear();

		/** @var \Customer\Model\Table\CarsTable $table */
		$table = $tableLocator->get('Cars');

		$this->assertFalse($table->hasBehavior('Translate'));
	}
}
