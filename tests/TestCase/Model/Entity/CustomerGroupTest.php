<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Model\Entity;


use Awyiss\Model\Entity\CustomerGroup;
use Awyiss\Test\TestSuite\TestCase;
use Cake\Datasource\FactoryLocator;


/**
 * CustomerGroup Entity Test Case
 *
 * @see \Awyiss\Model\Entity\CustomerGroup
 */
class CustomerGroupTest extends TestCase {
	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\CustomerGroup::$fieldMap
	 */
	public function testFieldMapCompleteness(): void {
		/** @var \Awyiss\Model\Table\CustomerGroupsTable $table */
		$table = FactoryLocator::get('Table')->get('CustomerGroups');
		$entity = $table->newDefaultEntity();
		$entityArray = $entity->toArray();

		foreach ($entityArray as $key => $value) {
			$this->assertStringNotContainsString('_', $key);
		}
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\CustomerGroup::$_accessible
	 */
	public function testAccessibleFields(): void {
		$entity = new CustomerGroup();

		$this->assertSame([
			'title' => true,
			'active' => true,
			'customers' => false,
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
	 * @see \Awyiss\Model\Entity\CustomerGroup
	 */
	public function testEntityConstruction(): void {
		$properties = [
			'id' => 1,
			'title' => 'Test Customer Group',
			'active' => true,
			'deleted' => false,
		];

		$entity = new CustomerGroup($properties);

		$this->assertEquals(1, $entity->id);
		$this->assertEquals('Test Customer Group', $entity->title);
		$this->assertTrue($entity->active);
		$this->assertFalse($entity->deleted);
	}
}
