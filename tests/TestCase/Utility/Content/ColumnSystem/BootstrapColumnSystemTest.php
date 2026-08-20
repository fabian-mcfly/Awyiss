<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Utility\Content\ColumnSystem;


use Awyiss\Test\TestSuite\TestCase;
use Awyiss\Utility\Content\ColumnSystem\BootstrapColumn;
use Awyiss\Utility\Content\ColumnSystem\BootstrapColumnSystem;


/**
 * Test case for BootstrapColumnSystem
 *
 * @see \Awyiss\Utility\Content\ColumnSystem\BootstrapColumnSystem
 */
class BootstrapColumnSystemTest extends TestCase {
	/**
	 * @inheritDoc
	 */
	public static function tearDownAfterClass(): void {
		parent::tearDownAfterClass();

		BootstrapColumnSystem::setMaxDenominator(12);
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Content\ColumnSystem\BootstrapColumnSystem::getColumnWidths()
	 */
	public function testGetColumnWidthsReturnsCorrectNumberOfColumns(): void {
		$columnWidths = BootstrapColumnSystem::getColumnWidths();

		$this->assertCount(12, $columnWidths);
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Content\ColumnSystem\BootstrapColumnSystem::getColumnWidths()
	 */
	public function testGetColumnWidthsReturnsBootstrapColumnInstances(): void {
		$columnWidths = BootstrapColumnSystem::getColumnWidths();

		foreach ($columnWidths as $column) {
			$this->assertInstanceOf(BootstrapColumn::class, $column);
		}
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Content\ColumnSystem\BootstrapColumnSystem::getColumnWidths()
	 */
	public function testGetColumnWidthsContainsCorrectFractions(): void {
		$columnWidths = BootstrapColumnSystem::getColumnWidths();

		$expectedFractions = ['12/12', '1/12', '2/12', '3/12', '4/12', '5/12', '6/12', '7/12', '8/12', '9/12', '10/12', '11/12'];

		$this->assertSame($expectedFractions, array_keys($columnWidths));
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Content\ColumnSystem\BootstrapColumnSystem::getColumnWidths()
	 */
	public function testGetColumnWidthsColumnsHaveCorrectNumeratorAndDenominator(): void {
		$columnWidths = BootstrapColumnSystem::getColumnWidths();

		for ($i = 1; $i <= 12; $i++) {
			$fraction = $i . '/12';
			$column = $columnWidths[ $fraction ];

			$this->assertEquals($i, $column->getNumerator());
			$this->assertEquals(12, $column->getDenominator());
		}
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Content\ColumnSystem\BootstrapColumnSystem::getColumnIndents()
	 */
	public function testGetColumnIndentsExcludesFullWidthColumn(): void {
		$columnIndents = BootstrapColumnSystem::getColumnIndents();

		$this->assertArrayNotHasKey('12/12', $columnIndents);
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Content\ColumnSystem\BootstrapColumnSystem::getColumnIndents()
	 */
	public function testGetColumnIndentsReturnsCorrectNumberOfIndents(): void {
		$columnIndents = BootstrapColumnSystem::getColumnIndents();

		$this->assertCount(11, $columnIndents);
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Content\ColumnSystem\BootstrapColumnSystem::getColumnIndents()
	 */
	public function testGetColumnIndentsContainsCorrectFractions(): void {
		$columnIndents = BootstrapColumnSystem::getColumnIndents();

		$expectedFractions = ['1/12', '2/12', '3/12', '4/12', '5/12', '6/12', '7/12', '8/12', '9/12', '10/12', '11/12'];

		$this->assertSame($expectedFractions, array_keys($columnIndents));
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Content\ColumnSystem\BootstrapColumnSystem::getColumnIndents()
	 */
	public function testGetColumnIndentsColumnsHaveOffsetCssClassPrefix(): void {
		$columnIndents = BootstrapColumnSystem::getColumnIndents();

		/** @var BootstrapColumn $column */
		foreach ($columnIndents as $column) {
			$this->assertStringStartsWith('offset-', $column->getCssClass());
		}
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Content\ColumnSystem\BootstrapColumnSystem::getColumnIndents()
	 */
	public function testGetColumnIndentsColumnsHaveCorrectNumeratorAndDenominator(): void {
		$columnIndents = BootstrapColumnSystem::getColumnIndents();

		for ($i = 1; $i <= 11; $i++) {
			$fraction = $i . '/12';
			$column = $columnIndents[ $fraction ];

			$this->assertEquals($i, $column->getNumerator());
			$this->assertEquals(12, $column->getDenominator());
		}
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Content\ColumnSystem\BootstrapColumnSystem::getName()
	 */
	public function testGetNameReturnsBootstrap(): void {
		$name = BootstrapColumnSystem::getName();

		$this->assertEquals('Bootstrap', $name);
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Content\ColumnSystem\BootstrapColumnSystem::getScssFilePaths()
	 */
	public function testGetScssFilePaths(): void {
		$filePaths = BootstrapColumnSystem::getScssFilePaths();

		$this->assertEquals([], $filePaths);
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Content\ColumnSystem\BootstrapColumnSystem::setMaxDenominator()
	 */
	public function testSetMaxDenominatorChangesColumnCount(): void {
		BootstrapColumnSystem::setMaxDenominator(6);
		$columnWidths = BootstrapColumnSystem::getColumnWidths();

		$this->assertCount(6, $columnWidths);
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Content\ColumnSystem\BootstrapColumnSystem::setMaxDenominator()
	 */
	public function testSetMaxDenominatorChangesColumnFractions(): void {
		BootstrapColumnSystem::setMaxDenominator(4);
		$columnWidths = BootstrapColumnSystem::getColumnWidths();

		$expectedFractions = ['4/4', '1/4', '2/4', '3/4'];
		$this->assertSame($expectedFractions, array_keys($columnWidths));
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Content\ColumnSystem\BootstrapColumnSystem::setMaxDenominator()
	 */
	public function testSetMaxDenominatorAffectsColumnIndents(): void {
		BootstrapColumnSystem::setMaxDenominator(3);
		$columnIndents = BootstrapColumnSystem::getColumnIndents();

		$this->assertCount(2, $columnIndents); // 3 total - 1 full width = 2
		$expectedFractions = ['1/3', '2/3'];
		$this->assertSame($expectedFractions, array_keys($columnIndents));
	}
}
