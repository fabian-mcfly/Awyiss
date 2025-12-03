<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Model\Entity;


use Awyiss\Model\Entity\MediaSelector;
use Awyiss\Test\TestSuite\TestCase;
use Cake\Datasource\FactoryLocator;


/**
 * MediaSelector Entity Test Case
 *
 * @see \Awyiss\Model\Entity\MediaSelector
 */
class MediaSelectorTest extends TestCase {
	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\MediaSelector::$fieldMap
	 */
	public function testFieldMapCompleteness(): void {
		/** @var \Awyiss\Model\Table\MediaSelectorsTable $table */
		$table = FactoryLocator::get('Table')->get('MediaSelectors');
		$entity = $table->newDefaultEntity();
		$entityArray = $entity->toArray();

		foreach ($entityArray as $key => $value) {
			$this->assertStringNotContainsString('_', $key);
		}
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\MediaSelector::$_accessible
	 */
	public function testAccessibleFields(): void {
		$entity = new MediaSelector();

		$this->assertSame([
			'title' => true,
			'identifier' => true,
			'active' => true,
			'_translations' => true,
			'_publicationData' => true,
			'mediaAssignments' => true,
			'mediaElementAssignments' => true,
		], $entity->getAccessible());
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\MediaSelector::_setIdentifier()
	 * @noinspection PhpConditionAlreadyCheckedInspection
	 */
	public function testIdentifierCleaningViaPropertyAssignment(): void {
		$entity = new MediaSelector();

		$entity->identifier = 'Image Gallery';
		$this->assertEquals('image_gallery', $entity->identifier);

		$entity->identifier = 'ImageGallery';
		$this->assertEquals('imagegallery', $entity->identifier);

		$entity->identifier = 'Image-Gallery';
		$this->assertEquals('image_gallery', $entity->identifier);

		$entity->identifier = 'Image Gallery!@#$%';
		$this->assertEquals('image_gallery', $entity->identifier);

		$entity->identifier = 'VIDEO SELECTOR';
		$this->assertEquals('video_selector', $entity->identifier);

		$entity->identifier = 'Product Image Selector';
		$this->assertEquals('product_image_selector', $entity->identifier);

		$entity->identifier = null;
		$this->assertNull($entity->identifier);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\MediaSelector::_setIdentifier()
	 */
	public function testIdentifierCleaningViaSetMethod(): void {
		$entity = new MediaSelector();

		$entity->set('identifier', 'Image Gallery');
		$this->assertEquals('image_gallery', $entity->identifier);

		$entity->set('identifier', 'ImageGallery');
		$this->assertEquals('imagegallery', $entity->identifier);

		$entity->set('identifier', 'Image-Gallery');
		$this->assertEquals('image_gallery', $entity->identifier);

		$entity->set('identifier', 'Image Gallery!@#$%');
		$this->assertEquals('image_gallery', $entity->identifier);

		$entity->set('identifier', 'VIDEO SELECTOR');
		$this->assertEquals('video_selector', $entity->identifier);

		$entity->set('identifier', 'Product Image Selector');
		$this->assertEquals('product_image_selector', $entity->identifier);

		/** @noinspection PhpRedundantOptionalArgumentInspection */
		$entity->set('identifier', null);
		$this->assertNull($entity->identifier);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\MediaSelector
	 */
	public function testEntityConstruction(): void {
		$properties = [
			'id' => 1,
			'title' => 'Product Gallery',
			'identifier' => 'Product Image Gallery',
			'active' => true,
			'deleted' => false,
		];

		$entity = new MediaSelector($properties);

		$this->assertEquals(1, $entity->id);
		$this->assertEquals('Product Gallery', $entity->title);
		$this->assertEquals('product_image_gallery', $entity->identifier);
		$this->assertTrue($entity->active);
		$this->assertFalse($entity->deleted);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\MediaSelector::$fieldMap
	 */
	public function testFieldMapDuringConstruction(): void {
		$properties = [
			'title' => 'Test Media Selector',
			'identifier' => 'test_selector',
			'active' => true,
		];

		$entity = new MediaSelector($properties);
		$entityArray = $entity->toArray();

		foreach ($entityArray as $key => $value) {
			$this->assertStringNotContainsString('_', $key);
		}
	}
}
