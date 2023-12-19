<?php declare(strict_types=1);


namespace Awyiss\Twig\Extension;


use Twig\Extension\AbstractExtension;


class AwyissExtension extends AbstractExtension {
	public function getTests (): array {
		return [
			new \Twig\TwigTest('string', function ($ax_value) {
				return is_string($ax_value);
			}),
			new \Twig\TwigTest('array', function ($ax_value) {
				return is_array($ax_value);
			}),
		];
	}


	public function getFunctions (): array {
		return [
			new \Twig\TwigFunction('staticCall', function(string $ax_class, string $as_method, ...$aa_args) {
				if (class_exists($ax_class) && method_exists($ax_class, $as_method)) {
					return call_user_func_array([$ax_class, $as_method], $aa_args);
				}

				return NULL;
			}),

			new \Twig\TwigFunction('combine', function($aa_keys, $aa_values) {
				return array_combine($aa_keys, $aa_values);
			}),

			new \Twig\TwigFunction('naturalSort', function(array $aa_data, int|string $as_key = NULL) {
				uasort($aa_data, function($a, $b) use ($as_key) {
					if (!empty($as_key)) {
						return strnatcmp($a[ $as_key ], $b[ $as_key ]);
					}

					return strnatcmp($a, $b);
				});

				return $aa_data;
			}),
		];
	}
}