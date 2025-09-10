<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Utility\Media;


use Awyiss\Model\Entity\Media;
use Awyiss\Model\Entity\MediaResizedImage;
use Awyiss\Model\Enum\ProcessStatus;
use Awyiss\Model\Enum\ResizeStrategy;
use Awyiss\Test\TestSuite\TestCase;
use Awyiss\Utility\Media\ResizedImageManager;
use InvalidArgumentException;
use ReflectionClass;


/**
 * Test case for ResizedImageManagerTest class
 *
 * @see \Awyiss\Utility\Media\ResizedImageManager
 */
class ResizedImageManagerTest extends TestCase {
	/**
	 * @inheritDoc
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function setUp(): void {
		parent::setUp();

		// Clear static storage before each test
		ResizedImageManager::clear();
	}


	/**
	 * @inheritDoc
	 */
	public function tearDown(): void {
		ResizedImageManager::clear();
		parent::tearDown();
	}


	/**
	 * Test addMediaItem and getMediaItems methods
	 *
	 * @return void
	 * @see \Awyiss\Utility\Media\ResizedImageManager::addMediaItem()
	 * @see \Awyiss\Utility\Media\ResizedImageManager::getMediaItems()
	 * @see \Awyiss\Utility\Media\ResizedImageManager::getResizedItems()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testAddAndGetMediaItems(): void {
		// Create test media with resized images
		$media = $this->createMedia(100);
		$resizedImage = $this->createResizedImage(200, 100);
		$media->mediaResizedImages = [$resizedImage];

		// Add to manager
		ResizedImageManager::addMediaItem($media);

		// Verify stored correctly
		$mediaItems = ResizedImageManager::getMediaItems();
		$this->assertCount(1, $mediaItems);
		$this->assertSame($media, $mediaItems[100]);

		// Verify resized images also stored
		$resizedItems = ResizedImageManager::getResizedItems(100);
		$this->assertNotNull($resizedItems);
		$this->assertSame($resizedImage, $resizedItems[200]);
	}


	/**
	 * Test addMediaItemsFromEntity method
	 *
	 * @return void
	 * @see \Awyiss\Utility\Media\ResizedImageManager::addMediaItemsFromEntity()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testAddMediaItemsFromEntity(): void {
		// Create entity with media assignments
		/** @var \Awyiss\Model\Entity\Content $entity */
		$entity = $this->fetchTable('Contents')->newEntity([]);
		$media100 = $this->createMedia(100);
		$media101 = $this->createMedia(101);

		// Test scenario 1: Direct Media objects
		$entity->mediaAssignments = [
			'section1' => [
				'image' => $media100,
				'_other' => 'something',
			],
		];

		ResizedImageManager::addMediaItemsFromEntity($entity);
		$mediaItems = ResizedImageManager::getMediaItems();
		$this->assertCount(1, $mediaItems);
		$this->assertSame($media100, $mediaItems[100]);

		// Clear and test scenario 2: MediaAssignment objects
		ResizedImageManager::clear();

		/** @var \Awyiss\Model\Entity\MediaAssignment $assignment */
		$assignment = $this->fetchTable('MediaAssignments')->newEntity([]);
		$assignment->media = $media101;

		$entity->mediaAssignments = [
			'section2' => [
				'image' => $assignment,
			],
		];

		ResizedImageManager::addMediaItemsFromEntity($entity);
		$mediaItems = ResizedImageManager::getMediaItems();
		$this->assertCount(1, $mediaItems);
		$this->assertSame($media101, $mediaItems[101]);

		// Clear and test scenario 3: multiple sections with mixed media
		ResizedImageManager::clear();

		$entity->mediaAssignments = [
			'section1' => [
				'image1' => $media100,
				'_other' => 'something',
			],
			'section2' => [
				'image2' => $media100,
				'image3' => $media101,
			],
		];

		ResizedImageManager::addMediaItemsFromEntity($entity);
		$mediaItems = ResizedImageManager::getMediaItems();
		$this->assertCount(2, $mediaItems);
		$this->assertSame($media100, $mediaItems[100]);
		$this->assertSame($media101, $mediaItems[101]);

		// Test scenario 3: Array of Media objects
		ResizedImageManager::clear();
		$entity->mediaAssignments = [
			'section3' => [
				'gallery' => [$media100, $media101],
			],
		];

