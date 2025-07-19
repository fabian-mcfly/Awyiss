<?php declare(strict_types=1);


namespace Awyiss\Utility\Content;


/**
 * Class BootstrapColumnSystem
 */
class BootstrapColumnSystem extends AbstractColumnSystem {
	/**
	 * @var string The class name of the column
	 */
	protected static string $columnClassName = BootstrapColumn::class;
	/**
	 * @var int The maximum denominator
	 */
	protected static int $maxDenominator = 12;
	/**
	 * @var array<array> The column widths, indexed by the max denominator fraction
	 */
	protected static array $columnWidths = [];


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

			/**
			 * @see \Awyiss\Utility\Content\BootstrapColumn::__construct()
			 */
			$la_fractions[ $ls_fraction ] = new static::$columnClassName(
				numerator: $li_i,
				denominator: $maxDenominator,
			);
		}

		static::sortFractions($la_fractions);


		return $la_fractions;
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
