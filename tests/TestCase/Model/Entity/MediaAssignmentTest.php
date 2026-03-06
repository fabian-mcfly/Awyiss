<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Model\Entity;


use Awyiss\Model\Entity\MediaAssignment;
use Awyiss\Test\TestSuite\TestCase;
use Cake\Datasource\FactoryLocator;


/**
 * MediaAssignment Entity Test Case
 *
 * @see \Awyiss\Model\Entity\MediaAssignment
 */
class MediaAssignmentTest extends TestCase {
	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\MediaAssignment::$fieldMap
	 */
	public function testFieldMapCompleteness(): void {
		/** @var \Awyiss\Model\Table\MediaAssignmentsTable $table */
		$table = FactoryLocator::get('Table')->get('MediaAssignments');
		$entity = $table->newDefaultEntity();
		$entityArray = $entity->toArray();

		foreach ($entityArray as $key => $value) {
			$this->assertStringNotContainsString('_', $key);
		}
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\MediaAssignment::$_accessible
	 */
	public function testAccessibleFields(): void {
		$entity = new MediaAssignment();

		$this->assertSame([
			'mediaElementId' => true,
			'mediaElementSelectorIdentifier' => true,
			'mediaId' => true,
			'mediaFolderId' => true,
			'scope' => true,
			'foreignKey' => true,
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
	 * @see \Awyiss\Model\Entity\MediaAssignment
	 */
	public function testEntityConstruction(): void {
		$properties = [
			'id' => 1,
			'mediaElementId' => 123,
			'mediaElementSelectorIdentifier' => 'gallery_selector',
			'mediaId' => 456,
			'mediaFolderId' => 789,
			'scope' => 'Pages',
			'foreignKey' => 101,
			'systemOrder' => 10,
		];

		$entity = new MediaAssignment($properties);

		$this->assertEquals(1, $entity->id);
		$this->assertEquals(123, $entity->mediaElementId);
		$this->assertEquals('gallery_selector', $entity->mediaElementSelectorIdentifier);
		$this->assertEquals(456, $entity->mediaId);
		$this->assertEquals(789, $entity->mediaFolderId);
		$this->assertEquals('Pages', $entity->scope);
		$this->assertEquals(101, $entity->foreignKey);
		$this->assertEquals(10, $entity->systemOrder);
	}
}
