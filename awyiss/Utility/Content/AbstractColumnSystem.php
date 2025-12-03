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
		if (!isset(static::$columnWidths[ static::$maxDenominator ])) {
			static::$columnWidths[ static::$maxDenominator ] = static::buildUniqueFractions(1, static::$maxDenominator);
		}


		return static::$columnWidths[ static::$maxDenominator ];
	}


	/**
	 * @inheritDoc
	 */
	public static function getColumnIndents(): array {
		$blocklistedFractions = [
			'1/1',
		];

		$indents = array_diff_key(static::getColumnWidths(), array_flip($blocklistedFractions));

		foreach ($indents as $key => $column) {
			$column = clone $column;
			$column->setCssClassPrefix('ColumnIndent');

			$indents[ $key ] = $column;
		}


		return $indents;
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
		if ($maxDenominator < 1) {
			throw new RuntimeException(sprintf('The maximum denominator must be at least 1, %d given', $maxDenominator));
		}

		static::$maxDenominator = $maxDenominator;
	}


	/**
	 * @param int $minDenominator
	 * @param int $maxDenominator
	 * @return array
	 */
	protected static function buildUniqueFractions(int $minDenominator, int $maxDenominator): array {
		$fractions = [];

		//Generate all possible fractions
		for ($denominator = $minDenominator; $denominator <= $maxDenominator; $denominator++) {
			for ($numerator = 1; $numerator <= $denominator; $numerator++) {
				$gcd = static::gcd($numerator, $denominator);

				$simplifiedNumerator = $numerator / $gcd;
				$simplifiedDenominator = $denominator / $gcd;

				$fraction = $simplifiedNumerator . '/' . $simplifiedDenominator;

				// Avoid adding duplicates
				if (!array_key_exists($fraction, $fractions)) {
					/**
					 * @see \Awyiss\Utility\Content\AwyissColumn::__construct()
					 */
					$fractions[ $fraction ] = new static::$columnClassName(
						numerator: $simplifiedNumerator,
						denominator: $simplifiedDenominator,
					);
				}
			}
		}

		static::sortFractions($fractions);

		return $fractions;
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
		// Continue the loop until $secondNumber is zero.
		while ($secondNumber != 0) {
			$temp = $secondNumber;
			$secondNumber = $firstNumber % $secondNumber;
			$firstNumber = $temp;
		}


		//When $secondNumber is zero, $firstNumber contains the GCD of the original two numbers.
		return $firstNumber;
	}


	/**
	 * Sort fractions by their numerical value, with 1/1 coming first.
	 *
	 * @param array $fractions
	 * @return void
	 */
	protected static function sortFractions(array &$fractions): void {
		uasort($fractions, function (ColumnInterface $a, ColumnInterface $b) {
			// Check if either fraction is [1, 1] and adjust ordering
			if ($a->getNumerator() / $a->getDenominator() === 1) {
				return -1; // $a is [1, 1], so it should come before $b
			}
			if ($b->getNumerator() / $b->getDenominator() === 1) {
				return 1; // $b is [1, 1], so it should come after $a
			}

			return $a->getNumerator() / $a->getDenominator() <=> $b->getNumerator() / $b->getDenominator();
		});
	}
}
