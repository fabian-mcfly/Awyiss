<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Model\Table;


use Awyiss\Model\Entity\Usergroup;
use Awyiss\Model\Table\UsergroupsTable;
use Awyiss\ORM\Association\BelongsTo;
use Awyiss\ORM\Association\BelongsToMany;
use Awyiss\ORM\Association\HasMany;
use Awyiss\ORM\Association\HasOne;
use Awyiss\Test\TestSuite\TestCase;
use Awyiss\Validation\Validator;
use Cake\Datasource\FactoryLocator;


/**
 * UsergroupsTable Test Case
 *
 * @see \Awyiss\Model\Table\UsergroupsTable
 */
class UsergroupsTableTest extends TestCase {
	/**
	 * @var \Awyiss\Model\Table\UsergroupsTable
	 */
	protected UsergroupsTable $usergroupsTable;


	/**
	 * @inheritDoc
	 */
	protected function setUp(): void {
		parent::setUp();

		/** @noinspection PhpFieldAssignmentTypeMismatchInspection */
		$this->usergroupsTable = FactoryLocator::get('Table')->get('Usergroups');
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\UsergroupPermissionsTable::ATTRIBUTABLE
	 */
	public function testAttributableConstant(): void {
		/** @noinspection PhpUnitAssertTrueWithIncompatibleTypeArgumentInspection */
		$this->assertTrue($this->usergroupsTable::ATTRIBUTABLE);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\UsergroupsTable::TABLE
	 */
	public function testTableConstant(): void {
		$this->assertEquals('usergroups', $this->usergroupsTable::TABLE);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\UsergroupsTable::initializeAssociations()
	 */
	public function testInitializeAssociations(): void {
		$this->assertCount(8, $this->usergroupsTable->associations()->keys());

		$this->assertTrue($this->usergroupsTable->hasAssociation('UsergroupPermissions'));
		$usergroupPermissionsAssociation = $this->usergroupsTable->getAssociation('UsergroupPermissions');
		$this->assertInstanceOf(HasMany::class, $usergroupPermissionsAssociation);
		$this->assertTrue($usergroupPermissionsAssociation->getCascadeCallbacks());
		$this->assertTrue($usergroupPermissionsAssociation->getDependent());
		$this->assertSame('replace', $usergroupPermissionsAssociation->getSaveStrategy());

		$this->assertTrue($this->usergroupsTable->hasAssociation('Users'));
		$usersAssociation = $this->usergroupsTable->getAssociation('Users');
		$this->assertInstanceOf(BelongsToMany::class, $usersAssociation);
		$this->assertTrue($usersAssociation->getCascadeCallbacks());
		$this->assertTrue($usersAssociation->getDependent());

		// 'MediaAssignments' must also exist
		$this->assertTrue($this->usergroupsTable->hasAssociation('MediaAssignments'));
		$mediaAssignmentsAssociation = $this->usergroupsTable->getAssociation('MediaAssignments');
		$this->assertInstanceOf(HasMany::class, $mediaAssignmentsAssociation);
		$this->assertTrue($mediaAssignmentsAssociation->getCascadeCallbacks());
		$this->assertTrue($mediaAssignmentsAssociation->getDependent());

		// 'CreatedByUser' must also exist
		$this->assertTrue($this->usergroupsTable->hasAssociation('CreatedByUser'));
		$createdByUserAssociation = $this->usergroupsTable->getAssociation('CreatedByUser');
		$this->assertInstanceOf(BelongsTo::class, $createdByUserAssociation);
		$this->assertFalse($createdByUserAssociation->getCascadeCallbacks());
		$this->assertFalse($createdByUserAssociation->getDependent());

		// 'ChangedByUser' must also exist
		$this->assertTrue($this->usergroupsTable->hasAssociation('ChangedByUser'));
		$changedByUserAssociation = $this->usergroupsTable->getAssociation('ChangedByUser');
		$this->assertInstanceOf(BelongsTo::class, $changedByUserAssociation);
		$this->assertFalse($changedByUserAssociation->getCascadeCallbacks());
		$this->assertFalse($changedByUserAssociation->getDependent());

		// 'DeletedByUser' must also exist
		$this->assertTrue($this->usergroupsTable->hasAssociation('DeletedByUser'));
		$deletedByUserAssociation = $this->usergroupsTable->getAssociation('DeletedByUser');
		$this->assertInstanceOf(BelongsTo::class, $deletedByUserAssociation);
		$this->assertFalse($deletedByUserAssociation->getCascadeCallbacks());
		$this->assertFalse($deletedByUserAssociation->getDependent());

		// 'Usergroups_title_translation' must also exist
		$this->assertTrue($this->usergroupsTable->hasAssociation('Usergroups_title_translation'));
		$titleTranslationAssociation = $this->usergroupsTable->getAssociation('Usergroups_title_translation');
		$this->assertInstanceOf(HasOne::class, $titleTranslationAssociation);
		$this->assertFalse($titleTranslationAssociation->getCascadeCallbacks());
		$this->assertFalse($titleTranslationAssociation->getDependent());

		// 'I18n' must also exist
		$this->assertTrue($this->usergroupsTable->hasAssociation('I18n'));
		$i18nAssociation = $this->usergroupsTable->getAssociation('I18n');
		$this->assertInstanceOf(HasMany::class, $i18nAssociation);
		$this->assertFalse($i18nAssociation->getCascadeCallbacks());
		$this->assertTrue($i18nAssociation->getDependent());
		$this->assertEquals('append', $i18nAssociation->getSaveStrategy());
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\UsergroupsTable::validationDefault()
	 */
	public function testValidationDefault(): void {
		$validator = new Validator();
		$result = $this->usergroupsTable->validationDefault($validator);

		$this->assertInstanceOf(Validator::class, $result);
		$this->assertSame('Usergroups', $result->getI18nDomain());

		// Test required fields
		$this->assertTrue($result->hasField('title'));
		$this->assertSame('create', $result->field('title')->isPresenceRequired());

		// Test other fields
		$this->assertTrue($result->hasField('id'));
		$this->assertTrue($result->hasField('active'));
		$this->assertTrue($result->hasField('deleted'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\UsergroupsTable::validationDefault()
	 */
	public function testEntityValidationSuccess(): void {
		$data = [
			'title' => 'Test Usergroup',
			'active' => true,
			'deleted' => false,
		];

		$entity = $this->usergroupsTable->newDefaultEntity($data);
		$errors = $entity->getErrors();

		$this->assertEmpty($errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\UsergroupsTable::validationDefault()
	 */
	public function testEntityValidationMissingRequired(): void {
		$data = [
			'active' => true,
		];

		$entity = $this->usergroupsTable->newDefaultEntity();
		$this->usergroupsTable->patchEntity($entity, $data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('title', $errors);
		$this->assertArrayHasKey('_required', $errors['title']);
		$this->assertSame('usergroups::error_required', $errors['title']['_required']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\UsergroupsTable::validationDefault()
	 */
	public function testEntityValidationInvalidTypes(): void {
		$data = [
			'id' => 'not_an_integer',
			'title' => true,
			'active' => 'not_a_boolean',
			'deleted' => 'not_a_boolean',
		];

		$entity = $this->usergroupsTable->newDefaultEntity();
		$this->usergroupsTable->patchEntity($entity, $data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('id', $errors);
		$this->assertArrayHasKey('title', $errors);
		$this->assertArrayHasKey('active', $errors);
		$this->assertArrayHasKey('deleted', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\UsergroupsTable::validationDefault()
	 */
	public function testEntityValidationFieldLength(): void {
		$data = [
			'id' => 123456789123, // exceeds 11 char limit
			'title' => str_repeat('a', 101), // exceeds 100 char limit
		];

		$entity = $this->usergroupsTable->newDefaultEntity();
		$this->usergroupsTable->patchEntity($entity, $data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('id', $errors);
		$this->assertArrayHasKey('title', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\UsergroupsTable::validationDefault()
	 */
	public function testEntityValidationEmptyTitle(): void {
		$data = [
			'title' => '',
		];

		$entity = $this->usergroupsTable->newDefaultEntity();
		$this->usergroupsTable->patchEntity($entity, $data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('title', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\UsergroupsTable::validationDefault()
	 */
	public function _estEntityValidationBlankTitle(): void {
		$data = [
			'title' => '   ', // Only whitespace
		];

		$entity = $this->usergroupsTable->newDefaultEntity();
		$this->usergroupsTable->patchEntity($entity, $data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('title', $errors);
		$this->assertArrayHasKey('notBlank', $errors['title']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\UsergroupsTable::buildRules()
	 */
	public function testBuildRulesUniqueTitle(): void {
		$data = [
			'title' => 'Unique Usergroup Title',
			'active' => true,
			'deleted' => false,
		];

		$entity = $this->usergroupsTable->newDefaultEntity();
		$this->usergroupsTable->patchEntity($entity, $data);

		$result = $this->usergroupsTable->checkRules($entity);
		$this->assertTrue($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\UsergroupsTable::buildRules()
	 */
	public function testBuildRulesExistingTitle(): void {
		// Try to create another usergroup with the same title as an existing one
		$data = [
			'title' => 'all access', // This title already exists in seed data
			'active' => true,
			'deleted' => false,
		];

		$entity = $this->usergroupsTable->newDefaultEntity();
		$this->usergroupsTable->patchEntity($entity, $data);

		$result = $this->usergroupsTable->checkRules($entity);

		$this->assertFalse($result);

		$errors = $entity->getErrors();
		$this->assertArrayHasKey('title', $errors);
		$this->assertArrayHasKey('titleUnique', $errors['title']);
		$this->assertSame('usergroups::error_title_unique', $errors['title']['titleUnique']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\UsergroupsTable::buildRules()
	 */
	public function testBuildRulesUpdateWithSameTitle(): void {
		// Get an existing usergroup
		$existingUsergroup = $this->usergroupsTable->find()->where(['title' => 'all access'])->first();
		$this->assertNotNull($existingUsergroup);

		// Update with the same title should be allowed
		$this->usergroupsTable->patchEntity($existingUsergroup, ['title' => 'all access']);

		$result = $this->usergroupsTable->checkRules($existingUsergroup);
		$this->assertTrue($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\UsergroupsTable::newDefaultEntity()
	 */
	public function testNewDefaultEntity(): void {
		/** @var \Awyiss\Model\Entity\Usergroup $entity */
		$entity = $this->usergroupsTable->newDefaultEntity();

		$this->assertInstanceOf(Usergroup::class, $entity);
		$this->assertTrue($entity->isNew());

		// Test default values
		$this->assertNull($entity->title);
		$this->assertTrue($entity->active);
		$this->assertFalse($entity->deleted);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\UsergroupsTable::newDefaultEntity()
	 */
	public function testNewDefaultEntityWithData(): void {
		$additionalData = [
			'title' => 'Custom Usergroup',
			'active' => false,
			'deleted' => true,
		];

		$entity = $this->usergroupsTable->newDefaultEntity($additionalData);

		$this->assertInstanceOf(Usergroup::class, $entity);
		$this->assertTrue($entity->isNew());

		$this->assertSame('Custom Usergroup', $entity->title);
		$this->assertFalse($entity->active);
		$this->assertTrue($entity->deleted);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\UsergroupsTable::$translate
	 */
	public function testTranslateBehavior(): void {
		$this->assertTrue($this->usergroupsTable->hasBehavior('Translate'));

		$config = $this->usergroupsTable->getBehavior('Translate')->getConfig();

		$this->assertIsArray($config['fields']);
		$this->assertSame(['title'], $config['fields']);
	}
}
