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
			'customerGroupAccessSettings' => true,
			'customerGroupAssignments' => true,
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
		$this->assertEquals('imageGallery', $entity->identifier);

		$entity->identifier = 'ImageGallery';
		$this->assertEquals('imageGallery', $entity->identifier);

		$entity->identifier = 'Image-Gallery';
		$this->assertEquals('imageGallery', $entity->identifier);

		$entity->identifier = 'Image Gallery!@#$%';
		$this->assertEquals('imageGallery', $entity->identifier);

		$entity->identifier = 'VIDEO SELECTOR';
		$this->assertEquals('vIDEOSELECTOR', $entity->identifier);

		$entity->identifier = 'Product Image Selector';
		$this->assertEquals('productImageSelector', $entity->identifier);

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
		$this->assertEquals('imageGallery', $entity->identifier);

		$entity->set('identifier', 'ImageGallery');
		$this->assertEquals('imageGallery', $entity->identifier);

		$entity->set('identifier', 'Image-Gallery');
		$this->assertEquals('imageGallery', $entity->identifier);

		$entity->set('identifier', 'Image Gallery!@#$%');
		$this->assertEquals('imageGallery', $entity->identifier);

		$entity->set('identifier', 'VIDEO SELECTOR');
		$this->assertEquals('vIDEOSELECTOR', $entity->identifier);

		$entity->set('identifier', 'Product Image Selector');
		$this->assertEquals('productImageSelector', $entity->identifier);

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
		$this->assertEquals('productImageGallery', $entity->identifier);
		$this->assertTrue($entity->active);
		$this->assertFalse($entity->deleted);
	}
}
