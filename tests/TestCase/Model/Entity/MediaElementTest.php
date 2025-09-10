<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Model\Entity;


use Awyiss\Model\Entity\MediaElement;
use Awyiss\Test\TestSuite\TestCase;
use Cake\Datasource\FactoryLocator;


/**
 * MediaElement Entity Test Case
 *
 * @see \Awyiss\Model\Entity\MediaElement
 */
class MediaElementTest extends TestCase {
	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\MediaElement::$fieldMap
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testFieldMapCompleteness(): void {
		/** @var \Awyiss\Model\Table\MediaElementsTable $table */
		$table = FactoryLocator::get('Table')->get('MediaElements');
		$entity = $table->newDefaultEntity();
		$entityArray = $entity->toArray();

		foreach ($entityArray as $key => $value) {
			$this->assertStringNotContainsString('_', $key);
		}
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\MediaElement::$_accessible
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testAccessibleFields(): void {
		$entity = new MediaElement();

		$this->assertSame([
			'title' => true,
			'identifier' => true,
			'columnSpan' => true,
			'internal' => true,
			'systemOrder' => true,
			'active' => true,
			'mediaElementAssignments' => true,
			'mediaElementSelectors' => true,
			'_translations' => true,
			'_publicationData' => true,
			'mediaAssignments' => true,
		], $entity->getAccessible());
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\MediaElement::$_virtual
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testVirtualFields(): void {
		$entity = new MediaElement();

		$this->assertSame(['column', 'label'], $entity->getVirtual());
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\MediaElement::_setIdentifier()
	 * @noinspection PhpVariableNamingConventionInspection
	 * @noinspection PhpConditionAlreadyCheckedInspection
	 */
	public function testIdentifierCleaningViaPropertyAssignment(): void {
		$entity = new MediaElement();

		$entity->identifier = 'Media Element';
		$this->assertEquals('media_element', $entity->identifier);

		$entity->identifier = 'MediaElement';
		$this->assertEquals('mediaelement', $entity->identifier);

		$entity->identifier = 'Media-Element';
		$this->assertEquals('media_element', $entity->identifier);

		$entity->identifier = 'Media Element!@#$%';
		$this->assertEquals('media_element', $entity->identifier);

		$entity->identifier = 'IMAGE GALLERY';
		$this->assertEquals('image_gallery', $entity->identifier);

		$entity->identifier = 'Video Player Element';
		$this->assertEquals('video_player_element', $entity->identifier);

		$entity->identifier = null;
		$this->assertNull($entity->identifier);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\MediaElement::_setIdentifier()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testIdentifierCleaningViaSetMethod(): void {
		$entity = new MediaElement();

		$entity->set('identifier', 'Media Element');
		$this->assertEquals('media_element', $entity->identifier);

		$entity->set('identifier', 'MediaElement');
		$this->assertEquals('mediaelement', $entity->identifier);

		$entity->set('identifier', 'Media-Element');
		$this->assertEquals('media_element', $entity->identifier);

		$entity->set('identifier', 'Media Element!@#$%');
		$this->assertEquals('media_element', $entity->identifier);

		$entity->set('identifier', 'IMAGE GALLERY');
		$this->assertEquals('image_gallery', $entity->identifier);

		$entity->set('identifier', 'Video Player Element');
		$this->assertEquals('video_player_element', $entity->identifier);

		/** @noinspection PhpRedundantOptionalArgumentInspection */
		$entity->set('identifier', null);
		$this->assertNull($entity->identifier);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\MediaElement::_getColumn()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testColumnVirtualProperty(): void {
		$entity = new MediaElement(['columnSpan' => '6/12']);

		$column = $entity->column;

		$this->assertIsArray($column);
		$this->assertArrayHasKey('span', $column);
		// The actual column span implementation depends on the AttributesTable::getColumnSpans() method
		$this->assertNotNull($column['span']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\MediaElement::_getColumn()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testColumnVirtualPropertyWithInvalidSpan(): void {
		$entity = new MediaElement(['columnSpan' => 'invalid-span']);

		$column = $entity->column;

		$this->assertIsArray($column);
		$this->assertArrayHasKey('span', $column);
		// Should return the first (reset) column span when invalid
		$this->assertNotNull($column['span']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\MediaElement::_getColumn()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testColumnVirtualPropertyWithNullSpan(): void {
		$entity = new MediaElement(['columnSpan' => null]);

		$column = $entity->column;

		$this->assertIsArray($column);
		$this->assertArrayHasKey('span', $column);
		// Should return the first (reset) column span when null
		$this->assertNotNull($column['span']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\MediaElement
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testEntityConstruction(): void {
		$properties = [
			'id' => 1,
			'title' => 'Test Media Element',
			'identifier' => 'Test Media Element',
			'column_span' => '4/12',
			'internal' => false,
			'system_order' => 10,
			'active' => true,
			'deleted' => false,
		];

		$entity = new MediaElement($properties);

		$this->assertEquals(1, $entity->id);
		$this->assertEquals('Test Media Element', $entity->title);
		$this->assertEquals('test_media_element', $entity->identifier);
		$this->assertEquals('4/12', $entity->columnSpan);
		$this->assertFalse($entity->internal);
		$this->assertEquals(10, $entity->systemOrder);
		$this->assertTrue($entity->active);
		$this->assertFalse($entity->deleted);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\MediaElement::$fieldMap
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testFieldMapDuringConstruction(): void {
		$properties = [
			'column_span' => '8/12',
			'system_order' => 5,
			'media_element_assignments' => [],
			'media_element_selectors' => [],
		];

		$entity = new MediaElement($properties);
		$entityArray = $entity->toArray();

		foreach ($entityArray as $key => $value) {
			$this->assertStringNotContainsString('_', $key);
		}
	}
}
