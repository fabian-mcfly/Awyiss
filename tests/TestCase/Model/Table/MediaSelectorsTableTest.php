<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Model\Table;


use Awyiss\Model\Entity\MediaSelector;
use Awyiss\Model\Table\MediaSelectorsTable;
use Awyiss\ORM\Association\BelongsTo;
use Awyiss\ORM\Association\HasMany;
use Awyiss\ORM\Association\HasOne;
use Awyiss\Test\TestSuite\TestCase;
use Awyiss\Validation\Validator;
use Cake\Datasource\FactoryLocator;
use Cake\Datasource\RulesChecker;


/**
 * MediaSelectorsTable Test Case
 *
 * @see \Awyiss\Model\Table\MediaSelectorsTable
 */
class MediaSelectorsTableTest extends TestCase {
	/**
	 * @var \Awyiss\Model\Table\MediaSelectorsTable
	 */
	protected MediaSelectorsTable $mediaSelectorsTable;


	/**
	 * @inheritDoc
	 */
	protected function setUp(): void {
		parent::setUp();

		/** @noinspection PhpFieldAssignmentTypeMismatchInspection */
		$this->mediaSelectorsTable = FactoryLocator::get('Table')->get('MediaSelectors');
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\MediaSelectorsTable::ATTRIBUTABLE
	 */
	public function testAttributableConstant(): void {
		$this->assertFalse($this->mediaSelectorsTable::ATTRIBUTABLE);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\MediaSelectorsTable::TABLE
	 */
	public function testTableConstant(): void {
		$this->assertEquals('media_selectors', $this->mediaSelectorsTable::TABLE);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\MediaSelectorsTable::initializeAssociations()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testInitializeAssociations(): void {
		$this->assertCount(6, $this->mediaSelectorsTable->associations()->keys());

		// Test MediaElementSelectors association (HasMany)
		$this->assertTrue($this->mediaSelectorsTable->hasAssociation('MediaElementSelectors'));
		$mediaElementSelectorsAssociation = $this->mediaSelectorsTable->getAssociation('MediaElementSelectors');
		$this->assertInstanceOf(HasMany::class, $mediaElementSelectorsAssociation);
		$this->assertTrue($mediaElementSelectorsAssociation->getCascadeCallbacks());
		$this->assertTrue($mediaElementSelectorsAssociation->getDependent());
		$this->assertEquals('replace', $mediaElementSelectorsAssociation->getSaveStrategy());

		// Test user tracking associations
		$this->assertTrue($this->mediaSelectorsTable->hasAssociation('CreatedByUser'));
		$createdByUserAssociation = $this->mediaSelectorsTable->getAssociation('CreatedByUser');
		$this->assertInstanceOf(BelongsTo::class, $createdByUserAssociation);
		$this->assertFalse($createdByUserAssociation->getCascadeCallbacks());
		$this->assertFalse($createdByUserAssociation->getDependent());

		$this->assertTrue($this->mediaSelectorsTable->hasAssociation('ChangedByUser'));
		$changedByUserAssociation = $this->mediaSelectorsTable->getAssociation('ChangedByUser');
		$this->assertInstanceOf(BelongsTo::class, $changedByUserAssociation);
		$this->assertFalse($changedByUserAssociation->getCascadeCallbacks());
		$this->assertFalse($changedByUserAssociation->getDependent());

		$this->assertTrue($this->mediaSelectorsTable->hasAssociation('DeletedByUser'));
		$deletedByUserAssociation = $this->mediaSelectorsTable->getAssociation('DeletedByUser');
		$this->assertInstanceOf(BelongsTo::class, $deletedByUserAssociation);
		$this->assertFalse($deletedByUserAssociation->getCascadeCallbacks());
		$this->assertFalse($deletedByUserAssociation->getDependent());

		// Test translation associations
		$this->assertTrue($this->mediaSelectorsTable->hasAssociation('MediaSelectors_title_translation'));
		$titleTranslationAssociation = $this->mediaSelectorsTable->getAssociation('MediaSelectors_title_translation');
		$this->assertInstanceOf(HasOne::class, $titleTranslationAssociation);
		$this->assertFalse($titleTranslationAssociation->getCascadeCallbacks());
		$this->assertFalse($titleTranslationAssociation->getDependent());

		$this->assertTrue($this->mediaSelectorsTable->hasAssociation('I18n'));
		$i18nAssociation = $this->mediaSelectorsTable->getAssociation('I18n');
		$this->assertInstanceOf(HasMany::class, $i18nAssociation);
		$this->assertFalse($i18nAssociation->getCascadeCallbacks());
		$this->assertTrue($i18nAssociation->getDependent());
		$this->assertEquals('append', $i18nAssociation->getSaveStrategy());
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\MediaSelectorsTable::validationDefault()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testValidationDefault(): void {
		$validator = new Validator();
		$result = $this->mediaSelectorsTable->validationDefault($validator);

		$this->assertInstanceOf(Validator::class, $result);
		$this->assertSame('media_selectors', $result->getI18nDomain());

		// Test required fields
		$this->assertTrue($result->hasField('title'));
		$this->assertSame('create', $result->field('title')->isPresenceRequired());

		$this->assertTrue($result->hasField('identifier'));
		$this->assertSame('create', $result->field('identifier')->isPresenceRequired());

		// Test other fields
		$this->assertTrue($result->hasField('id'));
		$this->assertTrue($result->hasField('active'));
		$this->assertTrue($result->hasField('deleted'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\MediaSelectorsTable::validationDefault()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testEntityValidationSuccess(): void {
		$data = [
			'title' => 'Test Media Selector',
			'identifier' => 'test_media_selector',
			'active' => true,
			'deleted' => false,
		];

		$entity = $this->mediaSelectorsTable->newEntity($data);
		$errors = $entity->getErrors();

		$this->assertEmpty($errors, 'Valid data should not produce validation errors');
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\MediaSelectorsTable::validationDefault()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testEntityValidationMissingRequired(): void {
		$data = [
			'active' => true,
		];

		$entity = $this->mediaSelectorsTable->newEntity($data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('title', $errors);
		$this->assertArrayHasKey('identifier', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\MediaSelectorsTable::validationDefault()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testEntityValidationInvalidTypes(): void {
		$data = [
			'id' => 'not_an_integer',
			'title' => true,
			'identifier' => true,
			'active' => 'not_a_boolean',
			'deleted' => 'not_a_boolean',
		];

		$entity = $this->mediaSelectorsTable->newEntity($data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('id', $errors);
		$this->assertArrayHasKey('title', $errors);
		$this->assertArrayHasKey('identifier', $errors);
		$this->assertArrayHasKey('active', $errors);
		$this->assertArrayHasKey('deleted', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\MediaSelectorsTable::validationDefault()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testEntityValidationFieldLength(): void {
		$data = [
			'id' => 123456789123, // exceeds 11 char limit
			'title' => str_repeat('a', 51), // exceeds 50 char limit
			'identifier' => str_repeat('b', 51), // exceeds 50 char limit
		];

		$entity = $this->mediaSelectorsTable->newEntity($data, ['guard' => false]);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('id', $errors);
		$this->assertArrayHasKey('title', $errors);
		$this->assertArrayHasKey('identifier', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\MediaSelectorsTable::validationDefault()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testEntityValidationNotBlank(): void {
		$data = [
			'title' => '   ', // only whitespace
			'identifier' => '   ', // only whitespace
		];

		$entity = $this->mediaSelectorsTable->newEntity($data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('title', $errors);
		$this->assertArrayHasKey('identifier', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\MediaSelectorsTable::buildRules()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testBuildRulesIdentifierUniqueValid(): void {
		$entity = $this->mediaSelectorsTable->newEntity([
			'title' => 'Unique Selector',
			'identifier' => 'unique_selector',
			'active' => true,
		]);

		$result = $this->mediaSelectorsTable->checkRules($entity);
		$this->assertTrue($result);

		// Check that the identifier is unique
		$this->assertEmpty($entity->getErrors());
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\MediaSelectorsTable::buildRules()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testBuildRulesIdentifierUniqueInvalid(): void {
		$entity = $this->mediaSelectorsTable->get(1);

		$entity2 = unserialize(serialize($entity));
		$entity2->setNew(true); // Mark as new to simulate a new entity

		$result = $this->mediaSelectorsTable->checkRules($entity2);

		$this->assertFalse($result, 'Second entity with duplicate identifier should fail');

		$errors = $entity2->getErrors();
		$this->assertArrayHasKey('identifier', $errors);
		$this->assertArrayHasKey('identifierUnique', $errors['identifier']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\MediaSelectorsTable::buildRules()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testBuildRulesNotDefaultSelectorValid(): void {
		// Test that default selectors (id < 10) cannot be created or updated
		$entity = $this->mediaSelectorsTable->newEntity([
			'title' => 'Default Selector',
			'identifier' => 'default_selector',
			'active' => true,
			'id' => 10, // Default selector ID
		]);

		$entity->set('id', 10);
		$entity->setNew(false);

		$result = $this->mediaSelectorsTable->checkRules($entity);

		$this->assertTrue($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\MediaSelectorsTable::buildRules()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testBuildRulesNotDefaultSelectorDeletionInvalid(): void {
		// Test that default selectors (id < 10) cannot be deleted
		$entity = $this->mediaSelectorsTable->get(1);

		$result = $this->mediaSelectorsTable->checkRules($entity, RulesChecker::DELETE);

		$this->assertFalse($result);

		$errors = $entity->getErrors();
		$this->assertArrayHasKey('_general', $errors);
		$this->assertArrayHasKey('notDefaultSelectorDeletion', $errors['_general']);
		$this->assertSame('media_selectors::error_not_default_selector_deletion', $errors['_general']['notDefaultSelectorDeletion']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\MediaSelectorsTable::newDefaultEntity()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testNewDefaultEntity(): void {
		/** @var \Awyiss\Model\Entity\MediaSelector $entity */
		$entity = $this->mediaSelectorsTable->newDefaultEntity();

		$this->assertInstanceOf(MediaSelector::class, $entity);
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
	 * @see \Awyiss\Model\Table\MediaSelectorsTable::newDefaultEntity()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testNewDefaultEntityWithData(): void {
		$additionalData = [
			'title' => 'Custom Media Selector',
			'identifier' => 'custom_media_selector',
			'active' => false,
		];

		/** @var \Awyiss\Model\Entity\MediaSelector $entity */
		$entity = $this->mediaSelectorsTable->newDefaultEntity($additionalData);

		$this->assertInstanceOf(MediaSelector::class, $entity);
		$this->assertTrue($entity->isNew());

		// Check custom values
		$this->assertSame('Custom Media Selector', $entity->title);
		$this->assertSame('custom_media_selector', $entity->identifier);
		$this->assertFalse($entity->active);
		$this->assertFalse($entity->deleted); // Should remain default
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\MediaSelectorsTable::$translate
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testTranslateBehavior(): void {
		$this->assertTrue($this->mediaSelectorsTable->hasBehavior('Translate'));

		$config = $this->mediaSelectorsTable->getBehavior('Translate')->getConfig();

		// Auto-realm
		$this->assertNull($config['realm'] ?? null);

		$this->assertIsArray($config['fields']);
		$this->assertSame(['title'], $config['fields']);
	}
}
