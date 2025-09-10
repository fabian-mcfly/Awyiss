<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\ORM\Rule;


use Awyiss\ORM\Rule\ExistsIn;
use Awyiss\Test\TestSuite\TestCase;
use Cake\Database\Schema\TableSchema;
use Cake\Datasource\EntityInterface;
use Cake\ORM\Association;
use Cake\ORM\Table;
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
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->mockTable = $this->createMock(Table::class);

		$this->mockAssociation = $this->createMock(Association::class);

		$this->mockEntity = $this->createMock(EntityInterface::class);

		$this->existsInRule = new ExistsIn(['user_id'], $this->mockTable);
	}


	/**
	 * Test __invoke method returns true when source and target tables are same
	 *
	 * @return void
	 * @see \Awyiss\ORM\Rule\ExistsIn::__invoke()
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
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
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testInvokeWithAllowNullableNullsFiltersNullableFields(): void {
		$schema = $this->createMock(TableSchema::class);
		$sourceTable = $this->createMock(Table::class);

		$existsInRule = new ExistsIn(['user_id', 'role_id'], $this->mockTable, [
			'allowNullableNulls' => true,
		]);

		$options = [
			'repository' => $sourceTable,
			'_sourceTable' => $this->createMock(Table::class),
		];

		$this->mockTable->method('getPrimaryKey')->willReturn(['id', 'role_id']);
		$this->mockEntity->method('extract')->willReturnOnConsecutiveCalls(
			['user_id' => 1, 'role_id' => null], // dirty check
			['user_id' => 1] // final extraction after filtering
		);

		$sourceTable->method('getSchema')->willReturn($schema);
		$schema->method('getColumn')->willReturnMap([
			['user_id', ['type' => 'integer']],
			['role_id', ['type' => 'integer', 'null' => true]],
		]);
		$schema->method('isNullable')->willReturnMap([
			['user_id', false],
			['role_id', true],
		]);
		$this->mockEntity->method('get')->willReturnMap([
			['user_id', 1],
			['role_id', null],
		]);

		$this->mockTable->method('aliasField')->willReturnCallback(function ($field) {
			return 'Users.' . $field;
		});
		$this->mockTable->method('exists')->with(['Users.id IS' => 1], [])->willReturn(true);

		$result = $existsInRule->__invoke($this->mockEntity, $options);

		$this->assertTrue($result);
	}


	/**
	 * Test __invoke method without allowNullableNulls option filters nullable null fields
	 *
	 * @return void
	 * @see \Awyiss\ORM\Rule\ExistsIn::__invoke()
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testInvokeWithoutAllowNullableNullsFiltersNullableFields(): void {
		$schema = $this->createMock(TableSchema::class);
		$sourceTable = $this->createMock(Table::class);

		$existsInRule = new ExistsIn(['user_id', 'role_id'], $this->mockTable, [
			'allowNullableNulls' => false,
		]);

		$options = [
			'repository' => $sourceTable,
			'_sourceTable' => $this->createMock(Table::class),
		];

		$this->mockTable->method('getPrimaryKey')->willReturn(['id', 'role_id']);
		$this->mockEntity->method('extract')->willReturnOnConsecutiveCalls(
			['user_id' => 1, 'role_id' => null], // dirty check
			['user_id' => 1, 'role_id' => null] // final extraction
		);

		$sourceTable->method('getSchema')->willReturn($schema);
		$schema->method('getColumn')->willReturnMap([
			['user_id', ['type' => 'integer']],
			['role_id', ['type' => 'integer', 'null' => true]],
		]);
		$schema->method('isNullable')->willReturnMap([
			['user_id', false],
			['role_id', true],
		]);

		$this->mockEntity->method('get')->willReturnMap([
			['user_id', 1],
			['role_id', null],
		]);

		$this->mockTable->method('aliasField')->willReturnCallback(function ($field) {
			return 'Users.' . $field;
		});
		$this->mockTable->expects($this->once())->method('exists')->with(['Users.id IS' => 1, 'Users.role_id IS' => null], [])->willReturn(true);

		$result = $existsInRule->__invoke($this->mockEntity, $options);

		$this->assertTrue($result);
	}


	/**
	 * Test __invoke method calls attributeFieldsAreDirty method when fields are not dirty
	 *
	 * @return void
	 * @see \Awyiss\ORM\Rule\ExistsIn::__invoke()
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testInvokeCallsAttributeFieldsAreDirtyWhenEntityFieldsAreNotDirty(): void {
		$existsInRule = $this->getMockBuilder(ExistsIn::class)->
			setConstructorArgs([['user_id'], $this->mockAssociation])
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
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testInvokeNotCallsAttributeFieldsAreDirtyWhenEntityFieldsAreDirty(): void {
		$existsInRule = $this->getMockBuilder(ExistsIn::class)->
			setConstructorArgs([['user_id'], $this->mockAssociation])
			->onlyMethods(['attributeFieldsAreDirty'])
			->getMock();

		$options = [
			'repository' => $this->mockTable,
		];

		$this->mockEntity->expects($this->exactly(2))->method('extract')->willReturn(['user_id']);

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
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testInvokeSetsFinderFromAssociation(): void {
		$existsInRule = new ExistsIn(['user_id'], $this->mockAssociation, ['something' => 'else']);

		$options = [
			'repository' => $this->mockAssociation,
		];

		$this->mockAssociation->expects($this->once())->method('getBindingKey')->willReturn('id');
		$this->mockAssociation->expects($this->once())->method('getTarget')->willReturn($this->mockTable);
		$this->mockAssociation->expects($this->once())->method('getFinder')->willReturn('withUsers');
		$this->mockEntity->method('extract')->willReturnOnConsecutiveCalls(
			['user_id' => 1], // dirty check
			['user_id' => 1]// final extraction
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
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testAttributeFieldsAreDirtyWithMatchingAttributes(): void {
		$attributesEntity = $this->createMock(EntityInterface::class);
		$mainEntity = $this->createMock(EntityInterface::class);

		// Mock the get method instead of __get
		$mainEntity->method('get')
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
				'keys' => ['key1', 'key2'],
			],
		];

		$this->mockAssociation->method('getFinder')->willReturn($finder);
		$this->mockAssociation->method('getTarget')->willReturn($targetTable);

		// Mock the extract method to return dirty fields
		$attributesEntity->method('extract')
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
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testAttributeFieldsAreNotDirtyWithMatchingAttributes(): void {
		$attributesEntity = $this->createMock(EntityInterface::class);
		$mainEntity = $this->createMock(EntityInterface::class);

		// Mock the get method instead of __get
		$mainEntity->method('get')
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
				'keys' => ['key1', 'key2'],
			],
		];

		$this->mockAssociation->method('getFinder')->willReturn($finder);
		$this->mockAssociation->method('getTarget')->willReturn($targetTable);

		// Mock the extract method to return non-dirty fields
		$attributesEntity->method('extract')
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
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testSetRepositoryThrowsExceptionWhenAssociationDoesNotExist(): void {
		$associationName = 'NonExistentAssociation';
		$existsInRule = new ExistsIn(['user_id'], $associationName);

		$this->mockTable->method('hasAssociation')->with($associationName)->willReturn(false);

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage("ExistsIn rule for 'user_id' is invalid. 'NonExistentAssociation' is not associated with");

		$this->callProtectedMethod($existsInRule, 'setRepository', $this->mockTable);
	}
}
