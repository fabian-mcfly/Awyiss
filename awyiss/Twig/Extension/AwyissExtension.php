<?php declare(strict_types=1);


namespace Awyiss\Twig\Extension;


use Awyiss\Core\App;
use Awyiss\Core\LocalConfig;
use Awyiss\Middleware\LocaleMiddleware;
use Awyiss\Model\Entity\Page;
use Awyiss\Module\ModulesProvider;
use Awyiss\Twig\NodeVisitor\ExtendsNodeVisitor;
use Awyiss\Utility\Arrays;
use Awyiss\Utility\Inflector;
use Cake\Collection\CollectionInterface;
use Cake\Core\Configure;
use Cake\Http\Exception\RedirectException;
use Cake\Utility\Hash;
use Dom\HTMLDocument;
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
				$return = json_decode($json, true);

				// If the JSON is invalid, return null
				return !is_array($return) ? null : $return;
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
				function (array $context, string $name, array $options = []): ?string {
					if (empty($context['page']) || !$context['page'] instanceof Page) {
						throw new InvalidArgumentException('The "content" function requires a Page entity in the context.');
					}

					$options = Hash::merge(['viewVars' => $context], $options);

					try {
						return $context['_view']->cell('Frontend/Contents', [
							'contentArea' => $name,
							'page' => $context['page'],
							'view' => $context['_view'],
							'options' => $options,
						])->render() ?: null;
					}
					catch (RedirectException $ex) {
						// Redirects are handled by the middleware
						header('Location: ' . $ex->getMessage(), true, $ex->getCode());
						exit;
					}
				},
				['needs_context' => true, 'is_safe' => ['all']]
			),

			new TwigFunction('dump', function (): void {
				dump(...func_get_args());
			}),

			new TwigFunction(
				'form',
				function (array $context, string|int $identifier, array $options = []): ?string {
					if (empty($context['page']) || !$context['page'] instanceof Page) {
						throw new InvalidArgumentException('The "form" function requires a Page entity in the context.');
					}

					$options = Hash::merge(['viewVars' => $context, 'includeWrapper' => true], $options);

					try {
						return $context['_view']->cell('Frontend/Form', [
							'identifier' => $identifier,
							'page' => $context['page'],
							'view' => $context['_view'],
							'options' => $options,
						])->render() ?: null;
					}
					catch (RedirectException $ex) {
						// Redirects are handled by the middleware
						header('Location: ' . $ex->getMessage(), true, $ex->getCode());
						exit;
					}
				},
				['needs_context' => true, 'is_safe' => ['all']]
			),

			new TwigFunction('getClass', function (object $class): string {
				return get_class($class);
			}),

			new TwigFunction(
				'globalContent',
				function (array $context, string $name, array $options = []): ?string {
					$options = Hash::merge(['viewVars' => $context], $options);

					try {
						return $context['_view']->cell('Frontend/GlobalContents', [
							'identifier' => $name,
							'view' => $context['_view'],
							'options' => $options,
						])->render() ?: null;
					}
					catch (RedirectException $ex) {
						// Redirects are handled by the middleware
						header('Location: ' . $ex->getMessage(), true, $ex->getCode());
						exit;
					}
				},
				['needs_context' => true, 'is_safe' => ['all']]
			),

			new TwigFunction('__', '__'),
			new TwigFunction('__f', '__f'),
			new TwigFunction('__n', '__n'),
			new TwigFunction('__d', '__d'),
			new TwigFunction('__dn', '__dn'),
			new TwigFunction('__x', '__x'),
			new TwigFunction('__xn', '__xn'),
			new TwigFunction('__dx', '__dx'),
			new TwigFunction('__dxn', '__dxn'),
			new TwigFunction('__df', '__df'),
			new TwigFunction('__dfx', '__dfx'),
			new TwigFunction('__l', '__l'),
			new TwigFunction('__ld', '__ld'),

			new TwigFunction(
				'hashPrinter',
				function (CollectionInterface|array $data, string $value, string $key, string $spacer = '- ', int $levelOffset = 0): array {
					$data = is_array($data) ? $data : $data->toList();

					$return = [];
					foreach ($data as $dataKey => $dataItem) {
						$return[ $key === 'key' ? $dataKey : $dataItem[ $key ] ] = str_repeat($spacer, $dataItem['level'] - $levelOffset) . $dataItem[ $value ];
					}


					return $return;
				}
			),

			new TwigFunction('localConfig', LocalConfig::read(...)),

			new TwigFunction(
				'menu',
				function (array $context, string $name, array $options = []): ?string {
					if (empty($context['languageShortcode']) || strlen($context['languageShortcode']) !== 2) {
						throw new InvalidArgumentException('The "menu" function requires languageShortcode string in the context.');
					}

					$options = Hash::merge(['viewVars' => $context], $options);

					try {
						return $context['_view']->cell('Frontend/Menu', [
							'identifier' => $name,
							'languageShortcode' => $context['languageShortcode'],
							'view' => $context['_view'],
							'options' => $options,
						])->render() ?: null;
					}
					catch (RedirectException $ex) {
						// Redirects are handled by the middleware
						header('Location: ' . $ex->getMessage(), true, $ex->getCode());
						exit;
					}
				},
				['needs_context' => true, 'is_safe' => ['all']]
			),

			new TwigFunction('module', $this->moduleFunction(...), ['needs_context' => true, 'is_safe' => ['all']]),

			new TwigFunction('naturalSort', function (array $data, int|string|null $key = null): array {
				Arrays::naturalSort($data, $key);

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
				'survey',
				function (array $context, string|int $identifier, array $options = []): ?string {
					if (!($context['page'] ?? null) instanceof Page) {
						throw new InvalidArgumentException('The "content" function requires a Page entity in the context.');
					}

					$options = Hash::merge(['viewVars' => $context], $options);

					try {
						return $context['_view']->cell('Frontend/Survey', [
							'identifier' => $identifier,
							'page' => $context['page'],
							'view' => $context['_view'],
							'options' => $options,
						])->render() ?: null;
					}
					catch (RedirectException $ex) {
						// Redirects are handled by the middleware
						header('Location: ' . $ex->getMessage(), true, $ex->getCode());
						exit;
					}
				},
				['needs_context' => true, 'is_safe' => ['all']]
			),

			new TwigFunction(
				'wordCount',
				function (array $context, string $contents): int {
					$dom = HTMLDocument::createFromString($contents, LIBXML_NOERROR, 'UTF-8');

					$html = '';

					$body = $dom->querySelector('body');

					// Remove unwanted nodes
					$unwantedNodeNames = [
						'.Module-Breadcrumbs', 'footer', 'header', 'nav', 'template', 'style', 'script', 'nav', 'form', 'noscript',
						'link', 'meta', 'picture', 'video', 'audio', 'img', 'input', 'select', 'textarea', 'button', 'canvas', 'iframe', 'svg',
					];
					foreach ($unwantedNodeNames as $unwantedNodeName) {
						$unwantedNodes = $body->querySelectorAll($unwantedNodeName);
						foreach ($unwantedNodes as $unwantedNode) {
							$unwantedNode->parentNode->removeChild($unwantedNode);
						}
					}

					while ($body->firstChild) {
						$html .= $dom->saveHTML($body->firstChild);
						$body->removeChild($body->firstChild);
					}

					$cleanText = str_replace(['<br>', '<br/>', '<br />'], ' ', $html);
					$cleanText = strip_tags($cleanText);
					$cleanText = str_replace('&nbsp;', ' ', $cleanText);
					$cleanText = preg_replace('/([\s\n\r\t]|\xC2\xA0|\xE2\x80\xAF)/', ' ', $cleanText);
					$cleanText = preg_replace('/[ ]+/', ' ', $cleanText);

					$words = array_filter(explode(' ', $cleanText));
					return count($words);
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

			new TwigTest('pageRole', function ($value): bool {
				/** @var class-string<\Awyiss\Model\Enum\PageRoleEnumInterface> $pageRoleEnum */
				$pageRoleEnum = App::className('PageRole', 'Model/Enum');

				return $pageRoleEnum::tryFromName($value) !== null;
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
		static $inliner;

		if (!isset($inliner)) {
			$inliner = new CssToInlineStyles();
		}

		return $inliner->convert($body, implode("\n", $cssFiles));
	}


	/**
	 * @param array|null $attributes
	 * @return string
	 */
	public function htmlDataAttributes(?array $attributes): string {
		if (!$attributes) {
			return '';
		}

		$htmlParts = [];
		foreach ($attributes as $key => $value) {
			$htmlParts[] = sprintf('data-%s="%s"', $key, htmlspecialchars($value, ENT_QUOTES, 'UTF-8'));
		}

		return implode(' ', $htmlParts);
	}


	/**
	 * @param array $context
	 * @param string $name
	 * @param array $options
	 * @return string
	 * @throws \Exception
	 */
	public function moduleFunction(array $context, string $name, array $options = []): string {
		static $modules;

		if (empty($context['_view'])) {
			throw new InvalidArgumentException('The "module" function requires a View object in the context.');
		}

		if (!isset($modules)) {
			$modules = ModulesProvider::getModuleFiles();
		}

		// Get the value of the data-identifier attribute
		$identifier = Inflector::variable($name);

		if (!isset($modules[ $identifier ])) {
			return '';
		}

		/** @var class-string<\Awyiss\Module\ModuleInterface> $moduleClass */
		$moduleClass = $modules[ $identifier ];

		$mediaRenderOptions = $context['mediaRenderOptions'] ?? null;
		if (!$mediaRenderOptions && !empty($context['designSettings'])) {
			$designVariables = $context['designSettings'];

			/** @var class-string<\Awyiss\Utility\Media\MediaRenderOptions> $className */
			$className = App::className('MediaRenderOptions', 'Utility/Media');

			$mediaRenderOptions = new $className(
				baseWidth: intval($designVariables['pageWidth'] ?? 1920),
				breakpoints: Configure::read('Awyiss.Media.Frontend.defaultBreakpoints'),
				singleColumnBreakpoint: intval($designVariables['singleColumnBreakpoint'] ?? 768),
			);
		}

		$entity = $options['entity'] ?? null;

		$language = $context['language'] ?? $context['currentLanguage'] ?? LocaleMiddleware::getLanguage();

		return $moduleClass::render($options, $context['_view'], $mediaRenderOptions, $entity, $language);
	}
}
