<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\ORM;


use Awyiss\ORM\Marshaller;
use Awyiss\Test\TestSuite\TestCase;
use Cake\Event\EventInterface;
use Cake\ORM\Entity;
use Cake\ORM\Table;


/**
 * Test case for Marshaller
 *
 * @see \Awyiss\ORM\Marshaller
 */
class MarshallerTest extends TestCase {
	/**
	 * @var \Awyiss\ORM\Marshaller
	 */
	protected Marshaller $marshaller;
	/**
	 * @var \Cake\ORM\Table
	 */
	protected Table $mockTable;
	/**
	 * @var \Cake\ORM\Entity
	 */
	protected Entity $mockEntity;


	/**
	 * @inheritDoc
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->mockTable = $this->createMock(Table::class);
		$this->mockEntity = $this->createMock(Entity::class);
		$this->marshaller = new Marshaller($this->mockTable);
	}


	/**
	 * Test merge method with accessibleFields as string
	 *
	 * @return void
	 * @see \Awyiss\ORM\Marshaller::merge()
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 */
	public function testMergeWithAccessibleFieldsAsString(): void {
		$data = ['name' => 'Test Name', 'email' => 'test@example.com'];
		$options = ['fields' => ['name', 'email'], 'accessibleFields' => 'name'];

		$this->mockEntity->expects($this->once())->method('isNew')->willReturn(true);

		$this->mockEntity->expects($this->once())->method('setAccess')->with('name', true);

		$this->mockEntity->expects($this->once())->method('setErrors');

		$this->mockEntity->expects($this->exactly(2))->method('set');

		$this->mockTable->expects($this->never())->method('getEntityClass');

		$this->mockTable->expects($this->once())->method('getAlias')->willReturn('TestTable');

		$result = $this->marshaller->merge($this->mockEntity, $data, $options);

		$this->assertSame($this->mockEntity, $result);
	}


	/**
	 * Test merge method with accessibleFields as array with string values
	 *
	 * @return void
	 * @see \Awyiss\ORM\Marshaller::merge()
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 */
	public function testMergeWithAccessibleFieldsAsArrayWithStringValues(): void {
		$data = ['name' => 'Test Name', 'email' => 'test@example.com'];
		$options = ['fields' => ['name', 'email'], 'accessibleFields' => ['name', 'email']];

		$this->mockEntity->expects($this->once())->method('isNew')->willReturn(true);

		$this->mockEntity->expects($this->exactly(2))->method('setAccess')->willReturnCallback(function ($field, $value) {
			static $callCount = 0;
			$callCount++;

			if ($callCount === 1) {
				$this->assertEquals('name', $field);
				$this->assertTrue($value);
			}
			elseif ($callCount === 2) {
				$this->assertEquals('email', $field);
				$this->assertTrue($value);
			}
		});

		$this->mockEntity->expects($this->once())->method('setErrors');

		$this->mockEntity->expects($this->exactly(2))->method('set');

		$this->mockTable->expects($this->never())->method('getEntityClass');

		$this->mockTable->expects($this->once())->method('getAlias')->willReturn('TestTable');

		$result = $this->marshaller->merge($this->mockEntity, $data, $options);

		$this->assertSame($this->mockEntity, $result);
	}


	/**
	 * Test merge method with accessibleFields as associative array
	 *
	 * @return void
	 * @see \Awyiss\ORM\Marshaller::merge()
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 */
	public function testMergeWithAccessibleFieldsAsAssociativeArray(): void {
		$data = ['name' => 'Test Name', 'email' => 'test@example.com'];
		$options = ['fields' => ['name', 'email'], 'accessibleFields' => ['name' => true, 'email' => false]];

		$this->mockEntity->expects($this->once())->method('isNew')->willReturn(true);

		$this->mockEntity->expects($this->exactly(2))->method('setAccess')->willReturnCallback(function ($field, $value) {
			static $callCount = 0;
			$callCount++;

			if ($callCount === 1) {
				$this->assertEquals('name', $field);
				$this->assertTrue($value);
			}
			elseif ($callCount === 2) {
				$this->assertEquals('email', $field);
				$this->assertFalse($value);
			}
		});

		$this->mockEntity->expects($this->once())->method('setErrors');

		$this->mockEntity->expects($this->exactly(2))->method('set');

		$this->mockTable->expects($this->never())->method('getEntityClass');

		$this->mockTable->expects($this->once())->method('getAlias')->willReturn('TestTable');

		$result = $this->marshaller->merge($this->mockEntity, $data, $options);

		$this->assertSame($this->mockEntity, $result);
	}


