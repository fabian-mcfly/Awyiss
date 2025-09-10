<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Model\Entity;


use Awyiss\Model\Entity\UsergroupsUser;
use Awyiss\Test\TestSuite\TestCase;
use Cake\Datasource\FactoryLocator;


/**
 * UsergroupsUser Entity Test Case
 *
 * @see \Awyiss\Model\Entity\UsergroupsUser
 */
class UsergroupsUserTest extends TestCase {
	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\UsergroupsUser::$fieldMap
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testFieldMapCompleteness(): void {
		/** @var \Awyiss\Model\Table\UsergroupsUsersTable $table */
		$table = FactoryLocator::get('Table')->get('UsergroupsUsers');
		$entity = $table->newDefaultEntity();
		$entityArray = $entity->toArray();

		foreach ($entityArray as $key => $value) {
			$this->assertStringNotContainsString('_', $key);
		}
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\UsergroupsUser::$_accessible
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testAccessibleFields(): void {
		$entity = new UsergroupsUser();

		$this->assertSame([
			'usergroupId' => true,
			'userId' => true,
			'usergroup' => true,
			'user' => true,
			'_translations' => true,
			'_publicationData' => true,
			'mediaAssignments' => true,
			'mediaElementAssignments' => true,
		], $entity->getAccessible());
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\UsergroupsUser
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testEntityConstruction(): void {
		$properties = [
			'id' => 1,
			'usergroup_id' => 123,
			'user_id' => 456,
		];

		$entity = new UsergroupsUser($properties);

		$this->assertEquals(1, $entity->id);
		$this->assertEquals(123, $entity->usergroupId);
		$this->assertEquals(456, $entity->userId);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\UsergroupsUser::$fieldMap
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testFieldMapDuringConstruction(): void {
		$properties = [
			'usergroup_id' => 789,
			'user_id' => 101,
		];

		$entity = new UsergroupsUser($properties);
		$entityArray = $entity->toArray();

		foreach ($entityArray as $key => $value) {
			$this->assertStringNotContainsString('_', $key);
		}
	}
}
