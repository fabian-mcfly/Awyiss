<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Model\Entity;


use Awyiss\Model\Entity\MediaElementAssignment;
use Awyiss\Test\TestSuite\TestCase;
use Cake\Datasource\FactoryLocator;


/**
 * MediaElementAssignment Entity Test Case
 *
 * @see \Awyiss\Model\Entity\MediaElementAssignment
 */
class MediaElementAssignmentTest extends TestCase {
	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\MediaElementAssignment::$fieldMap
	 */
	public function testFieldMapCompleteness(): void {
		/** @var \Awyiss\Model\Table\MediaElementAssignmentsTable $table */
		$table = FactoryLocator::get('Table')->get('MediaElementAssignments');
		$entity = $table->newDefaultEntity();
		$entityArray = $entity->toArray();

		foreach ($entityArray as $key => $value) {
			$this->assertStringNotContainsString('_', $key);
		}
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\MediaElementAssignment::$_accessible
	 */
	public function testAccessibleFields(): void {
		$entity = new MediaElementAssignment();

		$this->assertSame([
			'mediaElementId' => true,
			'mediaElement' => true,
			'scope' => true,
			'foreignKey' => true,
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
	 * @see \Awyiss\Model\Entity\MediaElementAssignment
	 */
	public function testEntityConstruction(): void {
		$properties = [
			'id' => 1,
			'mediaElementId' => 123,
			'scope' => 'Pages',
			'foreignKey' => 456,
		];

		$entity = new MediaElementAssignment($properties);

		$this->assertEquals(1, $entity->id);
		$this->assertEquals(123, $entity->mediaElementId);
		$this->assertEquals('Pages', $entity->scope);
		$this->assertEquals(456, $entity->foreignKey);
	}
}
