<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Utility\Content;


use Awyiss\Test\TestSuite\TestCase;
use Awyiss\Utility\Content\AwyissColumn;
use InvalidArgumentException;


/**
 * Test case for AwyissColumn
 *
 * @see \Awyiss\Utility\Content\AwyissColumn
 */
class AwyissColumnTest extends TestCase {
	/**
	 * @return void
	 * @see \Awyiss\Utility\Content\AwyissColumn::getLabel()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testGetLabelWhenLabelIsNotSet(): void {
		$column = new AwyissColumn(6, 12);

		$label = $column->getLabel();

		// Should return full width translation
		$this->assertStringContainsString('column_system::column_width (50%)', $label);

		$column = new AwyissColumn(12, 12);

		$label = $column->getLabel();

		// Should return full width translation
		$this->assertStringContainsString('column_width_full', $label);
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Content\AwyissColumn::getLabel()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testGetLabelWhenLabelIsSet(): void {
		$column = new AwyissColumn(6, 12, 'Custom Label');

		$this->assertEquals('Custom Label', $column->getLabel());
	}


	/**
	 * @testWith [3, 12, "25%"]
	 *           [4, 12, "33%"]
	 *           [8, 12, "67%"]
	 *           [1, 4, "25%"]
	 *           [2, 3, "67%"]
	 * @param int $numerator
	 * @param int $denominator
	 * @param string $expectedPercentage
	 * @return void
	 * @see \Awyiss\Utility\Content\AwyissColumn::getLabel()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testGetLabelWithDifferentFractions(int $numerator, int $denominator, string $expectedPercentage): void {
		$column = new AwyissColumn($numerator, $denominator);
		$label = $column->getLabel();

		$this->assertStringContainsString($expectedPercentage, $label);
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Content\AwyissColumn::getPercentage()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testGetPercentageWithDefaultPrecision(): void {
		$column = new AwyissColumn(1, 3);

		$percentage = $column->getPercentage();

		$this->assertEquals(0.3333333333333333, $percentage);
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Content\AwyissColumn::getPercentage()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testGetPercentageWithCustomPrecision(): void {
		$column = new AwyissColumn(1, 3);

		$percentage = $column->getPercentage(2);

		$this->assertEquals(0.33, $percentage);
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Content\AwyissColumn::getPercentage()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testGetPercentageWithZeroPrecision(): void {
		$column = new AwyissColumn(1, 3);

		$percentage = $column->getPercentage(0);

		$this->assertEquals(0.0, $percentage);
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Content\AwyissColumn::getPercentage()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testGetPercentageWithHighPrecision(): void {
		$column = new AwyissColumn(1, 6);

		$percentage = $column->getPercentage(6);

		$this->assertEquals(0.166667, $percentage);
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Content\AwyissColumn::getPercentage()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testGetPercentageWithFullWidth(): void {
		$column = new AwyissColumn(12, 12);

		$percentage = $column->getPercentage();

		$this->assertEquals(1.0, $percentage);
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Content\AwyissColumn::getPercentage()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testGetPercentageWithZeroNumerator(): void {
		$column = new AwyissColumn(0, 12);

		$percentage = $column->getPercentage();

		$this->assertEquals(0.0, $percentage);
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Content\AwyissColumn::__construct()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testConstructorWithZeroDenominator(): void {
		$this->expectException(InvalidArgumentException::class);

		new AwyissColumn(6, 0);
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Content\AwyissColumn::__construct()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testConstructorWithNegativeNominator(): void {
		$this->expectException(InvalidArgumentException::class);

		new AwyissColumn(-1, 12);
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Content\AwyissColumn::__construct()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testConstructorWithNegativeDenominator(): void {
		$this->expectException(InvalidArgumentException::class);

		new AwyissColumn(6, -12);
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Content\AwyissColumn::getPercentage()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testNumeratorGreaterThanDenominator(): void {
		$column = new AwyissColumn(15, 12);

		$percentage = $column->getPercentage();

		$this->assertEquals(1.25, $percentage);
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Content\AwyissColumn::jsonSerialize()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testJsonSerialize(): void {
		$column = new AwyissColumn(6, 12);

		$json = json_encode($column);
		$data = json_decode($json, true);

		$this->assertSame([
			'cssClass' => 'Column-50',
			'denominator' => 12,
			'fraction' => '6/12',
			'label' => 'column_system::column_width (50%)',
			'numerator' => 6,
			'percentage' => 0.5,
		], $data, 'JSON serialization should match expected structure');
	}
}
