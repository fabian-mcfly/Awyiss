<?php declare(strict_types=1);


namespace Awyiss\Utility\Content;


use RuntimeException;


/**
 * Class BootstrapColumnSystem
 */
class BootstrapColumnSystem implements ColumnSystemInterface {
	/**
	 * @var string The class name of the column
	 */
	protected static string $columnClassName = BootstrapColumn::class;
	/**
	 * @var int The maximum denominator
	 */
	protected static int $maxDenominator = 12;
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
			static::$columnWidths = static::buildFractions(static::$maxDenominator);
		}


		return static::$columnWidths;
	}


	/**
	 * @inheritDoc
	 */
	public static function getColumnIndents(): array {
		$la_blocklistedFractions = [
			sprintf('%s/%s', static::$maxDenominator, static::$maxDenominator),
		];

		$la_indents = array_diff_key(static::getColumnWidths(), array_flip($la_blocklistedFractions));

		foreach ($la_indents as $ls_key => $lo_column) {
			$lo_column = clone $lo_column;
			$lo_column->setCssClassPrefix('offset');

			$la_indents[ $ls_key ] = $lo_column;
		}


		return $la_indents;
	}


	/**
	 * @param int $maxDenominator
	 * @return array
	 */
	protected static function buildFractions(int $maxDenominator): array {
		$la_fractions = [];

		for ($li_i = 1; $li_i <= $maxDenominator; $li_i++) {
			$ls_fraction = $li_i . '/' . $maxDenominator;

			$la_fractions[ $ls_fraction ] = new static::$columnClassName(
				fraction: $ls_fraction,
				numerator: $li_i,
				denominator: $maxDenominator,
			);
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
	 * @inheritDoc
	 */
	public static function getScssFilePaths(): array {
		return [];
	}


	/**
	 * @inheritDoc
	 */
	public static function getName(): string {
		return 'Bootstrap';
	}
}
