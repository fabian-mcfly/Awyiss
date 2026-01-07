<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Model\Entity;


use Awyiss\Model\Entity\MenuEntry;
use Awyiss\Test\TestSuite\TestCase;
use Cake\Datasource\FactoryLocator;


/**
 * MenuEntry Entity Test Case
 *
 * @see \Awyiss\Model\Entity\MenuEntry
 */
class MenuEntryTest extends TestCase {
	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\MenuEntry::$fieldMap
	 */
	public function testFieldMapCompleteness(): void {
		/** @var \Awyiss\Model\Table\MenuEntriesTable $table */
		$table = FactoryLocator::get('Table')->get('MenuEntries');
		$entity = $table->newDefaultEntity();
		$entityArray = $entity->toArray();

		foreach ($entityArray as $key => $value) {
			$this->assertStringNotContainsString('_', $key);
		}
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\MenuEntry::$_accessible
	 */
	public function testAccessibleFields(): void {
		$entity = new MenuEntry();

		$this->assertSame([
			'menuId' => true,
			'languageShortcode' => true,
			'parentId' => true,
			'title' => true,
			'link' => true,
			'external' => true,
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
	 * @see \Awyiss\Model\Entity\MenuEntry::getChildren()
	 */
	public function testGetChildren(): void {
		/** @var \Awyiss\Model\Table\MenuEntriesTable $table */
		$table = FactoryLocator::get('Table')->get('MenuEntries');
		/** @var \Awyiss\Model\Entity\MenuEntry $parent */
		$parent = $table->get(1); // "Dienstleistungen" (menu_id: 2) has children: 2, 3, 4, 5, 6

		$children = $parent->getChildren();

		$this->assertNotNull($children);
		$this->assertCount(5, $children); // IDs: 2, 3, 4, 5, 6
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\MenuEntry::getNestedChildren()
	 */
	public function testGetNestedChildren(): void {
		/** @var \Awyiss\Model\Table\MenuEntriesTable $table */
		$table = FactoryLocator::get('Table')->get('MenuEntries');
		/** @var \Awyiss\Model\Entity\MenuEntry $parent */
		$parent = $table->get(7); // "Über uns" (menu_id: 2) has children: 8, 9, 10, 11, 12

		$nestedChildren = $parent->getNestedChildren();

		$this->assertNotNull($nestedChildren);
		$this->assertCount(5, $nestedChildren); // IDs: 8, 9, 10, 11, 12
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\MenuEntry::getParent()
	 */
	public function testGetParent(): void {
		/** @var \Awyiss\Model\Table\MenuEntriesTable $table */
		$table = FactoryLocator::get('Table')->get('MenuEntries');
		/** @var \Awyiss\Model\Entity\MenuEntry $child */
		$child = $table->get(2); // "Seefracht" has parent_id = 1

		$parent = $child->getParent();

		$this->assertNotNull($parent);
		$this->assertEquals(1, $parent->id);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\MenuEntry::getParent()
	 */
	public function testGetParentWithNoParent(): void {
		/** @var \Awyiss\Model\Table\MenuEntriesTable $table */
		$table = FactoryLocator::get('Table')->get('MenuEntries');
		/** @var \Awyiss\Model\Entity\MenuEntry $menuEntry */
		$menuEntry = $table->get(1); // "Dienstleistungen" has parent_id = null

		$parent = $menuEntry->getParent();

		$this->assertNull($parent);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\MenuEntry::getParents()
	 */
	public function testGetParents(): void {
		/** @var \Awyiss\Model\Table\MenuEntriesTable $table */
		$table = FactoryLocator::get('Table')->get('MenuEntries');
		/** @var \Awyiss\Model\Entity\MenuEntry $deepChild */
		$deepChild = $table->get(26); // "Seefracht" (menu_id: 1) has parent_id = 25, which has parent_id = null

		$parents = $deepChild->getParents();

		$this->assertNotNull($parents);
		$this->assertCount(1, $parents); // Only parent ID: 25
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\MenuEntry::getParents()
	 */
	public function testGetParentsWithNoParents(): void {
		/** @var \Awyiss\Model\Table\MenuEntriesTable $table */
		$table = FactoryLocator::get('Table')->get('MenuEntries');
		/** @var \Awyiss\Model\Entity\MenuEntry $menuEntry */
		$menuEntry = $table->get(1); // "Dienstleistungen" with parent_id = null

		$parents = $menuEntry->getParents();

		$this->assertEmpty($parents);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\MenuEntry
	 */
	public function testEntityConstruction(): void {
		$properties = [
			'id' => 1,
			'menu_id' => 123,
			'language_shortcode' => 'de',
			'parent_id' => 456,
			'title' => 'Test Menu Entry',
			'link' => '/test-link',
			'external' => false,
			'system_order' => 10,
			'active' => true,
			'deleted' => false,
		];

		$entity = new MenuEntry($properties);

		$this->assertEquals(1, $entity->id);
		$this->assertEquals(123, $entity->menuId);
		$this->assertEquals('de', $entity->languageShortcode);
		$this->assertEquals(456, $entity->parentId);
		$this->assertEquals('Test Menu Entry', $entity->title);
		$this->assertEquals('/test-link', $entity->link);
		$this->assertFalse($entity->external);
		$this->assertEquals(10, $entity->systemOrder);
		$this->assertTrue($entity->active);
		$this->assertFalse($entity->deleted);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\MenuEntry::$fieldMap
	 */
	public function testFieldMapDuringConstruction(): void {
		$properties = [
			'menu_id' => 789,
			'language_shortcode' => 'en',
			'parent_id' => 101,
			'system_order' => 5,
		];

		$entity = new MenuEntry($properties);
		$entityArray = $entity->toArray();

		foreach ($entityArray as $key => $value) {
			$this->assertStringNotContainsString('_', $key);
		}
	}
}
