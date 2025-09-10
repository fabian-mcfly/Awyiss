<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\ORM\Association;


use Awyiss\Model\Table;
use Awyiss\ORM\Association\HasMany;
use Awyiss\Test\TestSuite\TestCase;
use Cake\ORM\Query;


/**
 * Test case for HasMany
 *
 * @see \Awyiss\ORM\Association\HasMany
 */
class HasManyTest extends TestCase {
	/**
	 * @var \Awyiss\ORM\Association\HasMany
	 */
	protected HasMany $hasManyAssociation;
	/**
	 * @var \Awyiss\Model\Table
	 */
	protected Table $mockSourceTable;
	/**
	 * @var \Awyiss\Model\Table
	 */
	protected Table $mockTargetTable;
	/**
	 * @var \Cake\ORM\Query
	 */
	protected Query $mockQuery;


	/**
	 * @inheritDoc
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->mockSourceTable = $this->createMock(Table::class);
		$this->mockTargetTable = $this->createMock(Table::class);
		$this->mockQuery = $this->createMock(Query::class);

		$this->hasManyAssociation = new HasMany('Comments', [
			'sourceTable' => $this->mockSourceTable,
			'targetTable' => $this->mockTargetTable,
		]);
	}


	/**
	 * @testWith [true]
	 *           [false]
	 * @param bool $exists
	 * @return void
	 * @see \Awyiss\ORM\Association\HasMany::exists()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testExistsPassesOptionsToTargetTable(bool $exists): void {
		$conditions = ['dummy' => 123];
		$options = [
			'contain' => ['Users', 'Roles'],
			'finder' => 'active',
			'cache' => true,
			'limit' => 5,
		];

		$this->mockTargetTable->method('find')->willReturn($this->mockQuery);

		$this->mockQuery->expects($this->exactly(2))->method('where')->willReturn($this->mockQuery);

		$this->mockQuery->expects($this->once())->method('clause')->with('where')->willReturn($conditions);

		// Verify that the exact options are passed to the target table's exists method
		$this->mockTargetTable->expects($this->once())->method('exists')->with($conditions, $this->identicalTo($options))->willReturn($exists);

		$result = $this->hasManyAssociation->exists($conditions, $options);

		$this->assertSame($exists, $result);
	}
}
