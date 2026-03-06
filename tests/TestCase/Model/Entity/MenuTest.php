<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Model\Entity;


use Awyiss\Model\Entity\Menu;
use Awyiss\Test\TestSuite\TestCase;
use Cake\Datasource\FactoryLocator;


/**
 * Menu Entity Test Case
 *
 * @see \Awyiss\Model\Entity\Menu
 */
class MenuTest extends TestCase {
	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Menu::$fieldMap
	 */
	public function testFieldMapCompleteness(): void {
		/** @var \Awyiss\Model\Table\MenusTable $table */
		$table = FactoryLocator::get('Table')->get('Menus');
		$entity = $table->newDefaultEntity();
		$entityArray = $entity->toArray();

		foreach ($entityArray as $key => $value) {
			$this->assertStringNotContainsString('_', $key);
		}
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Menu::$_accessible
	 */
	public function testAccessibleFields(): void {
		$entity = new Menu();

		$this->assertSame([
			'title' => true,
			'identifier' => true,
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
	 * @see \Awyiss\Model\Entity\Menu::_setIdentifier()
	 * @noinspection PhpConditionAlreadyCheckedInspection
	 */
	public function testIdentifierCleaningViaPropertyAssignment(): void {
		$entity = new Menu();

		$entity->identifier = 'Main Menu';
		$this->assertEquals('mainMenu', $entity->identifier);

		$entity->identifier = 'MainMenu';
		$this->assertEquals('mainMenu', $entity->identifier);

		$entity->identifier = 'Main-Menu';
		$this->assertEquals('mainMenu', $entity->identifier);

		$entity->identifier = 'Main Menu!@#$%';
		$this->assertEquals('mainMenu', $entity->identifier);

		$entity->identifier = 'FOOTER MENU';
		$this->assertEquals('fOOTERMENU', $entity->identifier);

		$entity->identifier = 'Social Media Menu';
		$this->assertEquals('socialMediaMenu', $entity->identifier);

		$entity->identifier = null;
		$this->assertNull($entity->identifier);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Menu::_setIdentifier()
	 */
	public function testIdentifierCleaningViaSetMethod(): void {
		$entity = new Menu();

		$entity->set('identifier', 'Main Menu');
		$this->assertEquals('mainMenu', $entity->identifier);

		$entity->set('identifier', 'MainMenu');
		$this->assertEquals('mainMenu', $entity->identifier);

		$entity->set('identifier', 'Main-Menu');
		$this->assertEquals('mainMenu', $entity->identifier);

		$entity->set('identifier', 'Main Menu!@#$%');
		$this->assertEquals('mainMenu', $entity->identifier);

		$entity->set('identifier', 'FOOTER MENU');
		$this->assertEquals('fOOTERMENU', $entity->identifier);

		$entity->set('identifier', 'Social Media Menu');
		$this->assertEquals('socialMediaMenu', $entity->identifier);

		/** @noinspection PhpRedundantOptionalArgumentInspection */
		$entity->set('identifier', null);
		$this->assertNull($entity->identifier);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Menu
	 */
	public function testEntityConstruction(): void {
		$properties = [
			'id' => 1,
			'title' => 'Hauptmenü',
			'identifier' => 'Main Menu',
			'active' => true,
			'deleted' => false,
		];

		$entity = new Menu($properties);

		$this->assertEquals(1, $entity->id);
		$this->assertEquals('Hauptmenü', $entity->title);
		$this->assertEquals('mainMenu', $entity->identifier);
		$this->assertTrue($entity->active);
		$this->assertFalse($entity->deleted);
	}
}
