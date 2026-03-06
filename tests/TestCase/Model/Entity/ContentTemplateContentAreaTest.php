<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Model\Entity;


use Awyiss\Model\Entity\ContentTemplateContentArea;
use Awyiss\Test\TestSuite\TestCase;
use Cake\Datasource\FactoryLocator;


/**
 * ContentTemplateContentArea Entity Test Case
 *
 * @see \Awyiss\Model\Entity\ContentTemplateContentArea
 */
class ContentTemplateContentAreaTest extends TestCase {
	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\ContentTemplateContentArea::$fieldMap
	 */
	public function testFieldMapCompleteness(): void {
		/** @var \Awyiss\Model\Table\ContentTemplateContentAreasTable $table */
		$table = FactoryLocator::get('Table')->get('ContentTemplateContentAreas');
		$entity = $table->newDefaultEntity();
		$entityArray = $entity->toArray();

		foreach ($entityArray as $key => $value) {
			$this->assertStringNotContainsString('_', $key);
		}
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\ContentTemplateContentArea::$_accessible
	 */
	public function testAccessibleFields(): void {
		$entity = new ContentTemplateContentArea();

		$this->assertSame([
			'contentTemplateId' => true,
			'contentAreaId' => true,
			'pageTemplateId' => true,
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
	 * @see \Awyiss\Model\Entity\ContentTemplateContentArea::$_virtual
	 */
	public function testVirtualFields(): void {
		$entity = new ContentTemplateContentArea();

		$this->assertSame([], $entity->getVirtual());
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\ContentTemplateContentArea
	 */
	public function testEntityConstruction(): void {
		$properties = [
			'id' => 1,
			'contentTemplateId' => 123,
			'contentAreaId' => 456,
			'pageTemplateId' => 789,
		];

		$entity = new ContentTemplateContentArea($properties);

		$this->assertEquals(1, $entity->id);
		$this->assertEquals(123, $entity->contentTemplateId);
		$this->assertEquals(456, $entity->contentAreaId);
		$this->assertEquals(789, $entity->pageTemplateId);
	}
}
