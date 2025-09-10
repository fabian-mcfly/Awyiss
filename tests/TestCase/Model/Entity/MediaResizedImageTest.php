<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Model\Entity;


use Awyiss\Model\Entity\MediaResizedImage;
use Awyiss\Test\TestSuite\TestCase;
use Cake\Datasource\FactoryLocator;


/**
 * MediaResizedImage Entity Test Case
 *
 * @see \Awyiss\Model\Entity\MediaResizedImage
 */
class MediaResizedImageTest extends TestCase {
	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\MediaResizedImage::$fieldMap
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testFieldMapCompleteness(): void {
		/** @var \Awyiss\Model\Table\MediaResizedImagesTable $table */
		$table = FactoryLocator::get('Table')->get('MediaResizedImages');
		$entity = $table->newDefaultEntity();
		$entityArray = $entity->toArray();

		foreach ($entityArray as $key => $value) {
			$this->assertStringNotContainsString('_', $key);
		}
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\MediaResizedImage::$_accessible
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testAccessibleFields(): void {
		$entity = new MediaResizedImage();

		$this->assertSame([
			'mediaId' => true,
			'name' => true,
			'path' => true,
			'width' => true,
			'height' => true,
			'realWidth' => true,
			'realHeight' => true,
			'strategy' => true,
			'status' => true,
			'_translations' => true,
			'_publicationData' => true,
			'mediaAssignments' => true,
			'mediaElementAssignments' => true,
		], $entity->getAccessible());
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\MediaResizedImage::$_virtual
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testVirtualFields(): void {
		$entity = new MediaResizedImage();

		$this->assertSame(['extension', 'pathAbsolute', 'realWidth', 'realHeight'], $entity->getVirtual());
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\MediaResizedImage::_getExtension()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testExtensionVirtualPropertyWithValidName(): void {
		$entity = new MediaResizedImage(['name' => 'image.jpg']);

		$extension = $entity->extension;

		$this->assertEquals('jpg', $extension);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\MediaResizedImage::_getExtension()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testExtensionVirtualPropertyWithMultipleDots(): void {
		$entity = new MediaResizedImage(['name' => 'my.image.file.png']);

		$extension = $entity->extension;

		$this->assertEquals('png', $extension);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\MediaResizedImage::_getExtension()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testExtensionVirtualPropertyWithNoExtension(): void {
		$entity = new MediaResizedImage(['name' => 'filename']);

		$extension = $entity->extension;

		$this->assertNull($extension);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\MediaResizedImage::_getExtension()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testExtensionVirtualPropertyWithNullName(): void {
		$entity = new MediaResizedImage(['name' => null]);

		$extension = $entity->extension;

		$this->assertNull($extension);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\MediaResizedImage::_getPathAbsolute()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testPathAbsoluteVirtualPropertyWithValidPath(): void {
		$entity = new MediaResizedImage(['path' => 'media/images/test.jpg']);

		$pathAbsolute = $entity->pathAbsolute;

		$this->assertIsString($pathAbsolute);
		$this->assertSame(WWW_ROOT . 'media/images/test.jpg', $pathAbsolute);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\MediaResizedImage::_getPathAbsolute()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testPathAbsoluteVirtualPropertyWithNullPath(): void {
		$entity = new MediaResizedImage(['path' => null]);

		$pathAbsolute = $entity->pathAbsolute;

		$this->assertNull($pathAbsolute);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\MediaResizedImage
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testEntityConstruction(): void {
		$properties = [
			'id' => 1,
			'media_id' => 123,
			'name' => 'resized_image.jpg',
			'path' => 'media/resized/image.jpg',
			'width' => 800,
			'height' => 600,
			'real_width' => 750,
			'real_height' => 550,
			'strategy' => 'fit',
			'status' => 'completed',
		];

		$entity = new MediaResizedImage($properties);

		$this->assertEquals(1, $entity->id);
		$this->assertEquals(123, $entity->mediaId);
		$this->assertEquals('resized_image.jpg', $entity->name);
		$this->assertEquals('media/resized/image.jpg', $entity->path);
		$this->assertEquals(800, $entity->width);
		$this->assertEquals(600, $entity->height);
		$this->assertEquals(750, $entity->realWidth);
		$this->assertEquals(550, $entity->realHeight);
		$this->assertEquals('fit', $entity->strategy);
		$this->assertEquals('completed', $entity->status);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\MediaResizedImage::$fieldMap
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testFieldMapDuringConstruction(): void {
		$properties = [
			'media_id' => 456,
			'real_width' => 1024,
			'real_height' => 768,
		];

		$entity = new MediaResizedImage($properties);
		$entityArray = $entity->toArray();

		foreach ($entityArray as $key => $value) {
			$this->assertStringNotContainsString('_', $key);
		}
	}
}
