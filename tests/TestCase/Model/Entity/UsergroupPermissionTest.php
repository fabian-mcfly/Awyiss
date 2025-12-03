<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Model\Entity;


use Awyiss\Authorization\Permission\PermissionAccess;
use Awyiss\Authorization\Permission\PermissionInterface;
use Awyiss\Model\Entity\UsergroupPermission;
use Awyiss\Test\TestSuite\TestCase;
use Cake\Datasource\FactoryLocator;


/**
 * UsergroupPermission Entity Test Case
 *
 * @see \Awyiss\Model\Entity\UsergroupPermission
 */
class UsergroupPermissionTest extends TestCase {
	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\UsergroupPermission::$fieldMap
	 */
	public function testFieldMapCompleteness(): void {
		/** @var \Awyiss\Model\Table\UsergroupPermissionsTable $table */
		$table = FactoryLocator::get('Table')->get('UsergroupPermissions');
		$entity = $table->newDefaultEntity();
		$entityArray = $entity->toArray();

		foreach ($entityArray as $key => $value) {
			$this->assertStringNotContainsString('_', $key);
		}
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\UsergroupPermission::$_accessible
	 */
	public function testAccessibleFields(): void {
		$entity = new UsergroupPermission();

		$this->assertSame([
			'usergroupId' => true,
			'scope' => true,
			'identifier' => true,
			'access' => true,
			'settings' => true,
			'_translations' => true,
			'_publicationData' => true,
			'mediaAssignments' => true,
			'mediaElementAssignments' => true,
		], $entity->getAccessible());
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\UsergroupPermission
	 */
	public function testImplementsInterface(): void {
		$entity = new UsergroupPermission();

		$this->assertInstanceOf(PermissionInterface::class, $entity);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\UsergroupPermission::_setScope()
	 * @noinspection PhpConditionAlreadyCheckedInspection
	 */
	public function testScopeCleaningViaPropertyAssignment(): void {
		$entity = new UsergroupPermission();

		$entity->scope = 'testScope';
		$this->assertEquals('test_scope', $entity->scope);

		$entity->scope = 'TestScope';
		$this->assertEquals('test_scope', $entity->scope);

		$entity->scope = 'testHTMLScope';
		$this->assertEquals('test_h_t_m_l_scope', $entity->scope);

		$entity->scope = 'already_underscored';
		$this->assertEquals('already_underscored', $entity->scope);

		$entity->scope = null;
		$this->assertNull($entity->scope);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\UsergroupPermission::_setScope()
	 */
	public function testScopeCleaningViaSetMethod(): void {
		$entity = new UsergroupPermission();

		$entity->set('scope', 'testScope');
		$this->assertEquals('test_scope', $entity->scope);

		$entity->set('scope', 'TestScope');
		$this->assertEquals('test_scope', $entity->scope);

		$entity->set('scope', 'testHTMLScope');
		$this->assertEquals('test_h_t_m_l_scope', $entity->scope);

		$entity->set('scope', 'already_underscored');
		$this->assertEquals('already_underscored', $entity->scope);

		/** @noinspection PhpRedundantOptionalArgumentInspection */
		$entity->set('scope', null);
		$this->assertNull($entity->scope);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\UsergroupPermission::_setIdentifier()
	 * @noinspection PhpConditionAlreadyCheckedInspection
	 */
	public function testIdentifierCleaningViaPropertyAssignment(): void {
		$entity = new UsergroupPermission();

		$entity->identifier = 'testIdentifier';
		$this->assertEquals('test_identifier', $entity->identifier);

		$entity->identifier = 'TestIdentifier';
		$this->assertEquals('test_identifier', $entity->identifier);

		$entity->identifier = 'testHTMLElement';
		$this->assertEquals('test_h_t_m_l_element', $entity->identifier);

		$entity->identifier = 'already_underscored';
		$this->assertEquals('already_underscored', $entity->identifier);

		$entity->identifier = null;
		$this->assertNull($entity->identifier);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\UsergroupPermission::_setIdentifier()
	 */
	public function testIdentifierCleaningViaSetMethod(): void {
		$entity = new UsergroupPermission();

		$entity->set('identifier', 'testIdentifier');
		$this->assertEquals('test_identifier', $entity->identifier);

		$entity->set('identifier', 'TestIdentifier');
		$this->assertEquals('test_identifier', $entity->identifier);

		$entity->set('identifier', 'testHTMLElement');
		$this->assertEquals('test_h_t_m_l_element', $entity->identifier);

		$entity->set('identifier', 'already_underscored');
		$this->assertEquals('already_underscored', $entity->identifier);

		/** @noinspection PhpRedundantOptionalArgumentInspection */
		$entity->set('identifier', null);
		$this->assertNull($entity->identifier);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\UsergroupPermission
	 */
	public function testEntityConstruction(): void {
		$properties = [
			'id' => 1,
			'usergroup_id' => 123,
			'scope' => 'TestScope',
			'identifier' => 'TestIdentifier',
			'access' => PermissionAccess::Granted,
			'settings' => ['key' => 'value'],
		];

		$entity = new UsergroupPermission($properties);

		$this->assertEquals(1, $entity->id);
		$this->assertEquals(123, $entity->usergroupId);
		$this->assertEquals('test_scope', $entity->scope);
		$this->assertEquals('test_identifier', $entity->identifier);
		$this->assertEquals(PermissionAccess::Granted, $entity->access);
		$this->assertEquals(['key' => 'value'], $entity->settings);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\UsergroupPermission::$fieldMap
	 */
	public function testFieldMapDuringConstruction(): void {
		$properties = [
			'usergroup_id' => 456,
		];

		$entity = new UsergroupPermission($properties);
		$entityArray = $entity->toArray();

		foreach ($entityArray as $key => $value) {
			$this->assertStringNotContainsString('_', $key);
		}
	}
}