	/**
	 * Test merge method with setter option passed to entity set
	 * when fields option is set
	 *
	 * @return void
	 * @see \Awyiss\ORM\Marshaller::merge()
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 */
	public function testMergeWithSetterOptionPassedToEntitySetWhenFieldsIsSet(): void {
		$data = ['name' => 'Test Name'];
		$options = ['fields' => ['name'], 'setter' => false];

		$this->mockEntity->expects($this->once())->method('isNew')->willReturn(true);

		$this->mockEntity->expects($this->once())->method('setErrors');

		$this->mockEntity->expects($this->once())->method('set')->with('name', 'Test Name', ['setter' => false]);

		$this->mockTable->expects($this->never())->method('getEntityClass');

		$this->mockTable->expects($this->once())->method('getAlias')->willReturn('TestTable');

		$result = $this->marshaller->merge($this->mockEntity, $data, $options);

		$this->assertSame($this->mockEntity, $result);
	}


	/**
	 * Test merge method with setter option passed to entity set
	 * when fields option is not set
	 *
	 * @return void
	 * @see \Awyiss\ORM\Marshaller::merge()
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 */
	public function testMergeWithSetterOptionPassedToEntitySetWhenFieldsIsNotSet(): void {
		$data = ['name' => 'Test Name'];
		$options = ['setter' => false];

		$this->mockEntity->expects($this->once())->method('isNew')->willReturn(true);

		$this->mockEntity->expects($this->once())->method('setErrors');

		$this->mockEntity->expects($this->never())->method('set');

		$this->mockEntity->expects($this->once())->method('patch')->with(['name' => 'Test Name'], ['setter' => false]);

		$this->mockTable->expects($this->never())->method('getEntityClass');

		$this->mockTable->expects($this->once())->method('getAlias')->willReturn('TestTable');

		$result = $this->marshaller->merge($this->mockEntity, $data, $options);

		$this->assertSame($this->mockEntity, $result);
	}

	/**
	 * Test merge method with default setter option when not specified
	 * when fields option is set
	 *
	 * @return void
	 * @see \Awyiss\ORM\Marshaller::merge()
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 */
	public function testMergeWithDefaultSetterOptionWhenFieldsIsSet(): void {
		$data = ['name' => 'Test Name'];
		$options = ['fields' => ['name']];

		$this->mockEntity->expects($this->once())->method('isNew')->willReturn(true);

		$this->mockEntity->expects($this->once())->method('setErrors');

		$this->mockEntity->expects($this->once())->method('set')->with('name', 'Test Name', ['setter' => true]);

		$this->mockTable->expects($this->never())->method('getEntityClass');

		$this->mockTable->expects($this->once())->method('getAlias')->willReturn('TestTable');

		$result = $this->marshaller->merge($this->mockEntity, $data, $options);

		$this->assertSame($this->mockEntity, $result);
	}


	/**
	 * Test merge method with default setter option when not specified
	 * when fields option is not set
	 *
	 * @return void
	 * @see \Awyiss\ORM\Marshaller::merge()
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 */
	public function testMergeWithDefaultSetterOptionWhenFieldsIsNotSet(): void {
		$data = ['name' => 'Test Name'];
		$options = [];

		$this->mockEntity->expects($this->once())->method('isNew')->willReturn(true);

		$this->mockEntity->expects($this->once())->method('setErrors');

		$this->mockEntity->expects($this->never())->method('set');

		$this->mockEntity->expects($this->once())->method('patch')->with(['name' => 'Test Name'], ['setter' => true]);

		$this->mockTable->expects($this->never())->method('getEntityClass');

		$this->mockTable->expects($this->once())->method('getAlias')->willReturn('TestTable');

		$result = $this->marshaller->merge($this->mockEntity, $data, $options);

		$this->assertSame($this->mockEntity, $result);
	}



