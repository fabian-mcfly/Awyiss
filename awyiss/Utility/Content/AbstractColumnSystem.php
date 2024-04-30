<?php declare(strict_types=1);


namespace Awyiss\Utility\Content;


use RuntimeException;


/**
 * Class AbstractColumnSystem
 */
abstract class AbstractColumnSystem implements ColumnSystemInterface {
	/**
	 * @var string The class name of the column
	 */
	protected static string $columnClassName = AwyissColumn::class;
	/**
	 * @var int The maximum denominator for the column system
	 */
	protected static int $maxDenominator = 6;
	/**
	 * @var array The column widths
	 */
	protected static array $columnWidths;


	/**
	 * Constructor. Throw an exception since this class only offers static methods
	 */
	private function __construct() {
		throw new RuntimeException(sprintf('The class `%s` cannot be instantiated', static::class));
	}


	/**
	 * @inheritDoc
	 */
	public static function getColumnWidths(): array {
		if (!isset(static::$columnWidths)) {
			static::$columnWidths = static::buildUniqueFractions(1, static::$maxDenominator);
		}


		return static::$columnWidths;
	}


	/**
	 * @inheritDoc
	 */
	public static function getColumnIndents(): array {
		$la_blocklistedFractions = [
			'1/1',
		];

		$la_indents = array_diff_key(static::getColumnWidths(), array_flip($la_blocklistedFractions));

		foreach ($la_indents as $ls_key => $lo_column) {
			$lo_column = clone $lo_column;
			$lo_column->setCssClassPrefix('ColumnIndent');

			$la_indents[ $ls_key ] = $lo_column;
		}


		return $la_indents;
	}


	/**
	 * @inheritDoc
	 */
	public static function getMaxDenominator(): int {
		return static::$maxDenominator;
	}


	/**
	 * @inheritDoc
	 */
	public static function setMaxDenominator(int $maxDenominator): void {
		static::$maxDenominator = $maxDenominator;
	}


	/**
	 * @inheritDoc
	 */
	public static function getScssColumnList(): array {
		$la_columnList = [];
		foreach (static::getColumnWidths() as $lo_column) {
			$la_columnList[] = $lo_column->getFraction();
		}

		return $la_columnList;
	}


	/**
	 * @param int $minDenominator
	 * @param int $maxDenominator
	 * @return array
	 */
	protected static function buildUniqueFractions(int $minDenominator, int $maxDenominator): array {
		$la_fractions = [];

		//Generate all possible fractions
		for ($li_denominator = $minDenominator; $li_denominator <= $maxDenominator; $li_denominator++) {
			for ($li_numerator = 1; $li_numerator <= $li_denominator; $li_numerator++) {
				$li_gcd = static::gcd($li_numerator, $li_denominator);

				$li_simplifiedNumerator = $li_numerator / $li_gcd;
				$li_simplifiedDenominator = $li_denominator / $li_gcd;

				$ls_fraction = $li_simplifiedNumerator . '/' . $li_simplifiedDenominator;

				// Avoid adding duplicates
				if (!array_key_exists($ls_fraction, $la_fractions)) {
					$la_fractions[ $ls_fraction ] = new static::$columnClassName(
						fraction: $ls_fraction,
						numerator: $li_simplifiedNumerator,
						denominator: $li_simplifiedDenominator,
					);
				}
			}
		}

		//Sort fractions by their numerical value
		uasort($la_fractions, function (ColumnInterface $a, ColumnInterface $b) {
			// Check if either fraction is [1, 1] and adjust ordering
			if ($a->getNumerator() / $a->getDenominator() === 1) {
				return -1; // $a is [1, 1], so it should come before $b
			}
			if ($b->getNumerator() / $b->getDenominator() === 1) {
				return 1; // $b is [1, 1], so it should come after $a
			}

			return $a->getNumerator() / $a->getDenominator() <=> $b->getNumerator() / $b->getDenominator();
		});


		return $la_fractions;
	}


	/**
	 * Calculates the Greatest Common Divisor (GCD) of two numbers using the Euclidean algorithm.
	 * The Euclidean algorithm is an efficient method for computing the greatest common divisor (GCD) of two numbers,
	 * the largest number that divides both of them without leaving a remainder.
	 *
	 * @param int $firstNumber The first number.
	 * @param int $secondNumber The second number.
	 * @return int The GCD of the two numbers.
	 */
	protected static function gcd(int $firstNumber, int $secondNumber): int {
		$li_firstNumber = $firstNumber;
		$li_secondNumber = $secondNumber;

		//Continue the loop until $li_secondNumber is zero.
		while ($li_secondNumber != 0) {
			//Temporary variable to hold $li_secondNumber.
			$li_temp = $li_secondNumber;
			//Set $li_secondNumber to the remainder of $li_firstNumber divided by $li_secondNumber.
			$li_secondNumber = $li_firstNumber % $li_secondNumber;
			//Set $li_firstNumber to the previously stored $li_secondNumber (stored in $temp).
			$li_firstNumber = $li_temp;
		}


		//When $li_secondNumber is zero, $li_firstNumber contains the GCD of the original two numbers.
		return $li_firstNumber;
	}
}
