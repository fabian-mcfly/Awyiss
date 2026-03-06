<?php

/**
 * @noinspection PhpComposerExtensionStubsInspection
 */


declare(strict_types=1); // phpcs:ignore


namespace Awyiss\Test\TestCase\Event\Backend;


use ArrayObject;
use Awyiss\Configuration\ConfigOptions\MediaConfigOptions;
use Awyiss\Event\Backend\MediaListener;
use Awyiss\Event\EventListenersProvider;
use Awyiss\Model\Entity\Media;
use Awyiss\Model\Table\UrlHistoryTable;
use Awyiss\Test\TestSuite\TestCase;
use Cake\Core\Configure;
use Cake\Datasource\FactoryLocator;
use Cake\Event\Event;
use Cake\I18n\DateTime;
use Cake\ORM\Query\InsertQuery;
use Laminas\Diactoros\UploadedFile;
use Symfony\Component\Process\Process;


/**
 * MediaListener Test Case
 *
 * @see \Awyiss\Event\Backend\MediaListener
 */
class MediaListenerTest extends TestCase {
	/**
	 * @var \Awyiss\Event\Backend\MediaListener
	 */
	protected MediaListener $listener;
	/**
	 * @var string
	 */
	protected string $tmpDir;
	/**
	 * @var array<string>
	 */
	protected array $tmpFiles = [];


