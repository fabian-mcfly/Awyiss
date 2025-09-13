<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Utility\Content;


use Awyiss\Test\TestSuite\TestCase;
use Awyiss\Utility\Content\BootstrapColumn;
use InvalidArgumentException;


/**
 * Test case for BootstrapColumn
 *
 * @see \Awyiss\Utility\Content\BootstrapColumn
 */
class BootstrapColumnTest extends TestCase {
	/**
	 * @return void
	 * @see \Awyiss\Utility\Content\BootstrapColumn::getLabel()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testGetLabelWhenLabelIsNotSet(): void {
		$column = new BootstrapColumn(6, 12);

		$label = $column->getLabel();

		// Should return full width translation
		$this->assertStringContainsString('column_system::column_width (50%)', $label);

		$column = new BootstrapColumn(12, 12);

		$label = $column->getLabel();

		// Should return full width translation
		$this->assertStringContainsString('column_width_full', $label);
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Content\BootstrapColumn::getLabel()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testGetLabelWhenLabelIsSet(): void {
		$column = new BootstrapColumn(6, 12, 'Custom Label');

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
	 * @see \Awyiss\Utility\Content\BootstrapColumn::getLabel()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testGetLabelWithDifferentFractions(int $numerator, int $denominator, string $expectedPercentage): void {
		$column = new BootstrapColumn($numerator, $denominator);
		$label = $column->getLabel();

		$this->assertStringContainsString($expectedPercentage, $label);
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Content\BootstrapColumn::getPercentage()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testGetPercentageWithDefaultPrecision(): void {
		$column = new BootstrapColumn(1, 3);

		$percentage = $column->getPercentage();

		$this->assertEquals(0.3333333333333333, $percentage);
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Content\BootstrapColumn::getPercentage()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testGetPercentageWithCustomPrecision(): void {
		$column = new BootstrapColumn(1, 3);

		$percentage = $column->getPercentage(2);

		$this->assertEquals(0.33, $percentage);
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Content\BootstrapColumn::getPercentage()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testGetPercentageWithZeroPrecision(): void {
		$column = new BootstrapColumn(1, 3);

		$percentage = $column->getPercentage(0);

		$this->assertEquals(0.0, $percentage);
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Content\BootstrapColumn::getPercentage()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testGetPercentageWithHighPrecision(): void {
		$column = new BootstrapColumn(1, 6);

		$percentage = $column->getPercentage(6);

		$this->assertEquals(0.166667, $percentage);
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Content\BootstrapColumn::getPercentage()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testGetPercentageWithFullWidth(): void {
		$column = new BootstrapColumn(12, 12);

		$percentage = $column->getPercentage();

		$this->assertEquals(1.0, $percentage);
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Content\BootstrapColumn::getPercentage()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testGetPercentageWithZeroNumerator(): void {
		$column = new BootstrapColumn(0, 12);

		$percentage = $column->getPercentage();

		$this->assertEquals(0.0, $percentage);
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Content\BootstrapColumn::__construct()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testConstructorWithZeroDenominator(): void {
		$this->expectException(InvalidArgumentException::class);

		new BootstrapColumn(6, 0);
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Content\BootstrapColumn::__construct()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testConstructorWithNegativeNominator(): void {
		$this->expectException(InvalidArgumentException::class);

		new BootstrapColumn(-1, 12);
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Content\BootstrapColumn::__construct()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testConstructorWithNegativeDenominator(): void {
		$this->expectException(InvalidArgumentException::class);

		new BootstrapColumn(6, -12);
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Content\BootstrapColumn::getPercentage()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testNumeratorGreaterThanDenominator(): void {
		$column = new BootstrapColumn(15, 12);

		$percentage = $column->getPercentage();

		$this->assertEquals(1.25, $percentage);
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Content\BootstrapColumn::jsonSerialize()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testJsonSerialize(): void {
		$column = new BootstrapColumn(6, 12);

		$json = json_encode($column);
		$data = json_decode($json, true);

		$this->assertSame([
			'cssClass' => 'col-md-6',
			'denominator' => 12,
			'fraction' => '6/12',
			'label' => 'column_system::column_width (50%)',
			'numerator' => 6,
			'percentage' => 0.5,
		], $data, 'JSON serialization should match expected structure');
	}
}
