<?php declare(strict_types=1);


namespace Awyiss\Utility\Content\ColumnSystem;


/**
 * Class BootstrapColumnSystem
 */
class BootstrapColumnSystem extends AbstractColumnSystem {
	/**
	 * @var string The class name of the column
	 */
	protected static string $columnClassName = BootstrapColumn::class;
	/**
	 * @var array<array> The column widths, indexed by the max denominator fraction
	 */
	protected static array $columnWidths = [];
	/**
	 * @var int The maximum denominator
	 */
	protected static int $maxDenominator = 12;


	/**
	 * @inheritDoc
	 */
	public static function getColumnWidths(): array {
		if (!isset(static::$columnWidths[ static::$maxDenominator ])) {
			static::$columnWidths[ static::$maxDenominator ] = static::buildFractions(static::$maxDenominator);
		}


		return static::$columnWidths[ static::$maxDenominator ];
	}


	/**
	 * @inheritDoc
	 */
	public static function getColumnIndents(): array {
		$blocklistedFractions = [
			sprintf('%s/%s', static::$maxDenominator, static::$maxDenominator),
		];

		$indents = array_diff_key(static::getColumnWidths(), array_flip($blocklistedFractions));

		foreach ($indents as $key => $column) {
			$column = clone $column;
			$column->setCssClassPrefix('offset-md');

			$indents[ $key ] = $column;
		}


		return $indents;
	}


	/**
	 * @param int $maxDenominator
	 * @return array
	 */
	protected static function buildFractions(int $maxDenominator): array {
		$fractions = [];

		for ($i = 1; $i <= $maxDenominator; $i++) {
			$fraction = $i . '/' . $maxDenominator;

			/**
			 * @see \Awyiss\Utility\Content\ColumnSystem\BootstrapColumn::__construct()
			 */
			$fractions[ $fraction ] = new static::$columnClassName(
				numerator: $i,
				denominator: $maxDenominator,
			);
		}

		static::sortFractions($fractions);


		return $fractions;
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
