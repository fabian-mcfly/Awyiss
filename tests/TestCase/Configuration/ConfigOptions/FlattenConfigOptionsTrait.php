<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Configuration\ConfigOptions;


use Awyiss\Configuration\ConfigOption;
use Awyiss\Configuration\ConfigOptionCollection;
use Awyiss\Configuration\ConfigOptionsInterface;


/**
 * Trait FlattenConfigOptionsTrait
 */
trait FlattenConfigOptionsTrait {
	/**
	 * @param \Awyiss\Configuration\ConfigOptionsInterface|array $configOptions
	 * @param array $currentKeys
	 * @return array<\Awyiss\Configuration\ConfigOption>
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	protected function flattenConfigOptions(ConfigOptionsInterface|array $configOptions, array $currentKeys = []): array {
		$result = [];

		foreach ($configOptions as $key => $value) {
			$newKeys = array_merge($currentKeys, [$key]);

			if ($value instanceof ConfigOption) {
				$path = implode('.', $newKeys);
				$result[ $path ] = $value;
			}
			elseif (is_array($value)) {
				$result = array_merge(
					$result,
					$this->flattenConfigOptions($value, $newKeys)
				);
			}
			elseif ($value instanceof ConfigOptionCollection) {
				$result = array_merge(
					$result,
					$this->flattenConfigOptions($value->toArray(), $newKeys)
				);
			}
		}

		return $result;
	}
}
