<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Model\Entity;


use Awyiss\Model\Entity\CustomerGroupAccessSetting;
use Awyiss\Test\TestSuite\TestCase;
use Cake\Datasource\FactoryLocator;


/**
 * CustomerGroupAccessSetting Entity Test Case
 *
 * @see \Awyiss\Model\Entity\CustomerGroupAccessSetting
 */
class CustomerGroupAccessSettingTest extends TestCase {
	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\CustomerGroupAccessSetting::$fieldMap
	 */
	public function testFieldMapCompleteness(): void {
		/** @var \Awyiss\Model\Table\CustomerGroupAccessSettingsTable $table */
		$table = FactoryLocator::get('Table')->get('CustomerGroupAccessSettings');
		$entity = $table->newDefaultEntity();
		$entityArray = $entity->toArray();

		foreach ($entityArray as $key => $value) {
			$this->assertStringNotContainsString('_', $key);
		}
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\CustomerGroupAccessSetting::$_accessible
	 */
	public function testAccessibleFields(): void {
		$entity = new CustomerGroupAccessSetting();

		$this->assertSame([
			'scope' => true,
			'foreignKey' => true,
			'accessType' => true,
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
	 * @see \Awyiss\Model\Entity\CustomerGroupAccessSetting
	 */
	public function testEntityConstruction(): void {
		$properties = [
			'id' => 1,
			'scope' => 'Pages',
			'foreign_key' => 101,
			'access_type' => 'specific_groups',
		];

		$entity = new CustomerGroupAccessSetting($properties);

		$this->assertEquals(1, $entity->id);
		$this->assertEquals('Pages', $entity->scope);
		$this->assertEquals(101, $entity->foreignKey);
		$this->assertEquals('specific_groups', $entity->accessType);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\CustomerGroupAccessSetting::$fieldMap
	 */
	public function testFieldMapDuringConstruction(): void {
		$properties = [
			'scope' => 'Surveys',
			'foreign_key' => 202,
			'access_type' => 'all_groups',
		];

		$entity = new CustomerGroupAccessSetting($properties);
		$entityArray = $entity->toArray();

		foreach ($entityArray as $key => $value) {
			$this->assertStringNotContainsString('_', $key);
		}
	}
}
