<?php declare(strict_types=1);


namespace Awyiss\Utility;


use Cake\I18n\I18n;
use Collator;


/**
 * Inflector
 *
 * @package Awyiss\Utility
 */
class Arrays {
	/**
	 * @var array<string, \Collator> $collators
	 */
	protected static array $collators = [];


	/**
	 * @param array $data
	 * @param string|int|null $field
	 * @param bool $orderByKey
	 * @param int $direction
	 * @return void
	 */
	public static function naturalSort(array &$data, string|int|null $field = null, bool $orderByKey = false, int $direction = SORT_ASC): void {
		$locale = I18n::getLocale();

		if (!isset(static::$collators[ $locale ])) {
			$collator = static::$collators[ $locale ] = new Collator($locale);

			/**
			 * Ignore case but not accents
			 * This will allow sorting 'Äpfel' after 'Apfel', not after 'Zitronen'
			 */
			/** @noinspection PhpExpectedValuesShouldBeUsedInspection */
			$collator->setStrength(Collator::SECONDARY);
			// Enable natural sorting for numbers
			$collator->setAttribute(Collator::NUMERIC_COLLATION, Collator::ON);
		}

		$collator ??= static::$collators[ $locale ];

		if ($orderByKey) {
			uksort($data, function (mixed $a, mixed $b) use ($collator, $direction): int {
				if ($direction === SORT_DESC) {
					return $collator->compare($b, $a);
				}

				return $collator->compare($a, $b);
			});

			return;
		}

		uasort($data, function (mixed $a, mixed $b) use ($field, $collator, $direction): int {
			if ($field !== null) {
				$aValue = is_object($a) ? $a->{ $field } : $a[ $field ];
				$bValue = is_object($b) ? $b->{ $field } : $b[ $field ];

				if ($direction === SORT_DESC) {
					return $collator->compare($bValue, $aValue);
				}

				return $collator->compare($aValue, $bValue);
			}

			if ($direction === SORT_DESC) {
				return $collator->compare($b, $a);
			}

			return $collator->compare($a, $b);
		});
	}
}
