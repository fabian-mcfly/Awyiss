<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\ORM\Association;


use Awyiss\Model\Table;
use Awyiss\ORM\Association\BelongsToMany;
use Awyiss\Test\TestSuite\TestCase;
use Cake\ORM\Query;


/**
 * Test case for BelongsToMany
 *
 * @see \Awyiss\ORM\Association\BelongsToMany
 */
class BelongsToManyTest extends TestCase {
	/**
	 * @var \Awyiss\ORM\Association\BelongsToMany
	 */
	protected BelongsToMany $belongsToManyAssociation;
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
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->mockQuery = $this->createMock(Query::class);

		$this->mockSourceTable = $this->createMock(Table::class);
		$this->mockSourceTable->method('getTable')->willReturn('posts');
		$this->mockSourceTable->method('getAlias')->willReturn('Posts');
		$this->mockSourceTable->method('getRegistryAlias')->willReturn('Posts');

		$this->mockTargetTable = $this->createMock(Table::class);
		$this->mockTargetTable->method('getTable')->willReturn('tags');
		$this->mockTargetTable->method('getAlias')->willReturn('Tags');
		$this->mockTargetTable->method('getRegistryAlias')->willReturn('Tags');

		// Create a partial mock of BelongsToMany to override the find method
		$this->belongsToManyAssociation = $this->getMockBuilder(BelongsToMany::class)
			->setConstructorArgs(['Tags', [
				'sourceTable' => $this->mockSourceTable,
				'targetTable' => $this->mockTargetTable,
				'joinTable' => 'posts_tags',
				'foreignKey' => 'post_id',
				'targetForeignKey' => 'tag_id',
			]])
			->onlyMethods(['find'])
			->getMock();

		// Mock the find method to return our query mock
		$this->belongsToManyAssociation->method('find')->willReturn($this->mockQuery);
	}


	/**
	 * @testWith [true]
	 *           [false]
	 * @param bool $exists
	 * @return void
	 * @see \Awyiss\ORM\Association\BelongsToMany::exists()
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

		$this->mockQuery->expects($this->exactly(1))->method('where')->willReturn($this->mockQuery);

		$this->mockQuery->expects($this->once())->method('clause')->with('where')->willReturn($conditions);

		// Verify that the exact options are passed to the target table's exists method
		$this->mockTargetTable->expects($this->once())->method('exists')->with($conditions, $this->identicalTo($options))->willReturn($exists);

		$result = $this->belongsToManyAssociation->exists($conditions, $options);

		$this->assertSame($exists, $result);
	}


	/**
	 * Test hasThrough method returns false when no through table is set
	 *
	 * @return void
	 * @see \Awyiss\ORM\Association\BelongsToMany::hasThrough()
	 */
	public function testHasThroughReturnsFalseWhenNoThroughTable(): void {
		$result = $this->belongsToManyAssociation->hasThrough();

		$this->assertFalse($result);
	}


	/**
	 * Test hasThrough method returns true when through table is set
	 *
	 * @return void
	 * @see \Awyiss\ORM\Association\BelongsToMany::hasThrough()
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 */
	public function testHasThroughReturnsTrueWhenThroughTableIsSet(): void {
		$throughTable = $this->createMock(Table::class);

		$belongsToManyAssociation = new BelongsToMany('Tags', [
			'sourceTable' => $this->mockSourceTable,
			'targetTable' => $this->mockTargetTable,
			'through' => $throughTable,
		]);

		$result = $belongsToManyAssociation->hasThrough();

		$this->assertTrue($result);
	}
}
