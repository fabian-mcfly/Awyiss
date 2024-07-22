<?php declare(strict_types=1);


namespace Awyiss\Utility;


use Cake\Utility\Inflector as CakeInflector;


/**
 * Inflector
 *
 * @package Awyiss\Utility
 */
class Inflector extends CakeInflector {
	/**
	 * Converts strings delimited by '-', '\', '_' and ' ' to have each part capitalized,
	 * either keeping the delimiter or replacing it with a custom delimiter.
	 *
	 * If $delimiter is set to false, the delimiter will be removed.
	 *
	 * Example:
	 * ```
	 * Inflector::ucparts('foo-bar');
	 * // 'Foo-Bar'
	 *
	 * Inflector::ucparts('foo-bar', '_');
	 * // 'Foo_Bar'
	 *
	 * Inflector::ucparts('foo-bar', false);
	 * // 'FooBar'
	 * ```
	 *
	 * @param string $string
	 * @param string|bool $delimiter
	 * @return string
	 */
	public static function ucparts(string $string, string|bool $delimiter = true): string {
		$ls_string = ucwords(strtolower($string));

		foreach (['-', '\'', '_', ' '] as $ls_delimiter) {
			if (!str_contains($ls_string, $ls_delimiter)) {
				continue;
			}

			$ls_string = implode(
				$delimiter ? (is_string($delimiter) ? $delimiter : $ls_delimiter) : '',
				array_map('ucfirst', explode($ls_delimiter, $ls_string))
			);
		}

		return $ls_string;
	}
}
