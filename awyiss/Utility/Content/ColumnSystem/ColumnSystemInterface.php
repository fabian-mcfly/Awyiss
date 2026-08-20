<?php declare(strict_types=1);


namespace Awyiss\Utility\Content\ColumnSystem;


/**
 * Interface ColumnSystemInterface
 */
interface ColumnSystemInterface {
	/**
	 * Returns the column widths
	 *
	 * @return array<string, \Awyiss\Utility\Content\ColumnSystem\ColumnInterface>
	 */
	public static function getColumnWidths(): array;


	/**
	 * Returns the column indents
	 *
	 * @return array<string, \Awyiss\Utility\Content\ColumnSystem\ColumnInterface>
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


	/**
	 * Returns the SCSS file paths for the column system
	 * The array should have two keys: 'pre' and 'post'
	 *
	 * Elements in the 'pre' array will be included before the regular SCSS files
	 * Elements in the 'post' array will be included after the regular SCSS files
	 *
	 * The paths can either be absolute or relative to the SCSS directory, with or without the .scss extension
	 *
	 * @return array{pre: string[], post: string[]} The SCSS file paths
	 */
	public static function getScssFilePaths(): array;
}
