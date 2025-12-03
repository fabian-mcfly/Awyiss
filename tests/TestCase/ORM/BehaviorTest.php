<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\ORM;


use Awyiss\Model\Table;
use Awyiss\ORM\Behavior;
use Awyiss\Test\TestSuite\TestCase;
use RuntimeException;


/**
 * Test case for Behavior
 *
 * @see \Awyiss\ORM\Behavior
 */
class BehaviorTest extends TestCase {
	/**
	 * @var \Awyiss\Model\Table
	 */
	protected Table $mockTable;
	/**
	 * @var \Awyiss\ORM\Behavior
	 */
	protected Behavior $behavior;


	/**
	 * @inheritDoc
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->mockTable = $this->createMock(Table::class);
	}


	/**
	 * Test constructor with default configuration
	 *
	 * @return void
	 * @see \Awyiss\ORM\Behavior::__construct()
	 */
	public function testConstructorWithDefaultConfiguration(): void {
		$this->behavior = new Behavior($this->mockTable);

		// Check that implementedMethods is set to empty array by default
		$this->assertEquals([], $this->behavior->getConfig('implementedMethods'));

		// Check that implementedEvents is set to defaultEvents
		$expectedDefaultEvents = [
			'beforeMarshal',
			'afterMarshal',
			'beforeFind',
			'beforeSave',
			'afterSave',
			'afterSaveCommit',
			'beforeDelete',
			'afterDelete',
			'afterDeleteCommit',
			'buildValidator',
			'buildRules',
			'beforeRules',
			'afterRules',
		];
		$this->assertEquals($expectedDefaultEvents, $this->behavior->getConfig('implementedEvents'));
	}


	/**
	 * Test constructor with custom implementedEvents configuration
	 *
	 * @return void
	 * @see \Awyiss\ORM\Behavior::__construct()
	 */
	public function testConstructorWithCustomImplementedEvents(): void {
		$customEvents = ['beforeSave', 'afterSave'];
		$config = ['implementedEvents' => $customEvents];

		$this->behavior = new Behavior($this->mockTable, $config);

		// Should preserve custom implementedEvents
		$this->assertEquals($customEvents, $this->behavior->getConfig('implementedEvents'));
	}


	/**
	 * Test constructor with empty implementedEvents configuration
	 *
	 * @return void
	 * @see \Awyiss\ORM\Behavior::__construct()
	 */
	public function testConstructorWithEmptyImplementedEvents(): void {
		$config = ['implementedEvents' => []];

		$this->behavior = new Behavior($this->mockTable, $config);

		// Should preserve empty array when explicitly set
		$this->assertEquals([], $this->behavior->getConfig('implementedEvents'));
	}


	/**
	 * Test constructor with null implementedEvents sets defaultEvents
	 *
	 * @return void
	 * @see \Awyiss\ORM\Behavior::__construct()
	 */
	public function testConstructorWithNullImplementedEventsUsesDefault(): void {
		$config = ['implementedEvents' => null];

		$this->behavior = new Behavior($this->mockTable, $config);

		$expectedDefaultEvents = [
			'beforeMarshal',
			'afterMarshal',
			'beforeFind',
			'beforeSave',
			'afterSave',
			'afterSaveCommit',
			'beforeDelete',
			'afterDelete',
			'afterDeleteCommit',
			'buildValidator',
			'buildRules',
			'beforeRules',
			'afterRules',
		];
		$this->assertEquals($expectedDefaultEvents, $this->behavior->getConfig('implementedEvents'));
	}


	/**
	 * Test constructor with custom implementedMethods configuration
	 *
	 * @return void
	 * @see \Awyiss\ORM\Behavior::__construct()
	 */
	public function testConstructorWithCustomImplementedMethods(): void {
		$customMethods = ['customMethod1', 'customMethod2'];
		$config = ['implementedMethods' => $customMethods];

		$this->behavior = new Behavior($this->mockTable, $config);

		// Should preserve custom implementedMethods
		$this->assertEquals($customMethods, $this->behavior->getConfig('implementedMethods'));
	}


	/**
	 * Test constructor with empty implementedMethods configuration
	 *
	 * @return void
	 * @see \Awyiss\ORM\Behavior::__construct()
	 */
	public function testConstructorWithEmptyImplementedMethods(): void {
		$config = ['implementedMethods' => []];

		$this->behavior = new Behavior($this->mockTable, $config);

		// Should set to empty array when explicitly empty
		$this->assertEquals([], $this->behavior->getConfig('implementedMethods'));
	}


	/**
	 * Test implementedEvents returns empty array when no events configured
	 *
	 * @return void
	 * @see \Awyiss\ORM\Behavior::implementedEvents()
	 */
	public function testImplementedEventsReturnsEmptyArrayWhenNoEvents(): void {
		$config = ['implementedEvents' => []];
		$this->behavior = new Behavior($this->mockTable, $config);

		$result = $this->behavior->implementedEvents();

		$this->assertEquals([], $result);
	}


