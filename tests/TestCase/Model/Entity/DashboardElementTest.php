<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Model\Entity;


use Awyiss\Model\Entity\DashboardElement;
use Awyiss\Test\TestSuite\TestCase;
use Cake\Datasource\FactoryLocator;
use Cake\TestSuite\IntegrationTestTrait;


/**
 * DashboardElement Entity Test Case
 *
 * @see \Awyiss\Model\Entity\DashboardElement
 */
class DashboardElementTest extends TestCase {
	use IntegrationTestTrait;


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\DashboardElement::$fieldMap
	 */
	public function testFieldMapCompleteness(): void {
		/** @var \Awyiss\Model\Table\DashboardElementsTable $table */
		$table = FactoryLocator::get('Table')->get('DashboardElements');
		$entity = $table->newDefaultEntity();
		$entityArray = $entity->toArray();

		foreach ($entityArray as $key => $value) {
			$this->assertStringNotContainsString('_', $key);
		}
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\DashboardElement::$_accessible
	 */
	public function testAccessibleFields(): void {
		$entity = new DashboardElement();

		$this->assertSame([
			'scope' => true,
			'title' => true,
			'access' => true,
			'settings' => true,
			'systemOrder' => true,
			'active' => true,
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
	 * @see \Awyiss\Model\Entity\DashboardElement::_setAccess()
	 * @noinspection PhpConditionAlreadyCheckedInspection
	 */
	public function testAccessCleaningViaPropertyAssignment(): void {
		$entity = new DashboardElement();

		$entity->access = ['admin', 'moderator'];
		$this->assertEquals(['admin', 'moderator'], $entity->access);

		$entity->access = '["admin", "moderator"]';
		$this->assertEquals(['admin', 'moderator'], $entity->access);

		$entity->access = [];
		$this->assertNull($entity->access);

		$entity->access = '';
		$this->assertNull($entity->access);

		$entity->access = null;
		$this->assertNull($entity->access);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\DashboardElement::_setAccess()
	 */
	public function testAccessCleaningViaSetMethod(): void {
		$entity = new DashboardElement();

		$entity->set('access', ['admin', 'moderator']);
		$this->assertEquals(['admin', 'moderator'], $entity->access);

		$entity->set('access', '["admin", "moderator"]');
		$this->assertEquals(['admin', 'moderator'], $entity->access);

		$entity->set('access', []);
		$this->assertNull($entity->access);

		$entity->set('access', '');
		$this->assertNull($entity->access);

		/** @noinspection PhpRedundantOptionalArgumentInspection */
		$entity->set('access', null);
		$this->assertNull($entity->access);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\DashboardElement
	 */
	public function testEntityConstruction(): void {
		$properties = [
			'id' => 1,
			'scope' => 'BackendOverview',
			'title' => 'Test Dashboard Element',
			'access' => '["Admin", "moderator"]',
			'settings' => ['setting1' => 'value1', 'setting2' => 'value2'],
			'systemOrder' => 10,
			'active' => true,
			'deleted' => false,
		];

		$entity = new DashboardElement($properties);

		$this->assertEquals(1, $entity->id);
		$this->assertEquals('BackendOverview', $entity->scope);
		$this->assertEquals('Test Dashboard Element', $entity->title);
		$this->assertEquals(['Admin', 'moderator'], $entity->access);
		$this->assertEquals(['setting1' => 'value1', 'setting2' => 'value2'], $entity->settings);
		$this->assertEquals(10, $entity->systemOrder);
		$this->assertTrue($entity->active);
		$this->assertFalse($entity->deleted);
	}
}
