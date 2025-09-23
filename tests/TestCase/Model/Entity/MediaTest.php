<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Model\Entity;


use Awyiss\Model\Entity\Media;
use Awyiss\Test\TestSuite\TestCase;
use Cake\Datasource\FactoryLocator;
use Symfony\Component\Process\Process;


/**
 * Media Entity Test Case
 *
 * @see \Awyiss\Model\Entity\Media
 */
class MediaTest extends TestCase {
	/**
	 * @var array
	 */
	protected array $tempDirs = [];
	/**
	 * @var array
	 */
	protected array $tempMediaFolderIds = [];
	/**
	 * @var array
	 */
	protected array $tempMediaIds = [];
	/**
	 * @var array
	 */
	protected array $tempResizedImageIds = [];


	/**
	 * @inheritDoc
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function tearDown(): void {
		// Clean up temporary database records first
		if (!empty($this->tempResizedImageIds)) {
			/** @var \Awyiss\Model\Table\MediaResizedImagesTable $resizedTable */
			$resizedTable = FactoryLocator::get('Table')->get('MediaResizedImages');
			$resizedTable->deleteAll(['id IN' => $this->tempResizedImageIds]);
			$this->tempResizedImageIds = [];
		}

		if (!empty($this->tempMediaFolderIds)) {
			/** @var \Awyiss\Model\Table\MediaFoldersTable $mediaFolderTable */
			$mediaFolderTable = FactoryLocator::get('Table')->get('MediaFolders');
			$mediaFolderTable->deleteAll(['id IN' => $this->tempMediaFolderIds]);
			$this->tempMediaFolderIds = [];
		}

		if (!empty($this->tempMediaIds)) {
			/** @var \Awyiss\Model\Table\MediaTable $mediaTable */
			$mediaTable = FactoryLocator::get('Table')->get('Media');
			$mediaTable->deleteAll(['id IN' => $this->tempMediaIds]);
			$this->tempMediaIds = [];
		}

		// Clean up temporary directories
		foreach ($this->tempDirs as $dir) {
			new Process(['rm', '-rf', WWW_ROOT . $dir])->run();
		}
		$this->tempDirs = [];

		parent::tearDown();
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Media::$fieldMap
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testFieldMapCompleteness(): void {
		/** @var \Awyiss\Model\Table\MediaTable $table */
		$table = FactoryLocator::get('Table')->get('Media');
		$entity = $table->newDefaultEntity();
		$entityArray = $entity->toArray();

		foreach ($entityArray as $key => $value) {
			$this->assertStringNotContainsString('_', $key);
		}
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Media::$_accessible
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testAccessibleFields(): void {
		$entity = new Media();

		$this->assertSame([
			'mediaFolderId' => true,
			'name' => true,
			'path' => true,
			'alt' => true,
			'width' => true,
			'height' => true,
			'mimeType' => true,
			'metaData' => true,
			'averageColor' => true,
			'preview' => true,
			'avif' => true,
			'webp' => true,
			'crop' => true,
			'focusPoint' => true,
			'systemOrder' => true,
			'file' => true,
			'_translations' => true,
			'_publicationData' => true,
			'mediaAssignments' => true,
			'mediaElementAssignments' => true,
		], $entity->getAccessible());
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Media::$_virtual
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testVirtualFields(): void {
		$entity = new Media();

		$this->assertSame([
			'label',
			'isAudio',
			'isImage',
			'isVideo',
			'cleanName',
			'originalCleanName',
			'extension',
			'originalExtension',
			'pathAbsolute',
			'originalPathAbsolute',
			'previewName',
			'originalPreviewName',
			'previewPath',
			'originalPreviewPath',
			'previewPathAbsolute',
			'originalPreviewPathAbsolute',
			'avifName',
			'originalAvifName',
			'avifPath',
			'originalAvifPath',
			'avifPathAbsolute',
			'originalAvifPathAbsolute',
			'webpName',
			'originalWebpName',
			'webpPath',
			'originalWebpPath',
			'webpPathAbsolute',
			'originalWebpPathAbsolute',
			'filemtime',
			'previewFilemtime',
		], $entity->getVirtual());
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Media::isAudio()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testIsAudioMethod(): void {
		$audioEntity = new Media(['mimeType' => 'audio/mpeg']);
		$this->assertTrue($audioEntity->isAudio());

		$audioEntity = new Media(['mimeType' => 'audio/ogg']);
		$this->assertTrue($audioEntity->isAudio());

		$videoEntity = new Media(['mimeType' => 'video/mp4']);
		$this->assertFalse($videoEntity->isAudio());

		$imageEntity = new Media(['mimeType' => 'image/jpeg']);
		$this->assertFalse($imageEntity->isAudio());
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Media::_getIsAudio()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testIsAudioVirtualProperty(): void {
		$entity = new Media(['mimeType' => 'audio/mpeg']);
		$this->assertTrue($entity->isAudio);

		$entity = new Media(['mimeType' => 'audio/ogg']);
		$this->assertTrue($entity->isAudio);

		$entity = new Media(['mimeType' => 'audio/wav']);
		$this->assertTrue($entity->isAudio);

		$entity = new Media(['mimeType' => 'audio/webm']);
		$this->assertTrue($entity->isAudio);

		$entity = new Media(['mimeType' => 'image/jpeg']);
		$this->assertFalse($entity->isAudio);

		$entity = new Media(['mimeType' => 'video/mp4']);
		$this->assertFalse($entity->isAudio);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Media::isImage()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testIsImageMethod(): void {
		$imageEntity = new Media(['mimeType' => 'image/jpeg']);
		$this->assertTrue($imageEntity->isImage());

		$imageEntity = new Media(['mimeType' => 'image/png']);
		$this->assertTrue($imageEntity->isImage());

		$audioEntity = new Media(['mimeType' => 'audio/mpeg']);
		$this->assertFalse($audioEntity->isImage());

		$videoEntity = new Media(['mimeType' => 'video/mp4']);
		$this->assertFalse($videoEntity->isImage());
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Media::_getIsImage()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testIsImageVirtualProperty(): void {
		$entity = new Media(['mimeType' => 'image/avif']);
		$this->assertTrue($entity->isImage);

		$entity = new Media(['mimeType' => 'image/jpeg']);
		$this->assertTrue($entity->isImage);

		$entity = new Media(['mimeType' => 'image/png']);
		$this->assertTrue($entity->isImage);

		$entity = new Media(['mimeType' => 'image/gif']);
		$this->assertTrue($entity->isImage);

		$entity = new Media(['mimeType' => 'image/webp']);
		$this->assertTrue($entity->isImage);

		$entity = new Media(['mimeType' => 'audio/mpeg']);
		$this->assertFalse($entity->isImage);

		$entity = new Media(['mimeType' => 'video/mp4']);
		$this->assertFalse($entity->isImage);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Media::isVideo()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testIsVideoMethod(): void {
		$videoEntity = new Media(['mimeType' => 'video/mp4']);
		$this->assertTrue($videoEntity->isVideo());

		$videoEntity = new Media(['mimeType' => 'video/webm']);
		$this->assertTrue($videoEntity->isVideo());

		$audioEntity = new Media(['mimeType' => 'audio/mpeg']);
		$this->assertFalse($audioEntity->isVideo());

		$imageEntity = new Media(['mimeType' => 'image/jpeg']);
		$this->assertFalse($imageEntity->isVideo());
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Media::_getIsVideo()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testIsVideoVirtualProperty(): void {
		$entity = new Media(['mimeType' => 'video/mp4']);
		$this->assertTrue($entity->isVideo);

		$entity = new Media(['mimeType' => 'video/ogg']);
		$this->assertTrue($entity->isVideo);

		$entity = new Media(['mimeType' => 'video/webm']);
		$this->assertTrue($entity->isVideo);

		$entity = new Media(['mimeType' => 'image/jpeg']);
		$this->assertFalse($entity->isVideo);

		$entity = new Media(['mimeType' => 'audio/mpeg']);
		$this->assertFalse($entity->isVideo);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Media::originalIsImage()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testOriginalIsImageMethod(): void {
		$entity = new Media();
		$entity->patch([
			'mimeType' => 'image/jpeg',
		], ['asOriginal' => true]);
		$entity->patch([
			'mimeType' => 'application/pdf',
		]);
		$this->assertTrue($entity->originalIsImage());

		$entity = new Media();
		$entity->patch([
			'mimeType' => 'video/mp4',
		], ['asOriginal' => true]);
		$entity->patch([
			'mimeType' => 'application/pdf',
		]);
		$this->assertFalse($entity->originalIsImage());

		$entityWithoutOriginal = new Media();
		$this->assertFalse($entityWithoutOriginal->originalIsImage());
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Media::_getCleanName()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testCleanNameVirtualProperty(): void {
		$entity = new Media(['name' => 'logo-awyiss.jpg']);
		$this->assertEquals('logo-awyiss', $entity->cleanName);

		$entity = new Media(['name' => 'multimedia-test.mp4']);
		$this->assertEquals('multimedia-test', $entity->cleanName);

		$entity = new Media(['name' => 'filename-without-extension']);
		$this->assertEquals('filename-without-extension', $entity->cleanName);

		$entity = new Media(['name' => null]);
		$this->assertNull($entity->cleanName);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Media::_getOriginalCleanName()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testOriginalCleanNameVirtualProperty(): void {
		$entity = new Media();
		$entity->patch([
			'name' => 'original-logo.png',
		], ['asOriginal' => true]);
		$entity->patch([
			'name' => 'logo.png',
		]);
		$this->assertEquals('original-logo', $entity->originalCleanName);

		$entityWithoutOriginal = new Media();
		$this->assertNull($entityWithoutOriginal->originalCleanName);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Media::_getExtension()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testExtensionVirtualProperty(): void {
		$entity = new Media(['name' => 'logo-awyiss.jpg']);
		$this->assertEquals('jpg', $entity->extension);

		$entity = new Media(['name' => 'multimedia-test.mp4']);
		$this->assertEquals('mp4', $entity->extension);

		$entity = new Media(['name' => 'filename-without-extension']);
		$this->assertNull($entity->extension);

		$entity = new Media(['name' => null]);
		$this->assertNull($entity->extension);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Media::_getOriginalExtension()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testOriginalExtensionVirtualProperty(): void {
		$entity = new Media();
		$entity->patch([
			'name' => 'original-logo.png',
		], ['asOriginal' => true]);
		$entity->patch([
			'name' => 'logo.png',
		]);
		$this->assertEquals('png', $entity->originalExtension);

		$entityWithoutOriginal = new Media();
		$this->assertNull($entityWithoutOriginal->originalExtension);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Media::_getPathAbsolute()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testPathAbsoluteVirtualProperty(): void {
		$entity = new Media(['path' => 'media/images/test.jpg']);
		$pathAbsolute = $entity->pathAbsolute;

		$this->assertSame(WWW_ROOT . 'media/images/test.jpg', $pathAbsolute);

		$entity = new Media(['path' => null]);
		$this->assertNull($entity->pathAbsolute);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Media::_getOriginalPathAbsolute()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testOriginalPathAbsoluteVirtualProperty(): void {
		$entity = new Media();
		$entity->patch([
			'path' => 'media/original/test.jpg',
		], ['asOriginal' => true]);
		$entity->patch([
			'path' => 'media/images/test.jpg',
		]);
		$pathAbsolute = $entity->originalPathAbsolute;

		$this->assertSame(WWW_ROOT . 'media/original/test.jpg', $pathAbsolute);

		$entityWithoutOriginal = new Media();
		$this->assertNull($entityWithoutOriginal->originalPathAbsolute);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Media::_getPreviewName()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testPreviewNameVirtualProperty(): void {
		// Non-image file should have preview name
		$entity = new Media(['name' => 'document.pdf', 'mimeType' => 'application/pdf']);
		$this->assertEquals('document.jpg', $entity->previewName);

		// Image file should not have preview name
		$entity = new Media(['name' => 'image.jpg', 'mimeType' => 'image/jpeg']);
		$this->assertNull($entity->previewName);

		$entity = new Media(['name' => null]);
		$this->assertNull($entity->previewName);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Media::_getOriginalPreviewName()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testOriginalPreviewNameVirtualProperty(): void {
		$entity = new Media();
		$entity->patch([
			'name' => 'original-document.pdf',
			'mimeType' => 'application/pdf',
		], ['asOriginal' => true]);
		$entity->patch([
			'name' => 'document.png',
			'mimeType' => 'image/png',
		]);
		$this->assertEquals('original-document.jpg', $entity->originalPreviewName);

		$entityWithoutOriginal = new Media();
		$this->assertNull($entityWithoutOriginal->originalPreviewName);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Media::_getAvifName()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testAvifNameVirtualProperty(): void {
		$entity = new Media(['name' => 'logo-awyiss.jpg']);
		$this->assertEquals('logo-awyiss.jpg.avif', $entity->avifName);

		$entity = new Media(['name' => null]);
		$this->assertNull($entity->avifName);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Media::_getOriginalAvifName()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testOriginalAvifNameVirtualProperty(): void {
		$entity = new Media();
		$entity->patch([
			'name' => 'original-logo.jpg',
		], ['asOriginal' => true]);
		$entity->patch([
			'name' => 'logo.jpg',
		]);
		$this->assertEquals('original-logo.jpg.avif', $entity->originalAvifName);

		$entityWithoutOriginal = new Media();
		$this->assertNull($entityWithoutOriginal->originalAvifName);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Media::_getWebpName()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testWebpNameVirtualProperty(): void {
		$entity = new Media(['name' => 'logo-awyiss.jpg']);
		$this->assertEquals('logo-awyiss.jpg.webp', $entity->webpName);

		$entity = new Media(['name' => null]);
		$this->assertNull($entity->webpName);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Media::_getOriginalWebpName()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testOriginalWebpNameVirtualProperty(): void {
		$entity = new Media();
		$entity->patch([
			'name' => 'original-logo.jpg',
		], ['asOriginal' => true]);
		$entity->patch([
			'name' => 'logo.jpg',
		]);
		$this->assertEquals('original-logo.jpg.webp', $entity->originalWebpName);

		$entityWithoutOriginal = new Media();
		$this->assertNull($entityWithoutOriginal->originalWebpName);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Media::_getAvifPath()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testAvifPathVirtualProperty(): void {
		$entity = new Media([
			'name' => 'logo.jpg',
			'path' => 'media/images/logo.jpg',
			'mimeType' => 'image/jpeg',
		]);
		$this->assertEquals('media/images/_avif' . DS . 'logo.jpg.avif', $entity->avifPath);

		// AVIF files should return null
		$entity = new Media([
			'name' => 'logo.avif',
			'path' => 'media/images/logo.avif',
			'mimeType' => 'image/avif',
		]);
		$this->assertNull($entity->avifPath);

		$entity = new Media(['path' => null]);
		$this->assertNull($entity->avifPath);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Media::_getOriginalAvifPath()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testOriginalAvifPathVirtualProperty(): void {
		$entity = new Media();
		$entity->patch([
			'name' => 'original-logo.jpg',
			'path' => 'media/images/original-logo.jpg',
			'mimeType' => 'image/jpeg',
		], ['asOriginal' => true]);
		$entity->patch([
			'name' => 'logo.png',
			'path' => 'media/images/logo.png',
			'mimeType' => 'image/png',
		]);
		$this->assertEquals('media/images/_avif' . DS . 'original-logo.jpg.avif', $entity->originalAvifPath);

		// Original AVIF files should return null
		$entity = new Media();
		$entity->patch([
			'name' => 'original-logo.avif',
			'path' => 'media/images/original-logo.avif',
			'mimeType' => 'image/avif',
		], ['asOriginal' => true]);
		$this->assertNull($entity->originalAvifPath);

		$entityWithoutOriginal = new Media();
		$this->assertNull($entityWithoutOriginal->originalAvifPath);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Media::_getAvifPathAbsolute()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testAvifPathAbsoluteVirtualProperty(): void {
		$entity = new Media([
			'name' => 'logo.jpg',
			'path' => 'media/images/logo.jpg',
			'mimeType' => 'image/jpeg',
		]);
		$expected = WWW_ROOT . str_replace('/', DS, 'media/images/_avif' . DS . 'logo.jpg.avif');
		$this->assertEquals($expected, $entity->avifPathAbsolute);

		// AVIF files should return null
		$entity = new Media([
			'name' => 'logo.avif',
			'path' => 'media/images/logo.avif',
			'mimeType' => 'image/avif',
		]);
		$this->assertNull($entity->avifPathAbsolute);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Media::_getOriginalAvifPathAbsolute()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testOriginalAvifPathAbsoluteVirtualProperty(): void {
		$entity = new Media();
		$entity->patch([
			'name' => 'original-logo.jpg',
			'path' => 'media/images/original-logo.jpg',
			'mimeType' => 'image/jpeg',
		], ['asOriginal' => true]);
		$entity->patch([
			'name' => 'logo.png',
			'path' => 'media/images/logo.png',
			'mimeType' => 'image/png',
		]);
		$expected = WWW_ROOT . str_replace('/', DS, 'media/images/_avif' . DS . 'original-logo.jpg.avif');
		$this->assertEquals($expected, $entity->originalAvifPathAbsolute);

		// Original AVIF files should return null
		$entity = new Media();
		$entity->patch([
			'name' => 'original-logo.avif',
			'path' => 'media/images/original-logo.avif',
			'mimeType' => 'image/avif',
		], ['asOriginal' => true]);
		$this->assertNull($entity->originalAvifPathAbsolute);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Media::_getWebpPath()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testWebpPathVirtualProperty(): void {
		$entity = new Media([
			'name' => 'logo.jpg',
			'path' => 'media/images/logo.jpg',
			'mimeType' => 'image/jpeg',
		]);
		$this->assertEquals('media/images/_webp' . DS . 'logo.jpg.webp', $entity->webpPath);

		// WebP files should return null
		$entity = new Media([
			'name' => 'logo.webp',
			'path' => 'media/images/logo.webp',
			'mimeType' => 'image/webp',
		]);
		$this->assertNull($entity->webpPath);

		$entity = new Media(['path' => null]);
		$this->assertNull($entity->webpPath);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Media::_getOriginalWebpPath()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testOriginalWebpPathVirtualProperty(): void {
		$entity = new Media();
		$entity->patch([
			'name' => 'original-logo.jpg',
			'path' => 'media/images/original-logo.jpg',
			'mimeType' => 'image/jpeg',
		], ['asOriginal' => true]);
		$entity->patch([
			'name' => 'logo.png',
			'path' => 'media/images/logo.png',
			'mimeType' => 'image/png',
		]);
		$this->assertEquals('media/images/_webp' . DS . 'original-logo.jpg.webp', $entity->originalWebpPath);

		// Original WebP files should return null
		$entity = new Media();
		$entity->patch([
			'name' => 'original-logo.webp',
			'path' => 'media/images/original-logo.webp',
			'mimeType' => 'image/webp',
		], ['asOriginal' => true]);
		$this->assertNull($entity->originalWebpPath);

		$entityWithoutOriginal = new Media();
		$this->assertNull($entityWithoutOriginal->originalWebpPath);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Media::_getWebpPathAbsolute()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testWebpPathAbsoluteVirtualProperty(): void {
		$entity = new Media([
			'name' => 'logo.jpg',
			'path' => 'media/images/logo.jpg',
			'mimeType' => 'image/jpeg',
		]);
		$expected = WWW_ROOT . str_replace('/', DS, 'media/images/_webp' . DS . 'logo.jpg.webp');
		$this->assertEquals($expected, $entity->webpPathAbsolute);

		// WebP files should return null
		$entity = new Media([
			'name' => 'logo.webp',
			'path' => 'media/images/logo.webp',
			'mimeType' => 'image/webp',
		]);
		$this->assertNull($entity->webpPathAbsolute);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Media::_getOriginalWebpPathAbsolute()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testOriginalWebpPathAbsoluteVirtualProperty(): void {
		$entity = new Media();
		$entity->patch([
			'name' => 'original-logo.jpg',
			'path' => 'media/images/original-logo.jpg',
			'mimeType' => 'image/jpeg',
		], ['asOriginal' => true]);
		$entity->patch([
			'name' => 'logo.png',
			'path' => 'media/images/logo.png',
			'mimeType' => 'image/png',
		]);
		$expected = WWW_ROOT . str_replace('/', DS, 'media/images/_webp' . DS . 'original-logo.jpg.webp');
		$this->assertEquals($expected, $entity->originalWebpPathAbsolute);

		// Original WebP files should return null
		$entity = new Media();
		$entity->patch([
			'name' => 'original-logo.webp',
			'path' => 'media/images/original-logo.webp',
			'mimeType' => 'image/webp',
		], ['asOriginal' => true]);
		$this->assertNull($entity->originalWebpPathAbsolute);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Media::findAlternatives()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testFindAlternativesWithActualData(): void {
		/** @var \Awyiss\Model\Table\MediaTable $table */
		$table = FactoryLocator::get('Table')->get('Media');
		/** @var \Awyiss\Model\Entity\Media $media */
		$media = $table->get(2); // logo-awyiss.jpg from seed data

		$alternatives = $media->findAlternatives();

		$this->assertIsArray($alternatives);
		$this->assertCount(8, $alternatives);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Media::findAlternatives()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testFindAlternativesWithNoName(): void {
		$entity = new Media(['name' => null]);
		$alternatives = $entity->findAlternatives();

		$this->assertNull($alternatives);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Media
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testEntityConstruction(): void {
		$properties = [
			'id' => 1,
			'media_folder_id' => 123,
			'mime_type' => 'image/jpeg',
			'name' => 'test-image.jpg',
			'path' => 'media/images/test-image.jpg',
			'alt' => 'Test image alt text',
			'width' => 1920,
			'height' => 1080,
			'meta_data' => ['key' => 'value'],
			'average_color' => '#FF0000',
			'preview' => 1,
			'avif' => 1,
			'webp' => 1,
			'crop' => null,
			'focus_point' => '50,50',
			'system_order' => 10,
		];

		$entity = new Media($properties);

		$this->assertEquals(1, $entity->id);
		$this->assertEquals(123, $entity->mediaFolderId);
		$this->assertEquals('image/jpeg', $entity->mimeType);
		$this->assertEquals('test-image.jpg', $entity->name);
		$this->assertEquals('media/images/test-image.jpg', $entity->path);
		$this->assertEquals('Test image alt text', $entity->alt);
		$this->assertEquals(1920, $entity->width);
		$this->assertEquals(1080, $entity->height);
		$this->assertEquals(['key' => 'value'], $entity->metaData);
		$this->assertEquals('FF0000', $entity->averageColor);
		$this->assertEquals(1, $entity->preview);
		$this->assertEquals(1, $entity->avif);
		$this->assertEquals(1, $entity->webp);
		$this->assertNull($entity->crop);
		$this->assertEquals('50,50', $entity->focusPoint);
		$this->assertEquals(10, $entity->systemOrder);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Media::$fieldMap
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testFieldMapDuringConstruction(): void {
		$properties = [
			'media_folder_id' => 456,
			'mime_type' => 'video/mp4',
			'meta_data' => ['duration' => 120],
			'average_color' => '#00FF00',
			'focus_point' => '25,75',
			'system_order' => 5,
			'media_resized_images' => [],
			'media_assignments' => [],
		];

		$entity = new Media($properties);
		$entityArray = $entity->toArray();

		foreach ($entityArray as $key => $value) {
			$this->assertStringNotContainsString('_', $key);
		}
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Media::_setName()
	 * @noinspection PhpVariableNamingConventionInspection
	 * @noinspection PhpConditionAlreadyCheckedInspection
	 */
	public function testNameCleaningViaPropertyAssignment(): void {
		$entity = new Media();

		$entity->name = 'Test File Name.jpg';
		$this->assertEquals('test-file-name.jpg', $entity->name);

		$entity->name = 'TestFileName.pdf';
		$this->assertEquals('testfilename.pdf', $entity->name);

		$entity->name = 'Test-File-Name.png';
		$this->assertEquals('test-file-name.png', $entity->name);

		$entity->name = 'Test File Name!@#$%.docx';
		$this->assertEquals('test-file-name.docx', $entity->name);

		$entity->name = 'UPPERCASE FILE.TXT';
		$this->assertEquals('uppercase-file.txt', $entity->name);

		// Test chained file suffixes removal
		$entity->name = 'filename.foo.bar.jpg';
		$this->assertEquals('filename.jpg', $entity->name);

		$entity->name = 'file.backup.old.2023.pdf';
		$this->assertEquals('file.pdf', $entity->name);

		// Test file without extension
		$entity->name = 'filename-without-extension';
		$this->assertEquals('filename-without-extension', $entity->name);

		$entity->name = null;
		$this->assertNull($entity->name);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Media::_setName()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testNameCleaningViaSetMethod(): void {
		$entity = new Media();

		$entity->set('name', 'Test File Name.jpg');
		$this->assertEquals('test-file-name.jpg', $entity->name);

		$entity->set('name', 'TestFileName.pdf');
		$this->assertEquals('testfilename.pdf', $entity->name);

		$entity->set('name', 'Test-File-Name.png');
		$this->assertEquals('test-file-name.png', $entity->name);

		$entity->set('name', 'Test File Name!@#$%.docx');
		$this->assertEquals('test-file-name.docx', $entity->name);

		$entity->set('name', 'UPPERCASE FILE.TXT');
		$this->assertEquals('uppercase-file.txt', $entity->name);

		// Test chained file suffixes removal
		$entity->set('name', 'filename.foo.bar.jpg');
		$this->assertEquals('filename.jpg', $entity->name);

		$entity->set('name', 'file.backup.old.2023.pdf');
		$this->assertEquals('file.pdf', $entity->name);

		// Test file without extension
		$entity->set('name', 'filename-without-extension');
		$this->assertEquals('filename-without-extension', $entity->name);

		/** @noinspection PhpRedundantOptionalArgumentInspection */
		$entity->set('name', null);
		$this->assertNull($entity->name);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Media::_getPreviewPath()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testPreviewPathVirtualProperty(): void {
		$entity = new Media([
			'name' => 'document.pdf',
			'path' => 'media/documents/document.pdf',
			'mimeType' => 'application/pdf',
		]);
		$this->assertEquals('media/documents/_pdf_preview' . DS . 'document.jpg', $entity->previewPath);

		// Image files should return null
		$entity = new Media([
			'name' => 'image.jpg',
			'path' => 'media/images/image.jpg',
			'mimeType' => 'image/jpeg',
		]);
		$this->assertNull($entity->previewPath);

		$entity = new Media(['path' => null]);
		$this->assertNull($entity->previewPath);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Media::_getOriginalPreviewPath()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testOriginalPreviewPathVirtualProperty(): void {
		$entity = new Media();
		$entity->patch([
			'name' => 'original-document.pdf',
			'path' => 'media/documents/original-document.pdf',
			'mimeType' => 'application/pdf',
		], ['asOriginal' => true]);
		$entity->patch([
			'name' => 'document.png',
			'path' => 'media/images/document.png',
			'mimeType' => 'image/png',
		]);
		$this->assertEquals('media/documents/_pdf_preview' . DS . 'original-document.jpg', $entity->originalPreviewPath);

		// Original image files should return null
		$entity = new Media();
		$entity->patch([
			'name' => 'original-image.jpg',
			'path' => 'media/images/original-image.jpg',
			'mimeType' => 'image/jpeg',
		], ['asOriginal' => true]);
		$this->assertNull($entity->originalPreviewPath);

		$entityWithoutOriginal = new Media();
		$this->assertNull($entityWithoutOriginal->originalPreviewPath);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Media::_getPreviewPathAbsolute()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testPreviewPathAbsoluteVirtualProperty(): void {
		$entity = new Media([
			'name' => 'document.pdf',
			'path' => 'media/documents/document.pdf',
			'mimeType' => 'application/pdf',
		]);
		$expected = WWW_ROOT . str_replace('/', DS, 'media/documents/_pdf_preview' . DS . 'document.jpg');
		$this->assertEquals($expected, $entity->previewPathAbsolute);

		// Image files should return null
		$entity = new Media([
			'name' => 'image.jpg',
			'path' => 'media/images/image.jpg',
			'mimeType' => 'image/jpeg',
		]);
		$this->assertNull($entity->previewPathAbsolute);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Media::_getOriginalPreviewPathAbsolute()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testOriginalPreviewPathAbsoluteVirtualProperty(): void {
		$entity = new Media();
		$entity->patch([
			'name' => 'original-document.pdf',
			'path' => 'media/documents/original-document.pdf',
			'mimeType' => 'application/pdf',
		], ['asOriginal' => true]);
		$entity->patch([
			'name' => 'document.png',
			'path' => 'media/images/document.png',
			'mimeType' => 'image/png',
		]);
		$expected = WWW_ROOT . str_replace('/', DS, 'media/documents/_pdf_preview' . DS . 'original-document.jpg');
		$this->assertEquals($expected, $entity->originalPreviewPathAbsolute);

		// Original image files should return null
		$entity = new Media();
		$entity->patch([
			'name' => 'original-image.jpg',
			'path' => 'media/images/original-image.jpg',
			'mimeType' => 'image/jpeg',
		], ['asOriginal' => true]);
		$this->assertNull($entity->originalPreviewPathAbsolute);

		$entityWithoutOriginal = new Media();
		$this->assertNull($entityWithoutOriginal->originalPreviewPathAbsolute);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Media::_setAverageColor()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testAverageColorSetter(): void {
		$entity = new Media();

		// Test with hash prefix - should be removed
		$entity->averageColor = '#FF0000';
		/** @noinspection PhpConditionAlreadyCheckedInspection */
		$this->assertEquals('FF0000', $entity->averageColor);

		// Test without hash prefix - should remain unchanged
		$entity->averageColor = '00FF00';
		/** @noinspection PhpConditionAlreadyCheckedInspection */
		$this->assertEquals('00FF00', $entity->averageColor);

		// Test with null - should remain null
		$entity->averageColor = null;
		/** @noinspection PhpConditionAlreadyCheckedInspection */
		$this->assertNull($entity->averageColor);

		// Test via set method
		$entity->set('averageColor', '#0000FF');
		/** @noinspection PhpConditionAlreadyCheckedInspection */
		$this->assertEquals('0000FF', $entity->averageColor);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Media::moveConvertedFiles()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testMoveConvertedFiles(): void {
		$tempDir = 'media' . DS . 'test_' . uniqid('moveConvertedFiles') . DS;
		mkdir(WWW_ROOT . $tempDir, 0755, true);
		$this->tempDirs[] = $tempDir;

		$entity = new Media();

		$entity->patch([
			'name' => 'original-test.pdf',
			'path' => $tempDir . 'original-test.pdf',
			'mimeType' => 'application/pdf',
		], ['asOriginal' => true]);

		$entity->patch([
			'name' => 'test.pdf',
			'path' => $tempDir . 'test.pdf',
			'mimeType' => 'application/pdf',
		]);

		// Create temporary original files to move
		$originalAvifDir = dirname($entity->originalAvifPathAbsolute);
		$originalWebpDir = dirname($entity->originalWebpPathAbsolute);
		$originalPreviewDir = dirname($entity->originalPreviewPathAbsolute);

		if (!is_dir($originalAvifDir)) {
			mkdir($originalAvifDir, 0755, true);
		}
		if (!is_dir($originalWebpDir)) {
			mkdir($originalWebpDir, 0755, true);
		}
		if (!is_dir($originalPreviewDir)) {
			mkdir($originalPreviewDir, 0755, true);
		}

		file_put_contents($entity->originalAvifPathAbsolute, 'test avif content');
		file_put_contents($entity->originalWebpPathAbsolute, 'test webp content');
		file_put_contents($entity->originalPreviewPathAbsolute, 'test preview content');

		// Execute the method
		$entity->moveConvertedFiles();

		// Verify files were moved
		$this->assertFileDoesNotExist($entity->originalAvifPathAbsolute);
		$this->assertFileDoesNotExist($entity->originalWebpPathAbsolute);
		$this->assertFileDoesNotExist($entity->originalPreviewPathAbsolute);

		$this->assertFileExists($entity->avifPathAbsolute);
		$this->assertFileExists($entity->webpPathAbsolute);
		$this->assertFileExists($entity->previewPathAbsolute);

		$this->assertEquals('test avif content', file_get_contents($entity->avifPathAbsolute));
		$this->assertEquals('test webp content', file_get_contents($entity->webpPathAbsolute));
		$this->assertEquals('test preview content', file_get_contents($entity->previewPathAbsolute));
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Media::moveConvertedFiles()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testMoveConvertedFilesDeletesWhenTargetIsNull(): void {
		$tempDir = 'media' . DS . 'test_' . uniqid('move_converted_files_null') . DS;
		mkdir(WWW_ROOT . $tempDir, 0755, true);
		$this->tempDirs[] = $tempDir;

		$entity = new Media();

		$entity->patch([
			'name' => 'original-test.pdf',
			'path' => $tempDir . 'original-test.pdf',
			'mimeType' => 'application/pdf',
		], ['asOriginal' => true]);

		$entity->patch([
			'name' => 'test.pdf',
			'path' => null, // No target path for converted file
			'mimeType' => 'application/pdf',
		]);

		// Create temporary original files to move
		$originalAvifDir = dirname($entity->originalAvifPathAbsolute);
		$originalWebpDir = dirname($entity->originalWebpPathAbsolute);
		$originalPreviewDir = dirname($entity->originalPreviewPathAbsolute);

		if (!is_dir($originalAvifDir)) {
			mkdir($originalAvifDir, 0755, true);
		}
		if (!is_dir($originalWebpDir)) {
			mkdir($originalWebpDir, 0755, true);
		}
		if (!is_dir($originalPreviewDir)) {
			mkdir($originalPreviewDir, 0755, true);
		}

		file_put_contents($entity->originalAvifPathAbsolute, 'test avif content');
		file_put_contents($entity->originalWebpPathAbsolute, 'test webp content');
		file_put_contents($entity->originalPreviewPathAbsolute, 'test preview content');

		// Execute the method
		$entity->moveConvertedFiles();

		// Verify files were removed
		$this->assertFileDoesNotExist($entity->originalAvifPathAbsolute);
		$this->assertFileDoesNotExist($entity->originalWebpPathAbsolute);
		$this->assertFileDoesNotExist($entity->originalPreviewPathAbsolute);

		$this->assertNull($entity->avifPathAbsolute);
		$this->assertNull($entity->webpPathAbsolute);
		$this->assertNull($entity->previewPathAbsolute);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Media::deleteConvertedFiles()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testDeleteConvertedFiles(): void {
		$tempDir = 'media' . DS . 'test_' . uniqid('del_converted_files') . DS;
		mkdir(WWW_ROOT . $tempDir, 0755, true);
		$this->tempDirs[] = $tempDir;

		$entity = new Media();

		$entity->patch([
			'name' => 'original-test.pdf',
			'path' => $tempDir . 'original-test.pdf',
			'mimeType' => 'application/pdf',
		], ['asOriginal' => true]);

		$entity->patch([
			'name' => 'test.pdf',
			'path' => $tempDir . 'test.pdf',
			'mimeType' => 'application/pdf',
		]);

		// Create temporary files to delete
		$previewDir = dirname($entity->previewPathAbsolute);
		$avifDir = dirname($entity->avifPathAbsolute);
		$webpDir = dirname($entity->webpPathAbsolute);
		$originalPreviewDir = dirname($entity->originalPreviewPathAbsolute);
		$originalAvifDir = dirname($entity->originalAvifPathAbsolute);
		$originalWebpDir = dirname($entity->originalWebpPathAbsolute);

		foreach ([$previewDir, $avifDir, $webpDir, $originalPreviewDir, $originalAvifDir, $originalWebpDir] as $dir) {
			if (!is_dir($dir)) {
				mkdir($dir, 0755, true);
			}
		}

		file_put_contents($entity->previewPathAbsolute, 'preview content');
		file_put_contents($entity->avifPathAbsolute, 'avif content');
		file_put_contents($entity->webpPathAbsolute, 'webp content');
		file_put_contents($entity->originalPreviewPathAbsolute, 'original preview content');
		file_put_contents($entity->originalAvifPathAbsolute, 'original avif content');
		file_put_contents($entity->originalWebpPathAbsolute, 'original webp content');

		// Verify all files were deleted
		$this->assertFileExists($entity->previewPathAbsolute);
		$this->assertFileExists($entity->avifPathAbsolute);
		$this->assertFileExists($entity->webpPathAbsolute);
		$this->assertFileExists($entity->originalPreviewPathAbsolute);
		$this->assertFileExists($entity->originalAvifPathAbsolute);
		$this->assertFileExists($entity->originalWebpPathAbsolute);

		// Execute the method
		$entity->deleteConvertedFiles();

		// Verify all files were deleted
		$this->assertFileDoesNotExist($entity->previewPathAbsolute);
		$this->assertFileDoesNotExist($entity->avifPathAbsolute);
		$this->assertFileDoesNotExist($entity->webpPathAbsolute);
		$this->assertFileDoesNotExist($entity->originalPreviewPathAbsolute);
		$this->assertFileDoesNotExist($entity->originalAvifPathAbsolute);
		$this->assertFileDoesNotExist($entity->originalWebpPathAbsolute);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Media::moveResizedFiles()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testMoveResizedFiles(): void {
		$tempDir = 'media' . DS . 'test_' . uniqid('move_resized_files') . DS;
		mkdir(WWW_ROOT . $tempDir, 0755, true);
		$this->tempDirs[] = $tempDir;

		/** @var \Awyiss\Model\Table\MediaFoldersTable $mediaFoldersTable */
		$mediaFoldersTable = FactoryLocator::get('Table')->get('MediaFolders');

		$mediaFolder = $mediaFoldersTable->newDefaultEntity([
			'title' => 'Temporary Media Folder',
			'path' => trim($tempDir, DS),
		]);
		$result = $mediaFoldersTable->save($mediaFolder);

		$this->assertNotFalse($result);
		$this->assertIsInt($mediaFolder->id);

		$this->tempMediaFolderIds[] = $mediaFolder->id;

		/** @var \Awyiss\Model\Table\MediaTable $mediaTable */
		$mediaTable = FactoryLocator::get('Table')->get('Media');
		/** @var \Awyiss\Model\Table\MediaResizedImagesTable $resizedTable */
		$resizedTable = FactoryLocator::get('Table')->get('MediaResizedImages');

		// Create temporary media entity in database
		$tempMedia = $mediaTable->newDefaultEntity([
			'mediaFolderId' => $mediaFolder->id,
			'mimeType' => 'image/jpeg',
			'name' => 'temp-original.jpg',
			'path' => $tempDir . 'temp-original.jpg',
			'width' => 100.0,
			'height' => 100.0,
		]);
		$result = $mediaTable->save($tempMedia);

		$this->assertNotFalse($result);
		$this->assertIsInt($tempMedia->id);

		$this->tempMediaIds[] = $tempMedia->id;

		// Create physical original file
		file_put_contents($tempMedia->pathAbsolute, 'temporary original content');

		$this->assertFileExists($tempMedia->pathAbsolute);

		$tempMedia->patch([
			'name' => 'temp-test.jpg',
			'path' => $tempDir . 'temp-test.jpg',
		]);

		// Create temporary resized image records
		$resizedImage1 = $resizedTable->newDefaultEntity([
			'mediaId' => $tempMedia->id,
			'name' => 'temp-original-[100x100].jpg',
			'path' => $tempDir . '_resized' . DS . 'temp-original-[100x100].jpg',
			'width' => 100,
			'height' => 100,
			'realWidth' => 100,
			'realHeight' => 100,
			'strategy' => 1,
			'status' => 1,
		]);
		$result = $resizedTable->save($resizedImage1);

		$this->assertNotFalse($result);
		$this->assertIsInt($resizedImage1->id);

		$this->tempResizedImageIds[] = $resizedImage1->id;

		$resizedImage2 = $resizedTable->newDefaultEntity([
			'mediaId' => $tempMedia->id,
			'name' => 'temp-original-[200x200].jpg',
			'path' => $tempDir . '_resized' . DS . 'temp-original-[200x200].jpg',
			'width' => 200,
			'height' => 200,
			'realWidth' => 200,
			'realHeight' => 200,
			'strategy' => 1,
			'status' => 1,
		]);
		$result = $resizedTable->save($resizedImage2);

		$this->assertNotFalse($result);
		$this->assertIsInt($resizedImage2->id);

		$this->tempResizedImageIds[] = $resizedImage2->id;

		$this->assertSame($tempDir . '_resized/temp-original-[100x100].jpg', $resizedImage1->path);
		$this->assertSame($tempDir . '_resized/temp-original-[200x200].jpg', $resizedImage2->path);

		// Create physical resized files
		$resizedDir = WWW_ROOT . $tempDir . '_resized' . DS;
		mkdir($resizedDir, 0755, true);
		file_put_contents(WWW_ROOT . $resizedImage1->path, 'resized content 100x100');
		file_put_contents(WWW_ROOT . $resizedImage2->path, 'resized content 200x200');

		$this->assertFileExists(WWW_ROOT . $resizedImage1->path);
		$this->assertFileExists(WWW_ROOT . $resizedImage2->path);

		// Execute the method
		$tempMedia->moveResizedFiles();

		// Verify database records were updated
		/** @var \Awyiss\Model\Entity\MediaResizedImage $updatedResized1 */
		$updatedResized1 = $resizedTable->get($resizedImage1->id);
		/** @var \Awyiss\Model\Entity\MediaResizedImage $updatedResized2 */
		$updatedResized2 = $resizedTable->get($resizedImage2->id);

		$this->assertEquals('temp-test-[100x100].jpg', $updatedResized1->name);
		$this->assertEquals('temp-test-[200x200].jpg', $updatedResized2->name);
		$this->assertSame($tempDir . '_resized/temp-test-[100x100].jpg', $updatedResized1->path);
		$this->assertSame($tempDir . '_resized/temp-test-[200x200].jpg', $updatedResized2->path);

		// Verify physical files were moved
		$this->assertFileDoesNotExist(WWW_ROOT . $resizedImage1->path);
		$this->assertFileDoesNotExist(WWW_ROOT . $resizedImage1->path);
		$this->assertFileExists(WWW_ROOT . $updatedResized1->path);
		$this->assertFileExists(WWW_ROOT . $updatedResized2->path);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Media::deleteResizedFiles()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testDeleteResizedFiles(): void {
		usleep(500);

		$tempDir = 'media' . DS . 'test_' . uniqid('del_resized_files') . DS;
		mkdir(WWW_ROOT . $tempDir, 0755, true);
		$this->tempDirs[] = $tempDir;

		/** @var \Awyiss\Model\Table\MediaFoldersTable $mediaFoldersTable */
		$mediaFoldersTable = FactoryLocator::get('Table')->get('MediaFolders');

		$mediaFolder = $mediaFoldersTable->newDefaultEntity([
			'title' => 'Temporary Media Folder',
			'path' => trim($tempDir, DS),
		]);
		$result = $mediaFoldersTable->save($mediaFolder);

		$this->assertNotFalse($result);
		$this->assertIsInt($mediaFolder->id);

		$this->tempMediaFolderIds[] = $mediaFolder->id;

		/** @var \Awyiss\Model\Table\MediaTable $mediaTable */
		$mediaTable = FactoryLocator::get('Table')->get('Media');
		/** @var \Awyiss\Model\Table\MediaResizedImagesTable $resizedTable */
		$resizedTable = FactoryLocator::get('Table')->get('MediaResizedImages');

		// Create temporary media entity in database
		$tempMedia = $mediaTable->newDefaultEntity([
			'mediaFolderId' => $mediaFolder->id,
			'mimeType' => 'image/jpeg',
			'name' => 'temp-delete.jpg',
			'path' => $tempDir . 'temp-delete.jpg',
			'width' => 100.0,
			'height' => 100.0,
		]);
		$result = $mediaTable->save($tempMedia);

		$this->assertNotFalse($result);
		$this->assertIsInt($tempMedia->id);

		$this->tempMediaIds[] = $tempMedia->id;

		// Create temporary resized image records
		$resizedImage1 = $resizedTable->newDefaultEntity([
			'media_id' => $tempMedia->id,
			'name' => 'temp-delete-[100x100].jpg',
			'path' => $tempDir . '_resized' . DS . 'temp-delete-[100x100].jpg',
			'width' => 100,
			'height' => 100,
			'real_width' => 100,
			'real_height' => 100,
			'strategy' => 1,
			'status' => 1,
		]);
		$result = $resizedTable->save($resizedImage1);

		$this->assertNotFalse($result);
		$this->assertIsInt($resizedImage1->id);

		$this->tempResizedImageIds[] = $resizedImage1->id;

		$resizedImage2 = $resizedTable->newDefaultEntity([
			'media_id' => $tempMedia->id,
			'name' => 'temp-delete-[200x200].jpg',
			'path' => $tempDir . '_resized' . DS . 'temp-delete-[200x200].jpg',
			'width' => 200,
			'height' => 200,
			'real_width' => 200,
			'real_height' => 200,
			'strategy' => 1,
			'status' => 1,
		]);
		$result = $resizedTable->save($resizedImage2);

		$this->assertNotFalse($result);
		$this->assertIsInt($resizedImage2->id);

		$this->tempResizedImageIds[] = $resizedImage2->id;

		$this->assertSame($tempDir . '_resized/temp-delete-[100x100].jpg', $resizedImage1->path);
		$this->assertSame($tempDir . '_resized/temp-delete-[200x200].jpg', $resizedImage2->path);

		// Create physical resized files
		$resizedDir = WWW_ROOT . $tempDir . '_resized' . DS;
		mkdir($resizedDir, 0755, true);
		file_put_contents(WWW_ROOT . $resizedImage1->path, 'resized content 100x100');
		file_put_contents(WWW_ROOT . $resizedImage2->path, 'resized content 200x200');

		$this->assertFileExists(WWW_ROOT . $resizedImage1->path);
		$this->assertFileExists(WWW_ROOT . $resizedImage2->path);

		// Execute the method
		$tempMedia->deleteResizedFiles();

		// Verify database records were deleted
		$this->assertEquals(0, $resizedTable->find()->where(['media_id' => $tempMedia->id])->count());

		// Verify physical files were deleted
		$this->assertFileDoesNotExist(WWW_ROOT . $resizedImage1->path);
		$this->assertFileDoesNotExist(WWW_ROOT . $resizedImage2->path);
	}
}
