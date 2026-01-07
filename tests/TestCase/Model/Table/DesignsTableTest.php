<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Model\Table;


use Awyiss\Model\Entity\Design;
use Awyiss\Model\Table\DesignsTable;
use Awyiss\ORM\Association\BelongsTo;
use Awyiss\ORM\Association\HasMany;
use Awyiss\ORM\Association\HasOne;
use Awyiss\Test\TestSuite\TestCase;
use Awyiss\Validation\Validator;
use Cake\Datasource\FactoryLocator;
use Cake\Datasource\RulesChecker;


/**
 * DesignsTable Test Case
 *
 * @see \Awyiss\Model\Table\DesignsTable
 */
class DesignsTableTest extends TestCase {
	/**
	 * @var \Awyiss\Model\Table\DesignsTable
	 */
	protected DesignsTable $designsTable;


	/**
	 * @inheritDoc
	 */
	protected function setUp(): void {
		parent::setUp();

		/** @noinspection PhpFieldAssignmentTypeMismatchInspection */
		$this->designsTable = FactoryLocator::get('Table')->get('Designs');
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\DesignsTable::ATTRIBUTABLE
	 */
	public function testAttributableConstant(): void {
		$this->assertFalse($this->designsTable::ATTRIBUTABLE);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\DesignsTable::TABLE
	 */
	public function testTableConstant(): void {
		$this->assertEquals('designs', $this->designsTable::TABLE);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\DesignsTable::initializeAssociations()
	 */
	public function testInitializeAssociations(): void {
		$this->assertCount(6, $this->designsTable->associations()->keys());

		// Test user tracking associations
		$this->assertTrue($this->designsTable->hasAssociation('CreatedByUser'));
		$createdByUserAssociation = $this->designsTable->getAssociation('CreatedByUser');
		$this->assertInstanceOf(BelongsTo::class, $createdByUserAssociation);
		$this->assertFalse($createdByUserAssociation->getCascadeCallbacks());
		$this->assertFalse($createdByUserAssociation->getDependent());

		$this->assertTrue($this->designsTable->hasAssociation('ChangedByUser'));
		$changedByUserAssociation = $this->designsTable->getAssociation('ChangedByUser');
		$this->assertInstanceOf(BelongsTo::class, $changedByUserAssociation);
		$this->assertFalse($changedByUserAssociation->getCascadeCallbacks());
		$this->assertFalse($changedByUserAssociation->getDependent());

		$this->assertTrue($this->designsTable->hasAssociation('DeletedByUser'));
		$deletedByUserAssociation = $this->designsTable->getAssociation('DeletedByUser');
		$this->assertInstanceOf(BelongsTo::class, $deletedByUserAssociation);
		$this->assertFalse($deletedByUserAssociation->getCascadeCallbacks());
		$this->assertFalse($deletedByUserAssociation->getDependent());

		// 'Designs_title_translation' must also exist
		$this->assertTrue($this->designsTable->hasAssociation('Designs_title_translation'));
		$titleTranslationAssociation = $this->designsTable->getAssociation('Designs_title_translation');
		$this->assertInstanceOf(HasOne::class, $titleTranslationAssociation);
		$this->assertFalse($titleTranslationAssociation->getCascadeCallbacks());
		$this->assertFalse($titleTranslationAssociation->getDependent());

		// 'Designs_textHtml_translation' must also exist
		$this->assertTrue($this->designsTable->hasAssociation('Designs_description_translation'));
		$descriptionTranslationAssociation = $this->designsTable->getAssociation('Designs_description_translation');
		$this->assertInstanceOf(HasOne::class, $descriptionTranslationAssociation);
		$this->assertFalse($descriptionTranslationAssociation->getCascadeCallbacks());
		$this->assertFalse($descriptionTranslationAssociation->getDependent());

		// 'I18n' must also exist
		$this->assertTrue($this->designsTable->hasAssociation('I18n'));
		$i18nAssociation = $this->designsTable->getAssociation('I18n');
		$this->assertInstanceOf(HasMany::class, $i18nAssociation);
		$this->assertFalse($i18nAssociation->getCascadeCallbacks());
		$this->assertTrue($i18nAssociation->getDependent());
		$this->assertEquals('append', $i18nAssociation->getSaveStrategy());
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\DesignsTable::validationDefault()
	 */
	public function testValidationDefault(): void {
		$validator = new Validator();
		$result = $this->designsTable->validationDefault($validator);

		$this->assertInstanceOf(Validator::class, $result);
		$this->assertSame('designs', $result->getI18nDomain());

		// Test required fields
		$this->assertTrue($result->hasField('identifier'));
		$this->assertSame('create', $result->field('identifier')->isPresenceRequired());

		// Test other fields
		$this->assertTrue($result->hasField('id'));
		$this->assertTrue($result->hasField('title'));
		$this->assertTrue($result->hasField('description'));
		$this->assertTrue($result->hasField('settings'));
		$this->assertTrue($result->hasField('css'));
		$this->assertTrue($result->hasField('inUse'));
		$this->assertTrue($result->hasField('deleted'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\DesignsTable::validationDefault()
	 */
	public function testEntityValidationSuccess(): void {
		$data = [
			'identifier' => 'test_design',
			'title' => 'Test Design',
			'description' => 'A test design',
			'settings' => ['color' => 'blue', 'font' => 'Arial'],
			'css' => 'body { color: red; }',
			'inUse' => false,
			'deleted' => false,
		];

		$entity = $this->designsTable->newEntity($data);
		$errors = $entity->getErrors();

		$this->assertEmpty($errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\DesignsTable::validationDefault()
	 */
	public function testEntityValidationMissingRequired(): void {
		$data = [
			'title' => 'Test Design',
		];

		$entity = $this->designsTable->newEntity($data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('identifier', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\DesignsTable::validationDefault()
	 */
	public function testEntityValidationInvalidTypes(): void {
		$data = [
			'id' => 'not_an_integer',
			'identifier' => true,
			'title' => true,
			'description' => true,
			'settings' => 'not_an_array',
			'css' => true,
			'inUse' => 'not_a_boolean',
			'deleted' => 'not_a_boolean',
		];

		$entity = $this->designsTable->newEntity($data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('id', $errors);
		$this->assertArrayHasKey('identifier', $errors);
		$this->assertArrayHasKey('title', $errors);
		$this->assertArrayHasKey('description', $errors);
		$this->assertArrayHasKey('settings', $errors);
		$this->assertArrayHasKey('css', $errors);
		$this->assertArrayHasKey('inUse', $errors);
		$this->assertArrayHasKey('deleted', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\DesignsTable::validationDefault()
	 */
	public function testEntityValidationFieldLength(): void {
		$data = [
			'id' => 123456789123, // exceeds 11 char limit
			'identifier' => str_repeat('a', 13), // exceeds 12 char limit
			'title' => str_repeat('b', 101), // exceeds 100 char limit
			'description' => str_repeat('c', 256), // exceeds 255 char limit
			'css' => str_repeat('d', 65536), // exceeds 65535 byte limit
		];

		$entity = $this->designsTable->newEntity($data, ['guard' => false]);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('id', $errors);
		$this->assertArrayHasKey('identifier', $errors);
		$this->assertArrayHasKey('title', $errors);
		$this->assertArrayHasKey('description', $errors);
		$this->assertArrayHasKey('css', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\DesignsTable::validationDefault()
	 */
	public function testEntityValidationNotBlank(): void {
		$data = [
			'identifier' => '   ', // only whitespace
			'title' => '   ', // only whitespace
		];

		$entity = $this->designsTable->newEntity($data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('identifier', $errors);
		$this->assertArrayHasKey('title', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\DesignsTable::validationDefault()
	 */
	public function testEntityValidationNotEmptyString(): void {
		$data = [
			'identifier' => '',
			'title' => '',
		];

		$entity = $this->designsTable->newEntity($data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('identifier', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\DesignsTable::validationDefault()
	 */
	public function testEntityValidationSettingsMaxLengthBytes(): void {
		// Create a large settings array that when JSON encoded exceeds 65535 bytes
		$largeSettings = [];
		for ($i = 0; $i < 1000; $i++) {
			$largeSettings["key_$i"] = str_repeat('x', 100);
		}

		$data = [
			'identifier' => 'test_design',
			'title' => 'Test Design',
			'settings' => $largeSettings,
		];

		$entity = $this->designsTable->newEntity($data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('settings', $errors);
		$this->assertArrayHasKey('maxLengthBytes', $errors['settings']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\DesignsTable::validationDefault()
	 */
	public function testEntityValidationAllowEmptyFields(): void {
		$data = [
			'identifier' => 'test_design',
			'title' => 'Test Design',
			'description' => null, // description allows empty
			'settings' => null, // settings allows empty
			'css' => null, // css allows empty
		];

		$entity = $this->designsTable->newEntity($data);
		$errors = $entity->getErrors();

		$this->assertArrayNotHasKey('description', $errors);
		$this->assertArrayNotHasKey('settings', $errors);
		$this->assertArrayNotHasKey('css', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\DesignsTable::buildRules()
	 */
	public function testBuildRulesUniqueIdentifier(): void {
		// Test with existing identifier (should fail)
		$data = [
			'identifier' => '35105eae51b2', // This identifier already exists in fixtures
			'title' => 'Test Design',
		];

		$entity = $this->designsTable->newEntity($data);
		$result = $this->designsTable->checkRules($entity);

		$this->assertFalse($result);

		$errors = $entity->getErrors();
		$this->assertArrayHasKey('identifier', $errors);
		$this->assertArrayHasKey('identifierUnique', $errors['identifier']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\DesignsTable::buildRules()
	 */
	public function testBuildRulesUniqueIdentifierValid(): void {
		// Test with unique identifier (should pass)
		$data = [
			'identifier' => 'unique_test_design',
			'title' => 'Test Design',
		];

		$entity = $this->designsTable->newEntity($data);
		$result = $this->designsTable->checkRules($entity);

		$this->assertTrue($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\DesignsTable::buildRules()
	 */
	public function testBuildDeleteRulesNotInUseValid(): void {
		$data = [
			'identifier' => 'test_design',
			'title' => 'Test Design',
			'inUse' => false,
		];

		$entity = $this->designsTable->newEntity($data);
		$entity->set('id', 9999);
		$entity->setNew(false);

		$result = $this->designsTable->checkRules($entity, RulesChecker::DELETE);
		$this->assertTrue($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\DesignsTable::buildRules()
	 */
	public function testBuildDeleteRulesNotInUseInvalid(): void {
		$data = [
			'identifier' => 'test_design_in_use',
			'title' => 'Test Design In Use',
			'inUse' => true,
		];

		$entity = $this->designsTable->newEntity($data);
		$entity->set('id', 9999);
		$entity->setNew(false);

		$result = $this->designsTable->checkRules($entity, RulesChecker::DELETE);
		$this->assertFalse($result);

		$errors = $entity->getErrors();
		$this->assertArrayHasKey('_general', $errors);
		$this->assertArrayHasKey('notInUse', $errors['_general']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\DesignsTable::newDefaultEntity()
	 */
	public function testNewDefaultEntity(): void {
		$entity = $this->designsTable->newDefaultEntity();

		$this->assertInstanceOf(Design::class, $entity);
		$this->assertTrue($entity->isNew());

		// Check default values
		$this->assertNull($entity->identifier);
		$this->assertNull($entity->title);
		$this->assertNull($entity->description);
		$this->assertNull($entity->settings);
		$this->assertNull($entity->css);
		$this->assertFalse($entity->inUse);
		$this->assertFalse($entity->deleted);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\DesignsTable::newDefaultEntity()
	 */
	public function testNewDefaultEntityWithData(): void {
		$additionalData = [
			'identifier' => 'custom_design',
			'title' => 'Custom Design',
			'description' => 'Custom description',
			'settings' => ['theme' => 'dark'],
			'css' => '.custom { color: blue; }',
			'inUse' => true,
		];

		$entity = $this->designsTable->newDefaultEntity($additionalData);

		$this->assertInstanceOf(Design::class, $entity);
		$this->assertTrue($entity->isNew());

		// Check custom values
		$this->assertSame('custom_design', $entity->identifier);
		$this->assertSame('Custom Design', $entity->title);
		$this->assertSame('Custom description', $entity->description);
		$this->assertSame(['theme' => 'dark'], $entity->settings);
		$this->assertSame('.custom { color: blue; }', $entity->css);
		$this->assertTrue($entity->inUse);

		// Check that defaults are preserved
		$this->assertFalse($entity->deleted);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\DesignsTable::$translate
	 */
	public function testTranslateBehavior(): void {
		$this->assertTrue($this->designsTable->hasBehavior('Translate'));

		$config = $this->designsTable->getBehavior('Translate')->getConfig();

		$this->assertNull($config['realm'] ?? null);

		$this->assertIsArray($config['fields']);
		$this->assertSame(['title', 'description'], $config['fields']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\DesignsTable::initializeSchema()
	 */
	public function testInitializeSchemaJsonColumns(): void {
		$schema = $this->designsTable->getSchema();

		// Test that settings column is configured as JSON type
		$this->assertSame('json', $schema->getColumnType('settings'));
	}
}
