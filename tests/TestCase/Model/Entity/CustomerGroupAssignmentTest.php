<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Model\Entity;


use Awyiss\Model\Entity\CustomerGroupAssignment;
use Awyiss\Test\TestSuite\TestCase;
use Cake\Datasource\FactoryLocator;


/**
 * CustomerGroupAssignment Entity Test Case
 *
 * @see \Awyiss\Model\Entity\CustomerGroupAssignment
 */
class CustomerGroupAssignmentTest extends TestCase {
	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\CustomerGroupAssignment::$fieldMap
	 */
	public function testFieldMapCompleteness(): void {
		/** @var \Awyiss\Model\Table\CustomerGroupAssignmentsTable $table */
		$table = FactoryLocator::get('Table')->get('CustomerGroupAssignments');
		$entity = $table->newDefaultEntity();
		$entityArray = $entity->toArray();

		foreach ($entityArray as $key => $value) {
			$this->assertStringNotContainsString('_', $key);
		}
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\CustomerGroupAssignment::$_accessible
	 */
	public function testAccessibleFields(): void {
		$entity = new CustomerGroupAssignment();

		$this->assertSame([
			'customerGroupId' => true,
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
	 * @see \Awyiss\Model\Entity\CustomerGroupAssignment
	 */
	public function testEntityConstruction(): void {
		$properties = [
			'id' => 1,
			'customerGroupId' => 123,
			'scope' => 'Pages',
			'foreignKey' => 101,
		];

		$entity = new CustomerGroupAssignment($properties);

		$this->assertEquals(1, $entity->id);
		$this->assertEquals(123, $entity->customerGroupId);
		$this->assertEquals('Pages', $entity->scope);
		$this->assertEquals(101, $entity->foreignKey);
	}
}
