<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Model\Entity;


use Awyiss\Model\Entity\Design;
use Awyiss\Test\TestSuite\TestCase;
use Cake\Datasource\FactoryLocator;


/**
 * Design Entity Test Case
 *
 * @see \Awyiss\Model\Entity\Design
 */
class DesignTest extends TestCase {
	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Design::$fieldMap
	 */
	public function testFieldMapCompleteness(): void {
		/** @var \Awyiss\Model\Table\DesignsTable $table */
		$table = FactoryLocator::get('Table')->get('Designs');
		$entity = $table->newDefaultEntity();
		$entityArray = $entity->toArray();

		foreach ($entityArray as $key => $value) {
			$this->assertStringNotContainsString('_', $key);
		}
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Design::$_accessible
	 */
	public function testAccessibleFields(): void {
		$entity = new Design();

		$this->assertSame([
			'identifier' => true,
			'title' => true,
			'description' => true,
			'settings' => true,
			'css' => true,
			'inUse' => true,
			'isPreview' => true,
			'_translations' => true,
			'_publicationData' => true,
			'mediaAssignments' => true,
			'mediaElementAssignments' => true,
		], $entity->getAccessible());
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Design
	 */
	public function testEntityConstruction(): void {
		$properties = [
			'id' => 1,
			'identifier' => 'test_design',
			'title' => 'Test Design',
			'description' => 'A test design for unit testing',
			'settings' => ['color' => 'blue', 'font' => 'Arial'],
			'css' => '.test { color: red; }',
			'in_use' => true,
			'is_preview' => false,
			'deleted' => false,
		];

		$entity = new Design($properties);

		$this->assertEquals(1, $entity->id);
		$this->assertEquals('test_design', $entity->identifier);
		$this->assertEquals('Test Design', $entity->title);
		$this->assertEquals('A test design for unit testing', $entity->description);
		$this->assertEquals(['color' => 'blue', 'font' => 'Arial'], $entity->settings);
		$this->assertEquals('.test { color: red; }', $entity->css);
		$this->assertTrue($entity->inUse);
		$this->assertFalse($entity->isPreview);
		$this->assertFalse($entity->deleted);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Design::$fieldMap
	 */
	public function testFieldMapDuringConstruction(): void {
		$properties = [
			'in_use' => true,
			'is_preview' => false,
		];

		$entity = new Design($properties);
		$entityArray = $entity->toArray();

		foreach ($entityArray as $key => $value) {
			$this->assertStringNotContainsString('_', $key);
		}
	}
}
