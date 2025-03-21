<?php declare(strict_types=1);


namespace Awyiss\Twig\Extension;


use Awyiss\Middleware\LocaleMiddleware;
use Awyiss\Model\Entity\Page;
use Awyiss\Module\ModulesProvider;
use Awyiss\Twig\Extension\NodeVisitor\ExtendsNodeVisitor;
use Awyiss\Utility\Inflector;
use Awyiss\Utility\Media\MediaRenderOptions;
use Cake\Collection\CollectionInterface;
use Cake\Core\Configure;
use Cake\Utility\Hash;
use InvalidArgumentException;
use TijsVerkoyen\CssToInlineStyles\CssToInlineStyles;
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
			new TwigFilter('inline_css', $this->inlineCss(...), ['is_safe' => ['all']]),

			new TwigFilter('data_attr', $this->htmlDataAttributes(...)),

			new TwigFilter('json_decode', function (string $json): ?array {
				$la_return = json_decode($json, true);


				// If the JSON is invalid, return null
				return !is_array($la_return) ? null : $la_return;
			}),

			new TwigFilter('prefixNumericClass', function (string $string): string {
				if (preg_match('/^\d/', $string)) {
					return 'Page' . $string;
				}

				return $string;
			}),

			new TwigFilter('repeat', function (string $string, int $times): string {
				return str_repeat($string, $times);
			}),

			new TwigFilter('ucparts', function (string $string, string|bool $delimiter = true): string {
				return Inflector::ucparts($string, $delimiter);
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

			new TwigFunction(
				'content',
				function (array $context, string $name, array $options = []) {
					if (empty($context['page']) || !$context['page'] instanceof Page) {
						throw new InvalidArgumentException('The "content" function requires a Page entity in the context.');
					}

					$la_options = ['viewVars' => $context];
					$la_options = Hash::merge($la_options, $options);

					return $context['_view']->cell('Frontend/Contents', [$name, $context['page'], $la_options]);
				},
				['needs_context' => true, 'is_safe' => ['all']]
			),

			new TwigFunction('dump', function (): void {
				dump(...func_get_args());
			}),

			new TwigFunction(
				'form',
				function (array $context, string|int $identifier, array $options = []) {
					if (empty($context['page']) || !$context['page'] instanceof Page) {
						throw new InvalidArgumentException('The "form" function requires a Page entity in the context.');
					}

					$la_options = ['viewVars' => $context];
					$la_options = Hash::merge($la_options, $options);

					return $context['_view']->cell('Frontend/Form', [$identifier, $context['page'], $la_options]);
				},
				['needs_context' => true, 'is_safe' => ['all']]
			),

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

			new TwigFunction(
				'menu',
				function (array $context, string $name, array $options = []) {
					if (empty($context['languageShortcode']) || strlen($context['languageShortcode']) !== 2) {
						throw new InvalidArgumentException('The "menu" function requires languageShortcode string in the context.');
					}

					$la_options = ['viewVars' => $context];
					$la_options = Hash::merge($la_options, $options);

					return $context['_view']->cell('Frontend/Menu', [$name, $context['languageShortcode'], $la_options]);
				},
				['needs_context' => true, 'is_safe' => ['all']]
			),

			new TwigFunction('module', $this->moduleFunction(...), ['needs_context' => true, 'is_safe' => ['all']]),

			new TwigFunction('naturalSort', function (array $data, int|string|null $key = null): array {
				$lx_key = $key;
				/** @noinspection PhpVariableNamingConventionInspection */
				uasort($data, function ($a, $b) use ($lx_key) {
					if (!empty($lx_key)) {
						return strnatcasecmp($a[ $lx_key ], $b[ $lx_key ]);
					}


					return strnatcasecmp($a, $b);
				});


				return $data;
			}),

			new TwigFunction('staticCall', function (string|object $class, string $method, ...$args): mixed {
				if (
					(
						(is_string($class) && class_exists($class)) ||
						(is_object($class))
					) &&
					method_exists($class, $method)
				) {
					return call_user_func_array([$class, $method], $args);
				}

				return null;
			}),

			new TwigFunction(
				'widget',
				function (array $context, string $name, array $options = []) {
					$la_options = ['viewVars' => $context];
					$la_options = Hash::merge($la_options, $options);

					return $context['_view']->cell('Frontend/Widgets', [$name, $la_options]);
				},
				['needs_context' => true, 'is_safe' => ['all']]
			),
		];
	}


	/**
	 * @inheritDoc
	 */
	public function getNodeVisitors(): array {
		return [
			new ExtendsNodeVisitor(),
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

			new TwigTest('file', function (string $value): bool {
				if (str_contains($value, '..')) {
					return false;
				}

				return file_exists(WWW_ROOT . $value) && is_file(WWW_ROOT . $value);
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


	/**
	 * @param string $body
	 * @param string ...$cssFiles
	 * @return string
	 */
	public static function inlineCss(string $body, string ...$cssFiles): string {
		static $lo_inliner;

		if (!isset($lo_inliner)) {
			$lo_inliner = new CssToInlineStyles();
		}

		return $lo_inliner->convert($body, implode("\n", $cssFiles));
	}


	/**
	 * @param array|null $attributes
	 * @return string
	 */
	public function htmlDataAttributes(?array $attributes): string {
		if (!$attributes) {
			return '';
		}

		$la_htmlParts = [];
		foreach ($attributes as $lx_key => $lx_value) {
			$la_htmlParts[] = sprintf('data-%s="%s"', $lx_key, htmlspecialchars($lx_value, ENT_QUOTES, 'UTF-8'));
		}

		return implode(' ', $la_htmlParts);
	}


	/**
	 * @param array $context
	 * @param string $name
	 * @param array $options
	 * @return string
	 * @throws \ReflectionException
	 * @throws \Exception
	 */
	public function moduleFunction(array $context, string $name, array $options = []): string {
		static $la_modules;

		if (empty($context['_view'])) {
			throw new InvalidArgumentException('The "module" function requires a View object in the context.');
		}

		if (!isset($la_modules)) {
			$la_modules = ModulesProvider::getModuleFiles();
		}

		// Get the value of the data-identifier attribute
		$ls_identifier = Inflector::variable($name);

		if (!isset($la_modules[ $ls_identifier ])) {
			return '';
		}

		/** @var class-string<\Awyiss\Module\ModuleInterface> $ls_moduleClass */
		$ls_moduleClass = $la_modules[ $ls_identifier ];

		$lo_mediaRenderOptions = $context['mediaRenderOptions'] ?? null;
		if (!$lo_mediaRenderOptions && !empty($context['designSettings'])) {
			$la_designVariables = $context['designSettings'];

			$lo_mediaRenderOptions = new MediaRenderOptions(
				baseWidth: intval($la_designVariables['pageWidth'] ?? 1920),
				breakpoints: Configure::read('Awyiss.Media.Frontend.defaultBreakpoints'),
				singleColumnBreakpoint: intval($la_designVariables['singleColumnBreakpoint'] ?? 768),
			);
		}

		$lo_entity = $options['entity'] ?? null;

		$lo_language = $context['language'] ?? $context['currentLanguage'] ?? LocaleMiddleware::getLanguage();

		return $ls_moduleClass::render($options, $context['_view'], $lo_mediaRenderOptions, $lo_entity, $lo_language);
	}
}
