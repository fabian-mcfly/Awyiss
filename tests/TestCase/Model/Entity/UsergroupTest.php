<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Model\Entity;


use Awyiss\Model\Entity\Usergroup;
use Awyiss\Test\TestSuite\TestCase;
use Cake\Datasource\FactoryLocator;


/**
 * Usergroup Entity Test Case
 *
 * @see \Awyiss\Model\Entity\Usergroup
 */
class UsergroupTest extends TestCase {
	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Usergroup::$fieldMap
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testFieldMapCompleteness(): void {
		/** @var \Awyiss\Model\Table\UsergroupsTable $table */
		$table = FactoryLocator::get('Table')->get('Usergroups');
		$entity = $table->newDefaultEntity();
		$entityArray = $entity->toArray();

		foreach ($entityArray as $key => $value) {
			$this->assertStringNotContainsString('_', $key);
		}
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Usergroup::$_accessible
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testAccessibleFields(): void {
		$entity = new Usergroup();

		$this->assertSame([
			'title' => true,
			'active' => true,
			'usergroupPermissions' => false,
			'users' => false,
			'_translations' => true,
			'_publicationData' => true,
			'mediaAssignments' => true,
			'mediaElementAssignments' => true,
		], $entity->getAccessible());
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Usergroup
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testEntityConstruction(): void {
		$properties = [
			'id' => 1,
			'title' => 'Test Usergroup',
			'active' => true,
			'deleted' => false,
		];

		$entity = new Usergroup($properties);

		$this->assertEquals(1, $entity->id);
		$this->assertEquals('Test Usergroup', $entity->title);
		$this->assertTrue($entity->active);
		$this->assertFalse($entity->deleted);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Usergroup::$fieldMap
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testFieldMapDuringConstruction(): void {
		$properties = [
			'usergroup_permissions' => [],
		];

		$entity = new Usergroup($properties);
		$entityArray = $entity->toArray();

		foreach ($entityArray as $key => $value) {
			$this->assertStringNotContainsString('_', $key);
		}
	}
}
