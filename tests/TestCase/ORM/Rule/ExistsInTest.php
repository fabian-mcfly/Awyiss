<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\ORM\Rule;


use Awyiss\ORM\Rule\ExistsIn;
use Awyiss\Test\TestSuite\TestCase;
use Cake\Database\Schema\TableSchema;
use Cake\Datasource\EntityInterface;
use Cake\ORM\Association;
use Cake\ORM\Table;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use RuntimeException;


/**
 * Test case for ExistsIn
 *
 * @see \Awyiss\ORM\Rule\ExistsIn
 */
class ExistsInTest extends TestCase {
	/**
	 * @var \Awyiss\ORM\Rule\ExistsIn
	 */
	protected ExistsIn $existsInRule;
	/**
	 * @var \Cake\ORM\Table
	 */
	protected Table $mockTable;
	/**
	 * @var \Cake\ORM\Association
	 */
	protected Association $mockAssociation;
	/**
	 * @var \Cake\Datasource\EntityInterface
	 */
	protected EntityInterface $mockEntity;


	/**
	 * @inheritDoc
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->mockTable = $this->createMock(Table::class);

		$this->mockAssociation = $this->createMock(Association::class);

		$this->mockEntity = $this->createMock(EntityInterface::class);

		$this->existsInRule = new ExistsIn(['userId'], $this->mockTable);
	}


	/**
	 * Test __invoke method returns true when source and target tables are same
	 *
	 * @return void
	 * @see \Awyiss\ORM\Rule\ExistsIn::__invoke()
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 */
	#[AllowMockObjectsWithoutExpectations]
	public function testInvokeReturnsTrueWhenSourceAndTargetAreSame(): void {
		$options = [
			'repository' => $this->mockTable,
			'_sourceTable' => $this->mockTable,
		];

		$this->mockTable->expects($this->once())->method('getPrimaryKey')->willReturn('id');

		$result = $this->existsInRule->__invoke($this->mockEntity, $options);

		$this->assertTrue($result);
	}


	/**
	 * Test __invoke method with allowNullableNulls option filters nullable null fields
	 *
	 * @return void
	 * @see \Awyiss\ORM\Rule\ExistsIn::__invoke()
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 */
	#[AllowMockObjectsWithoutExpectations]
	public function testInvokeWithAllowNullableNullsFiltersNullableFields(): void {
		$schema = $this->createStub(TableSchema::class);
		$sourceTable = $this->createStub(Table::class);

		$existsInRule = new ExistsIn(['userId', 'roleId'], $this->mockTable, [
			'allowNullableNulls' => true,
		]);

		$options = [
			'repository' => $sourceTable,
			'_sourceTable' => $this->createStub(Table::class),
		];

		$this->mockTable->method('getPrimaryKey')->willReturn(['id', 'roleId']);
		$this->mockEntity->method('extract')->willReturnOnConsecutiveCalls(
			['userId' => 1, 'roleId' => null], // dirty check
			['userId' => 1] // final extraction after filtering
		);

		$sourceTable->method('getSchema')->willReturn($schema);
		$schema->method('hasColumn')->willReturnMap([
			['userId', true],
			['roleId', true],
		]);
		$schema->method('getColumn')->willReturnMap([
			['userId', ['type' => 'integer']],
			['roleId', ['type' => 'integer', 'null' => true]],
		]);
		$schema->method('isNullable')->willReturnMap([
			['userId', false],
			['roleId', true],
		]);
		$this->mockEntity->method('get')->willReturnMap([
			['userId', 1],
			['roleId', null],
		]);

		$this->mockTable->method('aliasField')->willReturnCallback(function ($field) {
			return 'Users.' . $field;
		});
		$this->mockTable->expects($this->atLeastOnce())->method('exists')->with(['Users.id IS' => 1], [])->willReturn(true);

		$result = $existsInRule->__invoke($this->mockEntity, $options);

		$this->assertTrue($result);
	}


