<?php declare(strict_types=1);


namespace Awyiss\Utility\Contents;


/**
 * Interface ColumnSystemInterface
 */
interface ColumnSystemInterface {
	/**
	 * Returns the column widths
	 *
	 * @return array<string, \Awyiss\Utility\Contents\ColumnInterface>
	 */
	public static function getColumnWidths(): array;


	/**
	 * Returns the column indents
	 *
	 * @return array<string, \Awyiss\Utility\Contents\ColumnInterface>
	 */
	public static function getColumnIndents(): array;


	/**
	 * @return string The name of the column system
	 */
	public static function getName(): string;


	/**
	 * @return int
	 */
	public static function getMaxDenominator(): int;


	/**
	 * @param int $maxDenominator
	 * @return void
	 */
	public static function setMaxDenominator(int $maxDenominator): void;
}
