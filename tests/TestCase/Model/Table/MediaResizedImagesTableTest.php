<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Model\Table;


use Awyiss\Model\Entity\MediaResizedImage;
use Awyiss\Model\Enum\ProcessStatus;
use Awyiss\Model\Enum\ResizeStrategy;
use Awyiss\Model\Table\MediaResizedImagesTable;
use Awyiss\ORM\Association\BelongsTo;
use Awyiss\Test\TestSuite\TestCase;
use Awyiss\Validation\Validator;
use Cake\Datasource\FactoryLocator;
use InvalidArgumentException;


/**
 * MediaResizedImagesTable Test Case
 *
 * @see \Awyiss\Model\Table\MediaResizedImagesTable
 */
class MediaResizedImagesTableTest extends TestCase {
	/**
	 * @var \Awyiss\Model\Table\MediaResizedImagesTable
	 */
	protected MediaResizedImagesTable $mediaResizedImagesTable;


	/**
	 * @inheritDoc
	 */
	protected function setUp(): void {
		parent::setUp();

		/** @noinspection PhpFieldAssignmentTypeMismatchInspection */
		$this->mediaResizedImagesTable = FactoryLocator::get('Table')->get('MediaResizedImages');
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\MediaResizedImagesTable::ATTRIBUTABLE
	 */
	public function testAttributableConstant(): void {
		$this->assertFalse($this->mediaResizedImagesTable::ATTRIBUTABLE);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\MediaResizedImagesTable::TABLE
	 */
	public function testTableConstant(): void {
		$this->assertEquals('media_resized_images', $this->mediaResizedImagesTable::TABLE);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\MediaResizedImagesTable::initialize()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testInitializeAssociations(): void {
		$this->assertCount(1, $this->mediaResizedImagesTable->associations()->keys());

		// Test Media association (BelongsTo)
		$this->assertTrue($this->mediaResizedImagesTable->hasAssociation('Media'));
		$mediaAssociation = $this->mediaResizedImagesTable->getAssociation('Media');
		$this->assertInstanceOf(BelongsTo::class, $mediaAssociation);
		$this->assertFalse($mediaAssociation->getCascadeCallbacks());
		$this->assertFalse($mediaAssociation->getDependent());
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\MediaResizedImagesTable::newEntityFromMedia()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testNewEntityFromMediaWithDefaults(): void {
		/** @var \Awyiss\Model\Table\MediaTable $mediaResizedImagesTable */
		$mediaResizedImagesTable = FactoryLocator::get('Table')->get('Media');
		/** @var \Awyiss\Model\Entity\Media $media */
		$media = $mediaResizedImagesTable->get(4); // PNG image from seed

		$entity = $this->mediaResizedImagesTable->newEntityFromMedia($media, 800);

		/** @noinspection PhpConditionAlreadyCheckedInspection */
		$this->assertInstanceOf(MediaResizedImage::class, $entity);
		$this->assertTrue($entity->isNew());
		$this->assertSame(4, $entity->mediaId);
		$this->assertSame($media, $entity->media);
		$this->assertSame(800, $entity->width);
		$this->assertNull($entity->height);
		$this->assertEquals(ResizeStrategy::Contain, $entity->strategy);
		$this->assertSame('logo-awyiss-[w800].avif', $entity->name);
		$this->assertSame('../awyiss/Command/Media/TestFiles/_resized/logo-awyiss-[w800].avif', $entity->path);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\MediaResizedImagesTable::newEntityFromMedia()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testNewEntityFromMediaWithCustomParameters(): void {
		/** @var \Awyiss\Model\Table\MediaTable $mediaResizedImagesTable */
		$mediaResizedImagesTable = FactoryLocator::get('Table')->get('Media');
		/** @var \Awyiss\Model\Entity\Media $media */
		$media = $mediaResizedImagesTable->get(2); // JPG image from seed

		$entity = $this->mediaResizedImagesTable->newEntityFromMedia(
			$media,
			1200,
			800,
			ResizeStrategy::Cover,
			'webp'
		);

		/** @noinspection PhpConditionAlreadyCheckedInspection */
		$this->assertInstanceOf(MediaResizedImage::class, $entity);
		$this->assertSame(2, $entity->mediaId);
		$this->assertSame(1200, $entity->width);
		$this->assertSame(800, $entity->height);
		$this->assertEquals(ResizeStrategy::Cover, $entity->strategy);
		$this->assertSame('logo-awyiss-[w1200h800cover].webp', $entity->name);
		$this->assertSame('../awyiss/Command/Media/TestFiles/_resized/logo-awyiss-[w1200h800cover].webp', $entity->path);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\MediaResizedImagesTable::newEntityFromMedia()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testNewEntityFromMediaWithHeightOnly(): void {
		/** @var \Awyiss\Model\Table\MediaTable $mediaResizedImagesTable */
		$mediaResizedImagesTable = FactoryLocator::get('Table')->get('Media');
		/** @var \Awyiss\Model\Entity\Media $media */
		$media = $mediaResizedImagesTable->get(4);

		$entity = $this->mediaResizedImagesTable->newEntityFromMedia($media, null, 600);

		$this->assertNull($entity->width);
		$this->assertSame(600, $entity->height);
		$this->assertSame('logo-awyiss-[h600].avif', $entity->name);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\MediaResizedImagesTable::newEntityFromMedia()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testNewEntityFromMediaWithDifferentFormats(): void {
		/** @var \Awyiss\Model\Table\MediaTable $mediaResizedImagesTable */
		$mediaResizedImagesTable = FactoryLocator::get('Table')->get('Media');
		/** @var \Awyiss\Model\Entity\Media $media */
		$media = $mediaResizedImagesTable->get(4);

		$formats = ['avif', 'webp', 'jpg', 'png'];
		foreach ($formats as $format) {
			$entity = $this->mediaResizedImagesTable->newEntityFromMedia($media, 800, null, ResizeStrategy::Contain, $format);
			$this->assertStringEndsWith('.' . $format, $entity->name);
		}
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\MediaResizedImagesTable::newEntityFromMedia()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testNewEntityFromMediaWithUnsupportedFormat(): void {
		/** @var \Awyiss\Model\Table\MediaTable $mediaResizedImagesTable */
		$mediaResizedImagesTable = FactoryLocator::get('Table')->get('Media');
		/** @var \Awyiss\Model\Entity\Media $media */
		$media = $mediaResizedImagesTable->get(4);

		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('The format is not supported.');

		$this->mediaResizedImagesTable->newEntityFromMedia($media, 800, null, ResizeStrategy::Contain, 'bmp');
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\MediaResizedImagesTable::validationDefault()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testValidationDefault(): void {
		$validator = new Validator();
		$result = $this->mediaResizedImagesTable->validationDefault($validator);

		$this->assertInstanceOf(Validator::class, $result);
		$this->assertSame('media_resized_images', $result->getI18nDomain());

		// Test required fields
		$this->assertTrue($result->hasField('mediaId'));
		$this->assertSame('create', $result->field('mediaId')->isPresenceRequired());

		$this->assertTrue($result->hasField('name'));
		$this->assertSame('create', $result->field('name')->isPresenceRequired());

		$this->assertTrue($result->hasField('strategy'));
		$this->assertSame('create', $result->field('strategy')->isPresenceRequired());

		// Test other fields
		$this->assertTrue($result->hasField('id'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\MediaResizedImagesTable::validationDefault()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testEntityValidationSuccess(): void {
		$data = [
			'mediaId' => 1,
			'name' => 'test-image-[w800].avif',
			'path' => '/path/to/_resized/test-image-[w800].avif',
			'width' => 800,
			'height' => 600,
			'realWidth' => 800,
			'realHeight' => 600,
			'strategy' => ResizeStrategy::Contain,
			'status' => ProcessStatus::Success,
		];

		$entity = $this->mediaResizedImagesTable->newDefaultEntity();
		$this->mediaResizedImagesTable->patchEntity($entity, $data);
		$errors = $entity->getErrors();

		$this->assertEmpty($errors, 'Valid data should not produce validation errors');
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\MediaResizedImagesTable::validationDefault()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testEntityValidationMissingRequired(): void {
		$data = [
			'path' => '/path/to/image.avif',
		];

		$entity = $this->mediaResizedImagesTable->newDefaultEntity();
		$this->mediaResizedImagesTable->patchEntity($entity, $data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('mediaId', $errors);
		$this->assertArrayHasKey('name', $errors);
		$this->assertArrayHasKey('strategy', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\MediaResizedImagesTable::validationDefault()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testEntityValidationInvalidTypes(): void {
		$data = [
			'id' => 'not_an_integer',
			'mediaId' => 'not_an_integer',
			'name' => true,
			'strategy' => 'invalid_strategy',
			'status' => 'invalid_status',
		];

		$entity = $this->mediaResizedImagesTable->newDefaultEntity();
		$this->mediaResizedImagesTable->patchEntity($entity, $data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('id', $errors);
		$this->assertArrayHasKey('mediaId', $errors);
		$this->assertArrayHasKey('name', $errors);

		$this->assertArrayHasKey('status', $errors);
		$this->assertArrayHasKey('enum', $errors['status']);
		$this->assertSame('media_resized_images::error_enum', $errors['status']['enum']);

		$this->assertArrayHasKey('strategy', $errors);
		$this->assertArrayHasKey('enum', $errors['strategy']);
		$this->assertSame('media_resized_images::error_enum', $errors['strategy']['enum']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\MediaResizedImagesTable::validationDefault()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testEntityValidationFieldLength(): void {
		$data = [
			'id' => 123456789123, // exceeds 11 char limit
			'mediaId' => 123456789123, // exceeds 11 char limit
			'name' => str_repeat('a', 101), // exceeds 100 char limit
		];

		$entity = $this->mediaResizedImagesTable->newDefaultEntity();
		$this->mediaResizedImagesTable->patchEntity($entity, $data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('id', $errors);
		$this->assertArrayHasKey('mediaId', $errors);
		$this->assertArrayHasKey('name', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\MediaResizedImagesTable::validationDefault()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testEntityValidationNotBlank(): void {
		$data = [
			'mediaId' => 1,
			'name' => '   ', // only whitespace
			'strategy' => ResizeStrategy::Contain,
		];

		$entity = $this->mediaResizedImagesTable->newDefaultEntity();
		$this->mediaResizedImagesTable->patchEntity($entity, $data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('name', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\MediaResizedImagesTable::validationDefault()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testEntityValidationStrategyEnum(): void {
		// Test valid strategy
		$data = [
			'mediaId' => 1,
			'name' => 'test-image.avif',
			'strategy' => ResizeStrategy::Cover,
		];

		$entity = $this->mediaResizedImagesTable->newDefaultEntity();
		$this->mediaResizedImagesTable->patchEntity($entity, $data);
		$errors = $entity->getErrors();

		$this->assertArrayNotHasKey('strategy', $errors);

		// Test invalid strategy
		$data['strategy'] = 'invalid_strategy';

		$entity = $this->mediaResizedImagesTable->newDefaultEntity();
		$this->mediaResizedImagesTable->patchEntity($entity, $data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('strategy', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\MediaResizedImagesTable::buildRules()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testBuildRulesValidMediaId(): void {
		// Test with existing media
		$data = [
			'mediaId' => 1,
			'name' => 'test-resized.avif',
			'strategy' => ResizeStrategy::Contain,
		];

		$entity = $this->mediaResizedImagesTable->newDefaultEntity();
		$this->mediaResizedImagesTable->patchEntity($entity, $data);
		$result = $this->mediaResizedImagesTable->checkRules($entity);
		$this->assertTrue($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\MediaResizedImagesTable::buildRules()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testBuildRulesInvalidMediaId(): void {
		// Test with non-existing media
		$data = [
			'mediaId' => 99999,
			'name' => 'test-resized.avif',
			'strategy' => ResizeStrategy::Contain,
		];

		$entity = $this->mediaResizedImagesTable->newDefaultEntity();
		$this->mediaResizedImagesTable->patchEntity($entity, $data);
		$result = $this->mediaResizedImagesTable->checkRules($entity);
		$this->assertFalse($result);

		$errors = $entity->getErrors();
		$this->assertArrayHasKey('mediaId', $errors);
		$this->assertArrayHasKey('validMediaId', $errors['mediaId']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\MediaTable::buildRules()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testBuildRulesValidStatus(): void {
		$data = [
			'mediaId' => 1,
			'name' => 'test-resized.avif',
			'status' => 1, // Patching entity will convert to enum
		];

		$entity = $this->mediaResizedImagesTable->newDefaultEntity();

		$this->mediaResizedImagesTable->patchEntity($entity, $data);

		$this->assertSame(ProcessStatus::Success, $entity->status);

		$result = $this->mediaResizedImagesTable->checkRules($entity);

		$this->assertTrue($result);

		$entity->status = ProcessStatus::InProgress;

		$result = $this->mediaResizedImagesTable->checkRules($entity);

		$this->assertTrue($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\MediaTable::buildRules()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testBuildRulesInvalidStatus(): void {
		$data = [
			'mediaId' => 1,
			'name' => 'test-resized.avif',
			'status' => 'invalid_value', // Patching entity will convert to enum but fail here
		];

		$entity = $this->mediaResizedImagesTable->newDefaultEntity();

		$this->mediaResizedImagesTable->patchEntity($entity, $data);

		$this->assertSame(ProcessStatus::Undefined, $entity->status);

		$result = $this->mediaResizedImagesTable->checkRules($entity);

		$this->assertTrue($result);

		$entity->status = 'invalid';  // Setting a value directly will not convert to enum

		$result = $this->mediaResizedImagesTable->checkRules($entity);

		$this->assertFalse($result);

		$errors = $entity->getErrors();
		$this->assertArrayHasKey('status', $errors);
		$this->assertArrayHasKey('validStatus', $errors['status']);
		$this->assertSame('media_resized_images::error_valid_status', $errors['status']['validStatus']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\MediaTable::buildRules()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testBuildRulesValidStrategy(): void {
		$data = [
			'mediaId' => 1,
			'name' => 'test-resized.avif',
			'strategy' => 1, // Patching entity will convert to enum
		];

		$entity = $this->mediaResizedImagesTable->newDefaultEntity();

		$this->mediaResizedImagesTable->patchEntity($entity, $data);

		$this->assertSame(ResizeStrategy::Contain, $entity->strategy);

		$result = $this->mediaResizedImagesTable->checkRules($entity);

		$this->assertTrue($result);

		$entity->strategy = ResizeStrategy::Cover;

		$result = $this->mediaResizedImagesTable->checkRules($entity);

		$this->assertTrue($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\MediaTable::buildRules()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testBuildRulesInvalidStrategy(): void {
		$data = [
			'mediaId' => 1,
			'name' => 'test-resized.avif',
			'strategy' => 'invalid_value', // Patching entity will convert to enum but fail here
		];

		$entity = $this->mediaResizedImagesTable->newDefaultEntity();

		$this->mediaResizedImagesTable->patchEntity($entity, $data);

		$this->assertSame(ResizeStrategy::Contain, $entity->strategy);

		$result = $this->mediaResizedImagesTable->checkRules($entity);

		$this->assertTrue($result);

		$entity->strategy = 'invalid';  // Setting a value directly will not convert to enum

		$result = $this->mediaResizedImagesTable->checkRules($entity);

		$this->assertFalse($result);

		$errors = $entity->getErrors();
		$this->assertArrayHasKey('strategy', $errors);
		$this->assertArrayHasKey('validStrategy', $errors['strategy']);
		$this->assertSame('media_resized_images::error_valid_strategy', $errors['strategy']['validStrategy']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\MediaResizedImagesTable::newDefaultEntity()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testNewDefaultEntity(): void {
		/** @var \Awyiss\Model\Entity\MediaResizedImage $entity */
		$entity = $this->mediaResizedImagesTable->newDefaultEntity();

		$this->assertInstanceOf(MediaResizedImage::class, $entity);
		$this->assertTrue($entity->isNew());

		// Check default values
		$this->assertNull($entity->mediaId);
		$this->assertNull($entity->name);
		$this->assertNull($entity->path);
		$this->assertNull($entity->width);
		$this->assertNull($entity->height);
		$this->assertNull($entity->realWidth);
		$this->assertNull($entity->realHeight);
		$this->assertEquals(ResizeStrategy::Contain, $entity->strategy);
		$this->assertEquals(ProcessStatus::Undefined, $entity->status);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\MediaResizedImagesTable::newDefaultEntity()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testNewDefaultEntityWithData(): void {
		$additionalData = [
			'mediaId' => 2,
			'name' => 'custom-resized-[w1024].webp',
			'path' => '/custom/path/_resized/custom-resized-[w1024].webp',
			'width' => 1024,
			'height' => 768,
			'realWidth' => 1024,
			'realHeight' => 768,
			'strategy' => ResizeStrategy::Cover,
			'status' => ProcessStatus::InProgress,
		];

		/** @var \Awyiss\Model\Entity\MediaResizedImage $entity */
		$entity = $this->mediaResizedImagesTable->newDefaultEntity($additionalData);

		$this->assertInstanceOf(MediaResizedImage::class, $entity);
		$this->assertTrue($entity->isNew());

		// Check custom values
		$this->assertSame(2, $entity->mediaId);
		$this->assertSame('custom-resized-[w1024].webp', $entity->name);
		$this->assertSame('/custom/path/_resized/custom-resized-[w1024].webp', $entity->path);
		$this->assertSame(1024, $entity->width);
		$this->assertSame(768, $entity->height);
		$this->assertSame(1024, $entity->realWidth);
		$this->assertSame(768, $entity->realHeight);
		$this->assertEquals(ResizeStrategy::Cover, $entity->strategy);
		$this->assertEquals(ProcessStatus::InProgress, $entity->status);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\MediaResizedImagesTable::initializeSchema()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testInitializeSchemaEnumColumns(): void {
		$schema = $this->mediaResizedImagesTable->getSchema();

		// Test that enum columns are configured correctly
		$this->assertSame('enum-awyiss-model-enum-resizestrategy', $schema->getColumnType('strategy'));
		$this->assertSame('enum-awyiss-model-enum-processstatus', $schema->getColumnType('status'));
	}
}
