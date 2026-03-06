<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Model\Table;


use Awyiss\Awyiss;
use Awyiss\Model\Entity\MediaFolder;
use Awyiss\Model\Table\MediaFoldersTable;
use Awyiss\ORM\Association\BelongsTo;
use Awyiss\ORM\Association\HasMany;
use Awyiss\ORM\Association\HasOne;
use Awyiss\Test\TestSuite\TestCase;
use Awyiss\Validation\Validator;
use Cake\Datasource\FactoryLocator;
use Cake\Datasource\RulesChecker;
use Cake\ORM\Query\SelectQuery;


/**
 * MediaFoldersTable Test Case
 *
 * @see \Awyiss\Model\Table\MediaFoldersTable
 */
class MediaFoldersTableTest extends TestCase {
	/**
	 * @var \Awyiss\Model\Table\MediaFoldersTable
	 */
	protected MediaFoldersTable $mediaFoldersTable;


	/**
	 * @inheritDoc
	 */
	protected function setUp(): void {
		parent::setUp();

		/** @noinspection PhpFieldAssignmentTypeMismatchInspection */
		$this->mediaFoldersTable = FactoryLocator::get('Table')->get('MediaFolders');
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\MediaFoldersTable::ATTRIBUTABLE
	 */
	public function testAttributableConstant(): void {
		/** @noinspection PhpUnitAssertTrueWithIncompatibleTypeArgumentInspection */
		$this->assertTrue($this->mediaFoldersTable::ATTRIBUTABLE);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\MediaFoldersTable::TABLE
	 */
	public function testTableConstant(): void {
		$this->assertEquals('media_folders', $this->mediaFoldersTable::TABLE);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\MediaFoldersTable::initializeAssociations()
	 */
	public function testInitializeAssociations(): void {
		$this->assertCount(10, $this->mediaFoldersTable->associations()->keys());

		// Test Languages association (BelongsTo)
		$this->assertTrue($this->mediaFoldersTable->hasAssociation('Languages'));
		$languagesAssociation = $this->mediaFoldersTable->getAssociation('Languages');
		$this->assertInstanceOf(BelongsTo::class, $languagesAssociation);
		$this->assertFalse($languagesAssociation->getCascadeCallbacks());
		$this->assertFalse($languagesAssociation->getDependent());
		$this->assertSame('shortcode', $languagesAssociation->getBindingKey());
		$this->assertSame('languageShortcode', $languagesAssociation->getForeignKey());
		$this->assertSame(['realm' => Awyiss::REALM_FRONTEND], $languagesAssociation->getConditions());

		// Test Media association (HasMany)
		$this->assertTrue($this->mediaFoldersTable->hasAssociation('Media'));
		$mediaAssociation = $this->mediaFoldersTable->getAssociation('Media');
		$this->assertInstanceOf(HasMany::class, $mediaAssociation);
		$this->assertTrue($mediaAssociation->getCascadeCallbacks());
		$this->assertTrue($mediaAssociation->getDependent());

		// Test MediaAssignments association (HasMany)
		$this->assertTrue($this->mediaFoldersTable->hasAssociation('MediaAssignments'));
		$mediaAssignmentsAssociation = $this->mediaFoldersTable->getAssociation('MediaAssignments');
		$this->assertInstanceOf(HasMany::class, $mediaAssignmentsAssociation);
		$this->assertTrue($mediaAssignmentsAssociation->getCascadeCallbacks());
		$this->assertTrue($mediaAssignmentsAssociation->getDependent());
		$this->assertEquals('replace', $mediaAssignmentsAssociation->getSaveStrategy());

		// Test nest behavior associations
		$this->assertTrue($this->mediaFoldersTable->hasAssociation('ParentMediaFolders'));
		$parentMediaFoldersAssociation = $this->mediaFoldersTable->getAssociation('ParentMediaFolders');
		$this->assertInstanceOf(BelongsTo::class, $parentMediaFoldersAssociation);
		$this->assertFalse($parentMediaFoldersAssociation->getCascadeCallbacks());
		$this->assertFalse($parentMediaFoldersAssociation->getDependent());

		$this->assertTrue($this->mediaFoldersTable->hasAssociation('ChildMediaFolders'));
		$childMediaFoldersAssociation = $this->mediaFoldersTable->getAssociation('ChildMediaFolders');
		$this->assertInstanceOf(HasMany::class, $childMediaFoldersAssociation);
		$this->assertTrue($childMediaFoldersAssociation->getCascadeCallbacks());
		$this->assertTrue($childMediaFoldersAssociation->getDependent());

		// Test user tracking associations
		$this->assertTrue($this->mediaFoldersTable->hasAssociation('CreatedByUser'));
		$createdByUserAssociation = $this->mediaFoldersTable->getAssociation('CreatedByUser');
		$this->assertInstanceOf(BelongsTo::class, $createdByUserAssociation);
		$this->assertFalse($createdByUserAssociation->getCascadeCallbacks());
		$this->assertFalse($createdByUserAssociation->getDependent());

		$this->assertTrue($this->mediaFoldersTable->hasAssociation('ChangedByUser'));
		$changedByUserAssociation = $this->mediaFoldersTable->getAssociation('ChangedByUser');
		$this->assertInstanceOf(BelongsTo::class, $changedByUserAssociation);
		$this->assertFalse($changedByUserAssociation->getCascadeCallbacks());
		$this->assertFalse($changedByUserAssociation->getDependent());

		$this->assertTrue($this->mediaFoldersTable->hasAssociation('DeletedByUser'));
		$deletedByUserAssociation = $this->mediaFoldersTable->getAssociation('DeletedByUser');
		$this->assertInstanceOf(BelongsTo::class, $deletedByUserAssociation);
		$this->assertFalse($deletedByUserAssociation->getCascadeCallbacks());
		$this->assertFalse($deletedByUserAssociation->getDependent());

		// Test translation associations
		$this->assertTrue($this->mediaFoldersTable->hasAssociation('MediaFolders_title_translation'));
		$titleTranslationAssociation = $this->mediaFoldersTable->getAssociation('MediaFolders_title_translation');
		$this->assertInstanceOf(HasOne::class, $titleTranslationAssociation);
		$this->assertFalse($titleTranslationAssociation->getCascadeCallbacks());
		$this->assertFalse($titleTranslationAssociation->getDependent());

		$this->assertTrue($this->mediaFoldersTable->hasAssociation('I18n'));
		$i18nAssociation = $this->mediaFoldersTable->getAssociation('I18n');
		$this->assertInstanceOf(HasMany::class, $i18nAssociation);
		$this->assertFalse($i18nAssociation->getCascadeCallbacks());
		$this->assertTrue($i18nAssociation->getDependent());
		$this->assertEquals('append', $i18nAssociation->getSaveStrategy());
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\MediaFoldersTable::validationDefault()
	 */
	public function testValidationDefault(): void {
		$validator = new Validator();
		$result = $this->mediaFoldersTable->validationDefault($validator);

		$this->assertInstanceOf(Validator::class, $result);
		$this->assertSame('MediaFolders', $result->getI18nDomain());

		// Test required fields
		$this->assertTrue($result->hasField('languageShortcode'));
		$this->assertSame('create', $result->field('languageShortcode')->isPresenceRequired());

		$this->assertTrue($result->hasField('path'));
		$this->assertSame('create', $result->field('path')->isPresenceRequired());

		$this->assertTrue($result->hasField('title'));
		$this->assertSame('create', $result->field('title')->isPresenceRequired());

		// Test other fields
		$this->assertTrue($result->hasField('id'));
		$this->assertTrue($result->hasField('parentId'));
		$this->assertTrue($result->hasField('systemOrder'));
		$this->assertTrue($result->hasField('hidden'));
		$this->assertTrue($result->hasField('active'));
		$this->assertTrue($result->hasField('parentsActive'));
		$this->assertTrue($result->hasField('deleted'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\MediaFoldersTable::validationDefault()
	 */
	public function testEntityValidationSuccess(): void {
		$data = [
			'languageShortcode' => 'de',
			'path' => '../tmp/media/testfolder',
			'title' => 'Test Folder',
			'parentId' => 2,
			'systemOrder' => 1,
			'hidden' => false,
			'active' => true,
			'parentsActive' => true,
			'deleted' => false,
		];

		$entity = $this->mediaFoldersTable->newDefaultEntity();
		$this->mediaFoldersTable->patchEntity($entity, $data);
		$errors = $entity->getErrors();

		$this->assertEmpty($errors, 'Valid data should not produce validation errors');
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\MediaFoldersTable::validationDefault()
	 */
	public function testEntityValidationMissingRequired(): void {
		$data = [
			'parentId' => 2,
		];

		$entity = $this->mediaFoldersTable->newDefaultEntity();
		$this->mediaFoldersTable->patchEntity($entity, $data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('languageShortcode', $errors);
		$this->assertArrayHasKey('path', $errors);
		$this->assertArrayHasKey('title', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\MediaFoldersTable::validationDefault()
	 */
	public function testEntityValidationInvalidTypes(): void {
		$data = [
			'id' => 'not_an_integer',
			'parentId' => 'not_an_integer',
			'languageShortcode' => true,
			'path' => true,
			'title' => true,
			'systemOrder' => 'not_an_integer',
			'hidden' => 'not_a_boolean',
			'active' => 'not_a_boolean',
			'parentsActive' => 'not_a_boolean',
			'deleted' => 'not_a_boolean',
		];

		$entity = $this->mediaFoldersTable->newDefaultEntity();
		$this->mediaFoldersTable->patchEntity($entity, $data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('id', $errors);
		$this->assertArrayHasKey('parentId', $errors);
		$this->assertArrayHasKey('languageShortcode', $errors);
		$this->assertArrayHasKey('path', $errors);
		$this->assertArrayHasKey('title', $errors);
		$this->assertArrayHasKey('systemOrder', $errors);
		$this->assertArrayHasKey('hidden', $errors);
		$this->assertArrayHasKey('active', $errors);
		$this->assertArrayHasKey('parentsActive', $errors);
		$this->assertArrayHasKey('deleted', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\MediaFoldersTable::validationDefault()
	 */
	public function testEntityValidationFieldLength(): void {
		$data = [
			'id' => 123456789123, // exceeds 11 char limit
			'parentId' => 123456789123, // exceeds 11 char limit
			'languageShortcode' => 'en', // valid 2 char length
			'path' => str_repeat('a', 1025), // exceeds 1024 char limit
			'title' => str_repeat('b', 101), // exceeds 100 char limit
			'systemOrder' => 123456789123, // exceeds 11 char limit
		];

		$entity = $this->mediaFoldersTable->newDefaultEntity();
		$this->mediaFoldersTable->patchEntity($entity, $data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('id', $errors);
		$this->assertArrayHasKey('parentId', $errors);
		$this->assertArrayNotHasKey('languageShortcode', $errors); // Should be valid
		$this->assertArrayHasKey('path', $errors);
		$this->assertArrayHasKey('title', $errors);
		$this->assertArrayHasKey('systemOrder', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\MediaFoldersTable::validationDefault()
	 */
	public function testEntityValidationLanguageShortcodeExactLength(): void {
		// Test valid language shortcodes
		$validCodes = ['de', 'en', 'fr', 'es', '', null];
		foreach ($validCodes as $code) {
			$data = [
				'languageShortcode' => $code,
				'path' => '../tmp/media/test',
				'title' => 'Test',
			];

			$entity = $this->mediaFoldersTable->newDefaultEntity();
			$this->mediaFoldersTable->patchEntity($entity, $data);
			$errors = $entity->getErrors();
			$this->assertArrayNotHasKey('languageShortcode', $errors);
		}

		// Test invalid language shortcodes
		$invalidCodes = ['d', 'deu', 'english'];
		foreach ($invalidCodes as $code) {
			$data = [
				'languageShortcode' => $code,
				'path' => '../tmp/media/test',
				'title' => 'Test',
			];

			$entity = $this->mediaFoldersTable->newDefaultEntity();
			$this->mediaFoldersTable->patchEntity($entity, $data);
			$errors = $entity->getErrors();
			$this->assertArrayHasKey('languageShortcode', $errors);
		}
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\MediaFoldersTable::validationDefault()
	 */
	public function testEntityValidationNotBlank(): void {
		$data = [
			'languageShortcode' => 'de',
			'path' => '   ', // only whitespace
			'title' => '   ', // only whitespace
		];

		$entity = $this->mediaFoldersTable->newDefaultEntity();
		$this->mediaFoldersTable->patchEntity($entity, $data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('path', $errors);
		$this->assertArrayHasKey('title', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\MediaFoldersTable::buildRules()
	 */
	public function testBuildRulesLanguageExists(): void {
		// Test with existing language
		$data = [
			'languageShortcode' => 'de',
			'path' => '../tmp/media/test',
			'title' => 'Test Folder',
		];

		$entity = $this->mediaFoldersTable->newDefaultEntity();
		$this->mediaFoldersTable->patchEntity($entity, $data);
		$result = $this->mediaFoldersTable->checkRules($entity);
		$this->assertTrue($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\MediaFoldersTable::buildRules()
	 */
	public function testBuildRulesLanguageNotExists(): void {
		// Test with non-existing language
		$data = [
			'languageShortcode' => 'xx',
			'path' => '../tmp/media/test',
			'title' => 'Test Folder',
		];

		$entity = $this->mediaFoldersTable->newDefaultEntity();
		$this->mediaFoldersTable->patchEntity($entity, $data);
		$result = $this->mediaFoldersTable->checkRules($entity);
		$this->assertFalse($result);

		$errors = $entity->getErrors();
		$this->assertArrayHasKey('languageShortcode', $errors);
		$this->assertArrayHasKey('languageExists', $errors['languageShortcode']);
		$this->assertSame('media_folders::error_language_exists', $errors['languageShortcode']['languageExists']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\MediaFoldersTable::buildRules()
	 */
	public function testBuildRulesRootUnchangedLanguageShortcode(): void {
		// Test that root folder (id=1) cannot have languageShortcode changed from null
		/** @var \Awyiss\Model\Entity\MediaFolder $entity */
		$entity = $this->mediaFoldersTable->get(1);

		$data = [
			'languageShortcode' => 'de', // Should not be allowed for root
		];

		$this->mediaFoldersTable->patchEntity($entity, $data);
		$result = $this->mediaFoldersTable->checkRules($entity);
		$this->assertFalse($result);

		$errors = $entity->getErrors();
		$this->assertArrayHasKey('_general', $errors);
		$this->assertArrayHasKey('rootUnchanged', $errors['_general']);
		$this->assertSame('media_folders::error_root_language_shortcode_unchanged', $errors['_general']['rootUnchanged']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\MediaFoldersTable::buildRules()
	 */
	public function testBuildRulesRootUnchangedActive(): void {
		// Test that root folder (id=1) can have other fields changed (like active, hidden, etc.)
		/** @var \Awyiss\Model\Entity\MediaFolder $entity */
		$entity = $this->mediaFoldersTable->get(1);

		$data = [
			'title' => 'Media',
			'active' => false,
		];

		$this->mediaFoldersTable->patchEntity($entity, $data);
		$result = $this->mediaFoldersTable->checkRules($entity);
		$this->assertFalse($result);

		$errors = $entity->getErrors();
		$this->assertArrayHasKey('_general', $errors);
		$this->assertArrayHasKey('rootUnchanged', $errors['_general']);
		$this->assertSame('media_folders::error_root_active_unchanged', $errors['_general']['rootUnchanged']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\MediaFoldersTable::buildRules()
	 */
	public function testBuildRulesRootUnchangedActiveWhenCopy(): void {
		// Test that root folder (id=1) can have other fields changed (like active, hidden, etc.)
		/** @var \Awyiss\Model\Entity\MediaFolder $entity */
		$entity = $this->mediaFoldersTable->get(1);

		$data = [
			'title' => 'Media',
			'active' => false,
		];

		$this->mediaFoldersTable->patchEntity($entity, $data);
		$result = $this->mediaFoldersTable->checkRules($entity, RulesChecker::UPDATE, ['isCopy' => true]);
		$this->assertTrue($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\MediaFoldersTable::buildRules()
	 */
	public function testBuildRulesRootUnchangedHidden(): void {
		// Test that root folder (id=1) can have other fields changed (like active, hidden, etc.)
		/** @var \Awyiss\Model\Entity\MediaFolder $entity */
		$entity = $this->mediaFoldersTable->get(1);

		$data = [
			'title' => 'Media',
			'hidden' => true,
		];

		$this->mediaFoldersTable->patchEntity($entity, $data);
		$result = $this->mediaFoldersTable->checkRules($entity);
		$this->assertFalse($result);

		$errors = $entity->getErrors();
		$this->assertArrayHasKey('_general', $errors);
		$this->assertArrayHasKey('rootUnchanged', $errors['_general']);
		$this->assertSame('media_folders::error_root_hidden_unchanged', $errors['_general']['rootUnchanged']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\MediaFoldersTable::buildRules()
	 */
	public function testBuildRulesRootUnchangedHiddenWhenCopy(): void {
		// Test that root folder (id=1) can have other fields changed (like active, hidden, etc.)
		/** @var \Awyiss\Model\Entity\MediaFolder $entity */
		$entity = $this->mediaFoldersTable->get(1);

		$data = [
			'title' => 'Media',
			'hidden' => true,
		];

		$this->mediaFoldersTable->patchEntity($entity, $data);
		$result = $this->mediaFoldersTable->checkRules($entity, RulesChecker::UPDATE, ['isCopy' => true]);
		$this->assertTrue($result);
	}



	/**
	 * @return void
	 * @see \Awyiss\Model\Table\MediaFoldersTable::buildRules()
	 */
	public function testBuildRulesRootUnchangedTitle(): void {
		// Test that root folder (id=1) title cannot be changed from 'Media'
		/** @var \Awyiss\Model\Entity\MediaFolder $entity */
		$entity = $this->mediaFoldersTable->get(1);

		$data = [
			'title' => 'Changed Title', // Should not be allowed for root
		];

		$this->mediaFoldersTable->patchEntity($entity, $data);
		$result = $this->mediaFoldersTable->checkRules($entity);
		$this->assertFalse($result);

		$errors = $entity->getErrors();
		$this->assertArrayHasKey('_general', $errors);
		$this->assertArrayHasKey('rootUnchanged', $errors['_general']);
		$this->assertSame('media_folders::error_root_title_unchanged', $errors['_general']['rootUnchanged']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\MediaFoldersTable::buildRules()
	 */
	public function testBuildRulesRootUnchangedTitleWhenCopy(): void {
		// Test that root folder (id=1) title can be changed when copying
		/** @var \Awyiss\Model\Entity\MediaFolder $entity */
		$entity = $this->mediaFoldersTable->get(1);

		$data = [
			'title' => 'Changed Title', // Should be allowed when copying
		];

		$this->mediaFoldersTable->patchEntity($entity, $data);
		$result = $this->mediaFoldersTable->checkRules($entity, RulesChecker::UPDATE, ['isCopy' => true]);
		$this->assertTrue($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\MediaFoldersTable::buildRules()
	 */
	public function testBuildRulesRootUnchangedParentId(): void {
		// Test that root folder (id=1) parentId cannot be changed from null
		/** @var \Awyiss\Model\Entity\MediaFolder $entity */
		$entity = $this->mediaFoldersTable->get(1);

		$data = [
			'title' => 'Media',
			'parentId' => 2, // Should not be allowed for root
		];

		$this->mediaFoldersTable->patchEntity($entity, $data);
		$result = $this->mediaFoldersTable->checkRules($entity);
		$this->assertFalse($result);

		$errors = $entity->getErrors();
		$this->assertArrayHasKey('_general', $errors);
		$this->assertArrayHasKey('rootUnchanged', $errors['_general']);
		$this->assertSame('media_folders::error_root_parent_id_unchanged', $errors['_general']['rootUnchanged']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\MediaFoldersTable::buildRules()
	 */
	public function testBuildRulesRootUnchangedParentIdWhenCopy(): void {
		// Test that root folder (id=1) parentId can be changed when copying
		/** @var \Awyiss\Model\Entity\MediaFolder $entity */
		$entity = $this->mediaFoldersTable->get(1);

		$data = [
			'title' => 'Media',
			'parentId' => 2, // Should be allowed when copying
		];

		$this->mediaFoldersTable->patchEntity($entity, $data);
		$result = $this->mediaFoldersTable->checkRules($entity, RulesChecker::UPDATE, ['isCopy' => true]);
		$this->assertTrue($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\MediaFoldersTable::buildRules()
	 */
	public function testBuildRulesRootUnchangedPath(): void {
		// Test that root folder (id=1) path cannot be changed from 'media'
		/** @var \Awyiss\Model\Entity\MediaFolder $entity */
		$entity = $this->mediaFoldersTable->get(1);

		$data = [
			'title' => 'Media',
			'path' => '../different/path', // Should not be allowed for root
		];

		$this->mediaFoldersTable->patchEntity($entity, $data);
		$result = $this->mediaFoldersTable->checkRules($entity);
		$this->assertFalse($result);

		$errors = $entity->getErrors();
		$this->assertArrayHasKey('_general', $errors);
		$this->assertArrayHasKey('rootUnchanged', $errors['_general']);
		$this->assertSame('media_folders::error_root_path_unchanged', $errors['_general']['rootUnchanged']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\MediaFoldersTable::buildRules()
	 */
	public function testBuildRulesRootUnchangedPathWhenCopy(): void {
		// Test that root folder (id=1) path can be changed when copying
		/** @var \Awyiss\Model\Entity\MediaFolder $entity */
		$entity = $this->mediaFoldersTable->get(1);
		$data = [
			'title' => 'Media',
			'path' => '../different/path', // Should be allowed when copying
		];

		$this->mediaFoldersTable->patchEntity($entity, $data);
		$result = $this->mediaFoldersTable->checkRules($entity, RulesChecker::UPDATE, ['isCopy' => true]);
		$this->assertTrue($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\MediaFoldersTable::buildRules()
	 */
	public function testBuildRulesNotNestedUnderRoot(): void {
		// Test that folders cannot have root (id=1) as parent
		$data = [
			'languageShortcode' => 'de',
			'path' => '../tmp/media/test',
			'title' => 'Test Folder',
			'parentId' => 1, // Should not be allowed
		];

		$entity = $this->mediaFoldersTable->newDefaultEntity();
		$this->mediaFoldersTable->patchEntity($entity, $data);
		$result = $this->mediaFoldersTable->checkRules($entity);
		$this->assertFalse($result);

		$errors = $entity->getErrors();
		$this->assertArrayHasKey('parentId', $errors);
		$this->assertArrayHasKey('notNestedUnderRoot', $errors['parentId']);
		$this->assertSame('media_folders::error_not_nested_under_root', $errors['parentId']['notNestedUnderRoot']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\MediaFoldersTable::buildRules()
	 */
	public function testBuildRulesAllowedParentId(): void {
		// Test that folders can have other folders as parent (not root)
		$data = [
			'languageShortcode' => 'de',
			'path' => '../tmp/media/test',
			'title' => 'Test Folder',
			'parentId' => 2, // Should be allowed
		];

		$entity = $this->mediaFoldersTable->newDefaultEntity();
		$this->mediaFoldersTable->patchEntity($entity, $data);
		$result = $this->mediaFoldersTable->checkRules($entity);
		$this->assertTrue($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\MediaFoldersTable::buildRules()
	 */
	public function testBuildRulesNotRootDeletion(): void {
		// Test that root folder (id=1) cannot be deleted
		/** @var \Awyiss\Model\Entity\MediaFolder $entity */
		$entity = $this->mediaFoldersTable->get(1);

		$result = $this->mediaFoldersTable->checkRules($entity, RulesChecker::DELETE);
		$this->assertFalse($result);

		$errors = $entity->getErrors();
		$this->assertArrayHasKey('_general', $errors);
		$this->assertArrayHasKey('notRootDeletion', $errors['_general']);
		$this->assertSame('media_folders::error_not_root_deletion', $errors['_general']['notRootDeletion']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\MediaFoldersTable::buildRules()
	 */
	public function testBuildRulesAllowNonRootDeletion(): void {
		// Test that non-root folders can be deleted
		/** @var \Awyiss\Model\Entity\MediaFolder $entity */
		$entity = $this->mediaFoldersTable->get(2);

		$result = $this->mediaFoldersTable->checkRules($entity, RulesChecker::DELETE);
		$this->assertTrue($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\MediaFoldersTable::findActive()
	 */
	public function testFindActive(): void {
		$query = $this->mediaFoldersTable->find('active');

		/** @noinspection PhpConditionAlreadyCheckedInspection */
		$this->assertInstanceOf(SelectQuery::class, $query);

		// Verify the specific conditions are applied
		$sql = $query->sql();
		$this->assertStringContainsString('active = :c4', $sql);
		$this->assertStringContainsString('parentsActive = :c5', $sql);

		// Verify the bound values
		$valueBinder = $query->getValueBinder();
		$bindings = $valueBinder->bindings();

		$this->assertTrue($bindings[':c4']['value']);
		$this->assertTrue($bindings[':c5']['value']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\MediaFoldersTable::newDefaultEntity()
	 */
	public function testNewDefaultEntity(): void {
		/** @var \Awyiss\Model\Entity\MediaFolder $entity */
		$entity = $this->mediaFoldersTable->newDefaultEntity();

		$this->assertInstanceOf(MediaFolder::class, $entity);
		$this->assertTrue($entity->isNew());

		// Check default values
		$this->assertNull($entity->parentId);
		$this->assertNull($entity->languageShortcode);
		$this->assertNull($entity->path);
		$this->assertNull($entity->title);
		$this->assertSame(0, $entity->systemOrder);
		$this->assertFalse($entity->hidden);
		$this->assertTrue($entity->active);
		$this->assertTrue($entity->parentsActive);
		$this->assertFalse($entity->deleted);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\MediaFoldersTable::newDefaultEntity()
	 */
	public function testNewDefaultEntityWithData(): void {
		$additionalData = [
			'parentId' => 2,
			'languageShortcode' => 'en',
			'path' => '../tmp/media/custom',
			'title' => 'Custom Folder',
			'systemOrder' => 5,
			'hidden' => true,
			'active' => false,
			'parentsActive' => false,
		];

		/** @var \Awyiss\Model\Entity\MediaFolder $entity */
		$entity = $this->mediaFoldersTable->newDefaultEntity($additionalData);

		$this->assertInstanceOf(MediaFolder::class, $entity);
		$this->assertTrue($entity->isNew());

		// Check custom values
		$this->assertSame(2, $entity->parentId);
		$this->assertSame('en', $entity->languageShortcode);
		$this->assertSame('../tmp/media/custom', $entity->path);
		$this->assertSame('Custom Folder', $entity->title);
		$this->assertSame(5, $entity->systemOrder);
		$this->assertTrue($entity->hidden);
		$this->assertFalse($entity->active);
		$this->assertFalse($entity->parentsActive);
		$this->assertFalse($entity->deleted); // Should remain default
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\MediaFoldersTable::$nest
	 */
	public function testNestBehavior(): void {
		$this->assertTrue($this->mediaFoldersTable->hasBehavior('Nest'));

		$config = $this->mediaFoldersTable->getBehavior('Nest')->getConfig();

		$this->assertTrue($config['enabled']);
		$this->assertSame(['languageShortcode', 'hidden'], $config['relatedColumns']);
		$this->assertSame(['hidden'], $config['children']['blocklistedColumns']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\MediaFoldersTable::$search
	 */
	public function testSearchBehavior(): void {
		$this->assertTrue($this->mediaFoldersTable->hasBehavior('Search'));

		$config = $this->mediaFoldersTable->getBehavior('Search')->getConfig();

		$this->assertSame(['languageShortcode', 'hidden'], $config['blocklistedColumns']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\MediaFoldersTable::$systemOrder
	 */
	public function testSystemOrderBehavior(): void {
		$this->assertTrue($this->mediaFoldersTable->hasBehavior('SystemOrder'));

		$config = $this->mediaFoldersTable->getBehavior('SystemOrder')->getConfig();

		$this->assertSame(['languageShortcode', 'parentId', 'hidden'], $config['relatedColumns']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\MediaFoldersTable::$translate
	 */
	public function testTranslateBehavior(): void {
		$this->assertTrue($this->mediaFoldersTable->hasBehavior('Translate'));

		$config = $this->mediaFoldersTable->getBehavior('Translate')->getConfig();

		$this->assertNull($config['realm'] ?? null);

		$this->assertIsArray($config['fields']);
		$this->assertSame(['title'], $config['fields']);
	}
}