	/**
	 * Test merge method skips events when events option is false
	 *
	 * @return void
	 * @see \Awyiss\ORM\Marshaller::merge()
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 */
	public function testMergeSkipsEventsWhenEventsIsFalse(): void {
		$data = ['name' => 'Test Name'];
		$options = ['events' => false];

		$this->mockEntity->expects($this->once())->method('isNew')->willReturn(true);

		$this->mockEntity->expects($this->once())->method('setErrors');

		$this->mockEntity->expects($this->once())->method('patch');

		$this->mockTable->expects($this->never())->method('getEntityClass');

		$this->mockTable->expects($this->once())->method('getAlias')->willReturn('TestTable');

		$this->mockTable->expects($this->never())->method('dispatchEvent');

		$result = $this->marshaller->merge($this->mockEntity, $data, $options);

		$this->assertSame($this->mockEntity, $result);
	}


	/**
	 * Test merge method dispatches `beforeMarshal` and `afterMarshal` events when events option is true
	 *
	 * @return void
	 * @see \Awyiss\ORM\Marshaller::merge()
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 */
	public function testMergeDispatchesAfterMarshalEventWhenEventsIsTrue(): void {
		$data = ['name' => 'Test Name'];
		$options = ['events' => true];

		$this->mockEntity->expects($this->once())->method('isNew')->willReturn(true);

		$this->mockEntity->expects($this->once())->method('setErrors');

		$this->mockEntity->expects($this->once())->method('patch')->with($this->isArray());

		$this->mockTable->expects($this->never())->method('getEntityClass');

		$this->mockTable->expects($this->once())->method('getAlias')->willReturn('TestTable');

		$this->mockTable->expects($this->exactly(2))->method('dispatchEvent')->willReturnCallback(function (string $field): EventInterface {
			static $callCount = 0;
			$callCount++;

			if ($callCount === 1) {
				$this->assertEquals('Model.beforeMarshal', $field);
			} elseif ($callCount === 2) {
				$this->assertEquals('Model.afterMarshal', $field);
			}

			// Return must be an EventInterface instance, but we don't care about the details here
			return $this->createStub(EventInterface::class);
		});

		$result = $this->marshaller->merge($this->mockEntity, $data, $options);

		$this->assertSame($this->mockEntity, $result);
	}


	/**
	 * Test merge method dispatches `beforeMarshal` and `afterMarshal` event by default
	 *
	 * @return void
	 * @see \Awyiss\ORM\Marshaller::merge()
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 */
	public function testMergeDispatchesAfterMarshalEventByDefault(): void {
		$data = ['name' => 'Test Name'];
		$options = [];

		$this->mockEntity->expects($this->once())->method('isNew')->willReturn(true);

		$this->mockEntity->expects($this->once())->method('setErrors');

		$this->mockEntity->expects($this->once())->method('patch')->with($this->isArray());

		$this->mockTable->expects($this->never())->method('getEntityClass');

		$this->mockTable->expects($this->once())->method('getAlias')->willReturn('TestTable');

		$this->mockTable->expects($this->exactly(2))->method('dispatchEvent')->willReturnCallback(function (string $field): EventInterface {
			static $callCount = 0;
			$callCount++;

			if ($callCount === 1) {
				$this->assertEquals('Model.beforeMarshal', $field);
			}
			elseif ($callCount === 2) {
				$this->assertEquals('Model.afterMarshal', $field);
			}

			// Return must be an EventInterface instance, but we don't care about the details here
			return $this->createStub(EventInterface::class);
		});

		$result = $this->marshaller->merge($this->mockEntity, $data, $options);

		$this->assertSame($this->mockEntity, $result);
	}
}
