<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Model\Table;


use Awyiss\Model\Entity\Datatable;
use Awyiss\Model\Table\DatatablesTable;
use Awyiss\ORM\Association\BelongsTo;
use Awyiss\ORM\Association\HasMany;
use Awyiss\ORM\Association\HasOne;
use Awyiss\Test\TestSuite\TestCase;
use Awyiss\Validation\Validator;
use Cake\Datasource\FactoryLocator;
use Cake\Datasource\RulesChecker;


/**
 * DatatablesTable Test Case
 *
 * @see \Awyiss\Model\Table\DatatablesTable
 */
class DatatablesTableTest extends TestCase {
	/**
	 * @var \Awyiss\Model\Table\DatatablesTable
	 */
	protected DatatablesTable $datatablesTable;


	/**
	 * @inheritDoc
	 */
	protected function setUp(): void {
		parent::setUp();

		/** @noinspection PhpFieldAssignmentTypeMismatchInspection */
		$this->datatablesTable = FactoryLocator::get('Table')->get('Datatables');
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\DatatablesTable::ATTRIBUTABLE
	 */
	public function testAttributableConstant(): void {
		$this->assertFalse($this->datatablesTable::ATTRIBUTABLE);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\DatatablesTable::TABLE
	 */
	public function testTableConstant(): void {
		$this->assertEquals('datatables', $this->datatablesTable::TABLE);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\DatatablesTable::initializeAssociations()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testInitializeAssociations(): void {
		$this->assertCount(7, $this->datatablesTable->associations()->keys());

		// 'MediaAssignments' must also exist
		$this->assertTrue($this->datatablesTable->hasAssociation('MediaAssignments'));
		$mediaAssignmentsAssociation = $this->datatablesTable->getAssociation('MediaAssignments');
		$this->assertInstanceOf(HasMany::class, $mediaAssignmentsAssociation);
		$this->assertTrue($mediaAssignmentsAssociation->getCascadeCallbacks());
		$this->assertTrue($mediaAssignmentsAssociation->getDependent());

		// 'MediaElementAssignments' must also exist
		$this->assertTrue($this->datatablesTable->hasAssociation('MediaElementAssignments'));
		$mediaElementAssignmentsAssociation = $this->datatablesTable->getAssociation('MediaElementAssignments');
		$this->assertInstanceOf(HasMany::class, $mediaElementAssignmentsAssociation);
		$this->assertTrue($mediaElementAssignmentsAssociation->getCascadeCallbacks());
		$this->assertTrue($mediaElementAssignmentsAssociation->getDependent());

		// Test user tracking associations
		$this->assertTrue($this->datatablesTable->hasAssociation('CreatedByUser'));
		$createdByUserAssociation = $this->datatablesTable->getAssociation('CreatedByUser');
		$this->assertInstanceOf(BelongsTo::class, $createdByUserAssociation);
		$this->assertFalse($createdByUserAssociation->getCascadeCallbacks());
		$this->assertFalse($createdByUserAssociation->getDependent());

		$this->assertTrue($this->datatablesTable->hasAssociation('ChangedByUser'));
		$changedByUserAssociation = $this->datatablesTable->getAssociation('ChangedByUser');
		$this->assertInstanceOf(BelongsTo::class, $changedByUserAssociation);
		$this->assertFalse($changedByUserAssociation->getCascadeCallbacks());
		$this->assertFalse($changedByUserAssociation->getDependent());

		$this->assertTrue($this->datatablesTable->hasAssociation('DeletedByUser'));
		$deletedByUserAssociation = $this->datatablesTable->getAssociation('DeletedByUser');
		$this->assertInstanceOf(BelongsTo::class, $deletedByUserAssociation);
		$this->assertFalse($deletedByUserAssociation->getCascadeCallbacks());
		$this->assertFalse($deletedByUserAssociation->getDependent());

		// Test translation associations
		$this->assertTrue($this->datatablesTable->hasAssociation('Datatables_title_translation'));
		$titleTranslationAssociation = $this->datatablesTable->getAssociation('Datatables_title_translation');
		$this->assertInstanceOf(HasOne::class, $titleTranslationAssociation);
		$this->assertFalse($titleTranslationAssociation->getCascadeCallbacks());
		$this->assertFalse($titleTranslationAssociation->getDependent());

		// Test I18n association
		$this->assertTrue($this->datatablesTable->hasAssociation('I18n'));
		$i18nAssociation = $this->datatablesTable->getAssociation('I18n');
		$this->assertInstanceOf(HasMany::class, $i18nAssociation);
		$this->assertFalse($i18nAssociation->getCascadeCallbacks());
		$this->assertTrue($i18nAssociation->getDependent());
		$this->assertEquals('append', $i18nAssociation->getSaveStrategy());
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\DatatablesTable::validationDefault()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testValidationDefault(): void {
		$validator = new Validator();
		$result = $this->datatablesTable->validationDefault($validator);

		$this->assertInstanceOf(Validator::class, $result);
		$this->assertSame('datatables', $result->getI18nDomain());

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
	 * @see \Awyiss\Model\Table\DatatablesTable::validationDefault()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testEntityValidationSuccess(): void {
		$data = [
			'title' => 'Test Datatable',
			'identifier' => 'test_datatable',
			'active' => true,
			'deleted' => false,
		];

		$entity = $this->datatablesTable->newEntity($data);
		$errors = $entity->getErrors();

		$this->assertEmpty($errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\DatatablesTable::validationDefault()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testEntityValidationMissingRequired(): void {
		$data = [
			'active' => true,
		];

		$entity = $this->datatablesTable->newEntity($data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('title', $errors);
		$this->assertArrayHasKey('identifier', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\DatatablesTable::validationDefault()
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

		$entity = $this->datatablesTable->newEntity($data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('id', $errors);
		$this->assertArrayHasKey('title', $errors);
		$this->assertArrayHasKey('identifier', $errors);
		$this->assertArrayHasKey('active', $errors);
		$this->assertArrayHasKey('deleted', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\DatatablesTable::validationDefault()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testEntityValidationFieldLength(): void {
		$data = [
			'id' => 123456789123, // exceeds 11 char limit
			'title' => str_repeat('a', 101), // exceeds 100 char limit
			'identifier' => str_repeat('b', 101), // exceeds 100 char limit
		];

		$entity = $this->datatablesTable->newEntity($data, ['guard' => false]);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('id', $errors);
		$this->assertArrayHasKey('title', $errors);
		$this->assertArrayHasKey('identifier', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\DatatablesTable::validationDefault()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testEntityValidationNotBlank(): void {
		$data = [
			'title' => '   ', // only whitespace
			'identifier' => '   ', // only whitespace
		];

		$entity = $this->datatablesTable->newEntity($data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('title', $errors);
		$this->assertArrayHasKey('identifier', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\DatatablesTable::validationDefault()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testEntityValidationNotEmptyString(): void {
		$data = [
			'title' => '',
			'identifier' => '',
		];

		$entity = $this->datatablesTable->newEntity($data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('title', $errors);
		$this->assertArrayHasKey('identifier', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\DatatablesTable::buildRules()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testBuildRulesIdentifierUnchangedOnUpdate(): void {
		$entity = $this->datatablesTable->get(1);
		$entity->set('identifier', 'changed_identifier');

		$result = $this->datatablesTable->checkRules($entity);
		$this->assertFalse($result);

		$errors = $entity->getErrors();
		$this->assertArrayHasKey('identifier', $errors);
		$this->assertArrayHasKey('validIdentifier', $errors['identifier']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\DatatablesTable::buildRules()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testBuildRulesIdentifierUnchangedOnCopy(): void {
		$entity = $this->datatablesTable->get(1);
		$entity->set('identifier', 'changed_identifier');

		$result = $this->datatablesTable->checkRules($entity, RulesChecker::UPDATE, ['isCopy' => true]);
		$this->assertTrue($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\DatatablesTable::buildRules()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testBuildRulesIdentifierStartsWithAttributes(): void {
		$data = [
			'title' => 'Test Datatable',
			'identifier' => 'attributes_test',
		];

		$entity = $this->datatablesTable->newEntity($data);
		$result = $this->datatablesTable->checkRules($entity);

		$this->assertFalse($result);

		$errors = $entity->getErrors();
		$this->assertArrayHasKey('identifier', $errors);
		$this->assertArrayHasKey('validIdentifier', $errors['identifier']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\DatatablesTable::buildRules()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testBuildRulesIdentifierInBlocklist(): void {
		$blocklistedIdentifiers = [
			'cell',
			'content_area',
			'email',
			'element',
			'generic_page',
			'layout',
		];

		foreach ($blocklistedIdentifiers as $identifier) {
			$data = [
				'title' => 'Test Datatable',
				'identifier' => $identifier,
			];

			$entity = $this->datatablesTable->newEntity($data);
			$result = $this->datatablesTable->checkRules($entity);

			$this->assertFalse($result);

			$errors = $entity->getErrors();
			$this->assertArrayHasKey('identifier', $errors);
			$this->assertArrayHasKey('validIdentifier', $errors['identifier']);
		}
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\DatatablesTable::buildRules()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testBuildRulesIdentifierBackendController(): void {
		// Test with an identifier that would match a backend controller name
		$data = [
			'title' => 'Test Datatable',
			'identifier' => 'user', // This should match UsersController
		];

		$entity = $this->datatablesTable->newEntity($data);
		$result = $this->datatablesTable->checkRules($entity);

		$this->assertFalse($result);

		$errors = $entity->getErrors();
		$this->assertArrayHasKey('identifier', $errors);
		$this->assertArrayHasKey('validIdentifier', $errors['identifier']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\DatatablesTable::buildRules()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testBuildRulesIdentifierPageRole(): void {
		// Test with an identifier that matches a page role
		$data = [
			'title' => 'Test Datatable',
			'identifier' => 'product', // This should match a page role
		];

		$entity = $this->datatablesTable->newEntity($data);
		$result = $this->datatablesTable->checkRules($entity);

		$this->assertFalse($result);

		$errors = $entity->getErrors();
		$this->assertArrayHasKey('identifier', $errors);
		$this->assertArrayHasKey('validIdentifier', $errors['identifier']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\DatatablesTable::buildRules()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testBuildRulesIdentifierUniqueValid(): void {
		$data = [
			'title' => 'Test Datatable 1',
			'identifier' => 'unique_test_1',
		];

		$entity = $this->datatablesTable->newEntity($data);
		$result = $this->datatablesTable->checkRules($entity);
		$this->assertTrue($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\DatatablesTable::buildRules()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testBuildRulesIdentifierUniqueInvalid(): void {
		$data = [
			'title' => 'Test Datatable',
			'identifier' => 'car', // Existing identifier
		];

		$entity = $this->datatablesTable->newEntity($data);
		$result = $this->datatablesTable->checkRules($entity);
		$this->assertFalse($result);

		$errors = $entity->getErrors();
		$this->assertArrayHasKey('identifier', $errors);
		$this->assertArrayHasKey('validIdentifier', $errors['identifier']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\DatatablesTable::buildRules()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testBuildRulesIdentifierNotDirty(): void {
		// Test that validation doesn't run when identifier is not dirty
		$data = [
			'title' => 'Test Datatable',
			'identifier' => 'car', // This would normally fail
		];

		$entity = $this->datatablesTable->newEntity($data);
		$entity->setDirty('identifier', false); // Mark as not dirty
		$result = $this->datatablesTable->checkRules($entity);

		$this->assertTrue($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\DatatablesTable::newDefaultEntity()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testNewDefaultEntity(): void {
		$entity = $this->datatablesTable->newDefaultEntity();

		$this->assertInstanceOf(Datatable::class, $entity);
		$this->assertTrue($entity->isNew());

		// Check default values
		$this->assertNull($entity->title);
		$this->assertNull($entity->identifier);
		$this->assertTrue($entity->active);
		$this->assertFalse($entity->deleted);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\DatatablesTable::newDefaultEntity()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testNewDefaultEntityWithData(): void {
		$additionalData = [
			'title' => 'Custom Datatable',
			'identifier' => 'custom_datatable',
			'active' => false,
		];

		$entity = $this->datatablesTable->newDefaultEntity($additionalData);

		$this->assertInstanceOf(Datatable::class, $entity);
		$this->assertTrue($entity->isNew());

		// Check custom values
		$this->assertSame('Custom Datatable', $entity->title);
		$this->assertSame('custom_datatable', $entity->identifier);
		$this->assertFalse($entity->active);

		// Check that defaults are preserved
		$this->assertFalse($entity->deleted);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\DatatablesTable::$translate
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testTranslateBehavior(): void {
		$this->assertTrue($this->datatablesTable->hasBehavior('Translate'));

		$config = $this->datatablesTable->getBehavior('Translate')->getConfig();

		$this->assertNull($config['realm'] ?? null);

		$this->assertIsArray($config['fields']);
		$this->assertSame(['title'], $config['fields']);
	}
}
