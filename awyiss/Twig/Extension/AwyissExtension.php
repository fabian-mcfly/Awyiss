<?php declare(strict_types=1);


namespace Awyiss\Twig\Extension;


use Awyiss\Utility\Inflector;
use Cake\Collection\CollectionInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;
use Twig\TwigTest;


/**
 * Awyiss-specific Twig Functions
 */
class AwyissExtension extends AbstractExtension {
	/**
	 * Returns a list of filters to add to the existing list.
	 *
	 * @return array<\Twig\TwigFilter>
	 */
	public function getFilters(): array {
		return [
			new TwigFilter('json_decode', function (string $json): ?array {
				$la_return = json_decode($json, true);


				// If the JSON is invalid, return null
				return !is_array($la_return) ? null : $la_return;
			}),

			new TwigFilter('ucparts', function (string $string, string|bool $delimiter = true): string {
				return Inflector::ucparts($string, $delimiter);
			}),

			new TwigFilter('prefixNumericClass', function (string $string): string {
				if (preg_match('/^\d/', $string)) {
					return 'Page' . $string;
				}

				return $string;
			}),
		];
	}


	/**
	 * Returns a list of functions to add to the existing list.
	 *
	 * @return array<\Twig\TwigFunction>
	 */
	public function getFunctions(): array {
		return [
			new TwigFunction('combine', function (array $keys, array $values): array {
				return array_combine($keys, $values);
			}),

			new TwigFunction('dump', function (): void {
				dump(...func_get_args());
			}),

			new TwigFunction('getClass', function (object $class): string {
				return get_class($class);
			}),

			new TwigFunction('__', '__'),
			new TwigFunction('__n', '__n'),
			new TwigFunction('__d', '__d'),
			new TwigFunction('__dn', '__dn'),
			new TwigFunction('__x', '__x'),
			new TwigFunction('__xn', '__xn'),
			new TwigFunction('__dx', '__dx'),
			new TwigFunction('__dxn', '__dxn'),
			new TwigFunction('__df', '__df'),
			new TwigFunction('__dfx', '__dfx'),

			new TwigFunction(
				'hashPrinter',
				function (CollectionInterface|array $data, string $value, string $key, string $spacer = '- ', int $levelOffset = 0): array {
					$la_data = is_array($data) ? $data : $data->toList();

					$la_return = [];
					foreach ($la_data as $lx_key => $lx_item) {
						$la_return[ $key === 'key' ? $lx_key : $lx_item[ $key ] ] = str_repeat($spacer, $lx_item['level'] - $levelOffset) . $lx_item[ $value ];
					}


					return $la_return;
				}
			),

			new TwigFunction('naturalSort', function (array $data, int|string|null $key = null): array {
				/** @noinspection PhpVariableNamingConventionInspection */
				uasort($data, function ($a, $b) use ($key) {
					if (!empty($key)) {
						return strnatcasecmp($a[ $key ], $b[ $key ]);
					}


					return strnatcasecmp($a, $b);
				});


				return $data;
			}),

			new TwigFunction('staticCall', function (string $class, string $method, ...$args): mixed {
				if (class_exists($class) && method_exists($class, $method)) {
					return call_user_func_array([$class, $method], $args);
				}


				return null;
			}),
		];
	}


	/**
	 * Returns a list of tests to add to the existing list.
	 *
	 * @return array<\Twig\TwigTest>
	 */
	public function getTests(): array {
		return [
			new TwigTest('array', function ($value): bool {
				return is_array($value);
			}),

			new TwigTest('instanceOf', function ($object, $class): bool {
				return $object instanceof $class;
			}),

			new TwigTest('numeric', function ($value): bool {
				return is_numeric($value);
			}),

			new TwigTest('string', function ($value): bool {
				return is_string($value);
			}),
		];
	}
}
