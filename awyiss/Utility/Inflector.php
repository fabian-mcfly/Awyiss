<?php declare(strict_types=1);


namespace Awyiss\Utility;


use Cake\Utility\Inflector as CakeInflector;
use Cake\Utility\Text;


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
	 * @param string|false $delimiter
	 * @return string
	 */
	public static function ucparts(string $string, string|false $delimiter = '_'): string {
		$cacheKey = __FUNCTION__ . '__' . (is_bool($delimiter) ? (int)$delimiter : $delimiter);

		$result = static::_cache($cacheKey, $string);

		if ($result !== false) {
			return $result;
		}

		// Replace all inner uppercase characters that follow lowercase alphanumeric ones with itself, prepended with a space
		$result = preg_replace('/(?<=[a-z0-9])([A-Z])/', ' $1', $string);
		$result = ucwords(strtolower($result));
		$result = Text::slug($result, [
			'replacement' => '|',
		]);

		foreach (['|'] as $currentDelimiter) {
			if (!str_contains($result, $currentDelimiter)) {
				continue;
			}

			$result = implode(
				$delimiter ? (is_string($delimiter) ? $delimiter : $currentDelimiter) : '',
				array_map('ucfirst', explode($currentDelimiter, $result))
			);
		}

		static::_cache($cacheKey, $string, $result);

		return $result;
	}


	/**
	 * Prevents double underscores
	 *
	 * @inheritDoc
	 */
	public static function underscore(string $string): string {
		$string = parent::underscore($string);

		// Prevent double underscores
		return preg_replace('/_+/', '_', $string);
	}
}
