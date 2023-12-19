<?php declare(strict_types=1);


namespace Awyiss\Twig\Extension;


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
	 * @return TwigTest[]
	 */
	public function getTests (): array {
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
	 * @return TwigFunction[]
	 */
	public function getFunctions (): array {
		return [
			new TwigFunction('getClass', function($ax_class): string {
				return get_class($ax_class);
			}),

			new TwigFunction('combine', function($aa_keys, $aa_values): array {
				return array_combine($aa_keys, $aa_values);
			}),

			new TwigFunction('dump', function (): void {
				dump(...func_get_args());
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

			new TwigFunction('naturalSort', function(array $aa_data, int|string $as_key = NULL): array {
				uasort($aa_data, function($a, $b) use ($as_key) {
					if (!empty($as_key)) {
						return strnatcasecmp($a[ $as_key ], $b[ $as_key ]);
					}

					return strnatcasecmp($a, $b);
				});

				return $aa_data;
			}),

			new TwigFunction('staticCall', function(string $ax_class, string $as_method, ...$aa_args): mixed {
				if (class_exists($ax_class) && method_exists($ax_class, $as_method)) {
					return call_user_func_array([$ax_class, $as_method], $aa_args);
				}

				return NULL;
			}),
		];
	}
}
