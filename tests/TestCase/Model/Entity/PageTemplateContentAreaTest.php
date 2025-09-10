<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Model\Entity;


use Awyiss\Model\Entity\PageTemplateContentArea;
use Awyiss\Test\TestSuite\TestCase;
use Cake\Datasource\FactoryLocator;


/**
 * PageTemplateContentArea Entity Test Case
 *
 * @see \Awyiss\Model\Entity\PageTemplateContentArea
 */
class PageTemplateContentAreaTest extends TestCase {
	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\PageTemplateContentArea::$fieldMap
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testFieldMapCompleteness(): void {
		/** @var \Awyiss\Model\Table\PageTemplateContentAreasTable $table */
		$table = FactoryLocator::get('Table')->get('PageTemplateContentAreas');
		$entity = $table->newDefaultEntity();
		$entityArray = $entity->toArray();

		foreach ($entityArray as $key => $value) {
			$this->assertStringNotContainsString('_', $key);
		}
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\PageTemplateContentArea::$_accessible
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testAccessibleFields(): void {
		$entity = new PageTemplateContentArea();

		$this->assertSame([
			'pageTemplateId' => true,
			'contentAreaId' => true,
			'systemOrder' => true,
			'_translations' => true,
			'_publicationData' => true,
			'mediaAssignments' => true,
			'mediaElementAssignments' => true,
		], $entity->getAccessible());
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\PageTemplateContentArea::$_virtual
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testVirtualFields(): void {
		$entity = new PageTemplateContentArea();

		$this->assertSame([], $entity->getVirtual());
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\PageTemplateContentArea
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testEntityConstruction(): void {
		$properties = [
			'id' => 1,
			'page_template_id' => 123,
			'content_area_id' => 456,
			'system_order' => 10,
		];

		$entity = new PageTemplateContentArea($properties);

		$this->assertEquals(1, $entity->id);
		$this->assertEquals(123, $entity->pageTemplateId);
		$this->assertEquals(456, $entity->contentAreaId);
		$this->assertEquals(10, $entity->systemOrder);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\PageTemplateContentArea::$fieldMap
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testFieldMapDuringConstruction(): void {
		$properties = [
			'page_template_id' => 789,
			'content_area_id' => 101,
			'system_order' => 5,
		];

		$entity = new PageTemplateContentArea($properties);
		$entityArray = $entity->toArray();

		foreach ($entityArray as $key => $value) {
			$this->assertStringNotContainsString('_', $key);
		}
	}
}
