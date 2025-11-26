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
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public static function naturalSort(array &$data, string|int|null $field = null, bool $orderByKey = false): void {
		$ls_locale = I18n::getLocale();

		if (!isset(static::$collators[ $ls_locale ])) {
			$lo_collator = static::$collators[ $ls_locale ] = new Collator($ls_locale);

			/**
			 * Ignore case but not accents
			 * This will allow sorting 'Äpfel' after 'Apfel', not after 'Zitronen'
			 */
			$lo_collator->setStrength(Collator::SECONDARY);
			// Enable natural sorting for numbers
			$lo_collator->setAttribute(Collator::NUMERIC_COLLATION, Collator::ON);
		}

		$lo_collator ??= static::$collators[ $ls_locale ];

		if ($orderByKey) {
			uksort($data, function (mixed $a, mixed $b) use ($lo_collator): int {
				return $lo_collator->compare($a, $b);
			});

			return;
		}

		uasort($data, function (mixed $a, mixed $b) use ($field, $lo_collator): int {
			if ($field !== null) {
				$aValue = is_object($a) ? $a->{ $field } : $a[ $field ];
				$bValue = is_object($b) ? $b->{ $field } : $b[ $field ];

				return $lo_collator->compare($aValue, $bValue);
			}

			return $lo_collator->compare($a, $b);
		});
	}
}
