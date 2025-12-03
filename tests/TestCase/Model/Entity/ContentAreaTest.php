<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Model\Entity;


use Awyiss\Model\Entity\ContentArea;
use Awyiss\Test\TestSuite\TestCase;
use Cake\Datasource\FactoryLocator;


/**
 * ContentArea Entity Test Case
 *
 * @see \Awyiss\Model\Entity\ContentArea
 */
class ContentAreaTest extends TestCase {
	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\ContentArea::$fieldMap
	 */
	public function testFieldMapCompleteness(): void {
		/** @var \Awyiss\Model\Table\ContentAreasTable $table */
		$table = FactoryLocator::get('Table')->get('ContentAreas');
		$entity = $table->newDefaultEntity();
		$entityArray = $entity->toArray();

		foreach ($entityArray as $key => $value) {
			$this->assertStringNotContainsString('_', $key);
		}
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\ContentArea::$_accessible
	 */
	public function testAccessibleFields(): void {
		$entity = new ContentArea();

		$this->assertSame([
			'identifier' => true,
			'title' => true,
			'active' => true,
			'pageTemplates' => true,
			'contentTemplateContentAreas' => true,
			'_translations' => true,
			'_publicationData' => true,
			'mediaAssignments' => true,
			'mediaElementAssignments' => true,
		], $entity->getAccessible());
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\ContentArea
	 */
	public function testEntityConstruction(): void {
		$properties = [
			'id' => 1,
			'identifier' => 'main_content',
			'title' => 'Main Content Area',
			'active' => true,
			'deleted' => false,
		];

		$entity = new ContentArea($properties);

		$this->assertEquals(1, $entity->id);
		$this->assertEquals('main_content', $entity->identifier);
		$this->assertEquals('Main Content Area', $entity->title);
		$this->assertTrue($entity->active);
		$this->assertFalse($entity->deleted);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\ContentArea::$fieldMap
	 */
	public function testFieldMapDuringConstruction(): void {
		$properties = [
			'identifier' => 'test_area',
			'title' => 'Test Area',
			'page_templates' => [],
			'content_templates' => [],
		];

		$entity = new ContentArea($properties);
		$entityArray = $entity->toArray();

		foreach ($entityArray as $key => $value) {
			$this->assertStringNotContainsString('_', $key);
		}
	}
}
