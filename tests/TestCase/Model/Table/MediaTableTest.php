<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Model\Table;


use Awyiss\Awyiss;
use Awyiss\Model\Entity\Media;
use Awyiss\Model\Enum\ProcessStatus;
use Awyiss\Model\Table\MediaTable;
use Awyiss\ORM\Association\BelongsTo;
use Awyiss\ORM\Association\HasMany;
use Awyiss\ORM\Association\HasOne;
use Awyiss\Test\TestSuite\TestCase;
use Awyiss\Validation\Validator;
use Cake\Core\Configure;
use Cake\Datasource\FactoryLocator;
use Cake\Datasource\RulesChecker;
use Laminas\Diactoros\UploadedFile;


/**
 * MediaTable Test Case
 *
 * @see \Awyiss\Model\Table\MediaTable
 */
class MediaTableTest extends TestCase {
	/**
	 * @var \Awyiss\Model\Table\MediaTable
	 */
	protected MediaTable $mediaTable;


	/**
	 * @inheritDoc
	 */
	protected function setUp(): void {
		parent::setUp();

		/** @noinspection PhpFieldAssignmentTypeMismatchInspection */
		$this->mediaTable = FactoryLocator::get('Table')->get('Media');
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\MediaTable::ATTRIBUTABLE
	 */
	public function testAttributableConstant(): void {
		/** @noinspection PhpUnitAssertTrueWithIncompatibleTypeArgumentInspection */
		$this->assertTrue($this->mediaTable::ATTRIBUTABLE);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\MediaTable::TABLE
	 */
	public function testTableConstant(): void {
		$this->assertEquals('media', $this->mediaTable::TABLE);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\MediaTable::initializeAssociations()
	 */
	public function testInitializeAssociations(): void {
		$this->assertCount(9, $this->mediaTable->associations()->keys());

		// Test MediaAssignments association (HasMany)
		$this->assertTrue($this->mediaTable->hasAssociation('MediaAssignments'));
		$mediaAssignmentsAssociation = $this->mediaTable->getAssociation('MediaAssignments');
		$this->assertInstanceOf(HasMany::class, $mediaAssignmentsAssociation);
		$this->assertTrue($mediaAssignmentsAssociation->getCascadeCallbacks());
		$this->assertTrue($mediaAssignmentsAssociation->getDependent());
		$this->assertEquals('replace', $mediaAssignmentsAssociation->getSaveStrategy());

		// Test MediaFolders association (BelongsTo)
		$this->assertTrue($this->mediaTable->hasAssociation('MediaFolders'));
		$mediaFoldersAssociation = $this->mediaTable->getAssociation('MediaFolders');
		$this->assertInstanceOf(BelongsTo::class, $mediaFoldersAssociation);
		$this->assertFalse($mediaFoldersAssociation->getCascadeCallbacks());
		$this->assertFalse($mediaFoldersAssociation->getDependent());

		// Test MediaResizedImages association (HasMany)
		$this->assertTrue($this->mediaTable->hasAssociation('MediaResizedImages'));
		$mediaResizedImagesAssociation = $this->mediaTable->getAssociation('MediaResizedImages');
		$this->assertInstanceOf(HasMany::class, $mediaResizedImagesAssociation);
		$this->assertTrue($mediaResizedImagesAssociation->getCascadeCallbacks());
		$this->assertTrue($mediaResizedImagesAssociation->getDependent());
		$this->assertEquals('replace', $mediaResizedImagesAssociation->getSaveStrategy());

		// Test UrlHistory association (HasMany)
		$this->assertTrue($this->mediaTable->hasAssociation('UrlHistory'));
		$urlHistoryAssociation = $this->mediaTable->getAssociation('UrlHistory');
		$this->assertInstanceOf(HasMany::class, $urlHistoryAssociation);
		$this->assertTrue($urlHistoryAssociation->getCascadeCallbacks());
		$this->assertTrue($urlHistoryAssociation->getDependent());

		$this->assertSame(['scope' => 'Media'], $urlHistoryAssociation->getConditions());

		// Test user tracking associations
		$this->assertTrue($this->mediaTable->hasAssociation('CreatedByUser'));
		$createdByUserAssociation = $this->mediaTable->getAssociation('CreatedByUser');
		$this->assertInstanceOf(BelongsTo::class, $createdByUserAssociation);
		$this->assertFalse($createdByUserAssociation->getCascadeCallbacks());
		$this->assertFalse($createdByUserAssociation->getDependent());

		$this->assertTrue($this->mediaTable->hasAssociation('ChangedByUser'));
		$changedByUserAssociation = $this->mediaTable->getAssociation('ChangedByUser');
		$this->assertInstanceOf(BelongsTo::class, $changedByUserAssociation);
		$this->assertFalse($changedByUserAssociation->getCascadeCallbacks());
		$this->assertFalse($changedByUserAssociation->getDependent());

		$this->assertTrue($this->mediaTable->hasAssociation('DeletedByUser'));
		$deletedByUserAssociation = $this->mediaTable->getAssociation('DeletedByUser');
		$this->assertInstanceOf(BelongsTo::class, $deletedByUserAssociation);
		$this->assertFalse($deletedByUserAssociation->getCascadeCallbacks());
		$this->assertFalse($deletedByUserAssociation->getDependent());

		// Test translation associations
		$this->assertTrue($this->mediaTable->hasAssociation('Media_alt_translation'));
		$altTranslationAssociation = $this->mediaTable->getAssociation('Media_alt_translation');
		$this->assertInstanceOf(HasOne::class, $altTranslationAssociation);
		$this->assertFalse($altTranslationAssociation->getCascadeCallbacks());
		$this->assertFalse($altTranslationAssociation->getDependent());

		$this->assertTrue($this->mediaTable->hasAssociation('I18n'));
		$i18nAssociation = $this->mediaTable->getAssociation('I18n');
		$this->assertInstanceOf(HasMany::class, $i18nAssociation);
		$this->assertFalse($i18nAssociation->getCascadeCallbacks());
		$this->assertTrue($i18nAssociation->getDependent());
		$this->assertEquals('append', $i18nAssociation->getSaveStrategy());
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\MediaTable::validationDefault()
	 */
	public function testValidationDefault(): void {
		$validator = new Validator();
		$result = $this->mediaTable->validationDefault($validator);

		$this->assertInstanceOf(Validator::class, $result);
		$this->assertSame('Media', $result->getI18nDomain());

		// Test required fields
		$this->assertTrue($result->hasField('mediaFolderId'));
		$this->assertSame('create', $result->field('mediaFolderId')->isPresenceRequired());

		$this->assertTrue($result->hasField('name'));
		$this->assertSame('create', $result->field('name')->isPresenceRequired());

		// Test other fields
		$this->assertTrue($result->hasField('id'));
		$this->assertTrue($result->hasField('alt'));
		$this->assertTrue($result->hasField('metaData'));
		$this->assertTrue($result->hasField('systemOrder'));
		$this->assertTrue($result->hasField('file'));
		$this->assertTrue($result->hasField('mimeType'));
		$this->assertTrue($result->hasField('path'));
		$this->assertTrue($result->hasField('width'));
		$this->assertTrue($result->hasField('height'));
		$this->assertTrue($result->hasField('focusPoint'));
		$this->assertTrue($result->hasField('crop'));
		$this->assertTrue($result->hasField('averageColor'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\MediaTable::validationDefault()
	 */
	public function testEntityValidationSuccess(): void {
		$data = [
			'mediaFolderId' => 1,
			'mimeType' => 'image/jpeg',
			'name' => 'test-image.jpg',
			'path' => '/path/to/test-image.jpg',
			'alt' => 'Test image description',
			'width' => 1920.0,
			'height' => 1080.0,
			'metaData' => ['exif' => 'test'],
			'averageColor' => '#FF0000CC',
			'preview' => ProcessStatus::NotRequired,
			'webp' => ProcessStatus::NotRequired,
			'avif' => ProcessStatus::NotRequired,
			'crop' => ['x' => 0, 'y' => 0, 'width' => 100, 'height' => 100],
			'focusPoint' => '2,0',
			'systemOrder' => 1,
		];

		$entity = $this->mediaTable->newEntity($data);
		$errors = $entity->getErrors();

		$this->assertEmpty($errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\MediaTable::validationDefault()
	 */
	public function testEntityValidationMissingRequired(): void {
		$data = [
			'alt' => 'Test image',
		];

		$entity = $this->mediaTable->newEntity($data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('mediaFolderId', $errors);
		$this->assertArrayHasKey('name', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\MediaTable::validationDefault()
	 */
	public function testEntityValidationInvalidTypes(): void {
		$data = [
			'id' => 'not_an_integer',
			'mediaFolderId' => 'not_an_integer',
			'mimeType' => true,
			'name' => true,
			'path' => true,
			'alt' => true,
			'width' => 'not_a_float',
			'height' => 'not_a_float',
			'metaData' => 'not_an_array',
			'averageColor' => true,
			'crop' => 'not_an_array',
			'focusPoint' => 'not_in_list',
			'systemOrder' => 'not_an_integer',
		];

		$entity = $this->mediaTable->newEntity($data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('id', $errors);
		$this->assertArrayHasKey('mediaFolderId', $errors);
		$this->assertArrayHasKey('mimeType', $errors);
		$this->assertArrayHasKey('name', $errors);
		$this->assertArrayHasKey('path', $errors);
		$this->assertArrayHasKey('alt', $errors);
		$this->assertArrayHasKey('width', $errors);
		$this->assertArrayHasKey('height', $errors);
		$this->assertArrayHasKey('metaData', $errors);
		$this->assertArrayHasKey('averageColor', $errors);
		$this->assertArrayHasKey('systemOrder', $errors);
		$this->assertArrayHasKey('focusPoint', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\MediaTable::validationDefault()
	 */
	public function testEntityValidationFieldLength(): void {
		$data = [
			'id' => 123456789123, // exceeds 11 char limit
			'mediaFolderId' => 123456789123, // exceeds 11 char limit
			'mimeType' => str_repeat('a', 101), // exceeds 100 char limit
			'name' => str_repeat('b', 101), // exceeds 100 char limit
			'path' => str_repeat('c', 1125), // exceeds 1124 char limit
			'alt' => str_repeat('d', 256), // exceeds 255 char limit
			'averageColor' => str_repeat('e', 9), // exceeds 7 char limit
			'systemOrder' => 123456789123, // exceeds 11 char limit
			'metaData' => str_repeat('a', 16777215), // exceeds 16777215 byte limit
			'crop' => str_repeat('a', 65536), // exceeds 65535 byte limit
		];

		$entity = $this->mediaTable->newEntity($data, ['guard' => false]);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('id', $errors);
		$this->assertArrayHasKey('mediaFolderId', $errors);
		$this->assertArrayHasKey('mimeType', $errors);
		$this->assertArrayHasKey('name', $errors);
		$this->assertArrayHasKey('path', $errors);
		$this->assertArrayHasKey('alt', $errors);
		$this->assertArrayHasKey('averageColor', $errors);
		$this->assertArrayHasKey('systemOrder', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\MediaTable::validationDefault()
	 */
	public function testEntityValidationNotBlank(): void {
		$data = [
			'mediaFolderId' => 1,
			'mimeType' => '   ', // only whitespace
			'name' => '   ', // only whitespace
			'path' => '   ', // only whitespace
			'systemOrder' => 1,
		];

		$entity = $this->mediaTable->newEntity($data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('mimeType', $errors);
		$this->assertArrayHasKey('name', $errors);
		$this->assertArrayHasKey('path', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\MediaTable::validationDefault()
	 */
	public function testEntityValidationAverageColorExactLength(): void {
		// Test valid hex colors
		$validColors = ['#FF0000', '#FF0000CC', 'FF0000', 'FF0000CC'];
		foreach ($validColors as $color) {
			$data = [
				'mediaFolderId' => 1,
				'name' => 'test.jpg',
				'mimeType' => 'image/jpeg',
				'path' => '/test.jpg',
				'averageColor' => $color,
			];

			$entity = $this->mediaTable->newEntity($data);
			$errors = $entity->getErrors();
			$this->assertArrayNotHasKey('averageColor', $errors);
		}

		// Test invalid hex colors
		$invalidColors = ['#FF00', '#FF0000C', 'FF00', 'FF0000C', '#FF0000CCC'];
		foreach ($invalidColors as $color) {
			$data = [
				'mediaFolderId' => 1,
				'name' => 'test.jpg',
				'mimeType' => 'image/jpeg',
				'path' => '/test.jpg',
				'averageColor' => $color,
			];

			$entity = $this->mediaTable->newEntity($data);
			$errors = $entity->getErrors();
			$this->assertArrayHasKey('averageColor', $errors);
		}
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\MediaTable::validationDefault()
	 */
	public function testEntityValidationFocusPointInListValid(): void {
		// Test valid focus points
		$validFocusPoints = ['0,0', '0,1', '0,2', '1,0', '1,1', '1,2', '2,0', '2,1', '2,2'];
		foreach ($validFocusPoints as $focusPoint) {
			$data = [
				'mediaFolderId' => 1,
				'name' => 'test.jpg',
				'mimeType' => 'image/jpeg',
				'path' => '/test.jpg',
				'focusPoint' => $focusPoint,
			];

			$entity = $this->mediaTable->newEntity($data);
			$errors = $entity->getErrors();
			$this->assertArrayNotHasKey('focusPoint', $errors);
		}
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\MediaTable::validationDefault()
	 */
	public function testEntityValidationFocusPointInListInvalid(): void {
		// Test invalid focus point
		$data = [
			'mediaFolderId' => 1,
			'name' => 'test.jpg',
			'mimeType' => 'image/jpeg',
			'path' => '/test.jpg',
			'focusPoint' => '3,3',
		];

		$entity = $this->mediaTable->newEntity($data);
		$errors = $entity->getErrors();
		$this->assertArrayHasKey('focusPoint', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\MediaTable::buildRules()
	 */
	public function testBuildRulesValidMediaFolder(): void {
		// Test with existing media folder
		$data = [
			'mediaFolderId' => 1,
			'mimeType' => 'image/jpeg',
			'name' => 'test.jpg',
			'path' => '/test.jpg',
			'systemOrder' => 1,
		];

		$entity = $this->mediaTable->newEntity($data);
		$result = $this->mediaTable->checkRules($entity);
		$this->assertTrue($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\MediaTable::buildRules()
	 */
	public function testBuildRulesInvalidMediaFolder(): void {
		// Test with non-existing media folder
		$data = [
			'mediaFolderId' => 99999,
			'mimeType' => 'image/jpeg',
			'name' => 'test.jpg',
			'path' => '/test.jpg',
			'systemOrder' => 1,
		];

		$entity = $this->mediaTable->newEntity($data);
		$result = $this->mediaTable->checkRules($entity);
		$this->assertFalse($result);

		$errors = $entity->getErrors();
		$this->assertArrayHasKey('mediaFolderId', $errors);
		$this->assertArrayHasKey('validMediaFolderId', $errors['mediaFolderId']);
		// Error message comes from the nest behavior
		$this->assertEquals('Media::error_valid_media_folder_id', $errors['mediaFolderId']['validMediaFolderId']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\MediaTable::buildRules()
	 */
	public function testBuildRulesValidPreview(): void {
		$data = [
			'mediaFolderId' => 1,
			'mimeType' => 'image/jpeg',
			'name' => 'test.jpg',
			'path' => '/test.jpg',
			'systemOrder' => 1,
			'preview' => 1, // Patching entity will convert to enum
		];

		$entity = $this->mediaTable->newDefaultEntity();

		$this->mediaTable->patchEntity($entity, $data);

		$this->assertSame(ProcessStatus::Success, $entity->preview);

		$result = $this->mediaTable->checkRules($entity);

		$this->assertTrue($result);

		$entity->preview = ProcessStatus::InProgress;

		$result = $this->mediaTable->checkRules($entity);

		$this->assertTrue($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\MediaTable::buildRules()
	 */
	public function testBuildRulesInvalidPreview(): void {
		$data = [
			'mediaFolderId' => 1,
			'mimeType' => 'image/jpeg',
			'name' => 'test.jpg',
			'path' => '/test.jpg',
			'systemOrder' => 1,
			'preview' => 'invalid_value', // Patching entity will convert to enum but fail here
		];

		$entity = $this->mediaTable->newDefaultEntity();

		$this->mediaTable->patchEntity($entity, $data);

		$this->assertSame(ProcessStatus::Undefined, $entity->preview);

		$result = $this->mediaTable->checkRules($entity);

		$this->assertTrue($result);

		$entity->preview = 'invalid';  // Setting a value directly will not convert to enum

		$result = $this->mediaTable->checkRules($entity);

		$this->assertFalse($result);

		$errors = $entity->getErrors();
		$this->assertArrayHasKey('preview', $errors);
		$this->assertArrayHasKey('validPreview', $errors['preview']);
		$this->assertSame('Media::error_valid_preview', $errors['preview']['validPreview']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\MediaTable::buildRules()
	 */
	public function testBuildRulesValidAvif(): void {
		$data = [
			'mediaFolderId' => 1,
			'mimeType' => 'image/jpeg',
			'name' => 'test.jpg',
			'path' => '/test.jpg',
			'systemOrder' => 1,
			'avif' => 1, // Patching entity will convert to enum
		];

		$entity = $this->mediaTable->newDefaultEntity();

		$this->mediaTable->patchEntity($entity, $data);

		$this->assertSame(ProcessStatus::Success, $entity->avif);

		$result = $this->mediaTable->checkRules($entity);

		$this->assertTrue($result);

		$entity->avif = ProcessStatus::InProgress;

		$result = $this->mediaTable->checkRules($entity);

		$this->assertTrue($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\MediaTable::buildRules()
	 */
	public function testBuildRulesInvalidAvif(): void {
		$data = [
			'mediaFolderId' => 1,
			'mimeType' => 'image/jpeg',
			'name' => 'test.jpg',
			'path' => '/test.jpg',
			'systemOrder' => 1,
			'avif' => 'invalid_value', // Patching entity will convert to enum but fail here
		];

		$entity = $this->mediaTable->newDefaultEntity();

		$this->mediaTable->patchEntity($entity, $data);

		$this->assertSame(ProcessStatus::Undefined, $entity->avif);

		$result = $this->mediaTable->checkRules($entity);

		$this->assertTrue($result);

		$entity->avif = 'invalid';  // Setting a value directly will not convert to enum

		$result = $this->mediaTable->checkRules($entity);

		$this->assertFalse($result);

		$errors = $entity->getErrors();
		$this->assertArrayHasKey('avif', $errors);
		$this->assertArrayHasKey('validAvif', $errors['avif']);
		$this->assertSame('Media::error_valid_avif', $errors['avif']['validAvif']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\MediaTable::buildRules()
	 */
	public function testBuildRulesValidWebp(): void {
		$data = [
			'mediaFolderId' => 1,
			'mimeType' => 'image/jpeg',
			'name' => 'test.jpg',
			'path' => '/test.jpg',
			'systemOrder' => 1,
			'webp' => 1, // Patching entity will convert to enum
		];

		$entity = $this->mediaTable->newDefaultEntity();

		$this->mediaTable->patchEntity($entity, $data);

		$this->assertSame(ProcessStatus::Success, $entity->webp);

		$result = $this->mediaTable->checkRules($entity);

		$this->assertTrue($result);

		$entity->webp = ProcessStatus::InProgress;

		$result = $this->mediaTable->checkRules($entity);

		$this->assertTrue($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\MediaTable::buildRules()
	 */
	public function testBuildRulesInvalidWebp(): void {
		$data = [
			'mediaFolderId' => 1,
			'mimeType' => 'image/jpeg',
			'name' => 'test.jpg',
			'path' => '/test.jpg',
			'systemOrder' => 1,
			'webp' => 'invalid_value', // Patching entity will convert to enum but fail here
		];

		$entity = $this->mediaTable->newDefaultEntity();

		$this->mediaTable->patchEntity($entity, $data);

		$this->assertSame(ProcessStatus::Undefined, $entity->webp);

		$result = $this->mediaTable->checkRules($entity);

		$this->assertTrue($result);

		$entity->webp = 'invalid';  // Setting a value directly will not convert to enum

		$result = $this->mediaTable->checkRules($entity);

		$this->assertFalse($result);

		$errors = $entity->getErrors();
		$this->assertArrayHasKey('webp', $errors);
		$this->assertArrayHasKey('validWebp', $errors['webp']);
		$this->assertSame('Media::error_valid_webp', $errors['webp']['validWebp']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\MediaTable::buildRules()
	 */
	public function testBuildRulesValidFileNameExtensionWithValidFile(): void {
		// Create a temporary file for testing
		$tempFile = tempnam(sys_get_temp_dir(), 'test');
		file_put_contents($tempFile, 'fake jpeg content');

		$uploadedFile = new UploadedFile($tempFile, 100, UPLOAD_ERR_OK, 'test.jpg', 'image/jpeg');

		$data = [
			'mediaFolderId' => 1,
			'mimeType' => 'image/jpeg',
			'name' => 'test.jpg',
			'path' => '/test.jpg',
			'file' => $uploadedFile,
		];

		/** @var \Awyiss\Model\Entity\Media $entity */
		$entity = $this->mediaTable->newEntity($data);

		$result = $this->mediaTable->checkRules($entity);

		// Clean up first
		if (file_exists($tempFile)) {
			unlink($tempFile);
		}

		$this->assertTrue($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\MediaTable::buildRules()
	 */
	public function testBuildRulesValidFileNameExtensionWithNoExtension(): void {
		// Create a temporary file for testing
		$tempFile = tempnam(sys_get_temp_dir(), 'test');
		file_put_contents($tempFile, 'fake content');

		$uploadedFile = new UploadedFile($tempFile, 100, UPLOAD_ERR_OK, 'test', 'image/jpeg');

		$data = [
			'mediaFolderId' => 1,
			'mimeType' => 'image/jpeg',
			'name' => 'test',
			'path' => '/test',
			'file' => $uploadedFile,
		];

		/** @var \Awyiss\Model\Entity\Media $entity */
		$entity = $this->mediaTable->newEntity($data);
		// Mock no extension
		$entity->extension = null;

		$result = $this->mediaTable->checkRules($entity);

		// Clean up first
		if (file_exists($tempFile)) {
			unlink($tempFile);
		}

		$this->assertFalse($result);

		$errors = $entity->getErrors();
		$this->assertArrayHasKey('file', $errors);
		$this->assertArrayHasKey('validFileNameExtension', $errors['file']);
		$this->assertEquals('Media::error_media_has_file_extension', $errors['file']['validFileNameExtension']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\MediaTable::buildRules()
	 */
	public function testBuildRulesValidFileNameExtensionWithMismatchedExtension(): void {
		// Create a temporary file for testing
		$tempFile = tempnam(sys_get_temp_dir(), 'test');
		file_put_contents($tempFile, 'fake text content');

		$uploadedFile = new UploadedFile($tempFile, 100, UPLOAD_ERR_OK, 'test.txt', 'image/jpeg');

		$data = [
			'mediaFolderId' => 1,
			'mimeType' => 'image/jpeg',
			'name' => 'test.txt',
			'path' => '/test.txt',
			'file' => $uploadedFile,
		];

		/** @var \Awyiss\Model\Entity\Media $entity */
		$entity = $this->mediaTable->newEntity($data);

		$result = $this->mediaTable->checkRules($entity);

		// Clean up first
		if (file_exists($tempFile)) {
			unlink($tempFile);
		}

		$this->assertFalse($result);

		$errors = $entity->getErrors();
		$this->assertArrayHasKey('file', $errors);
		$this->assertArrayHasKey('validFileNameExtension', $errors['file']);
		$this->assertEquals('Media::error_media_mime_type_matches_extension', $errors['file']['validFileNameExtension']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\MediaTable::buildRules()
	 */
	public function testBuildRulesValidFileNameExtensionWithFileError(): void {
		// Test with file that has upload error - should pass validation
		$tempFile = tempnam(sys_get_temp_dir(), 'test');
		file_put_contents($tempFile, 'fake content');

		$uploadedFile = new UploadedFile($tempFile, 100, UPLOAD_ERR_INI_SIZE, 'test.jpg', 'image/jpeg');

		$data = [
			'mediaFolderId' => 1,
			'mimeType' => 'image/jpeg',
			'name' => 'test.jpg',
			'path' => '/test.jpg',
			'file' => $uploadedFile,
		];

		/** @var \Awyiss\Model\Entity\Media $entity */
		$entity = $this->mediaTable->newEntity($data);
		$entity->extension = 'jpg';

		$result = $this->mediaTable->checkRules($entity);

		// Clean up
		if (file_exists($tempFile)) {
			unlink($tempFile);
		}

		// Should pass because file has error
		$this->assertTrue($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\MediaTable::buildRules()
	 */
	public function testBuildRulesValidFileNameExtensionWithNoFile(): void {
		// Test without file - should pass validation
		$data = [
			'mediaFolderId' => 1,
			'mimeType' => 'image/jpeg',
			'name' => 'test.jpg',
			'path' => '/test.jpg',
		];

		/** @var \Awyiss\Model\Entity\Media $entity */
		$entity = $this->mediaTable->newEntity($data);

		$result = $this->mediaTable->checkRules($entity);

		// Should pass because no file is present
		$this->assertTrue($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\MediaTable::buildRules()
	 */
	public function testBuildRulesValidFileNameExtensionWithConfigureFallback(): void {
		$tempFile = tempnam(sys_get_temp_dir(), 'test');
		file_put_contents($tempFile, 'fake content that finfo cannot recognize');

		$uploadedFile = new UploadedFile($tempFile, 100, UPLOAD_ERR_OK, 'test.ppt', 'application/vnd-ms-powerpoint');

		$data = [
			'mediaFolderId' => 1,
			'mimeType' => 'application/vnd-ms-powerpoint',
			'name' => 'test.ppt',
			'path' => '/test.ppt',
			'file' => $uploadedFile,
		];

		/** @var \Awyiss\Model\Entity\Media $entity */
		$entity = $this->mediaTable->newEntity($data);

		$result = $this->mediaTable->checkRules($entity);

		// Clean up
		if (file_exists($tempFile)) {
			unlink($tempFile);
		}

		$this->assertTrue($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\MediaTable::buildRules()
	 */
	public function testBuildRulesValidFileNameExtensionWithConfigureFallbackInvalid(): void {
		$tempFile = tempnam(sys_get_temp_dir(), 'test');
		file_put_contents($tempFile, 'fake content that finfo cannot recognize');

		Configure::write('MimeTypes.unknown/mime-type', ['cba']);

		$uploadedFile = new UploadedFile($tempFile, 100, UPLOAD_ERR_OK, 'test.abc', 'unknown/mime-type');

		$data = [
			'mediaFolderId' => 1,
			'mimeType' => 'unknown/mime-type',
			'name' => 'test.abc',
			'path' => '/test.abc',
			'file' => $uploadedFile,
		];

		/** @var \Awyiss\Model\Entity\Media $entity */
		$entity = $this->mediaTable->newEntity($data);

		$result = $this->mediaTable->checkRules($entity);

		// Clean up
		if (file_exists($tempFile)) {
			unlink($tempFile);
		}

		Configure::delete('MimeTypes.unknown/mime-type');

		$this->assertFalse($result);

		$errors = $entity->getErrors();
		$this->assertArrayHasKey('file', $errors);
		$this->assertArrayHasKey('validFileNameExtension', $errors['file']);
		$this->assertEquals('Media::error_media_mime_type_matches_extension', $errors['file']['validFileNameExtension']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\MediaTable::buildRules()
	 */
	public function testBuildRulesUpdateMimeTypeModified(): void {
		// Test update where mimeType is modified (should fail)
		/** @var \Awyiss\Model\Entity\Media $entity */
		$entity = $this->mediaTable->get(2); // Existing media from seed
		$entity->mimeType = 'image/png'; // Change mime type

		$result = $this->mediaTable->checkRules($entity, RulesChecker::UPDATE);
		$this->assertFalse($result);

		$errors = $entity->getErrors();
		$this->assertArrayHasKey('file', $errors);
		$this->assertArrayHasKey('mimetypeNotModified', $errors['file']);
		$this->assertEquals('Media::error_mimetype_not_modified', $errors['file']['mimetypeNotModified']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\MediaTable::getMaxFileSize()
	 */
	public function testGetMaxFileSize(): void {
		$maxFileSize = $this->mediaTable->getMaxFileSize();

		$this->assertIsInt($maxFileSize);
		$this->assertGreaterThan(0, $maxFileSize);

		// Test that it respects PHP's upload_max_filesize setting
		$iniMaxFileSize = ini_get('upload_max_filesize');
		$this->assertNotEmpty($iniMaxFileSize);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\MediaTable::detectMimeType()
	 */
	public function testDetectMimeTypeWithMatchingTypes(): void {
		// Create a temporary file for testing
		$tempFile = tempnam(sys_get_temp_dir(), 'test');
		// Fake jpg content with real jpg header
		file_put_contents($tempFile, "\xFF\xD8\xFF\xE0\x00\x10JFIF\x00\x01\x01\x01\x00\x60\x00\x60\x00\x00\xFF\xDB\x00\x84\x00");

		$uploadedFile = new UploadedFile($tempFile, 100, UPLOAD_ERR_OK, 'test.jpg', 'image/jpeg');

		$mimeType = $this->mediaTable->detectMimeType($uploadedFile, 'jpg');

		// Clean up first
		if (file_exists($tempFile)) {
			unlink($tempFile);
		}

		$this->assertIsString($mimeType);
		$this->assertSame('image/jpeg', $mimeType);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\MediaTable::detectMimeType()
	 */
	public function testDetectMimeTypeWithMismatchedTypes(): void {
		// Create a temporary file for testing
		$tempFile = tempnam(sys_get_temp_dir(), 'test');
		file_put_contents($tempFile, 'fake jpeg content');

		$uploadedFile = new UploadedFile($tempFile, 100, UPLOAD_ERR_OK, 'test.jpg', 'image/png');

		$mimeType = $this->mediaTable->detectMimeType($uploadedFile, 'jpg');

		// Clean up first
		if (file_exists($tempFile)) {
			unlink($tempFile);
		}

		$this->assertIsString($mimeType);
		$this->assertEquals('text/plain', $mimeType); // Fallback to the real file content type
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\MediaTable::detectMimeType()
	 */
	public function testDetectMimeTypeWithExtensionFallback(): void {
		// Create a temporary file for testing
		$tempFile = tempnam(sys_get_temp_dir(), 'test');
		file_put_contents($tempFile, 'fake content that might be detected as text/plain');

		// Configure MIME types so both detected and client types support 'txt' extension
		Configure::write('MimeTypes.text/plain', ['txt']);
		Configure::write('MimeTypes.text/custom', ['txt']);

		$uploadedFile = new UploadedFile($tempFile, 100, UPLOAD_ERR_OK, 'test.txt', 'text/custom');

		// The file content will likely be detected as text/plain, but client says text/custom
		// Since both support 'txt' extension, it should return the client's MIME type
		$mimeType = $this->mediaTable->detectMimeType($uploadedFile, 'txt');

		// Clean up first
		if (file_exists($tempFile)) {
			unlink($tempFile);
		}
		Configure::delete('MimeTypes.text/plain');
		Configure::delete('MimeTypes.text/custom');

		// Should return client MIME type due to extension fallback logic
		$this->assertIsString($mimeType);
		$this->assertSame('text/custom', $mimeType);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\MediaTable::newDefaultEntity()
	 */
	public function testNewDefaultEntity(): void {
		/** @var \Awyiss\Model\Entity\Media $entity */
		$entity = $this->mediaTable->newDefaultEntity();

		$this->assertInstanceOf(Media::class, $entity);
		$this->assertTrue($entity->isNew());

		// Check default values
		$this->assertNull($entity->mediaFolderId);
		$this->assertNull($entity->mimeType);
		$this->assertNull($entity->name);
		$this->assertNull($entity->path);
		$this->assertNull($entity->alt);
		$this->assertNull($entity->width);
		$this->assertNull($entity->height);
		$this->assertNull($entity->metaData);
		$this->assertNull($entity->averageColor);
		$this->assertEquals(ProcessStatus::Undefined, $entity->preview);
		$this->assertEquals(ProcessStatus::Undefined, $entity->webp);
		$this->assertNull($entity->crop);
		$this->assertNull($entity->focusPoint);
		$this->assertSame(0, $entity->systemOrder);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\MediaTable::newDefaultEntity()
	 */
	public function testNewDefaultEntityWithData(): void {
		$additionalData = [
			'mediaFolderId' => 1,
			'mimeType' => 'image/jpeg',
			'name' => 'custom-image.jpg',
			'path' => '/custom-image.jpg',
			'alt' => 'Custom image description',
			'width' => 1920,
			'height' => 1080,
			'metaData' => ['custom' => 'data'],
			'averageColor' => '#FF0000',
			'preview' => ProcessStatus::InProgress,
			'webp' => ProcessStatus::Success,
			'crop' => ['x' => 10, 'y' => 10, 'width' => 100, 'height' => 100],
			'focusPoint' => '1,0',
			'systemOrder' => 5,
		];

		/** @var \Awyiss\Model\Entity\Media $entity */
		$entity = $this->mediaTable->newDefaultEntity($additionalData);

		$this->assertInstanceOf(Media::class, $entity);
		$this->assertTrue($entity->isNew());

		// Check custom values
		$this->assertSame(1, $entity->mediaFolderId);
		$this->assertSame('image/jpeg', $entity->mimeType);
		$this->assertSame('custom-image.jpg', $entity->name);
		$this->assertSame('/custom-image.jpg', $entity->path);
		$this->assertSame('Custom image description', $entity->alt);
		$this->assertSame(1920.0, $entity->width);
		$this->assertSame(1080.0, $entity->height);
		$this->assertSame(['custom' => 'data'], $entity->metaData);
		$this->assertSame('#FF0000', $entity->averageColor);
		$this->assertEquals(ProcessStatus::InProgress, $entity->preview);
		$this->assertEquals(ProcessStatus::Success, $entity->webp);
		$this->assertSame(['x' => 10, 'y' => 10, 'width' => 100, 'height' => 100], $entity->crop);
		$this->assertSame('1,0', $entity->focusPoint);
		$this->assertSame(5, $entity->systemOrder);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\MediaTable::$categories
	 */
	public function testCategoriesBehavior(): void {
		$this->assertTrue($this->mediaTable->hasBehavior('Categories'));

		$config = $this->mediaTable->getBehavior('Categories')->getConfig();

		$this->assertFalse($config['allowAggregation']);
		$this->assertFalse($config['allowUnassigned']);
		$this->assertSame('MediaFolders', $config['associationName']);
		$this->assertTrue($config['enabled']);
		$this->assertSame('forCurrentLanguage', $config['finder']);
		$this->assertSame('mediaFolder', $config['identifier']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\MediaTable::$systemOrder
	 */
	public function testSystemOrderBehavior(): void {
		$this->assertTrue($this->mediaTable->hasBehavior('SystemOrder'));

		$config = $this->mediaTable->getBehavior('SystemOrder')->getConfig();

		$this->assertSame(['mediaFolderId'], $config['relatedColumns']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\MediaTable::$translate
	 */
	public function testTranslateBehavior(): void {
		$this->assertTrue($this->mediaTable->hasBehavior('Translate'));

		$config = $this->mediaTable->getBehavior('Translate')->getConfig();

		$this->assertSame(Awyiss::REALM_FRONTEND, $config['realm']);

		$this->assertIsArray($config['fields']);
		$this->assertSame(['alt'], $config['fields']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\MediaTable::initializeSchema()
	 */
	public function testInitializeSchemaProcessStatusColumn(): void {
		$schema = $this->mediaTable->getSchema();

		// Test that ProcessStatus columns are configured as enum types
		$this->assertSame('enum-awyiss-model-enum-processstatus', $schema->getColumnType('preview'));
		$this->assertSame('enum-awyiss-model-enum-processstatus', $schema->getColumnType('avif'));
		$this->assertSame('enum-awyiss-model-enum-processstatus', $schema->getColumnType('webp'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\MediaTable::initializeSchema()
	 */
	public function testInitializeSchemaJsonColumns(): void {
		$schema = $this->mediaTable->getSchema();

		// Test that JSON columns are configured correctly
		$this->assertSame('json', $schema->getColumnType('metaData'));
		$this->assertSame('json', $schema->getColumnType('crop'));
	}
}