	/**
	 * Test implementedEvents calls table buildEventMap with correct parameters
	 *
	 * @return void
	 * @see \Awyiss\ORM\Behavior::implementedEvents()
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 */
	public function testImplementedEventsCallsTableBuildEventMap(): void {
		$eventMap = ['beforeSave', 'afterSave'];
		$priority = 10;
		$config = [
			'implementedEvents' => $eventMap,
			'priority' => $priority,
		];

		$expectedResult = [
			'Model.beforeSave' => 'beforeSave',
			'Model.afterSave' => 'afterSave',
		];

		$this->mockTable->expects($this->once())->method('buildEventMap')->with(
			$this->isInstanceOf(Behavior::class),
			$this->equalTo($eventMap),
			$this->equalTo($priority)
		)->willReturn($expectedResult);

		$this->behavior = new Behavior($this->mockTable, $config);

		$result = $this->behavior->implementedEvents();

		$this->assertEquals($expectedResult, $result);
	}


	/**
	 * Test implementedEvents with default priority (null)
	 *
	 * @return void
	 * @see \Awyiss\ORM\Behavior::implementedEvents()
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 */
	public function testImplementedEventsWithDefaultPriority(): void {
		$eventMap = ['beforeSave'];
		$config = ['implementedEvents' => $eventMap];

		$expectedResult = ['Model.beforeSave' => 'beforeSave'];

		$this->mockTable->expects($this->once())->method('buildEventMap')->with(
			$this->isInstanceOf(Behavior::class),
			$this->equalTo($eventMap),
			$this->isNull()
		)->willReturn($expectedResult);

		$this->behavior = new Behavior($this->mockTable, $config);

		$result = $this->behavior->implementedEvents();

		$this->assertEquals($expectedResult, $result);
	}


	/**
	 * Test enable method successfully enables behavior
	 *
	 * @return void
	 * @see \Awyiss\ORM\Behavior::enable()
	 */
	public function testEnableSuccessfullyEnablesBehavior(): void {
		$config = ['enabled' => false];
		$this->behavior = new Behavior($this->mockTable, $config);

		$result = $this->behavior->enable();

		$this->assertSame($this->behavior, $result);
		$this->assertTrue($this->behavior->getConfig('enabled'));
	}


	/**
	 * Test enable method throws exception when enabled config key doesn't exist
	 *
	 * @return void
	 * @see \Awyiss\ORM\Behavior::enable()
	 */
	public function testEnableThrowsExceptionWhenEnabledKeyMissing(): void {
		$this->behavior = new Behavior($this->mockTable);

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('Cannot enable behavior `Awyiss\ORM\Behavior`');

		$this->behavior->enable();
	}


	/**
	 * Test disable method successfully disables behavior
	 *
	 * @return void
	 * @see \Awyiss\ORM\Behavior::disable()
	 */
	public function testDisableSuccessfullyDisablesBehavior(): void {
		$config = ['enabled' => true];
		$this->behavior = new Behavior($this->mockTable, $config);

		$result = $this->behavior->disable();

		$this->assertSame($this->behavior, $result);
		$this->assertFalse($this->behavior->getConfig('enabled'));
	}


	/**
	 * Test disable method throws exception when enabled config key doesn't exist
	 *
	 * @return void
	 * @see \Awyiss\ORM\Behavior::disable()
	 */
	public function testDisableThrowsExceptionWhenEnabledKeyMissing(): void {
		$this->behavior = new Behavior($this->mockTable);

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('Cannot disable behavior `Awyiss\ORM\Behavior`');

		$this->behavior->disable();
	}


	/**
	 * Test constructor with complex configuration
	 *
	 * @return void
	 * @see \Awyiss\ORM\Behavior::__construct()
	 * @throws \ReflectionException
	 */
	public function testConstructorWithComplexConfiguration(): void {
		$config = [
			'implementedMethods' => ['customMethod1', 'customMethod2'],
			'implementedEvents' => ['beforeSave', 'afterSave', 'beforeDelete'],
			'priority' => 15,
			'enabled' => true,
			'customOption' => 'customValue',
		];

		$this->behavior = new Behavior($this->fetchTable('Users'), $config);

		$this->assertEquals(['customMethod1', 'customMethod2'], $this->behavior->getConfig('implementedMethods'));
		$this->assertEquals(['beforeSave', 'afterSave', 'beforeDelete'], $this->behavior->getConfig('implementedEvents'));
		$this->assertEquals(15, $this->behavior->getConfig('priority'));
		$this->assertTrue($this->behavior->getConfig('enabled'));
		$this->assertEquals('customValue', $this->behavior->getConfig('customOption'));

		$this->assertSame(['customMethod1', 'customMethod2'], $this->behavior->implementedMethods());
		// implementedEvents will return an empty array since no event has a matching method
		$this->assertSame([], $this->behavior->implementedEvents());
	}

	/**
	 * Test behavior with extending class that overrides defaultEvents
	 *
	 * @return void
	 * @see \Awyiss\ORM\Behavior::__construct()
	 * @see \Awyiss\ORM\Behavior::implementedEvents()
	 */
	public function testBehaviorWithCustomEvents(): void {
		// Create an anonymous class that extends Behavior with custom defaultEvents
		$customBehavior = new class ($this->fetchTable('Users'), ['implementedEvents' => ['dummyMethod1', 'dummyMethod2', 'dummyMethod3']]) extends Behavior {
			/** @noinspection PhpUnused */
			public function dummyMethod1() {
			}

			/** @noinspection PhpUnused */
			public function dummyMethod2() {
			}
		};

		// Check that the custom behavior has the correct implementedEvents
		$expectedEvents = [
			'Model.dummyMethod1' => 'dummyMethod1',
			'Model.dummyMethod2' => 'dummyMethod2',
		];
		$this->assertEquals($expectedEvents, $customBehavior->implementedEvents());
	}
}
