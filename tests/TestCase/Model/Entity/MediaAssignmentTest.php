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
			'media_element_id' => 123,
			'media_element_selector_identifier' => 'gallery_selector',
			'media_id' => 456,
			'media_folder_id' => 789,
			'scope' => 'Pages',
			'foreign_key' => 101,
			'system_order' => 10,
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


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\MediaAssignment::$fieldMap
	 */
	public function testFieldMapDuringConstruction(): void {
		$properties = [
			'media_element_id' => 999,
			'media_element_selector_identifier' => 'test_selector',
			'media_id' => 888,
			'media_folder_id' => 777,
			'foreign_key' => 666,
			'system_order' => 5,
			'media_element' => ['id' => 999, 'title' => 'Test Element'],
			'media_folder' => ['id' => 777, 'title' => 'Test Folder'],
		];

		$entity = new MediaAssignment($properties);
		$entityArray = $entity->toArray();

		foreach ($entityArray as $key => $value) {
			$this->assertStringNotContainsString('_', $key);
		}
	}
}
