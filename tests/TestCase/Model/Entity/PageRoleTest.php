<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Model\Entity;


use Awyiss\Model\Entity\PageRole;
use Awyiss\Test\TestSuite\TestCase;
use Cake\Datasource\FactoryLocator;


/**
 * PageRole Entity Test Case
 *
 * @see \Awyiss\Model\Entity\PageRole
 */
class PageRoleTest extends TestCase {
	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\PageRole::$fieldMap
	 */
	public function testFieldMapCompleteness(): void {
		/** @var \Awyiss\Model\Table\PageRolesTable $table */
		$table = FactoryLocator::get('Table')->get('PageRoles');
		$entity = $table->newDefaultEntity();
		$entityArray = $entity->toArray();

		foreach ($entityArray as $key => $value) {
			$this->assertStringNotContainsString('_', $key);
		}
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\PageRole::$_accessible
	 */
	public function testAccessibleFields(): void {
		$entity = new PageRole();

		$this->assertSame([
			'title' => true,
			'identifier' => true,
			'includeInLinklist' => true,
			'systemOrder' => true,
			'active' => true,
			'_translations' => true,
			'_publicationData' => true,
			'mediaAssignments' => true,
			'mediaElementAssignments' => true,
		], $entity->getAccessible());
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\PageRole::_setIdentifier()
	 * @noinspection PhpConditionAlreadyCheckedInspection
	 */
	public function testIdentifierCleaningViaPropertyAssignment(): void {
		$entity = new PageRole();

		$entity->identifier = 'Test Pages';
		$this->assertEquals('test_page', $entity->identifier);

		$entity->identifier = 'TestPages';
		$this->assertEquals('testpage', $entity->identifier);

		$entity->identifier = 'Test-Pages';
		$this->assertEquals('test_page', $entity->identifier);

		$entity->identifier = 'Test Pages!@#$%';
		$this->assertEquals('test_page', $entity->identifier);

		$entity->identifier = 'UPPERCASE PAGES';
		$this->assertEquals('uppercase_page', $entity->identifier);

		$entity->identifier = 'Test123Pages456';
		$this->assertEquals('testpage', $entity->identifier);

		$entity->identifier = 'Products';
		$this->assertEquals('product', $entity->identifier);

		$entity->identifier = 'Categories';
		$this->assertEquals('category', $entity->identifier);

		$entity->identifier = null;
		$this->assertNull($entity->identifier);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\PageRole::_setIdentifier()
	 */
	public function testIdentifierCleaningViaSetMethod(): void {
		$entity = new PageRole();

		$entity->set('identifier', 'Test Pages');
		$this->assertEquals('test_page', $entity->identifier);

		$entity->set('identifier', 'TestPages');
		$this->assertEquals('testpage', $entity->identifier);

		$entity->set('identifier', 'Test-Pages');
		$this->assertEquals('test_page', $entity->identifier);

		$entity->set('identifier', 'Test Pages!@#$%');
		$this->assertEquals('test_page', $entity->identifier);

		$entity->set('identifier', 'UPPERCASE PAGES');
		$this->assertEquals('uppercase_page', $entity->identifier);

		$entity->set('identifier', 'Test123Pages456');
		$this->assertEquals('testpage', $entity->identifier);

		$entity->set('identifier', 'Products');
		$this->assertEquals('product', $entity->identifier);

		$entity->set('identifier', 'Categories');
		$this->assertEquals('category', $entity->identifier);

		/** @noinspection PhpRedundantOptionalArgumentInspection */
		$entity->set('identifier', null);
		$this->assertNull($entity->identifier);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\PageRole
	 */
	public function testEntityConstruction(): void {
		$properties = [
			'id' => 1,
			'title' => 'Test Page Role',
			'identifier' => 'Test123Pages456',
			'include_in_linklist' => true,
			'system_order' => 10,
			'active' => true,
			'deleted' => false,
		];

		$entity = new PageRole($properties);

		$this->assertEquals(1, $entity->id);
		$this->assertEquals('Test Page Role', $entity->title);
		$this->assertEquals('testpage', $entity->identifier);
		$this->assertTrue($entity->includeInLinklist);
		$this->assertEquals(10, $entity->systemOrder);
		$this->assertTrue($entity->active);
		$this->assertFalse($entity->deleted);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\PageRole::$fieldMap
	 */
	public function testFieldMapDuringConstruction(): void {
		$properties = [
			'include_in_linklist' => false,
			'system_order' => 5,
		];

		$entity = new PageRole($properties);
		$entityArray = $entity->toArray();

		foreach ($entityArray as $key => $value) {
			$this->assertStringNotContainsString('_', $key);
		}
	}
}
