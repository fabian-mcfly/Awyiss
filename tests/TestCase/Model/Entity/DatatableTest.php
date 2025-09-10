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
	 * @noinspection PhpVariableNamingConventionInspection
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
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testAccessibleFields(): void {
		$entity = new Datatable();

		$this->assertSame([
			'title' => true,
			'identifier' => true,
			'active' => true,
			'_translations' => true,
			'_publicationData' => true,
			'mediaAssignments' => true,
			'mediaElementAssignments' => true,
		], $entity->getAccessible());
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Datatable::_setIdentifier()
	 * @noinspection PhpVariableNamingConventionInspection
	 * @noinspection PhpConditionAlreadyCheckedInspection
	 */
	public function testIdentifierCleaningViaPropertyAssignment(): void {
		$entity = new Datatable();

		$entity->identifier = 'User';
		$this->assertEquals('users', $entity->identifier);

		$entity->identifier = 'Product';
		$this->assertEquals('products', $entity->identifier);

		$entity->identifier = 'OrderItem';
		$this->assertEquals('orderitems', $entity->identifier);

		$entity->identifier = 'User123Data';
		$this->assertEquals('userdata', $entity->identifier);

		$entity->identifier = 'Customer Profile';
		$this->assertEquals('customer_profiles', $entity->identifier);

		$entity->identifier = 'Test-Item';
		$this->assertEquals('test_items', $entity->identifier);

		$entity->identifier = 'Category999Name';
		$this->assertEquals('categorynames', $entity->identifier);

		$entity->identifier = 'UPPERCASE';
		$this->assertEquals('uppercases', $entity->identifier);

		$entity->identifier = 'child';
		$this->assertEquals('children', $entity->identifier);

		$entity->identifier = 'person';
		$this->assertEquals('people', $entity->identifier);

		$entity->identifier = null;
		$this->assertNull($entity->identifier);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Datatable::_setIdentifier()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testIdentifierCleaningViaSetMethod(): void {
		$entity = new Datatable();

		$entity->set('identifier', 'User');
		$this->assertEquals('users', $entity->identifier);

		$entity->set('identifier', 'Product');
		$this->assertEquals('products', $entity->identifier);

		$entity->set('identifier', 'OrderItem');
		$this->assertEquals('orderitems', $entity->identifier);

		$entity->set('identifier', 'User123Data');
		$this->assertEquals('userdata', $entity->identifier);

		$entity->set('identifier', 'Customer Profile');
		$this->assertEquals('customer_profiles', $entity->identifier);

		$entity->set('identifier', 'Test-Item');
		$this->assertEquals('test_items', $entity->identifier);

		$entity->set('identifier', 'Category999Name');
		$this->assertEquals('categorynames', $entity->identifier);

		$entity->set('identifier', 'UPPERCASE');
		$this->assertEquals('uppercases', $entity->identifier);

		$entity->set('identifier', 'child');
		$this->assertEquals('children', $entity->identifier);

		$entity->set('identifier', 'person');
		$this->assertEquals('people', $entity->identifier);

		/** @noinspection PhpRedundantOptionalArgumentInspection */
		$entity->set('identifier', null);
		$this->assertNull($entity->identifier);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Datatable
	 * @noinspection PhpVariableNamingConventionInspection
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
		$this->assertEquals('users', $entity->identifier); // Should be cleaned by setter
		$this->assertTrue($entity->active);
		$this->assertFalse($entity->deleted);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Datatable::$fieldMap
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testFieldMapDuringConstruction(): void {
		$properties = [
			'title' => 'Test Title',
			'identifier' => 'TestIdentifier',
			'active' => true,
		];

		$entity = new Datatable($properties);
		$entityArray = $entity->toArray();

		foreach ($entityArray as $key => $value) {
			$this->assertStringNotContainsString('_', $key);
		}
	}
}
