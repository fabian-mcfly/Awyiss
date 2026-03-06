<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Model\Entity;


use Awyiss\Model\Entity\Datatable;
use Awyiss\Test\TestSuite\TestCase;
use Cake\Datasource\FactoryLocator;


/**
 * Datatable Entity Test Case
 *
 * @see \Awyiss\Model\Entity\Datatable
 */
class DatatableTest extends TestCase {
	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Datatable::$fieldMap
	 */
	public function testFieldMapCompleteness(): void {
		/** @var \Awyiss\Model\Table\DatatablesTable $table */
		$table = FactoryLocator::get('Table')->get('Datatables');
		$entity = $table->newDefaultEntity();
		$entityArray = $entity->toArray();

		foreach ($entityArray as $key => $value) {
			$this->assertStringNotContainsString('_', $key);
		}
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Datatable::$_accessible
	 */
	public function testAccessibleFields(): void {
		$entity = new Datatable();

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
	 * @see \Awyiss\Model\Entity\Datatable::_setIdentifier()
	 * @noinspection PhpConditionAlreadyCheckedInspection
	 */
	public function testIdentifierCleaningViaPropertyAssignment(): void {
		$entity = new Datatable();

		$entity->identifier = 'User';
		$this->assertEquals('Users', $entity->identifier);

		$entity->identifier = 'Product';
		$this->assertEquals('Products', $entity->identifier);

		$entity->identifier = 'OrderItem';
		$this->assertEquals('OrderItems', $entity->identifier);

		$entity->identifier = 'User123Data';
		$this->assertEquals('UserData', $entity->identifier);

		$entity->identifier = 'Customer Profile';
		$this->assertEquals('CustomerProfiles', $entity->identifier);

		$entity->identifier = 'Test-Item';
		$this->assertEquals('TestItems', $entity->identifier);

		$entity->identifier = 'Category999Name';
		$this->assertEquals('CategoryNames', $entity->identifier);

		$entity->identifier = 'UPPERCASE';
		$this->assertEquals('UPPERCASEs', $entity->identifier);

		$entity->identifier = 'child';
		$this->assertEquals('Children', $entity->identifier);

		$entity->identifier = 'person';
		$this->assertEquals('People', $entity->identifier);

		$entity->identifier = null;
		$this->assertNull($entity->identifier);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Datatable::_setIdentifier()
	 */
	public function testIdentifierCleaningViaSetMethod(): void {
		$entity = new Datatable();

		$entity->set('identifier', 'User');
		$this->assertEquals('Users', $entity->identifier);

		$entity->set('identifier', 'Product');
		$this->assertEquals('Products', $entity->identifier);

		$entity->set('identifier', 'OrderItem');
		$this->assertEquals('OrderItems', $entity->identifier);

		$entity->set('identifier', 'User123Data');
		$this->assertEquals('UserData', $entity->identifier);

		$entity->set('identifier', 'Customer Profile');
		$this->assertEquals('CustomerProfiles', $entity->identifier);

		$entity->set('identifier', 'Test-Item');
		$this->assertEquals('TestItems', $entity->identifier);

		$entity->set('identifier', 'Category999Name');
		$this->assertEquals('CategoryNames', $entity->identifier);

		$entity->set('identifier', 'UPPERCASE');
		$this->assertEquals('UPPERCASEs', $entity->identifier);

		$entity->set('identifier', 'child');
		$this->assertEquals('Children', $entity->identifier);

		$entity->set('identifier', 'person');
		$this->assertEquals('People', $entity->identifier);

		/** @noinspection PhpRedundantOptionalArgumentInspection */
		$entity->set('identifier', null);
		$this->assertNull($entity->identifier);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Datatable
	 */
	public function testEntityConstruction(): void {
		$properties = [
			'id' => 1,
			'title' => 'Test Datatable',
			'identifier' => 'User123',
			'active' => true,
			'deleted' => false,
		];

		$entity = new Datatable($properties);

		$this->assertEquals(1, $entity->id);
		$this->assertEquals('Test Datatable', $entity->title);
		$this->assertEquals('Users', $entity->identifier); // Should be cleaned by setter
		$this->assertTrue($entity->active);
		$this->assertFalse($entity->deleted);
	}
}
