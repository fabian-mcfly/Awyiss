<?php declare(strict_types=1);


namespace Awyiss\Twig\Extension;


use Awyiss\Core\LocalConfig;
use Cake\Collection\CollectionInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;
use Twig\TwigTest;


/**
 * Awyiss-specific Twig Functions
 */
class AwyissExtension extends AbstractExtension {
	/**
	 * Returns a list of tests to add to the existing list.
	 *
	 * @return array<TwigTest>
	 */
	public function getTests(): array {
		return [
			new TwigTest('array', function ($ax_value): bool {
				return is_array($ax_value);
			}),

			new TwigTest('instanceOf', function ($ao_object, $ax_class): bool {
				return $ao_object instanceof $ax_class;
			}),

			new TwigTest('numeric', function ($ax_value): bool {
				return is_numeric($ax_value);
			}),

			new TwigTest('string', function ($ax_value): bool {
				return is_string($ax_value);
			}),
		];
	}


	/**
	 * Returns a list of functions to add to the existing list.
	 *
	 * @return array<TwigFunction>
	 */
	public function getFunctions(): array {
		return [
			new TwigFunction('combine', function (array $aa_keys, array $aa_values): array {
				return array_combine($aa_keys, $aa_values);
			}),

			new TwigFunction('dump', function (): void {
				dump(...func_get_args());
			}),

			new TwigFunction('getClass', function (object $ao_class): string {
				return get_class($ao_class);
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

			new TwigFunction('hashPrinter', function (CollectionInterface|array $ax_data = null, string $as_value, string $as_key, string $as_spacer = '- '): mixed {
				$la_data = is_array($ax_data) ? $ax_data : $ax_data->toList();

				$la_return = [];
				foreach ($la_data AS $lx_item) {
					$la_return[ $lx_item[ $as_key ] ] = str_repeat($as_spacer, $lx_item['level']) . $lx_item[ $as_value ];
				}

				return $la_return;
			}),

			new TwigFunction('localConfig', function (string|array|null $ax_path = null, mixed $ax_default = null): mixed {
				return LocalConfig::read($ax_path, $ax_default);
			}),

			new TwigFunction('naturalSort', function (array $aa_data, int|string|null $as_key = null): array {
				uasort($aa_data, function ($a, $b) use ($as_key) {
					if (!empty($as_key)) {
						return strnatcasecmp($a[ $as_key ], $b[ $as_key ]);
					}


					return strnatcasecmp($a, $b);
				});


				return $aa_data;
			}),

			new TwigFunction('staticCall', function (string $as_class, string $as_method, ...$aa_args): mixed {
				if (class_exists($as_class) && method_exists($as_class, $as_method)) {
					return call_user_func_array([$as_class, $as_method], $aa_args);
				}


				return null;
			}),
		];
	}
}