	/**
	 * Test __invoke method without allowNullableNulls option filters nullable null fields
	 *
	 * @return void
	 * @see \Awyiss\ORM\Rule\ExistsIn::__invoke()
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 */
	#[AllowMockObjectsWithoutExpectations]
	public function testInvokeWithoutAllowNullableNullsFiltersNullableFields(): void {
		$schema = $this->createStub(TableSchema::class);
		$sourceTable = $this->createStub(Table::class);

		$existsInRule = new ExistsIn(['userId', 'roleId'], $this->mockTable, [
			'allowNullableNulls' => false,
		]);

		$options = [
			'repository' => $sourceTable,
			'_sourceTable' => $this->createStub(Table::class),
		];

		$this->mockTable->method('getPrimaryKey')->willReturn(['id', 'roleId']);
		$this->mockEntity->method('extract')->willReturnOnConsecutiveCalls(
			['userId' => 1, 'roleId' => null], // dirty check
			['userId' => 1, 'roleId' => null] // final extraction
		);

		$sourceTable->method('getSchema')->willReturn($schema);
		$schema->method('getColumn')->willReturnMap([
			['userId', ['type' => 'integer']],
			['roleId', ['type' => 'integer', 'null' => true]],
		]);
		$schema->method('isNullable')->willReturnMap([
			['userId', false],
			['roleId', true],
		]);

		$this->mockEntity->method('get')->willReturnMap([
			['userId', 1],
			['roleId', null],
		]);

		$this->mockTable->method('aliasField')->willReturnCallback(function ($field) {
			return 'Users.' . $field;
		});
		$this->mockTable->expects($this->once())->method('exists')->with(['Users.id IS' => 1, 'Users.roleId IS' => null], [])->willReturn(true);

		$result = $existsInRule->__invoke($this->mockEntity, $options);

		$this->assertTrue($result);
	}


	/**
	 * Test __invoke method calls attributeFieldsAreDirty method when fields are not dirty
	 *
	 * @return void
	 * @see \Awyiss\ORM\Rule\ExistsIn::__invoke()
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 */
	#[AllowMockObjectsWithoutExpectations]
	public function testInvokeCallsAttributeFieldsAreDirtyWhenEntityFieldsAreNotDirty(): void {
		$existsInRule = $this->getMockBuilder(ExistsIn::class)->
			setConstructorArgs([['userId'], $this->mockAssociation])
			->onlyMethods(['attributeFieldsAreDirty'])
			->getMock();

		$options = [
			'repository' => $this->mockTable,
		];

		$this->mockEntity->expects($this->once())->method('extract')->willReturn([]);

		$this->mockTable->expects($this->never())->method('aliasField');

		$this->mockTable->expects($this->never())->method('exists');

		$existsInRule->expects($this->once())->method('attributeFieldsAreDirty')->with($this->mockAssociation)->willReturn(false);

		$existsInRule->__invoke($this->mockEntity, $options);
	}


	/**
	 * Test __invoke method calls attributeFieldsAreDirty method when fields are not dirty
	 *
	 * @return void
	 * @see \Awyiss\ORM\Rule\ExistsIn::__invoke()
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 */
	#[AllowMockObjectsWithoutExpectations]
	public function testInvokeNotCallsAttributeFieldsAreDirtyWhenEntityFieldsAreDirty(): void {
		$existsInRule = $this->getMockBuilder(ExistsIn::class)->
			setConstructorArgs([['userId'], $this->mockAssociation])
			->onlyMethods(['attributeFieldsAreDirty'])
			->getMock();

		$options = [
			'repository' => $this->mockTable,
		];

		$this->mockEntity->expects($this->exactly(2))->method('extract')->willReturn(['userId']);

		$this->mockTable->expects($this->never())->method('aliasField');

		$this->mockTable->expects($this->never())->method('exists');

		$existsInRule->expects($this->never())->method('attributeFieldsAreDirty')->with($this->mockAssociation)->willReturn(false);

		$existsInRule->__invoke($this->mockEntity, $options);
	}


	/**
	 * Test __invoke method sets finder from association when not specified
	 *
	 * @return void
	 * @see \Awyiss\ORM\Rule\ExistsIn::__invoke()
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 */
	#[AllowMockObjectsWithoutExpectations]
	public function testInvokeSetsFinderFromAssociation(): void {
		$existsInRule = new ExistsIn(['userId'], $this->mockAssociation, ['something' => 'else']);

		$options = [
			'repository' => $this->mockAssociation,
		];

		$this->mockAssociation->expects($this->once())->method('getBindingKey')->willReturn('id');
		$this->mockAssociation->expects($this->once())->method('getTarget')->willReturn($this->mockTable);
		$this->mockAssociation->expects($this->once())->method('getFinder')->willReturn('withUsers');
		$this->mockEntity->method('extract')->willReturnOnConsecutiveCalls(
			['userId' => 1], // dirty check
			['userId' => 1]// final extraction
		);

		$this->mockTable->expects($this->once())->method('aliasField')->willReturnCallback(function ($field) {
			return 'Users.' . $field;
		});

		$this->mockAssociation->expects($this->once())->method('exists')->with(['Users.id IS' => 1], [
			'something' => 'else',
			'finder' => 'withUsers',
		])->willReturn(true);

		$result = $existsInRule->__invoke($this->mockEntity, $options);

		$this->assertTrue($result);
	}


