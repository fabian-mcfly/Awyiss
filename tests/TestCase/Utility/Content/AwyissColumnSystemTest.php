<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Utility\Content;


use Awyiss\Test\TestSuite\TestCase;
use Awyiss\Utility\Content\AwyissColumn;
use Awyiss\Utility\Content\AwyissColumnSystem;


/**
 * Test case for AwyissColumnSystem
 *
 * @see \Awyiss\Utility\Content\AwyissColumnSystem
 */
class AwyissColumnSystemTest extends TestCase {
	/**
	 * @inheritDoc
	 */
	public static function tearDownAfterClass(): void {
		parent::tearDownAfterClass();

		AwyissColumnSystem::setMaxDenominator(5);
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Content\AwyissColumnSystem::getColumnWidths()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testGetColumnWidthsReturnsCorrectNumberOfColumns(): void {
		$columnWidths = AwyissColumnSystem::getColumnWidths();

		$this->assertCount(10, $columnWidths);
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Content\AwyissColumnSystem::getColumnWidths()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testGetColumnWidthsReturnsAwyissColumnInstances(): void {
		$columnWidths = AwyissColumnSystem::getColumnWidths();

		foreach ($columnWidths as $column) {
			$this->assertInstanceOf(AwyissColumn::class, $column);
		}
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Content\AwyissColumnSystem::getColumnWidths()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testGetColumnWidthsContainsCorrectFractions(): void {
		$columnWidths = AwyissColumnSystem::getColumnWidths();

		$expectedFractions = ['1/1', '1/5', '1/4', '1/3', '2/5', '1/2', '3/5', '2/3', '3/4', '4/5'];

		$this->assertSame($expectedFractions, array_keys($columnWidths));
	}


	/**
	 * @testWith [1, 1]
	 *           [1, 2]
	 *           [1, 3]
	 *           [2, 3]
	 *           [1, 4]
	 *           [3, 4]
	 *           [1, 5]
	 *           [2, 5]
	 *           [3, 5]
	 *           [4, 5]
	 * @param int $numerator
	 * @param int $denominator
	 * @return void
	 * @see \Awyiss\Utility\Content\AwyissColumnSystem::getColumnWidths()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testGetColumnWidthsColumnsHaveCorrectNumeratorAndDenominator(int $numerator, int $denominator): void {
		$columnWidths = AwyissColumnSystem::getColumnWidths();

		$column = $columnWidths["$numerator/$denominator"];
		$this->assertEquals($numerator, $column->getNumerator());
		$this->assertEquals($denominator, $column->getDenominator());
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Content\AwyissColumnSystem::getColumnIndents()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testGetColumnIndentsExcludesFullWidthColumn(): void {
		$columnIndents = AwyissColumnSystem::getColumnIndents();

		$this->assertArrayNotHasKey('1/1', $columnIndents);
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Content\AwyissColumnSystem::getColumnIndents()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testGetColumnIndentsReturnsCorrectNumberOfIndents(): void {
		$columnIndents = AwyissColumnSystem::getColumnIndents();

		$this->assertCount(9, $columnIndents);
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Content\AwyissColumnSystem::getColumnIndents()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testGetColumnIndentsContainsCorrectFractions(): void {
		$columnIndents = AwyissColumnSystem::getColumnIndents();

		$expectedFractions = ['1/5', '1/4', '1/3', '2/5', '1/2', '3/5', '2/3', '3/4', '4/5'];

		$this->assertSame($expectedFractions, array_keys($columnIndents));
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Content\AwyissColumnSystem::getColumnIndents()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testGetColumnIndentsColumnsHaveOffsetCssClassPrefix(): void {
		$columnIndents = AwyissColumnSystem::getColumnIndents();

		/** @var AwyissColumn $column */
		foreach ($columnIndents as $column) {
			$this->assertStringStartsWith('ColumnIndent-', $column->getCssClass());
		}
	}


	/**
	 * @testWith [1, 2]
	 *           [1, 3]
	 *           [2, 3]
	 *           [1, 4]
	 *           [3, 4]
	 *           [1, 5]
	 *           [2, 5]
	 *           [3, 5]
	 *           [4, 5]
	 * @param int $numerator
	 * @param int $denominator
	 * @return void
	 * @see \Awyiss\Utility\Content\AwyissColumnSystem::getColumnIndents()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testGetColumnIndentsColumnsHaveCorrectNumeratorAndDenominator(int $numerator, int $denominator): void {
		$columnIndents = AwyissColumnSystem::getColumnIndents();

		$column = $columnIndents["$numerator/$denominator"];
		$this->assertEquals($numerator, $column->getNumerator());
		$this->assertEquals($denominator, $column->getDenominator());
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Content\AwyissColumnSystem::getName()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testGetNameReturnsAwyiss(): void {
		$name = AwyissColumnSystem::getName();

		$this->assertEquals('Awyiss', $name);
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Content\AwyissColumnSystem::getScssFilePaths()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testGetScssFilePaths(): void {
		$filePaths = AwyissColumnSystem::getScssFilePaths();

		$this->assertArrayHasKey('pre', $filePaths);
		$this->assertArrayHasKey(0, $filePaths['pre']);
		$this->assertArrayHasKey('post', $filePaths);
		$this->assertArrayHasKey(0, $filePaths['post']);

		$this->assertEquals(ROOT . DS . implode(DS, ['awyiss', 'assets', 'scss', 'Frontend', 'ColumnSystem', 'Awyiss', '_helpers.scss']), $filePaths['pre'][0]);
		$this->assertEquals(ROOT . DS . implode(DS, ['awyiss', 'assets', 'scss', 'Frontend', 'ColumnSystem', 'Awyiss', '_content_elements.scss']), $filePaths['post'][0]);
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Content\AwyissColumnSystem::setMaxDenominator()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testSetMaxDenominatorChangesColumnCount(): void {
		AwyissColumnSystem::setMaxDenominator(6);
		$columnWidths = AwyissColumnSystem::getColumnWidths();

		$this->assertCount(12, $columnWidths);
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Content\AwyissColumnSystem::setMaxDenominator()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testSetMaxDenominatorChangesColumnFractions(): void {
		AwyissColumnSystem::setMaxDenominator(4);
		$columnWidths = AwyissColumnSystem::getColumnWidths();

		$expectedFractions = ['1/1', '1/4', '1/3', '1/2', '2/3', '3/4'];
		$this->assertSame($expectedFractions, array_keys($columnWidths));
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Content\AwyissColumnSystem::setMaxDenominator()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testSetMaxDenominatorAffectsColumnIndents(): void {
		AwyissColumnSystem::setMaxDenominator(3);
		$columnIndents = AwyissColumnSystem::getColumnIndents();

		$this->assertCount(3, $columnIndents); // 4 total - 1 full width = 2
		$expectedFractions = ['1/3', '1/2', '2/3'];
		$this->assertSame($expectedFractions, array_keys($columnIndents));
	}
}