		ResizedImageManager::addMediaItemsFromEntity($entity);
		$mediaItems = ResizedImageManager::getMediaItems();
		$this->assertCount(2, $mediaItems);
		$this->assertSame($media100, $mediaItems[100]);
		$this->assertSame($media101, $mediaItems[101]);
	}


	/**
	 * Test setMediaItems method
	 *
	 * @return void
	 * @see \Awyiss\Utility\Media\ResizedImageManager::setMediaItems()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testSetMediaItems(): void {
		// Create test media
		$media100 = $this->createMedia(100);
		$media101 = $this->createMedia(101);

		// Test adding media objects
		ResizedImageManager::setMediaItems([$media100, $media101]);
		$mediaItems = ResizedImageManager::getMediaItems();
		$this->assertCount(2, $mediaItems);

		// Test adding media IDs (should fetch from table)
		$media2 = $this->fetchTable('Media')->get(2);

		ResizedImageManager::setMediaItems([2]);
		$mediaItems = ResizedImageManager::getMediaItems();
		$this->assertCount(3, $mediaItems);
		$this->assertEquals($media2, $mediaItems[2]);

		// Test replace mode (non-merge)
		ResizedImageManager::setMediaItems([$media100], false);
		$mediaItems = ResizedImageManager::getMediaItems();
		$this->assertCount(1, $mediaItems);
	}


	/**
	 * Test resize method with various scenarios
	 *
	 * @return void
	 * @see \Awyiss\Utility\Media\ResizedImageManager::resize()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testResizeFails(): void {
		// 1. Media without path
		$mediaNoPath = $this->createMedia(2);
		$mediaNoPath->path = null;
		$result = ResizedImageManager::resize($mediaNoPath, 400, 300);
		$this->assertNull($result);

		// 2. SVG media (should return null)
		$mediaSvg = $this->createMedia(3);
		$mediaSvg->mimeType = 'image/svg+xml';
		$result = ResizedImageManager::resize($mediaSvg, 400, 300);
		$this->assertNull($result);

		// 3. Media requiring preview but not ready
		$mediaPreviewNeeded = $this->createMedia(4);
		$mediaPreviewNeeded->preview = ProcessStatus::Undefined;
		$result = ResizedImageManager::resize($mediaPreviewNeeded, 400, 300);
		$this->assertNull($result);
	}


	/**
	 * Test resize
	 *
	 * @return void
	 * @see \Awyiss\Utility\Media\ResizedImageManager::resize()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testResizeWithImage(): void {
		/** @var \Awyiss\Model\Entity\Media $media */
		$media = $this->fetchTable('Media')->get(4);

		$this->assertEmpty(ResizedImageManager::getMediaItems());
		$this->assertNull(ResizedImageManager::getResizedItems(4));

		$resizedImage = ResizedImageManager::resize($media, 1024, null, null, ResizeStrategy::Contain, 'webp');

		$this->assertNotEmpty(ResizedImageManager::getMediaItems());

		$this->assertInstanceOf(MediaResizedImage::class, $resizedImage);
		$this->assertSame(1, $resizedImage->id);
		// Even when only resizing one item, all resized images are fetched
		$this->assertCount(27, ResizedImageManager::getResizedItems(4));
	}


	/**
	 * Test resize method without upscaling
	 *
	 * @return void
	 * @see \Awyiss\Utility\Media\ResizedImageManager::resize()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testResizeWithoutUpscale(): void {
		/** @var \Awyiss\Model\Entity\Media $media */
		$media = $this->fetchTable('Media')->get(4);

		/** @noinspection PhpRedundantOptionalArgumentInspection */
		$resizedImage = ResizedImageManager::resize($media, 1920, null, null, ResizeStrategy::Contain, 'avif', false, false);
		$this->assertInstanceOf(MediaResizedImage::class, $resizedImage);
		$this->assertSame(24, $resizedImage->id);

		/** @noinspection PhpRedundantOptionalArgumentInspection */
		$resizedImage = ResizedImageManager::resize($media, 2580, null, null, ResizeStrategy::Contain, 'avif', false, false);
		$this->assertNull($resizedImage);
	}


	/**
	 * Test resize method with various scenarios
	 *
	 * @return void
	 * @see \Awyiss\Utility\Media\ResizedImageManager::resize()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testResizeWithUpscale(): void {
		/** @var \Awyiss\Model\Entity\Media $media */
		$media = $this->fetchTable('Media')->get(4);

		$resizedImage = ResizedImageManager::resize($media, 1920, null, null, ResizeStrategy::Contain, 'avif', false, true);
		$this->assertInstanceOf(MediaResizedImage::class, $resizedImage);
		$this->assertSame(24, $resizedImage->id);

		$resizedImage = ResizedImageManager::resize($media, 2580, null, null, ResizeStrategy::Contain, 'avif', false, true);
		$this->assertInstanceOf(MediaResizedImage::class, $resizedImage);
		$this->assertGreaterThan(27, $resizedImage->id);
	}


	/**
	 * Test resize method with various scenarios
	 *
	 * @return void
	 * @see \Awyiss\Utility\Media\ResizedImageManager::resize()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testResizeWithoutDimensions(): void {
		/** @var \Awyiss\Model\Entity\Media $media */
		$media = $this->fetchTable('Media')->get(4);

		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('Either width or height must be set.');
		/** @noinspection PhpRedundantOptionalArgumentInspection */
		ResizedImageManager::resize($media, null, null);
	}


	/**
	 * Test resize when a matching resized image already exists
	 *
	 * @return void
	 * @see \Awyiss\Utility\Media\ResizedImageManager::resize()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testResizeWithImageAndExistingResizedImage(): void {
		/** @var \Awyiss\Model\Entity\Media $media */
		$media = $this->fetchTable('Media')->get(4);
		/** @var \Awyiss\Model\Entity\MediaResizedImage $resizedImage */
		$resizedImage = $this->fetchTable('MediaResizedImages')->get(1);

		// Manually set resized records
		$reflection = new ReflectionClass(ResizedImageManager::class);
		$property = $reflection->getProperty('resizedRecords');
		/** @noinspection PhpExpressionResultUnusedInspection */
		$property->setAccessible(true);
		$property->setValue(null, [4 => [1 => $resizedImage]]);

		// Test resize - should return existing image
		$result = ResizedImageManager::resize($media, 1024, null, null, ResizeStrategy::Contain, 'webp');
		$this->assertSame($resizedImage, $result);
		// When one item was set, additional items are not fetched
		$this->assertCount(1, ResizedImageManager::getResizedItems(4));
	}


	/**
	 * Test resize when a matching resized image already exists
	 *
	 * @return void
	 * @see \Awyiss\Utility\Media\ResizedImageManager::resize()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testResizeWithImageAndExistingResizedImageButDifferentStrategy(): void {
		/** @var \Awyiss\Model\Entity\Media $media */
		$media = $this->fetchTable('Media')->get(4);

		// Test resize - should return a new image
		$result = ResizedImageManager::resize($media, 1024, 1024, null, ResizeStrategy::Crop, 'webp');
		$this->assertInstanceOf(MediaResizedImage::class, $result);
		$this->assertGreaterThan(27, $result->id);

		// Test resize - should return a new image
		$result2 = ResizedImageManager::resize($media, 1024, 1024, null, ResizeStrategy::Cover, 'webp');
		$this->assertInstanceOf(MediaResizedImage::class, $result2);
		$this->assertGreaterThan(27, $result2->id);

		$this->assertNotSame($result->id, $result2->id);
	}


	/**
	 * Test resize when a matching resized image already exists
	 *
	 * @return void
	 * @see \Awyiss\Utility\Media\ResizedImageManager::resize()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testResizeWithImageWithNewResizedImage(): void {
		/** @var \Awyiss\Model\Entity\Media $media */
		$media = $this->fetchTable('Media')->get(4);

		// Test resize - should create new resized image
		$resizedImage = ResizedImageManager::resize($media, 800, 600, null, ResizeStrategy::Contain, 'webp');
		$this->assertInstanceOf(MediaResizedImage::class, $resizedImage);
		$this->assertGreaterThan(27, $resizedImage->id);

		// Verify resized images are stored
		$resizedItems = ResizedImageManager::getResizedItems(4);
		$this->assertCount(31, $resizedItems);
	}


	/**
	 * Test resize with aspect ratio
	 *
	 * @return void
	 * @see \Awyiss\Utility\Media\ResizedImageManager::resize()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testResizeWithAspectRatio(): void {
		/** @var \Awyiss\Model\Entity\Media $media */
		$media = $this->fetchTable('Media')->get(4);

		$this->assertEmpty(ResizedImageManager::getMediaItems());
		$this->assertNull(ResizedImageManager::getResizedItems(4));

		// Resize with width and aspect ratio
		$resizedImage = ResizedImageManager::resize($media, 1024, null, 1.3333, ResizeStrategy::Contain, 'webp', true);
		$this->assertNotEmpty(ResizedImageManager::getMediaItems());
		$this->assertInstanceOf(MediaResizedImage::class, $resizedImage);
		$this->assertGreaterThan(27, $resizedImage->id);
		$this->assertSame(768, $resizedImage->height);

		// Resize with height and aspect ratio
		$resizedImage2 = ResizedImageManager::resize($media, null, 768, 1.3333, ResizeStrategy::Contain, 'webp', true);
		$this->assertInstanceOf(MediaResizedImage::class, $resizedImage2);
		$this->assertGreaterThan(27, $resizedImage2->id);
		$this->assertSame(1024, $resizedImage2->width);

		$this->assertSame($resizedImage->id, $resizedImage2->id);

		// Resize with aspect ratio only
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('Either width or height must be set.');
		ResizedImageManager::resize($media, null, null, 1.3333, ResizeStrategy::Contain, 'webp', true);
	}


	/**
	 * Test resize will find existing resized image within threshold
	 *
	 * @return void
	 * @see \Awyiss\Utility\Media\ResizedImageManager::resize()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testResizeFindsWithinTreshold(): void {
		/** @var \Awyiss\Model\Entity\Media $media */
		$media = $this->fetchTable('Media')->get(4);

		$this->assertEmpty(ResizedImageManager::getMediaItems());
		$this->assertNull(ResizedImageManager::getResizedItems(4));

		// Will find the existing resized image with width 1024 because it is within the threshold of 10%
		$resizedImage = ResizedImageManager::resize($media, 960, null, null, ResizeStrategy::Contain, 'webp');

		$this->assertNotEmpty(ResizedImageManager::getMediaItems());
		$this->assertInstanceOf(MediaResizedImage::class, $resizedImage);
		$this->assertSame(1, $resizedImage->id);

		// Will find the existing resized image with width 1024 because it is within the threshold of 10%
		$resizedImage = ResizedImageManager::resize($media, 960, null, null, ResizeStrategy::Contain, 'webp', true);

		$this->assertInstanceOf(MediaResizedImage::class, $resizedImage);
		$this->assertGreaterThan(27, $resizedImage->id);
	}


	/**
	 * Test resize will find existing resized image within threshold, but only for the same format
	 *
	 * @return void
	 * @see \Awyiss\Utility\Media\ResizedImageManager::resize()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testResizeFindsWithinTresholdSameFormatOnly(): void {
		/** @var \Awyiss\Model\Entity\Media $media */
		$media = $this->fetchTable('Media')->get(4);

		$this->assertEmpty(ResizedImageManager::getMediaItems());
		$this->assertNull(ResizedImageManager::getResizedItems(4));

		// Will find the existing resized image with width 1024 because it is within the threshold of 10%
		/** @noinspection PhpRedundantOptionalArgumentInspection */
		$resizedImage = ResizedImageManager::resize($media, 960, null, null, ResizeStrategy::Contain, 'avif');

		$this->assertNotEmpty(ResizedImageManager::getMediaItems());
		$this->assertInstanceOf(MediaResizedImage::class, $resizedImage);
		// Will now find the ID 9
		$this->assertSame(9, $resizedImage->id);
	}


	/**
	 * Test resize will find existing resized image within threshold, but only for the same resize strategy
	 *
	 * @return void
	 * @see \Awyiss\Utility\Media\ResizedImageManager::resize()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testResizeFindsWithinTresholdSameStrategyOnly(): void {
		/** @var \Awyiss\Model\Entity\Media $media */
		$media = $this->fetchTable('Media')->get(4);

		$this->assertEmpty(ResizedImageManager::getMediaItems());
		$this->assertNull(ResizedImageManager::getResizedItems(4));

		$resizedImage = ResizedImageManager::resize($media, 1024, 800, null, ResizeStrategy::Crop, 'webp');

		$this->assertNotEmpty(ResizedImageManager::getMediaItems());
		$this->assertInstanceOf(MediaResizedImage::class, $resizedImage);
		$this->assertGreaterThan(27, $resizedImage->id);

		$resizedImage2 = ResizedImageManager::resize($media, 960, 780, null, ResizeStrategy::Crop, 'webp');

		$this->assertInstanceOf(MediaResizedImage::class, $resizedImage2);
		$this->assertGreaterThan(27, $resizedImage2->id);

		$this->assertSame($resizedImage->id, $resizedImage2->id);

		$resizedImage3 = ResizedImageManager::resize($media, 960, 780, null, ResizeStrategy::Cover, 'webp');

		$this->assertInstanceOf(MediaResizedImage::class, $resizedImage3);
		$this->assertGreaterThan(27, $resizedImage3->id);

		$this->assertNotSame($resizedImage->id, $resizedImage3->id);
	}

	/**
	 * Helper method to create a Media entity
	 *
	 * @param int $id
	 * @param int $width
	 * @param int $height
	 * @param string $type
	 * @return \Awyiss\Model\Entity\Media
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	protected function createMedia(int $id, int $width = 800, int $height = 600, string $type = 'jpg'): Media {
		$types = [
			'jpg' => [
				'mimeType' => 'image/jpeg',
				'preview' => ProcessStatus::NotRequired,
			],
			'png' => [
				'mimeType' => 'image/png',
				'preview' => ProcessStatus::NotRequired,
			],
			'svg' => [
				'mimeType' => 'image/svg+xml',
				'preview' => ProcessStatus::NotRequired,
			],
			'avif' => [
				'mimeType' => 'image/avif',
				'preview' => ProcessStatus::NotRequired,
			],
			'webp' => [
				'mimeType' => 'image/webp',
				'preview' => ProcessStatus::NotRequired,
			],
			'gif' => [
				'mimeType' => 'image/gif',
				'preview' => ProcessStatus::NotRequired,
			],
			'mp4' => [
				'mimeType' => 'video/mp4',
				'preview' => ProcessStatus::Undefined,
			],
			'pdf' => [
				'mimeType' => 'application/pdf',
				'preview' => ProcessStatus::Undefined,
			],
			'txt' => [
				'mimeType' => 'text/plain',
				'preview' => ProcessStatus::Undefined,
			],
		];

		/** @var \Awyiss\Model\Table\MediaTable $mediaTable */
		$mediaTable = $this->getTableLocator()->get('Media');

		/** @var \Awyiss\Model\Entity\Media $media */
		$media = $mediaTable->newEntity([]);
		$media->id = $id;
		$media->name = 'file' . $id . '.' . $type;
		$media->width = $width;
		$media->height = $height;
		$media->path =	'/path/to/media/file' . $id . '.' . $type;
		$media->mimeType = $types[ $type ]['mimeType'];
		$media->preview = $types[ $type ]['preview'];
		$media->avif = $type === 'avif' ? ProcessStatus::NotRequired : ProcessStatus::Undefined;
		$media->webp = $type === 'webp' ? ProcessStatus::NotRequired : ProcessStatus::Undefined;

		return $media;
	}


	/**
	 * Helper method to create a MediaResizedImage entity
	 *
	 * @param int $id
	 * @param int $mediaId
	 * @param int $width
	 * @param int $height
	 * @param \Awyiss\Model\Enum\ResizeStrategy $strategy
	 * @param string $extension
	 * @return \Awyiss\Model\Entity\MediaResizedImage
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	protected function createResizedImage(
		int $id,
		int $mediaId,
		int $width = 400,
		int $height = 300,
		ResizeStrategy $strategy = ResizeStrategy::Contain,
		string $extension = 'jpg'
	): MediaResizedImage {
		/** @var \Awyiss\Model\Table\MediaResizedImagesTable $mediaResizedTable */
		$mediaResizedTable = $this->getTableLocator()->get('MediaResizedImages');

		/** @var \Awyiss\Model\Entity\MediaResizedImage $resizedImage */
		$resizedImage = $mediaResizedTable->newEntity([]);
		$resizedImage->id = $id;
		$resizedImage->mediaId = $mediaId;
		$resizedImage->width = $width;
		$resizedImage->height = $height;
		$resizedImage->strategy = $strategy;
		$resizedImage->extension = $extension;

		return $resizedImage;
	}
}
