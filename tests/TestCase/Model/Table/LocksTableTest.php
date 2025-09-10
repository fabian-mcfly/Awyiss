<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Model\Table;


use Awyiss\Model\Entity\Lock;
use Awyiss\Model\Table\LocksTable;
use Awyiss\Test\TestSuite\TestCase;
use Cake\Datasource\FactoryLocator;


/**
 * LocksTable Test Case
 *
 * @see \Awyiss\Model\Table\LocksTable
 */
class LocksTableTest extends TestCase {
	/**
	 * @var \Awyiss\Model\Table\LocksTable
	 */
	protected LocksTable $locksTable;


	/**
	 * @inheritDoc
	 */
	protected function setUp(): void {
		parent::setUp();

		/** @noinspection PhpFieldAssignmentTypeMismatchInspection */
		$this->locksTable = FactoryLocator::get('Table')->get('Locks');
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\LocksTable::ATTRIBUTABLE
	 */
	public function testAttributableConstant(): void {
		$this->assertFalse($this->locksTable::ATTRIBUTABLE);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\LocksTable::TABLE
	 */
	public function testTableConstant(): void {
		$this->assertEquals('locks', $this->locksTable::TABLE);
	}

	/**
	 * @return void
	 * @see \Awyiss\Model\Table\LocksTable::newDefaultEntity()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testNewDefaultEntity(): void {
		/** @var \Awyiss\Model\Entity\Lock $entity */
		$entity = $this->locksTable->newDefaultEntity();

		$this->assertInstanceOf(Lock::class, $entity);
		$this->assertTrue($entity->isNew());

		// Check default values
		$this->assertNull($entity->id);
		$this->assertNull($entity->scope);
		$this->assertNull($entity->foreignKey);
		$this->assertNull($entity->uniqueId);
		$this->assertNull($entity->createdBy);
		$this->assertNull($entity->createdOn);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\LocksTable::newDefaultEntity()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testNewDefaultEntityWithData(): void {
		$additionalData = [
			'scope' => 'test_scope',
			'foreignKey' => 123,
			'uniqueId' => 'unique_test_id',
			'createdBy' => 1,
			'createdOn' => '2023-10-01 12:00:00',
		];

		/** @var \Awyiss\Model\Entity\Lock $entity */
		$entity = $this->locksTable->newDefaultEntity($additionalData);

		$this->assertInstanceOf(Lock::class, $entity);
		$this->assertTrue($entity->isNew());

		// Check provided values
		$this->assertNull($entity->id);
		$this->assertEquals('test_scope', $entity->scope);
		$this->assertEquals(123, $entity->foreignKey);
		$this->assertEquals('unique_test_id', $entity->uniqueId);
		$this->assertEquals(1, $entity->createdBy);
		$this->assertEquals('2023-10-01 12:00:00', $entity->createdOn->format('Y-m-d H:i:s'));
	}
}