	/**
	 * Test attributeFieldsAreDirty method with withMatchingAttributes finder
	 *
	 * @return void
	 * @see \Awyiss\ORM\Rule\ExistsIn::attributeFieldsAreDirty()
	 * @throws \ReflectionException
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 */
	#[AllowMockObjectsWithoutExpectations]
	public function testAttributeFieldsAreDirtyWithMatchingAttributes(): void {
		$attributesEntity = $this->createMock(EntityInterface::class);
		$mainEntity = $this->createMock(EntityInterface::class);

		// Mock the get method instead of __get
		$mainEntity
			->expects($this->atLeastOnce())
			->method('get')
			->with('attributes')
			->willReturn($attributesEntity);

		// Create a custom mock that includes the extractAttributeFields method
		$targetTable = new class extends Table {
			/**
			 * @noinspection PhpUnused
			 * @noinspection PhpUnusedParameterInspection
			 */
			public function extractAttributeFields(array $keys, bool $dirty = false): array {
				// This method will be mocked in the test
				return ['key2'];
			}
		};

		$finder = [
			'withMatchingAttributes' => [
				'entity' => $mainEntity,
				'fields' => ['key1', 'key2'],
			],
		];

		$this->mockAssociation->method('getFinder')->willReturn($finder);
		$this->mockAssociation->method('getTarget')->willReturn($targetTable);

		// Mock the extract method to return dirty fields
		$attributesEntity
			->expects($this->atLeastOnce())
			->method('extract')
			->with(['key2'], true)
			->willReturn(['key2']); // Has dirty attributes

		$result = $this->callProtectedMethod($this->existsInRule, 'attributeFieldsAreDirty', $this->mockAssociation);

		$this->assertTrue($result);
	}


	/**
	 * Test attributeFieldsAreDirty method with withMatchingAttributes finder
	 *
	 * @return void
	 * @see \Awyiss\ORM\Rule\ExistsIn::attributeFieldsAreDirty()
	 * @throws \ReflectionException
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 */
	#[AllowMockObjectsWithoutExpectations]
	public function testAttributeFieldsAreNotDirtyWithMatchingAttributes(): void {
		$attributesEntity = $this->createMock(EntityInterface::class);
		$mainEntity = $this->createMock(EntityInterface::class);

		// Mock the get method instead of __get
		$mainEntity
			->expects($this->atLeastOnce())
			->method('get')
			->with('attributes')
			->willReturn($attributesEntity);

		// Create a custom mock that includes the extractAttributeFields method
		$targetTable = new class extends Table {
			/**
			 * @noinspection PhpUnused
			 * @noinspection PhpUnusedParameterInspection
			 */
			public function extractAttributeFields(array $keys, bool $dirty = false): array {
				return ['key2'];
			}
		};

		$finder = [
			'withMatchingAttributes' => [
				'entity' => $mainEntity,
				'fields' => ['key1', 'key2'],
			],
		];

		$this->mockAssociation->method('getFinder')->willReturn($finder);
		$this->mockAssociation->method('getTarget')->willReturn($targetTable);

		// Mock the extract method to return non-dirty fields
		$attributesEntity
			->expects($this->atLeastOnce())
			->method('extract')
			->with(['key2'], true)
			->willReturn([]); // No dirty attributes

		$result = $this->callProtectedMethod($this->existsInRule, 'attributeFieldsAreDirty', $this->mockAssociation);

		$this->assertFalse($result);
	}


	/**
	 * Test setRepository method throws exception when association doesn't exist
	 *
	 * @return void
	 * @see \Awyiss\ORM\Rule\ExistsIn::setRepository()
	 * @throws \ReflectionException
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 */
	#[AllowMockObjectsWithoutExpectations]
	public function testSetRepositoryThrowsExceptionWhenAssociationDoesNotExist(): void {
		$associationName = 'NonExistentAssociation';
		$existsInRule = new ExistsIn(['userId'], $associationName);

		$this->mockTable->expects($this->atLeastOnce())->method('hasAssociation')->with($associationName)->willReturn(false);

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage("ExistsIn rule for 'userId' is invalid. 'NonExistentAssociation' is not associated with");

		$this->callProtectedMethod($existsInRule, 'setRepository', $this->mockTable);
	}
}