	/**
	 * @inheritDoc
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->listener = new MediaListener();

		$this->tmpDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'media_test_' . uniqid();
		if (!is_dir($this->tmpDir)) {
			mkdir($this->tmpDir, 0777, true);
		}
	}


	/**
	 * @inheritDoc
	 */
	protected function tearDown(): void {
		parent::tearDown();

		EventListenersProvider::reset();

		$tableLocator = FactoryLocator::get('Table');
		$tableLocator->clear();

		foreach ($this->tmpFiles as $file) {
			if (file_exists($file)) {
				unlink($file);
			}
		}

		if (is_dir($this->tmpDir)) {
			new Process(['rm', '-r', $this->tmpDir])->run();
		}
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\MediaListener::implementedEvents()
	 */
	public function testImplementedEvents(): void {
		$result = $this->listener->implementedEvents();

		$this->assertSame([
			'Model.Media.beforeSave' => 'beforeSave',
			'Model.Media.afterSave' => 'afterSave',
			'Model.Media.afterDelete' => 'afterDelete',
			'Configuration.Media.Frontend.resizing.fileType.afterSaveCommit' => 'clearMediaCacheAfterSave',
			'Configuration.Media.Frontend.resizing.quality.afterSaveCommit' => 'clearMediaCacheAfterSave',
			'Configuration.Media.Frontend.resizing.fileType.afterDeleteCommit' => 'clearMediaCacheAfterDelete',
			'Configuration.Media.Frontend.resizing.quality.afterDeleteCommit' => 'clearMediaCacheAfterDelete',
		], $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\MediaListener::beforeSave()
	 * @throws \ImagickException
	 */
	public function testBeforeSaveAddsExtensionWhenMissingWithKnownMimeType(): void {
		$mediaTable = $this->fetchTable('Media');
		$entity = $mediaTable->newDefaultEntity([
			'name' => 'test',
			'mimeType' => 'image/jpeg',
			'mediaFolderId' => 1,
		]);

		$event = new Event('Model.Media.beforeSave', $mediaTable);

		$this->listener->beforeSave($event, $entity, new ArrayObject());

		$this->assertSame('jpg', $entity->extension);
		$this->assertSame('test.jpg', $entity->name);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\MediaListener::beforeSave()
	 * @throws \ImagickException
	 */
	public function testBeforeSaveMarksPathAsCleanWhenNotChanged(): void {
		$mediaTable = $this->fetchTable('Media');
		$entity = $mediaTable->newDefaultEntity([
			'name' => 'test.jpg',
			'mimeType' => 'image/jpeg',
			'mediaFolderId' => 1,
			'path' => '../awyiss/Command/Media/TestFiles/test.jpg',
		]);
		$entity->clean();
		$entity->setNew(false);

		$entity->path = 'media/test.jpg';

		$this->assertTrue($entity->isDirty('path'));

		$event = new Event('Model.Media.beforeSave', $mediaTable);

		$this->listener->beforeSave($event, $entity, new ArrayObject());

		$this->assertSame('../awyiss/Command/Media/TestFiles/test.jpg', $entity->path);
		$this->assertFalse($entity->isDirty('path'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\MediaListener::beforeSave()
	 * @throws \ImagickException
	 */
	public function testBeforeSaveStopsEventWhenExtensionMissingAndUnknownMimeType(): void {
		$mediaTable = $this->fetchTable('Media');
		$entity = $mediaTable->newDefaultEntity([
			'name' => 'test',
			'mimeType' => 'application/unknown',
			'mediaFolderId' => 1,
		]);

		$event = new Event('Model.Media.beforeSave', $mediaTable);

		$this->listener->beforeSave($event, $entity, new ArrayObject());

		$this->assertTrue($event->isStopped());
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('name', $errors);
		$this->assertSame(['media::error_media_has_file_extension'], $errors['name']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\MediaListener::beforeSave()
	 * @throws \ImagickException
	 */
	public function testBeforeSaveSetsPathFromMediaFolder(): void {
		$mediaFoldersTable = $this->fetchTable('MediaFolders');
		$folder = $mediaFoldersTable->newDefaultEntity([
			'title' => 'Test Folder',
			'path' => 'media/test-folder',
		]);
		$mediaFoldersTable->save($folder);

		$mediaTable = $this->fetchTable('Media');
		$entity = $mediaTable->newDefaultEntity([
			'name' => 'test.jpg',
			'mimeType' => 'image/jpeg',
			'mediaFolderId' => $folder->id,
		]);

		$event = new Event('Model.Media.beforeSave', $mediaTable);

		$this->listener->beforeSave($event, $entity, new ArrayObject());

		$this->assertSame('media/test-folder/test.jpg', $entity->path);

		$mediaFoldersTable->deleteAll(['id' => $folder->id]);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\MediaListener::beforeSave()
	 * @throws \ImagickException
	 */
	public function testBeforeSaveSetsPathToMediaForUnknownFolder(): void {
		$mediaTable = $this->fetchTable('Media');
		$entity = $mediaTable->newDefaultEntity([
			'name' => 'test.jpg',
			'mimeType' => 'image/jpeg',
			'mediaFolderId' => 999,
		]);

		$event = new Event('Model.Media.beforeSave', $mediaTable);

		$this->listener->beforeSave($event, $entity, new ArrayObject());

		$this->assertSame('media/test.jpg', $entity->path);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\MediaListener::beforeSave()
	 * @throws \ImagickException
	 */
	public function testBeforeSaveSetsDimensionsForImage(): void {
		$tempFile = $this->createTempImageFile();
		$uploadedFile = new UploadedFile($tempFile, 100, UPLOAD_ERR_OK, 'test.jpg', 'image/jpeg');

		$mediaTable = $this->fetchTable('Media');
		$entity = $mediaTable->newDefaultEntity([
			'name' => 'test.jpg',
			'mimeType' => 'image/jpeg',
			'mediaFolderId' => 1,
			'file' => $uploadedFile,
		]);

		$event = new Event('Model.Media.beforeSave', $mediaTable);

		$this->listener->beforeSave($event, $entity, new ArrayObject());

		$this->assertSame(10.0, $entity->width);
		$this->assertSame(10.0, $entity->height);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\MediaListener::beforeSave()
	 * @throws \ImagickException
	 */
	public function testBeforeSaveSetsDimensionsForSvg(): void {
		$mediaTable = $this->fetchTable('Media');
		$event = new Event('Model.Media.beforeSave', $mediaTable);

		$svgContent = '<svg width="123" height="345" viewBox="0 0 234 456"><rect width="100" height="200" fill="red"/></svg>';
		$tempFile = $this->createTempFile($svgContent, 'test.svg');
		$uploadedFile = new UploadedFile($tempFile, strlen($svgContent), UPLOAD_ERR_OK, 'test.svg', 'image/svg+xml');

		$entity = $mediaTable->newDefaultEntity([
			'name' => 'test.svg',
			'mimeType' => 'image/svg+xml',
			'mediaFolderId' => 1,
			'file' => $uploadedFile,
		]);

		$this->listener->beforeSave($event, $entity, new ArrayObject());

		$this->assertSame(123.0, $entity->width);
		$this->assertSame(345.0, $entity->height);

		$svgContent = '<svg width="123" viewBox="0 0 234 456"><rect width="100" height="200" fill="red"/></svg>';
		$tempFile = $this->createTempFile($svgContent, 'test.svg');
		$uploadedFile = new UploadedFile($tempFile, strlen($svgContent), UPLOAD_ERR_OK, 'test.svg', 'image/svg+xml');

		$entity = $mediaTable->newDefaultEntity([
			'name' => 'test.svg',
			'mimeType' => 'image/svg+xml',
			'mediaFolderId' => 1,
			'file' => $uploadedFile,
		]);

		$this->listener->beforeSave($event, $entity, new ArrayObject());

		$this->assertSame(234.0, $entity->width);
		$this->assertSame(456.0, $entity->height);

		$svgContent = '<svg height="345" viewBox="0 0 234 456"><rect width="100" height="200" fill="red"/></svg>';
		$tempFile = $this->createTempFile($svgContent, 'test.svg');
		$uploadedFile = new UploadedFile($tempFile, strlen($svgContent), UPLOAD_ERR_OK, 'test.svg', 'image/svg+xml');

		$entity = $mediaTable->newDefaultEntity([
			'name' => 'test.svg',
			'mimeType' => 'image/svg+xml',
			'mediaFolderId' => 1,
			'file' => $uploadedFile,
		]);

		$this->listener->beforeSave($event, $entity, new ArrayObject());

		$this->assertSame(234.0, $entity->width);
		$this->assertSame(456.0, $entity->height);

		$svgContent = '<svg><rect width="100" height="200" fill="red"/></svg>';
		$tempFile = $this->createTempFile($svgContent, 'test.svg');
		$uploadedFile = new UploadedFile($tempFile, strlen($svgContent), UPLOAD_ERR_OK, 'test.svg', 'image/svg+xml');

		$entity = $mediaTable->newDefaultEntity([
			'name' => 'test.svg',
			'mimeType' => 'image/svg+xml',
			'mediaFolderId' => 1,
			'file' => $uploadedFile,
		]);

		$this->listener->beforeSave($event, $entity, new ArrayObject());

		$this->assertNull($entity->width);
		$this->assertNull($entity->height);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\MediaListener::beforeSave()
	 * @throws \ImagickException
	 */
	public function testBeforeSaveSetsAutoRotateForImage(): void {
		$tempFile = $this->createTempImageFile();
		$uploadedFile = new UploadedFile($tempFile, 100, UPLOAD_ERR_OK, 'test.jpg', 'image/jpeg');

		$mediaTable = $this->fetchTable('Media');
		$entity = $mediaTable->newDefaultEntity([
			'name' => 'test.jpg',
			'mimeType' => 'image/jpeg',
			'mediaFolderId' => 1,
			'file' => $uploadedFile,
		]);

		$event = new Event('Model.Media.beforeSave', $mediaTable);

		$this->listener->beforeSave($event, $entity, new ArrayObject());

		$this->assertSame('auto', $entity->crop['rotate']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\MediaListener::beforeSave()
	 * @throws \ImagickException
	 */
	public function testBeforeSaveSetsNotAutoRotateForImageWhenCropSet(): void {
		$tempFile = $this->createTempImageFile();
		$uploadedFile = new UploadedFile($tempFile, 100, UPLOAD_ERR_OK, 'test.jpg', 'image/jpeg');

		$mediaTable = $this->fetchTable('Media');
		$entity = $mediaTable->newDefaultEntity([
			'name' => 'test.jpg',
			'mimeType' => 'image/jpeg',
			'mediaFolderId' => 1,
			'file' => $uploadedFile,
			'crop' => ['x' => 0, 'y' => 0, 'width' => 10, 'height' => 10, 'rotate' => 90],
		]);

		$event = new Event('Model.Media.beforeSave', $mediaTable);

		$this->listener->beforeSave($event, $entity, new ArrayObject());

		$this->assertSame(90, $entity->crop['rotate']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\MediaListener::beforeSave()
	 * @throws \ImagickException
	 */
	public function testBeforeSaveNotSetsAutoRotateForSvg(): void {
		$svgContent = '<svg width="100" height="50"><rect width="100" height="200" fill="red"/></svg>';
		$tempFile = $this->createTempFile($svgContent, 'test.svg');
		$uploadedFile = new UploadedFile($tempFile, strlen($svgContent), UPLOAD_ERR_OK, 'test.svg', 'image/svg+xml');

		$mediaTable = $this->fetchTable('Media');
		$entity = $mediaTable->newDefaultEntity([
			'name' => 'test.svg',
			'mimeType' => 'image/svg+xml',
			'mediaFolderId' => 1,
			'file' => $uploadedFile,
		]);

		$event = new Event('Model.Media.beforeSave', $mediaTable);

		$this->listener->beforeSave($event, $entity, new ArrayObject());

		$this->assertNull($entity->crop);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\MediaListener::beforeSave()
	 * @throws \ImagickException
	 */
	public function testBeforeSaveNotSetsAutoRotateForNonImage(): void {
		$tempFile = $this->createTempFile('document content', 'test.pdf');
		$uploadedFile = new UploadedFile($tempFile, 16, UPLOAD_ERR_OK, 'test.pdf', 'application/pdf');

		$mediaTable = $this->fetchTable('Media');
		$entity = $mediaTable->newDefaultEntity([
			'name' => 'test.pdf',
			'mimeType' => 'application/pdf',
			'mediaFolderId' => 1,
			'file' => $uploadedFile,
		]);

		$event = new Event('Model.Media.beforeSave', $mediaTable);

		$this->listener->beforeSave($event, $entity, new ArrayObject());

		$this->assertNull($entity->crop);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\MediaListener::beforeSave()
	 * @throws \ImagickException
	 */
	public function testBeforeSaveNotSetsDimensionsNonImage(): void {
		$tempFile = $this->createTempFile('document content', 'test.pdf');
		$uploadedFile = new UploadedFile($tempFile, 16, UPLOAD_ERR_OK, 'test.pdf', 'application/pdf');

		$mediaTable = $this->fetchTable('Media');
		$entity = $mediaTable->newDefaultEntity([
			'name' => 'test.pdf',
			'mimeType' => 'application/pdf',
			'mediaFolderId' => 1,
			'file' => $uploadedFile,
		]);

		$event = new Event('Model.Media.beforeSave', $mediaTable);

		$this->listener->beforeSave($event, $entity, new ArrayObject());

		$this->assertNull($entity->width);
		$this->assertNull($entity->height);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\MediaListener::beforeSave()
	 * @throws \Exception
	 * @throws \ImagickException
	 */
	public function testBeforeSaveUsesExistingDataWithFileAndAutoOverwriteTrue(): void {
		Configure::write('Awyiss.Media.Backend.upload.autoOverwrite', true);

		$mediaTable = $this->fetchTable('Media');

		$media = [
			$media1 = $mediaTable->newDefaultEntity([
				'name' => 'test.jpg',
				'mimeType' => 'image/jpeg',
				'mediaFolderId' => 1,
				'path' => '../awyiss/Command/Media/TestFiles/test.jpg',
			]),
		];
		$media1->alt = 'Existing alt text';
		$media1->createdBy = 123;
		$media1->createdOn = '2023-01-01 12:00:00';
		$result = $mediaTable->saveMany($media, [
			'checkRules' => false,
			'audit' => ['skip' => true],
			'systemOrder' => ['skip' => true],
		]);

		$this->assertNotFalse($result);

		$tempFile = $this->createTempImageFile();
		$uploadedFile = new UploadedFile($tempFile, 7, UPLOAD_ERR_OK, 'test.jpg', 'image/jpeg');

		$media2 = $mediaTable->newDefaultEntity([
			'name' => 'test.jpg',
			'mimeType' => 'image/jpg',
			'mediaFolderId' => 1,
			'path' => '../awyiss/Command/Media/TestFiles/test.jpg',
			'file' => $uploadedFile,
		]);

		$event = new Event('Model.Media.beforeSave', $mediaTable);

		$this->listener->beforeSave($event, $media2, new ArrayObject());

		$this->assertSame('test.jpg', $media2->name);
		$this->assertSame('../awyiss/Command/Media/TestFiles/test.jpg', $media2->path);
		$this->assertSame('Existing alt text', $media2->alt);
		$this->assertSame(123, $media2->createdBy);
		$this->assertEquals(new DateTime('2023-01-01 12:00:00'), $media2->createdOn);

		$mediaTable->deleteAll(['id IN' => [$media1->id, $media2->id]]);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\MediaListener::beforeSave()
	 * @throws \Exception
	 * @throws \ImagickException
	 */
	public function testBeforeSaveEnsuresUniqueFileNameWithFileAndAutoOverwriteFalse(): void {
		Configure::write('Awyiss.Media.Backend.upload.autoOverwrite', false);

		$mediaTable = $this->fetchTable('Media');

		$media = [
			$media1 = $mediaTable->newDefaultEntity([
				'name' => 'test.jpg',
				'mimeType' => 'image/jpeg',
				'mediaFolderId' => 1,
				'path' => '../awyiss/Command/Media/TestFiles/test.jpg',
			]),
		];
		$media1->alt = 'Existing alt text';
		$media1->createdBy = 123;
		$media1->createdOn = '2023-01-01 12:00:00';
		$result = $mediaTable->saveMany($media, ['checkRules' => false, 'audit' => ['skip' => true]]);

		$this->assertNotFalse($result);

		$tempFile = $this->createTempImageFile();
		$uploadedFile = new UploadedFile($tempFile, 7, UPLOAD_ERR_OK, 'test.jpg', 'image/jpeg');

		$media2 = $mediaTable->newDefaultEntity([
			'name' => 'test.jpg',
			'mimeType' => 'image/jpeg',
			'mediaFolderId' => 1,
			'path' => '../awyiss/Command/Media/TestFiles/test.jpg',
			'file' => $uploadedFile,
		]);

		$event = new Event('Model.Media.beforeSave', $mediaTable);

		$this->listener->beforeSave($event, $media2, new ArrayObject());

		$this->assertNull($media2->id);
		$this->assertSame('test-2.jpg', $media2->name);
		$this->assertSame('../awyiss/Command/Media/TestFiles/test-2.jpg', $media2->path);
		$this->assertNull($media2->alt);
		$this->assertNull($media2->createdBy);
		$this->assertNull($media2->createdOn);

		$mediaTable->deleteAll(['id IN' => [$media1->id, $media2->id]]);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\MediaListener::beforeSave()
	 * @throws \Exception
	 * @throws \ImagickException
	 */
	public function testBeforeSaveEnsuresUniqueFileNameWithFileAndAutoOverwriteTrueAndFileNotNew(): void {
		Configure::write('Awyiss.Media.Backend.upload.autoOverwrite', true);

		$mediaTable = $this->fetchTable('Media');

		$media = [
			$media1 = $mediaTable->newDefaultEntity([
				'name' => 'test.jpg',
				'mimeType' => 'image/jpeg',
				'mediaFolderId' => 1,
				'path' => '../awyiss/Command/Media/TestFiles/test.jpg',
			]),
		];
		$result = $mediaTable->saveMany($media, ['checkRules' => false, 'audit' => ['skip' => true]]);

		$this->assertNotFalse($result);

		$tempFile = $this->createTempImageFile();
		$uploadedFile = new UploadedFile($tempFile, 7, UPLOAD_ERR_OK, 'test.jpg', 'image/jpeg');

		$media2 = $mediaTable->newDefaultEntity([
			'name' => 'test.jpg',
			'mimeType' => 'image/jpeg',
			'mediaFolderId' => 1,
			'path' => '../awyiss/Command/Media/TestFiles/test.jpg',
			'file' => $uploadedFile,
		]);
		$media2->setNew(false);

		$event = new Event('Model.Media.beforeSave', $mediaTable);

		$this->listener->beforeSave($event, $media2, new ArrayObject());

		$this->assertNull($media2->id);
		$this->assertSame('test-2.jpg', $media2->name);
		$this->assertSame('../awyiss/Command/Media/TestFiles/test-2.jpg', $media2->path);

		$mediaTable->deleteAll(['id IN' => [$media1->id, $media2->id]]);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\MediaListener::beforeSave()
	 * @throws \Exception
	 * @throws \ImagickException
	 */
	public function testBeforeSaveEnsuresUniqueFileNameWithoutFileAndNameDirty(): void {
		Configure::write('Awyiss.Media.Backend.upload.autoOverwrite', true);

		$mediaTable = $this->fetchTable('Media');

		$media = [
			$media1 = $mediaTable->newDefaultEntity([
				'name' => 'test.jpg',
				'mimeType' => 'image/jpeg',
				'mediaFolderId' => 1,
				'path' => '../awyiss/Command/Media/TestFiles/test.jpg',
			]),
			$media2 = $mediaTable->newDefaultEntity([
				'name' => 'other-test.jpg',
				'mimeType' => 'image/jpeg',
				'mediaFolderId' => 1,
				'path' => '../awyiss/Command/Media/TestFiles/other-test.jpg',
			]),
		];
		$result = $mediaTable->saveMany($media, ['checkRules' => false, 'audit' => ['skip' => true]]);

		$this->assertNotFalse($result);

		$media2->name = 'test.jpg';

		$event = new Event('Model.Media.beforeSave', $mediaTable);

		$this->listener->beforeSave($event, $media2, new ArrayObject());

		$this->assertSame('test-2.jpg', $media2->name);
		$this->assertSame('../awyiss/Command/Media/TestFiles/test-2.jpg', $media2->path);

		$mediaTable->deleteAll(['id IN' => [$media1->id, $media2->id]]);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\MediaListener::beforeSave()
	 * @throws \Exception
	 * @throws \ImagickException
	 */
	public function testBeforeSaveEnsuresUniqueFileNameWithoutFileAndFolderIdDirty(): void {
		Configure::write('Awyiss.Media.Backend.upload.autoOverwrite', true);

		$mediaTable = $this->fetchTable('Media');

		$media = [
			$media1 = $mediaTable->newDefaultEntity([
				'name' => 'test.jpg',
				'mimeType' => 'image/jpeg',
				'mediaFolderId' => 1,
				'path' => '../awyiss/Command/Media/TestFiles/test.jpg',
			]),
			$media2 = $mediaTable->newDefaultEntity([
				'name' => 'test.jpg',
				'mimeType' => 'image/jpeg',
				'mediaFolderId' => 2,
				'path' => '../awyiss/Command/Media/TestFiles/test.jpg',
			]),
		];
		$result = $mediaTable->saveMany($media, ['checkRules' => false, 'audit' => ['skip' => true]]);

		$this->assertNotFalse($result);

		$media2->mediaFolderId = 1;

		$event = new Event('Model.Media.beforeSave', $mediaTable);

		$this->listener->beforeSave($event, $media2, new ArrayObject());

		$this->assertSame('test-2.jpg', $media2->name);
		$this->assertSame('../awyiss/Command/Media/TestFiles/test-2.jpg', $media2->path);

		$mediaTable->deleteAll(['id IN' => [$media1->id, $media2->id]]);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\MediaListener::beforeSave()
	 * @throws \Exception
	 * @throws \ImagickException
	 */
	public function testBeforeSaveEnsuresUniqueFileNameWithFileUploadError(): void {
		Configure::write('Awyiss.Media.Backend.upload.autoOverwrite', true);

		$mediaTable = $this->fetchTable('Media');

		$media = [
			$media1 = $mediaTable->newDefaultEntity([
				'name' => 'test.jpg',
				'mimeType' => 'image/jpeg',
				'mediaFolderId' => 1,
				'path' => '../awyiss/Command/Media/TestFiles/test.jpg',
			]),
		];
		$media1->alt = 'Existing alt text';
		$media1->createdBy = 123;
		$media1->createdOn = '2023-01-01 12:00:00';
		$result = $mediaTable->saveMany($media, [
			'checkRules' => false,
			'audit' => ['skip' => true],
			'systemOrder' => ['skip' => true],
		]);

		$this->assertNotFalse($result);

		$tempFile = $this->createTempImageFile();
		$uploadedFile = new UploadedFile($tempFile, 7, UPLOAD_ERR_CANT_WRITE, 'test.jpg', 'image/jpeg');

		$media2 = $mediaTable->newDefaultEntity([
			'name' => 'test.jpg',
			'mimeType' => 'image/jpg',
			'mediaFolderId' => 1,
			'path' => '../awyiss/Command/Media/TestFiles/test.jpg',
			'file' => $uploadedFile,
		]);

		$event = new Event('Model.Media.beforeSave', $mediaTable);

		$this->listener->beforeSave($event, $media2, new ArrayObject());

		$this->assertSame('test-2.jpg', $media2->name);
		$this->assertSame('../awyiss/Command/Media/TestFiles/test-2.jpg', $media2->path);
		$this->assertNull($media2->alt);
		$this->assertNull($media2->createdBy);
		$this->assertNull($media2->createdOn);

		$mediaTable->deleteAll(['id IN' => [$media1->id, $media2->id]]);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\PagesListener::beforeSave()
	 * @throws \Exception
	 * @throws \ImagickException
	 */
	public function testBeforeSaveEnsuringUniqueFileNameNeverExceedsMaxLength(): void {
		Configure::write('Awyiss.Media.Backend.upload.autoOverwrite', true);

		$longName = substr(str_repeat('dummyfile', 11), 0, 96) . '.jpg';

		$mediaTable = $this->fetchTable('Media');

		$media = [
			$media1 = $mediaTable->newDefaultEntity([
				'name' => $longName,
				'mimeType' => 'image/jpeg',
				'mediaFolderId' => 1,
				'path' => '../awyiss/Command/Media/TestFiles/' . $longName,
			]),
			$media2 = $mediaTable->newDefaultEntity([
				'name' => $longName,
				'mimeType' => 'image/jpeg',
				'mediaFolderId' => 2,
				'path' => '../awyiss/Command/Media/TestFiles/' . $longName,
			]),
		];
		$result = $mediaTable->saveMany($media, ['checkRules' => false, 'audit' => ['skip' => true]]);

		$this->assertNotFalse($result);

		$media2->mediaFolderId = 1;

		$event = new Event('Model.Media.beforeSave', $mediaTable);

		$this->listener->beforeSave($event, $media2, new ArrayObject());

		$this->assertEquals(100, strlen($media2->name));
		$this->assertStringStartsWith('dummyfile', $media2->name);
		$this->assertStringEndsWith('dumm-2.jpg', $media2->name);
		$this->assertSame('../awyiss/Command/Media/TestFiles/dummyfiledummyfiledummyfiledummyfiledummyfiledummyfiledummyfiledummyfiledummyfiledummyfiledumm-2.jpg', $media2->path);

		$mediaTable->deleteAll(['id IN' => [$media1->id, $media2->id]]);
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\MediaListener::afterSave()
	 */
	public function testAfterSaveUnlinksFileWhenWhenFileUploadedAndPathChanged(): void {
		$mediaTable = $this->fetchTable('Media');
		$event = new Event('Model.Media.afterSave', $mediaTable);

		file_put_contents(WWW_ROOT . 'media' . DS . 'test.jpg', 'test');
		$this->assertFileExists(WWW_ROOT . 'media' . DS . 'test.jpg');

		$mockFile = $this->createMock(UploadedFile::class);
		$mockFile->method('getError')->willReturn(UPLOAD_ERR_OK);
		$mockFile->expects($this->once())->method('moveTo')->with(WWW_ROOT . 'media' . DS . 'new-test.jpg');

		$mockEntity = $this->getMockBuilder(Media::class)->onlyMethods([
			'deleteConvertedFiles',
			'moveConvertedFiles',
		])->getMock();
		$mockEntity->id = 123;
		$mockEntity->file = $mockFile;
		$mockEntity->name = 'test.jpg';
		$mockEntity->path = 'media/test.jpg';

		$mockEntity->clean();
		$mockEntity->path = 'media/new-test.jpg';

		$this->listener->afterSave($event, $mockEntity, new ArrayObject());

		$this->assertFileDoesNotExist(WWW_ROOT . 'media' . DS . 'test.jpg');
		$this->assertFileDoesNotExist(WWW_ROOT . 'media' . DS . 'new-test.jpg');
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\MediaListener::afterSave()
	 */
	public function testAfterSaveNotUnlinksFileWhenWhenFileUploadedAndPathNotChanged(): void {
		$mediaTable = $this->fetchTable('Media');
		$event = new Event('Model.Media.afterSave', $mediaTable);

		file_put_contents(WWW_ROOT . 'media' . DS . 'test.jpg', 'test');
		$this->assertFileExists(WWW_ROOT . 'media' . DS . 'test.jpg');

		$mockFile = $this->createMock(UploadedFile::class);
		$mockFile->method('getError')->willReturn(UPLOAD_ERR_OK);
		$mockFile->expects($this->once())->method('moveTo')->with(WWW_ROOT . 'media' . DS . 'test.jpg');

		$mockEntity = $this->getMockBuilder(Media::class)->onlyMethods([
			'deleteConvertedFiles',
			'moveConvertedFiles',
		])->getMock();
		$mockEntity->id = 123;
		$mockEntity->file = $mockFile;
		$mockEntity->name = 'test.jpg';
		$mockEntity->path = 'media/test.jpg';

		$mockEntity->clean();
		/** @noinspection PhpFieldImmediatelyRewrittenInspection */
		$mockEntity->path = 'media/new-test.jpg';
		$mockEntity->path = 'media/test.jpg';

		$this->listener->afterSave($event, $mockEntity, new ArrayObject());

		$this->assertFileExists(WWW_ROOT . 'media' . DS . 'test.jpg');
		$this->assertFileDoesNotExist(WWW_ROOT . 'media' . DS . 'new-test.jpg');
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\MediaListener::afterSave()
	 */
	public function testAfterSaveMovesFileWhenFileErrorAndPathChanged(): void {
		$mediaTable = $this->fetchTable('Media');
		$event = new Event('Model.Media.afterSave', $mediaTable);

		file_put_contents(WWW_ROOT . 'media' . DS . 'test.jpg', 'test');
		$this->assertFileExists(WWW_ROOT . 'media' . DS . 'test.jpg');

		$mockFile = $this->createMock(UploadedFile::class);
		$mockFile->method('getError')->willReturn(UPLOAD_ERR_CANT_WRITE);
		$mockFile->expects($this->never())->method('moveTo');

		$mockEntity = $this->getMockBuilder(Media::class)->onlyMethods([
			'deleteConvertedFiles',
			'moveConvertedFiles',
		])->getMock();
		$mockEntity->id = 123;
		$mockEntity->file = null;
		$mockEntity->name = 'test.jpg';
		$mockEntity->path = 'media/test.jpg';

		$mockEntity->clean();
		$mockEntity->setNew(false);
		$mockEntity->path = 'media/new-test.jpg';

		$this->listener->afterSave($event, $mockEntity, new ArrayObject());

		$this->assertFileDoesNotExist(WWW_ROOT . 'media' . DS . 'test.jpg');
		$this->assertFileExists(WWW_ROOT . 'media' . DS . 'new-test.jpg');

		unlink(WWW_ROOT . 'media' . DS . 'new-test.jpg');
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\MediaListener::afterSave()
	 */
	public function testAfterSaveNotMovesFileWhenFileErrorAndPathUnhanged(): void {
		$mediaTable = $this->fetchTable('Media');
		$event = new Event('Model.Media.afterSave', $mediaTable);

		file_put_contents(WWW_ROOT . 'media' . DS . 'test.jpg', 'test');
		$this->assertFileExists(WWW_ROOT . 'media' . DS . 'test.jpg');

		$mockFile = $this->createMock(UploadedFile::class);
		$mockFile->method('getError')->willReturn(UPLOAD_ERR_CANT_WRITE);
		$mockFile->expects($this->never())->method('moveTo');

		$mockEntity = $this->getMockBuilder(Media::class)->onlyMethods([
			'deleteConvertedFiles',
			'moveConvertedFiles',
		])->getMock();
		$mockEntity->id = 123;
		$mockEntity->file = null;
		$mockEntity->name = 'test.jpg';
		$mockEntity->path = 'media/test.jpg';

		$mockEntity->clean();
		$mockEntity->setNew(false);
		/** @noinspection PhpFieldImmediatelyRewrittenInspection */
		$mockEntity->path = 'media/new-test.jpg';
		$mockEntity->path = 'media/test.jpg';

		$this->listener->afterSave($event, $mockEntity, new ArrayObject());

		$this->assertFileExists(WWW_ROOT . 'media' . DS . 'test.jpg');
		$this->assertFileDoesNotExist(WWW_ROOT . 'media' . DS . 'new-test.jpg');

		unlink(WWW_ROOT . 'media' . DS . 'test.jpg');
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\MediaListener::afterSave()
	 */
	public function testAfterSaveMovesFileWhenNoFileAndPathChanged(): void {
		$mediaTable = $this->fetchTable('Media');
		$event = new Event('Model.Media.afterSave', $mediaTable);

		file_put_contents(WWW_ROOT . 'media' . DS . 'test.jpg', 'test');
		$this->assertFileExists(WWW_ROOT . 'media' . DS . 'test.jpg');

		$mockEntity = $this->getMockBuilder(Media::class)->onlyMethods([
			'deleteConvertedFiles',
			'moveConvertedFiles',
		])->getMock();
		$mockEntity->id = 123;
		$mockEntity->file = null;
		$mockEntity->name = 'test.jpg';
		$mockEntity->path = 'media/test.jpg';

		$mockEntity->clean();
		$mockEntity->setNew(false);
		$mockEntity->name = 'new-test.jpg';
		$mockEntity->path = 'media/new-test.jpg';

		$this->listener->afterSave($event, $mockEntity, new ArrayObject());

		$this->assertFileDoesNotExist(WWW_ROOT . 'media' . DS . 'test.jpg');
		$this->assertFileExists(WWW_ROOT . 'media' . DS . 'new-test.jpg');

		unlink(WWW_ROOT . 'media' . DS . 'new-test.jpg');
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\MediaListener::afterSave()
	 */
	public function testAfterSaveNotMovesFileWhenNoFileAndPathUnhanged(): void {
		$mediaTable = $this->fetchTable('Media');
		$event = new Event('Model.Media.afterSave', $mediaTable);

		file_put_contents(WWW_ROOT . 'media' . DS . 'test.jpg', 'test');
		$this->assertFileExists(WWW_ROOT . 'media' . DS . 'test.jpg');

		$mockEntity = $this->getMockBuilder(Media::class)->onlyMethods([
			'deleteConvertedFiles',
			'moveConvertedFiles',
		])->getMock();
		$mockEntity->id = 123;
		$mockEntity->file = null;
		$mockEntity->name = 'test.jpg';
		$mockEntity->path = 'media/test.jpg';

		$mockEntity->clean();
		$mockEntity->setNew(false);
		/** @noinspection PhpFieldImmediatelyRewrittenInspection */
		$mockEntity->path = 'media/new-test.jpg';
		$mockEntity->path = 'media/test.jpg';

		$this->listener->afterSave($event, $mockEntity, new ArrayObject());

		$this->assertFileExists(WWW_ROOT . 'media' . DS . 'test.jpg');
		$this->assertFileDoesNotExist(WWW_ROOT . 'media' . DS . 'new-test.jpg');

		unlink(WWW_ROOT . 'media' . DS . 'test.jpg');
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\MediaListener::afterSave()
	 */
	public function testAfterSaveDeletesConvertedFileWhenFileUploaded(): void {
		$mediaTable = $this->fetchTable('Media');
		$event = new Event('Model.Media.afterSave', $mediaTable);

		$mockFile = $this->createMock(UploadedFile::class);
		$mockFile->method('getError')->willReturn(UPLOAD_ERR_OK);
		$mockFile->expects($this->once())->method('moveTo')->with(WWW_ROOT . 'media' . DS . 'test.jpg');

		$mockEntity = $this->getMockBuilder(Media::class)->onlyMethods([
			'deleteConvertedFiles',
			'moveConvertedFiles',
		])->getMock();
		$mockEntity->id = 123;
		$mockEntity->file = $mockFile;
		$mockEntity->name = 'test.jpg';
		$mockEntity->path = 'media/test.jpg';

		$mockEntity->setNew(false);

		$mockEntity->expects($this->once())->method('deleteConvertedFiles');
		$mockEntity->expects($this->never())->method('moveConvertedFiles');

		$this->listener->afterSave($event, $mockEntity, new ArrayObject());
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\MediaListener::afterSave()
	 */
	public function testAfterSaveNotDeletesConvertedFileWhenFileError(): void {
		$mediaTable = $this->fetchTable('Media');
		$event = new Event('Model.Media.afterSave', $mediaTable);

		$mockFile = $this->createMock(UploadedFile::class);
		$mockFile->method('getError')->willReturn(UPLOAD_ERR_CANT_WRITE);
		$mockFile->expects($this->never())->method('moveTo');

		$mockEntity = $this->getMockBuilder(Media::class)->onlyMethods([
			'deleteConvertedFiles',
			'moveConvertedFiles',
		])->getMock();
		$mockEntity->id = 123;
		$mockEntity->file = $mockFile;
		$mockEntity->name = 'test.jpg';
		$mockEntity->path = 'media/test.jpg';

		$mockEntity->setNew(false);

		$mockEntity->expects($this->never())->method('deleteConvertedFiles');
		$mockEntity->expects($this->never())->method('moveConvertedFiles');

		$this->listener->afterSave($event, $mockEntity, new ArrayObject());
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\MediaListener::afterSave()
	 */
	public function testAfterSaveMovesConvertedFileWhenNoFileAndPathChanged(): void {
		$mediaTable = $this->fetchTable('Media');
		$event = new Event('Model.Media.afterSave', $mediaTable);

		file_put_contents(WWW_ROOT . 'media' . DS . 'test.jpg', 'test');
		$this->assertFileExists(WWW_ROOT . 'media' . DS . 'test.jpg');

		$mockEntity = $this->getMockBuilder(Media::class)->onlyMethods([
			'deleteConvertedFiles',
			'moveConvertedFiles',
		])->getMock();
		$mockEntity->id = 123;
		$mockEntity->file = null;
		$mockEntity->name = 'test.jpg';
		$mockEntity->path = 'media/test.jpg';

		$mockEntity->clean();
		$mockEntity->setNew(false);
		$mockEntity->name = 'new-test.jpg';
		$mockEntity->path = 'media/new-test.jpg';

		$mockEntity->expects($this->never())->method('deleteConvertedFiles');
		$mockEntity->expects($this->once())->method('moveConvertedFiles');

		$this->listener->afterSave($event, $mockEntity, new ArrayObject());

		if (file_exists(WWW_ROOT . 'media' . DS . 'test.jpg')) {
			unlink(WWW_ROOT . 'media' . DS . 'test.jpg');
		}
		if (file_exists(WWW_ROOT . 'media' . DS . 'new-test.jpg')) {
			unlink(WWW_ROOT . 'media' . DS . 'new-test.jpg');
		}
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\MediaListener::afterSave()
	 */
	public function testAfterSaveNotMovesConvertedFileWhenNoFileAndPathUnchanged(): void {
		$mediaTable = $this->fetchTable('Media');
		$event = new Event('Model.Media.afterSave', $mediaTable);

		$mockEntity = $this->getMockBuilder(Media::class)->onlyMethods([
			'deleteConvertedFiles',
			'moveConvertedFiles',
		])->getMock();
		$mockEntity->id = 123;
		$mockEntity->file = null;
		$mockEntity->name = 'test.jpg';
		$mockEntity->path = 'media/test.jpg';

		$mockEntity->clean();
		$mockEntity->setNew(false);
		/** @noinspection PhpFieldImmediatelyRewrittenInspection */
		$mockEntity->path = 'media/new-test.jpg';
		$mockEntity->path = 'media/test.jpg';

		$mockEntity->expects($this->never())->method('deleteConvertedFiles');
		$mockEntity->expects($this->never())->method('moveConvertedFiles');

		$this->listener->afterSave($event, $mockEntity, new ArrayObject());
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\MediaListener::afterSave()
	 */
	public function testAfterSaveDeletesResizedFileWhenFileUploaded(): void {
		$mediaTable = $this->fetchTable('Media');
		$event = new Event('Model.Media.afterSave', $mediaTable);

		$mockFile = $this->createMock(UploadedFile::class);
		$mockFile->method('getError')->willReturn(UPLOAD_ERR_OK);
		$mockFile->expects($this->once())->method('moveTo')->with(WWW_ROOT . 'media' . DS . 'test.jpg');

		$mockEntity = $this->getMockBuilder(Media::class)->onlyMethods([
			'deleteResizedFiles',
			'moveResizedFiles',
		])->getMock();
		$mockEntity->id = 123;
		$mockEntity->file = $mockFile;
		$mockEntity->name = 'test.jpg';
		$mockEntity->path = 'media/test.jpg';

		$mockEntity->setNew(false);

		$mockEntity->expects($this->once())->method('deleteResizedFiles');
		$mockEntity->expects($this->never())->method('moveResizedFiles');

		$this->listener->afterSave($event, $mockEntity, new ArrayObject());
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\MediaListener::afterSave()
	 */
	public function testAfterSaveNotDeletesResizedFileWhenFileError(): void {
		$mediaTable = $this->fetchTable('Media');
		$event = new Event('Model.Media.afterSave', $mediaTable);

		$mockFile = $this->createMock(UploadedFile::class);
		$mockFile->method('getError')->willReturn(UPLOAD_ERR_CANT_WRITE);
		$mockFile->expects($this->never())->method('moveTo');

		$mockEntity = $this->getMockBuilder(Media::class)->onlyMethods([
			'deleteResizedFiles',
			'moveResizedFiles',
		])->getMock();
		$mockEntity->id = 123;
		$mockEntity->file = $mockFile;
		$mockEntity->name = 'test.jpg';
		$mockEntity->path = 'media/test.jpg';

		$mockEntity->setNew(false);

		$mockEntity->expects($this->never())->method('deleteResizedFiles');
		$mockEntity->expects($this->never())->method('moveResizedFiles');

		$this->listener->afterSave($event, $mockEntity, new ArrayObject());
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\MediaListener::afterSave()
	 */
	public function testAfterSaveMovesResizedFileWhenNoFileAndPathChanged(): void {
		$mediaTable = $this->fetchTable('Media');
		$event = new Event('Model.Media.afterSave', $mediaTable);

		file_put_contents(WWW_ROOT . 'media' . DS . 'test.jpg', 'test');
		$this->assertFileExists(WWW_ROOT . 'media' . DS . 'test.jpg');

		$mockEntity = $this->getMockBuilder(Media::class)->onlyMethods([
			'deleteResizedFiles',
			'moveResizedFiles',
		])->getMock();
		$mockEntity->id = 123;
		$mockEntity->file = null;
		$mockEntity->name = 'test.jpg';
		$mockEntity->path = 'media/test.jpg';

		$mockEntity->clean();
		$mockEntity->setNew(false);
		$mockEntity->name = 'new-test.jpg';
		$mockEntity->path = 'media/new-test.jpg';

		$mockEntity->expects($this->never())->method('deleteResizedFiles');
		$mockEntity->expects($this->once())->method('moveResizedFiles');

		$this->listener->afterSave($event, $mockEntity, new ArrayObject());

		if (file_exists(WWW_ROOT . 'media' . DS . 'test.jpg')) {
			unlink(WWW_ROOT . 'media' . DS . 'test.jpg');
		}
		if (file_exists(WWW_ROOT . 'media' . DS . 'new-test.jpg')) {
			unlink(WWW_ROOT . 'media' . DS . 'new-test.jpg');
		}
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\MediaListener::afterSave()
	 */
	public function testAfterSaveNotMovesResizedFileWhenNoFileAndPathUnchanged(): void {
		$mediaTable = $this->fetchTable('Media');
		$event = new Event('Model.Media.afterSave', $mediaTable);

		$mockEntity = $this->getMockBuilder(Media::class)->onlyMethods([
			'deleteResizedFiles',
			'moveResizedFiles',
		])->getMock();
		$mockEntity->id = 123;
		$mockEntity->file = null;
		$mockEntity->name = 'test.jpg';
		$mockEntity->path = 'media/test.jpg';

		$mockEntity->clean();
		$mockEntity->setNew(false);
		/** @noinspection PhpFieldImmediatelyRewrittenInspection */
		$mockEntity->path = 'media/new-test.jpg';
		$mockEntity->path = 'media/test.jpg';

		$mockEntity->expects($this->never())->method('deleteResizedFiles');
		$mockEntity->expects($this->never())->method('moveResizedFiles');

		$this->listener->afterSave($event, $mockEntity, new ArrayObject());
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\MediaListener::afterSave()
	 */
	public function testAfterSaveNotCreatesHistoricalPathsWhenFileUploadedAndPathChangedAndConfigSettingDisabled(): void {
		Configure::write('Awyiss.Media.Backend.createHistoricalPaths', MediaConfigOptions::CREATE_HISTORICAL_PATHS_DISABLED);

		$mediaTable = $this->fetchTable('Media');
		$event = new Event('Model.Media.afterSave', $mediaTable);

		file_put_contents(WWW_ROOT . 'media' . DS . 'test.jpg', 'test');
		$this->assertFileExists(WWW_ROOT . 'media' . DS . 'test.jpg');

		// Mock the UrlHistory table
		$urlHistoryTable = $this->getMockBuilder(UrlHistoryTable::class)->disableOriginalConstructor()->onlyMethods(['insertQuery'])->getMock();
		$urlHistoryTable->expects($this->never())->method('insertQuery');

		$tableLocator = FactoryLocator::get('Table');
		$tableLocator->clear();
		$tableLocator->set('UrlHistory', $urlHistoryTable);

		$mockFile = $this->createMock(UploadedFile::class);
		$mockFile->method('getError')->willReturn(UPLOAD_ERR_OK);
		$mockFile->expects($this->once())->method('moveTo');

		$mockEntity = $this->getMockBuilder(Media::class)->onlyMethods(['deleteConvertedFiles', 'deleteResizedFiles'])->getMock();
		$mockEntity->id = 123;
		$mockEntity->file = $mockFile;
		$mockEntity->name = 'test.jpg';
		$mockEntity->path = 'media/test.jpg';

		$mockEntity->clean();
		$mockEntity->setNew(false);
		$mockEntity->name = 'new-test.jpg';
		$mockEntity->path = 'media/new-test.jpg';

		$this->listener->afterSave($event, $mockEntity, new ArrayObject());

		if (file_exists(WWW_ROOT . 'media' . DS . 'new-test.jpg')) {
			unlink(WWW_ROOT . 'media' . DS . 'new-test.jpg');
		}
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\MediaListener::afterSave()
	 */
	public function testAfterSaveNotCreatesHistoricalPathsWhenFileUploadedAndPathChangedAndConfigSettingFalse(): void {
		Configure::write('Awyiss.Media.Backend.createHistoricalPaths', false);

		$mediaTable = $this->fetchTable('Media');
		$event = new Event('Model.Media.afterSave', $mediaTable);

		file_put_contents(WWW_ROOT . 'media' . DS . 'test.jpg', 'test');
		$this->assertFileExists(WWW_ROOT . 'media' . DS . 'test.jpg');

		// Mock the UrlHistory table
		$urlHistoryTable = $this->getMockBuilder(UrlHistoryTable::class)->disableOriginalConstructor()->onlyMethods(['insertQuery'])->getMock();
		$urlHistoryTable->expects($this->never())->method('insertQuery');

		$tableLocator = FactoryLocator::get('Table');
		$tableLocator->clear();
		$tableLocator->set('UrlHistory', $urlHistoryTable);

		$mockFile = $this->createMock(UploadedFile::class);
		$mockFile->method('getError')->willReturn(UPLOAD_ERR_OK);
		$mockFile->expects($this->once())->method('moveTo');

		$mockEntity = $this->getMockBuilder(Media::class)->onlyMethods(['deleteConvertedFiles', 'deleteResizedFiles'])->getMock();
		$mockEntity->id = 123;
		$mockEntity->file = $mockFile;
		$mockEntity->name = 'test.jpg';
		$mockEntity->path = 'media/test.jpg';

		$mockEntity->clean();
		$mockEntity->setNew(false);
		$mockEntity->name = 'new-test.jpg';
		$mockEntity->path = 'media/new-test.jpg';

		$this->listener->afterSave($event, $mockEntity, new ArrayObject());

		if (file_exists(WWW_ROOT . 'media' . DS . 'new-test.jpg')) {
			unlink(WWW_ROOT . 'media' . DS . 'new-test.jpg');
		}
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\MediaListener::afterSave()
	 */
	public function testAfterSaveCreatesHistoricalPathsWhenFileUploadedAndPathChangedAndConfigSettingFileNameChange(): void {
		Configure::write('Awyiss.Media.Backend.createHistoricalPaths', MediaConfigOptions::CREATE_HISTORICAL_PATHS_FILE_NAME_CHANGE);

		$mediaTable = $this->fetchTable('Media');
		$event = new Event('Model.Media.afterSave', $mediaTable);

		file_put_contents(WWW_ROOT . 'media' . DS . 'test.jpg', 'test');
		$this->assertFileExists(WWW_ROOT . 'media' . DS . 'test.jpg');

		// Mock the Query object
		$mockQuery = $this->getMockBuilder(InsertQuery::class)->disableOriginalConstructor()->onlyMethods(['values', 'execute'])->getMock();
		$mockQuery->expects($this->once())->method('values')->with($this->callback(function (array $data) {
			return $data['url'] === 'media/test.jpg' &&
			$data['scope'] === 'Media' &&
			$data['foreignKey'] === 123 &&
			$data['status'] === 308;
		}))->willReturnSelf();
		$mockQuery->expects($this->once())->method('execute');

		// Mock the UrlHistory table
		$urlHistoryTable = $this->getMockBuilder(UrlHistoryTable::class)->disableOriginalConstructor()->onlyMethods(['insertQuery'])->getMock();
		$urlHistoryTable->expects($this->once())->method('insertQuery')->willReturn($mockQuery);
		$tableLocator = FactoryLocator::get('Table');
		$tableLocator->clear();
		$tableLocator->set('UrlHistory', $urlHistoryTable);

		$mockFile = $this->createMock(UploadedFile::class);
		$mockFile->method('getError')->willReturn(UPLOAD_ERR_OK);
		$mockFile->expects($this->once())->method('moveTo');

		$mockEntity = $this->getMockBuilder(Media::class)->onlyMethods(['deleteConvertedFiles', 'deleteResizedFiles'])->getMock();
		$mockEntity->id = 123;
		$mockEntity->file = $mockFile;
		$mockEntity->name = 'test.jpg';
		$mockEntity->path = 'media/test.jpg';

		$mockEntity->clean();
		$mockEntity->setNew(false);
		$mockEntity->name = 'new-test.jpg';
		$mockEntity->path = 'media/new-test.jpg';

		$this->listener->afterSave($event, $mockEntity, new ArrayObject());

		if (file_exists(WWW_ROOT . 'media' . DS . 'new-test.jpg')) {
			unlink(WWW_ROOT . 'media' . DS . 'new-test.jpg');
		}
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\MediaListener::afterSave()
	 */
	public function testAfterSaveCreatesHistoricalPathsWhenFileUploadedAndPathChangedAndConfigSettingAlways(): void {
		Configure::write('Awyiss.Media.Backend.createHistoricalPaths', MediaConfigOptions::CREATE_HISTORICAL_PATHS_ALWAYS);

		$mediaTable = $this->fetchTable('Media');
		$event = new Event('Model.Media.afterSave', $mediaTable);

		file_put_contents(WWW_ROOT . 'media' . DS . 'test.jpg', 'test');
		$this->assertFileExists(WWW_ROOT . 'media' . DS . 'test.jpg');

		// Mock the Query object
		$mockQuery = $this->getMockBuilder(InsertQuery::class)->disableOriginalConstructor()->onlyMethods(['values', 'execute'])->getMock();
		$mockQuery->expects($this->once())->method('values')->with($this->callback(function (array $data) {
			return $data['url'] === 'media/test.jpg' &&
			$data['scope'] === 'Media' &&
			$data['foreignKey'] === 123 &&
			$data['status'] === 308;
		}))->willReturnSelf();
		$mockQuery->expects($this->once())->method('execute');

		// Mock the UrlHistory table
		$urlHistoryTable = $this->getMockBuilder(UrlHistoryTable::class)->disableOriginalConstructor()->onlyMethods(['insertQuery'])->getMock();
		$urlHistoryTable->expects($this->once())->method('insertQuery')->willReturn($mockQuery);
		$tableLocator = FactoryLocator::get('Table');
		$tableLocator->clear();
		$tableLocator->set('UrlHistory', $urlHistoryTable);

		$mockFile = $this->createMock(UploadedFile::class);
		$mockFile->method('getError')->willReturn(UPLOAD_ERR_OK);
		$mockFile->expects($this->once())->method('moveTo');

		$mockEntity = $this->getMockBuilder(Media::class)->onlyMethods(['deleteConvertedFiles', 'deleteResizedFiles'])->getMock();
		$mockEntity->id = 123;
		$mockEntity->file = $mockFile;
		$mockEntity->name = 'test.jpg';
		$mockEntity->path = 'media/test.jpg';

		$mockEntity->clean();
		$mockEntity->setNew(false);
		$mockEntity->name = 'new-test.jpg';
		$mockEntity->path = 'media/new-test.jpg';

		$this->listener->afterSave($event, $mockEntity, new ArrayObject());

		if (file_exists(WWW_ROOT . 'media' . DS . 'new-test.jpg')) {
			unlink(WWW_ROOT . 'media' . DS . 'new-test.jpg');
		}
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\MediaListener::afterSave()
	 */
	public function testAfterSaveNotCreatesHistoricalPathsWhenFileUploadedAndPathChangedAndConfigSettingFolderNameChange(): void {
		Configure::write('Awyiss.Media.Backend.createHistoricalPaths', MediaConfigOptions::CREATE_HISTORICAL_PATHS_FOLDER_NAME_CHANGE);

		$mediaTable = $this->fetchTable('Media');
		$event = new Event('Model.Media.afterSave', $mediaTable);

		file_put_contents(WWW_ROOT . 'media' . DS . 'test.jpg', 'test');
		$this->assertFileExists(WWW_ROOT . 'media' . DS . 'test.jpg');

		// Mock the UrlHistory table
		$urlHistoryTable = $this->getMockBuilder(UrlHistoryTable::class)->disableOriginalConstructor()->onlyMethods(['insertQuery'])->getMock();
		$urlHistoryTable->expects($this->never())->method('insertQuery');

		$tableLocator = FactoryLocator::get('Table');
		$tableLocator->clear();
		$tableLocator->set('UrlHistory', $urlHistoryTable);

		$mockFile = $this->createMock(UploadedFile::class);
		$mockFile->method('getError')->willReturn(UPLOAD_ERR_OK);
		$mockFile->expects($this->once())->method('moveTo');

		$mockEntity = $this->getMockBuilder(Media::class)->onlyMethods(['deleteConvertedFiles', 'deleteResizedFiles'])->getMock();
		$mockEntity->id = 123;
		$mockEntity->file = $mockFile;
		$mockEntity->name = 'test.jpg';
		$mockEntity->path = 'media/test.jpg';

		$mockEntity->clean();
		$mockEntity->setNew(false);
		$mockEntity->name = 'new-test.jpg';
		$mockEntity->path = 'media/new-test.jpg';

		$this->listener->afterSave($event, $mockEntity, new ArrayObject());

		if (file_exists(WWW_ROOT . 'media' . DS . 'new-test.jpg')) {
			unlink(WWW_ROOT . 'media' . DS . 'new-test.jpg');
		}
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\MediaListener::afterSave()
	 */
	public function testAfterSaveNotCreatesHistoricalPathsWhenFileUploadErrorAndPathNotChanged(): void {
		Configure::write('Awyiss.Media.Backend.createHistoricalPaths', MediaConfigOptions::CREATE_HISTORICAL_PATHS_ALWAYS);

		$mediaTable = $this->fetchTable('Media');
		$event = new Event('Model.Media.afterSave', $mediaTable);

		file_put_contents(WWW_ROOT . 'media' . DS . 'test.jpg', 'test');
		$this->assertFileExists(WWW_ROOT . 'media' . DS . 'test.jpg');

		// Mock the UrlHistory table
		$urlHistoryTable = $this->getMockBuilder(UrlHistoryTable::class)->disableOriginalConstructor()->onlyMethods(['insertQuery'])->getMock();
		$urlHistoryTable->expects($this->never())->method('insertQuery');
		$tableLocator = FactoryLocator::get('Table');
		$tableLocator->clear();
		$tableLocator->set('UrlHistory', $urlHistoryTable);

		$mockFile = $this->createMock(UploadedFile::class);
		$mockFile->method('getError')->willReturn(UPLOAD_ERR_OK);
		$mockFile->expects($this->once())->method('moveTo');

		$mockEntity = $this->getMockBuilder(Media::class)->onlyMethods(['deleteConvertedFiles', 'deleteResizedFiles'])->getMock();
		$mockEntity->id = 123;
		$mockEntity->file = $mockFile;
		$mockEntity->name = 'test.jpg';
		$mockEntity->path = 'media/test.jpg';

		$mockEntity->clean();
		$mockEntity->setNew(false);
		/** @noinspection PhpFieldImmediatelyRewrittenInspection */
		$mockEntity->path = 'media/new-test.jpg';
		$mockEntity->path = 'media/test.jpg';

		$this->listener->afterSave($event, $mockEntity, new ArrayObject());

		if (file_exists(WWW_ROOT . 'media' . DS . 'new-test.jpg')) {
			unlink(WWW_ROOT . 'media' . DS . 'new-test.jpg');
		}
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\MediaListener::afterSave()
	 */
	public function testAfterSaveCreatesHistoricalPathsWhenFileUploadErrorAndPathChanged(): void {
		Configure::write('Awyiss.Media.Backend.createHistoricalPaths', MediaConfigOptions::CREATE_HISTORICAL_PATHS_ALWAYS);

		$mediaTable = $this->fetchTable('Media');
		$event = new Event('Model.Media.afterSave', $mediaTable);

		file_put_contents(WWW_ROOT . 'media' . DS . 'test.jpg', 'test');
		$this->assertFileExists(WWW_ROOT . 'media' . DS . 'test.jpg');

		// Mock the Query object
		$mockQuery = $this->getMockBuilder(InsertQuery::class)->disableOriginalConstructor()->onlyMethods(['values', 'execute'])->getMock();
		$mockQuery->expects($this->once())->method('values')->with($this->callback(function (array $data) {
			return $data['url'] === 'media/test.jpg' && $data['scope'] === 'Media' && $data['foreignKey'] === 123 && $data['status'] === 308;
		}))->willReturnSelf();
		$mockQuery->expects($this->once())->method('execute');

		// Mock the UrlHistory table
		$urlHistoryTable = $this->getMockBuilder(UrlHistoryTable::class)->disableOriginalConstructor()->onlyMethods(['insertQuery'])->getMock();
		$urlHistoryTable->expects($this->once())->method('insertQuery')->willReturn($mockQuery);
		$tableLocator = FactoryLocator::get('Table');
		$tableLocator->clear();
		$tableLocator->set('UrlHistory', $urlHistoryTable);

		$mockFile = $this->createMock(UploadedFile::class);
		$mockFile->method('getError')->willReturn(UPLOAD_ERR_OK);
		$mockFile->expects($this->once())->method('moveTo');

		$mockEntity = $this->getMockBuilder(Media::class)->onlyMethods(['deleteConvertedFiles', 'deleteResizedFiles'])->getMock();
		$mockEntity->id = 123;
		$mockEntity->file = $mockFile;
		$mockEntity->name = 'test.jpg';
		$mockEntity->path = 'media/test.jpg';

		$mockEntity->clean();
		$mockEntity->setNew(false);
		$mockEntity->name = 'new-test.jpg';
		$mockEntity->path = 'media/new-test.jpg';

		$this->listener->afterSave($event, $mockEntity, new ArrayObject());

		if (file_exists(WWW_ROOT . 'media' . DS . 'new-test.jpg')) {
			unlink(WWW_ROOT . 'media' . DS . 'new-test.jpg');
		}
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\MediaListener::afterSave()
	 */
	public function testAfterSaveNotCreatesHistoricalPathsWhenNoFileAndPathNotChanged(): void {
		Configure::write('Awyiss.Media.Backend.createHistoricalPaths', MediaConfigOptions::CREATE_HISTORICAL_PATHS_ALWAYS);

		$mediaTable = $this->fetchTable('Media');
		$event = new Event('Model.Media.afterSave', $mediaTable);

		file_put_contents(WWW_ROOT . 'media' . DS . 'test.jpg', 'test');
		$this->assertFileExists(WWW_ROOT . 'media' . DS . 'test.jpg');

		// Mock the UrlHistory table
		$urlHistoryTable = $this->getMockBuilder(UrlHistoryTable::class)->disableOriginalConstructor()->onlyMethods(['insertQuery'])->getMock();
		$urlHistoryTable->expects($this->never())->method('insertQuery');
		$tableLocator = FactoryLocator::get('Table');
		$tableLocator->clear();
		$tableLocator->set('UrlHistory', $urlHistoryTable);

		$mockEntity = $this->getMockBuilder(Media::class)->onlyMethods(['deleteConvertedFiles', 'deleteResizedFiles'])->getMock();
		$mockEntity->id = 123;
		$mockEntity->file = null;
		$mockEntity->name = 'test.jpg';
		$mockEntity->path = 'media/test.jpg';

		$mockEntity->clean();
		$mockEntity->setNew(false);
		/** @noinspection PhpFieldImmediatelyRewrittenInspection */
		$mockEntity->path = 'media/new-test.jpg';
		$mockEntity->path = 'media/test.jpg';

		$this->listener->afterSave($event, $mockEntity, new ArrayObject());

		if (file_exists(WWW_ROOT . 'media' . DS . 'new-test.jpg')) {
			unlink(WWW_ROOT . 'media' . DS . 'new-test.jpg');
		}
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\MediaListener::afterSave()
	 */
	public function testAfterSaveCreatesHistoricalPathsWhenNoFileAndPathChanged(): void {
		Configure::write('Awyiss.Media.Backend.createHistoricalPaths', MediaConfigOptions::CREATE_HISTORICAL_PATHS_ALWAYS);

		$mediaTable = $this->fetchTable('Media');
		$event = new Event('Model.Media.afterSave', $mediaTable);

		file_put_contents(WWW_ROOT . 'media' . DS . 'test.jpg', 'test');
		$this->assertFileExists(WWW_ROOT . 'media' . DS . 'test.jpg');

		// Mock the Query object
		$mockQuery = $this->getMockBuilder(InsertQuery::class)->disableOriginalConstructor()->onlyMethods(['values', 'execute'])->getMock();
		$mockQuery->expects($this->once())->method('values')->with($this->callback(function (array $data) {
			return $data['url'] === 'media/test.jpg' && $data['scope'] === 'Media' && $data['foreignKey'] === 123 && $data['status'] === 308;
		}))->willReturnSelf();
		$mockQuery->expects($this->once())->method('execute');

		// Mock the UrlHistory table
		$urlHistoryTable = $this->getMockBuilder(UrlHistoryTable::class)->disableOriginalConstructor()->onlyMethods(['insertQuery'])->getMock();
		$urlHistoryTable->expects($this->once())->method('insertQuery')->willReturn($mockQuery);
		$tableLocator = FactoryLocator::get('Table');
		$tableLocator->clear();
		$tableLocator->set('UrlHistory', $urlHistoryTable);

		$mockEntity = $this->getMockBuilder(Media::class)->onlyMethods(['deleteConvertedFiles', 'deleteResizedFiles'])->getMock();
		$mockEntity->id = 123;
		$mockEntity->file = null;
		$mockEntity->name = 'test.jpg';
		$mockEntity->path = 'media/test.jpg';

		$mockEntity->clean();
		$mockEntity->setNew(false);
		$mockEntity->name = 'new-test.jpg';
		$mockEntity->path = 'media/new-test.jpg';

		$this->listener->afterSave($event, $mockEntity, new ArrayObject());

		if (file_exists(WWW_ROOT . 'media' . DS . 'new-test.jpg')) {
			unlink(WWW_ROOT . 'media' . DS . 'new-test.jpg');
		}
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\MediaListener::afterSave()
	 */
	public function testAfterSaveNotDeleteResizedFilesWhenFocusPointUnchanged(): void {
		$mediaTable = $this->fetchTable('Media');
		$event = new Event('Model.Media.afterSave', $mediaTable);

		$mockEntity = $this->getMockBuilder(Media::class)->onlyMethods([
			'deleteResizedFiles',
			'deleteConvertedFiles',
			'moveConvertedFiles',
			'moveResizedFiles',
		])->getMock();

		$mockEntity->id = 123;
		$mockEntity->file = null;
		$mockEntity->name = 'test.jpg';
		$mockEntity->path = 'media/test.jpg';
		$mockEntity->focusPoint = '1,0';

		$mockEntity->clean();
		$mockEntity->setNew(false);
		/** @noinspection PhpFieldImmediatelyRewrittenInspection */
		$mockEntity->focusPoint = '0,0';
		$mockEntity->focusPoint = '1,0';

		$mockEntity->expects($this->never())->method('deleteResizedFiles');
		$mockEntity->expects($this->never())->method('deleteConvertedFiles');
		$mockEntity->expects($this->never())->method('moveConvertedFiles');
		$mockEntity->expects($this->never())->method('moveResizedFiles');

		$this->listener->afterSave($event, $mockEntity, new ArrayObject());
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\MediaListener::afterSave()
	 */
	public function testAfterSaveDeleteResizedFilesWhenFocusPointChanged(): void {
		$mediaTable = $this->fetchTable('Media');
		$event = new Event('Model.Media.afterSave', $mediaTable);

		$mockEntity = $this->getMockBuilder(Media::class)->onlyMethods([
			'deleteResizedFiles',
			'deleteConvertedFiles',
			'moveConvertedFiles',
			'moveResizedFiles',
		])->getMock();

		$mockEntity->id = 123;
		$mockEntity->file = null;
		$mockEntity->name = 'test.jpg';
		$mockEntity->path = 'media/test.jpg';
		$mockEntity->focusPoint = '1,0';

		$mockEntity->clean();
		$mockEntity->setNew(false);

		$mockEntity->focusPoint = '0,0';

		$mockEntity->expects($this->once())->method('deleteResizedFiles');
		$mockEntity->expects($this->never())->method('deleteConvertedFiles');
		$mockEntity->expects($this->never())->method('moveConvertedFiles');
		$mockEntity->expects($this->never())->method('moveResizedFiles');

		$this->listener->afterSave($event, $mockEntity, new ArrayObject());
	}



	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\MediaListener::afterDelete()
	 */
	public function testAfterDeleteDeletesConvertedFiles(): void {
		$mediaTable = $this->fetchTable('Media');
		$event = new Event('Model.Media.afterSave', $mediaTable);

		$mockEntity = $this->getMockBuilder(Media::class)->onlyMethods([
			'deleteConvertedFiles',
			'moveConvertedFiles',
		])->getMock();

		$mockEntity->id = 123;
		$mockEntity->file = null;
		$mockEntity->name = 'test.jpg';
		$mockEntity->path = 'media/test.jpg';
		$mockEntity->focusPoint = '1,0';

		$mockEntity->clean();
		$mockEntity->setNew(false);

		$mockEntity->focusPoint = '0,0';

		$mockEntity->expects($this->once())->method('deleteConvertedFiles');
		$mockEntity->expects($this->never())->method('moveConvertedFiles');

		$this->listener->afterDelete($event, $mockEntity, new ArrayObject());
	}


	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\MediaListener::afterDelete()
	 */
	public function testAfterDeleteDeletesResizedFiles(): void {
		$mediaTable = $this->fetchTable('Media');
		$event = new Event('Model.Media.afterSave', $mediaTable);

		$mockEntity = $this->getMockBuilder(Media::class)->onlyMethods([
			'deleteResizedFiles',
			'moveResizedFiles',
		])->getMock();

		$mockEntity->id = 123;
		$mockEntity->file = null;
		$mockEntity->name = 'test.jpg';
		$mockEntity->path = 'media/test.jpg';
		$mockEntity->focusPoint = '1,0';

		$mockEntity->clean();
		$mockEntity->setNew(false);

		$mockEntity->focusPoint = '0,0';

		$mockEntity->expects($this->once())->method('deleteResizedFiles');
		$mockEntity->expects($this->never())->method('moveResizedFiles');

		$this->listener->afterDelete($event, $mockEntity, new ArrayObject());
	}



	/**
	 * @return void
	 * @see \Awyiss\Event\Backend\MediaListener::afterDelete()
	 */
	public function testAfterDeleteRemovesFileWhenExists(): void {
		$testFile = $this->tmpDir . DIRECTORY_SEPARATOR . 'test-delete.txt';
		file_put_contents($testFile, 'test content');

		$this->assertFileExists($testFile);

		$mediaTable = $this->fetchTable('Media');
		$event = new Event('Model.Media.afterDelete', $mediaTable);

		$mockEntity = $this->createMock(Media::class);
		$mockEntity->method('__get')->with('path')->willReturn($testFile);
		$mockEntity->expects($this->once())->method('deleteResizedFiles');
		$mockEntity->expects($this->once())->method('deleteConvertedFiles');

		$this->listener->afterDelete($event, $mockEntity, new ArrayObject());

		$this->assertFileDoesNotExist($testFile);
	}



	/**
	 * @param string $content
	 * @param string $filename
	 * @return string
	 */
	protected function createTempFile(string $content, string $filename): string {
		$path = $this->tmpDir . DIRECTORY_SEPARATOR . $filename;
		file_put_contents($path, $content);
		$this->tmpFiles[] = $path;

		return $path;
	}


	/**
	 * @return string
	 */
	protected function createTempImageFile(): string {
		$image = imagecreate(10, 10);
		$backgroundColor = imagecolorallocate($image, 255, 255, 255);
		imagefill($image, 0, 0, $backgroundColor);

		$path = $this->tmpDir . DIRECTORY_SEPARATOR . 'test_image.jpg';
		imagejpeg($image, $path);
		imagedestroy($image);

		$this->tmpFiles[] = $path;

		return $path;
	}
}
