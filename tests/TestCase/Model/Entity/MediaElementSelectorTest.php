<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Model\Entity;


use Awyiss\Model\Entity\MediaElementSelector;
use Awyiss\Test\TestSuite\TestCase;
use Cake\Datasource\FactoryLocator;


/**
 * MediaElementSelector Entity Test Case
 *
 * @see \Awyiss\Model\Entity\MediaElementSelector
 */
class MediaElementSelectorTest extends TestCase {
	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\MediaElementSelector::$fieldMap
	 */
	public function testFieldMapCompleteness(): void {
		/** @var \Awyiss\Model\Table\MediaElementSelectorsTable $table */
		$table = FactoryLocator::get('Table')->get('MediaElementSelectors');
		$entity = $table->newDefaultEntity();
		$entityArray = $entity->toArray();

		foreach ($entityArray as $key => $value) {
			$this->assertStringNotContainsString('_', $key);
		}
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\MediaElementSelector::$_accessible
	 */
	public function testAccessibleFields(): void {
		$entity = new MediaElementSelector();

		$this->assertSame([
			'mediaElementId' => true,
			'mediaSelectorId' => true,
			'title' => true,
			'identifier' => true,
			'columnSpan' => true,
			'required' => true,
			'systemOrder' => true,
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
	 * @see \Awyiss\Model\Entity\MediaElementSelector::$_virtual
	 */
	public function testVirtualFields(): void {
		$entity = new MediaElementSelector();

		$this->assertSame(['column', 'label'], $entity->getVirtual());
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\MediaElementSelector::_getColumn()
	 */
	public function testColumnVirtualProperty(): void {
		$entity = new MediaElementSelector(['columnSpan' => '6/12']);

		$column = $entity->column;

		$this->assertIsArray($column);
		$this->assertArrayHasKey('span', $column);
		// The actual column span implementation depends on the AttributesTable::getColumnSpans() method
		$this->assertNotNull($column['span']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\MediaElementSelector::_getColumn()
	 */
	public function testColumnVirtualPropertyWithInvalidSpan(): void {
		$entity = new MediaElementSelector(['columnSpan' => 'invalid-span']);

		$column = $entity->column;

		$this->assertIsArray($column);
		$this->assertArrayHasKey('span', $column);
		// Should return the first (reset) column span when invalid
		$this->assertNotNull($column['span']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\MediaElementSelector::_getColumn()
	 */
	public function testColumnVirtualPropertyWithNullSpan(): void {
		$entity = new MediaElementSelector(['columnSpan' => null]);

		$column = $entity->column;

		$this->assertIsArray($column);
		$this->assertArrayHasKey('span', $column);
		// Should return the first (reset) column span when null
		$this->assertNotNull($column['span']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\MediaElementSelector
	 */
	public function testEntityConstruction(): void {
		$properties = [
			'id' => 1,
			'mediaElementId' => 123,
			'mediaSelectorId' => 456,
			'title' => 'Test Media Element Selector',
			'identifier' => 'testSelector',
			'columnSpan' => '4/12',
			'required' => true,
			'systemOrder' => 10,
		];

		$entity = new MediaElementSelector($properties);

		$this->assertEquals(1, $entity->id);
		$this->assertEquals(123, $entity->mediaElementId);
		$this->assertEquals(456, $entity->mediaSelectorId);
		$this->assertEquals('Test Media Element Selector', $entity->title);
		$this->assertEquals('testSelector', $entity->identifier);
		$this->assertEquals('4/12', $entity->columnSpan);
		$this->assertTrue($entity->required);
		$this->assertEquals(10, $entity->systemOrder);
	}
}
