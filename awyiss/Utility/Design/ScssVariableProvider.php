<?php
/**
 * @noinspection PhpInternalEntityUsedInspection
 */


declare(strict_types=1); // phpcs:ignore


namespace Awyiss\Utility\Design;


use Cake\Core\InstanceConfigTrait;
use Cake\Utility\Hash;
use InvalidArgumentException;
use ScssPhp\ScssPhp\Ast\Sass\Expression;
use ScssPhp\ScssPhp\Ast\Sass\Expression\BooleanExpression;
use ScssPhp\ScssPhp\Ast\Sass\Expression\ColorExpression;
use ScssPhp\ScssPhp\Ast\Sass\Expression\FunctionExpression;
use ScssPhp\ScssPhp\Ast\Sass\Expression\ListExpression;
use ScssPhp\ScssPhp\Ast\Sass\Expression\NumberExpression;
use ScssPhp\ScssPhp\Ast\Sass\Expression\StringExpression;
use ScssPhp\ScssPhp\Ast\Sass\Expression\VariableExpression;
use ScssPhp\ScssPhp\Ast\Sass\Statement\Stylesheet;
use ScssPhp\ScssPhp\Ast\Sass\Statement\VariableDeclaration;
use ScssPhp\ScssPhp\Exception\SassFormatException;


/**
 * SCSS variable provider
 */
class ScssVariableProvider {
	use InstanceConfigTrait;


	/**
	 * @var array $_defaultConfig Default configuration
	 */
	protected array $_defaultConfig = []; // phpcs:ignore
	/**
	 * The SCSS files to be read and parsed for variables.
	 *
	 * @var array $scssFiles
	 */
	protected array $scssFiles = [];


	/**
	 * @param array $config The design configuration
	 * @param array $scssFiles The SCSS files to be read and parsed for variables
	 */
	public function __construct(array $config, array $scssFiles = []) {
		if (isset($config['scssFiles']) && !$scssFiles) {
			$scssFiles = $config['scssFiles'];
		}

		unset($config['scssFiles']);
		$this->setConfig($config);

		$this->setScssFiles($scssFiles);
	}


	/**
	 * @param string $scssFile
	 * @return $this
	 */
	public function addScssFile(string $scssFile): static {
		if (!in_array($scssFile, $this->scssFiles)) {
			$this->scssFiles[] = $scssFile;
		}

		return $this;
	}


	/**
	 * @return array
	 */
	public function getScssFiles(): array {
		return $this->scssFiles;
	}


	/**
	 * @param array $scssFiles
	 * @return $this
	 */
	public function setScssFiles(array $scssFiles): static {
		$this->scssFiles = $scssFiles;

		$this->scssFiles = array_unique($this->scssFiles);

		return $this;
	}


	/**
	 * @return array
	 */
	public function getInternalVariables(): array {
		$vars = [];

		foreach ($this->scssFiles as $scssFile) {
			if (
				!str_ends_with($scssFile, '.scss')
				|| !file_exists($scssFile)
				|| !is_file($scssFile)
			) {
				throw new InvalidArgumentException(sprintf('The SCSS file `%s` does not exist.', $scssFile));
			}

			try {
				$stylesheet = Stylesheet::parseScss(file_get_contents($scssFile));
			}
			catch (SassFormatException) {
				continue;
			}

			foreach ($stylesheet->getChildren() as $var) {
				if (!is_a($var, VariableDeclaration::class)) {
					continue;
				}

				if (!$var->isGuarded()) {
					continue;
				}

				$vars[ $var->getName() ] = $var->getExpression();
			}
		}

		return $vars;
	}


	/**
	 * @return array
	 */
	public function getNormalizedInternalVariables(): array {
		$internalVariables = $this->getInternalVariables();

		foreach ($internalVariables as $key => $value) {
			$isBlocklisted = $this->variableIsBlocklisted($key);

			if ($isBlocklisted) {
				unset($internalVariables[ $key ]);
				continue;
			}

			$options = $this->normalizeValue($value);

			if ($options === null) {
				unset($internalVariables[ $key ]);
				continue;
			}

			$options = $this->mergeOptions($key, $options);

			$internalVariables[ $key ] = $options;
		}

		return $internalVariables;
	}


	/**
	 * @param FunctionExpression $function
	 * @return string
	 */
	protected function normalizeFunctionCall(FunctionExpression $function): string {
		$functionName = $function->getName();
		$arguments = $function->getArguments()->getPositional();

		$arguments = array_map(function (Expression $argument) {
			$options = $this->normalizeValue($argument);

			if ($options === null) {
				return '';
			}

			$value = $options['value'];
			if ($options['unit']) {
				$value .= $options['unit'];
			}

			if ($options['quotes']) {
				$value = $options['quotes'] . $value . $options['quotes'];
			}

			return $value;
		}, $arguments);

		return sprintf('%s(%s)', $functionName, implode(', ', $arguments));
	}


	/**
	 * Normalizes the value of a variable and returns the options.
	 *
	 * @param mixed $value
	 * @return array|null
	 */
	protected function normalizeValue(mixed $value): ?array {
		$options = [
			'unit' => null,
			'quotes' => null,
			'value' => null,
		];

		if (is_a($value, BooleanExpression::class)) {
			$options['value'] = $value->getValue();
		}
		elseif (is_a($value, ColorExpression::class)) {
			/** @noinspection PhpPossiblePolymorphicInvocationInspection */
			$options['value'] = $value
				->getValue()
				->getFormat()
				->getOriginal()
			;
		}
		elseif (is_a($value, NumberExpression::class)) {
			$options['value'] = $value->getValue();
			$options['unit'] = $value->getUnit();
		}
		elseif (is_a($value, StringExpression::class)) {
			$options['value'] = implode('', $value->getText()->getContents() ?? []);
			$options['quotes'] = $value->hasQuotes() ? '\'' : null;
		}
		elseif (is_a($value, ListExpression::class)) {
			$options['value'] = implode(
				$value->getSeparator()->getSeparator(),
				array_map(function (Expression $item) {
					$options = $this->normalizeValue($item);

					if ($options['quotes']) {
						$options['value'] = $options['quotes'] . $options['value'] . $options['quotes'];
					}

					return $options['value'];
				},
				$value->getContents())
			);
		}
		elseif (is_a($value, FunctionExpression::class)) {
			$options['value'] = $this->normalizeFunctionCall($value);
		}
		elseif (is_a($value, VariableExpression::class)) {
			$options['value'] = '$' . $value->getName();
		}
		elseif (is_object($value) && method_exists($value, 'getValue')) {
			$options['value'] = $value->getValue();
		}
		elseif (is_object($value) && method_exists($value, '__toString')) {
			$options['value'] = (string)$value;
		}
		elseif (is_scalar($value)) {
			$options['value'] = $value;
		}
		else {
			throw new InvalidArgumentException(sprintf('Unsupported value type: %s', gettype($value)));
		}

		return $options;
	}


	/**
	 * Merges the options of a variable with the default options.
	 * Default options are defined in the variable mapping, either by exact key or by matching pattern.
	 *
	 * @param string $key
	 * @param mixed $options
	 * @return array
	 */
	protected function mergeOptions(string $key, mixed $options): array {
		$units = $this->getConfig('units');
		$variableMapping = $this->getConfig('variableMapping');

		$mergedOptions = $options;
		if (isset($variableMapping[ $key ])) {
			$mergedOptions = Hash::merge($variableMapping[ $key ], $options);
		}
		else {
			$patternOptions = [];
			foreach ($variableMapping as $pattern => $mapping) {
				if (preg_match('/^' . $pattern . '$/', $key, $matches)) {
					$patternOptions = $mapping;
					break;
				}
			}

			if ($patternOptions) {
				$mergedOptions = Hash::merge($patternOptions, $options);
			}

			if (isset($mergedOptions['associatedVariables']) && !empty($matches)) {
				foreach ($mergedOptions['associatedVariables'] as &$associatedVariable) {
					$associatedVariable = preg_replace_callback('/\$(\d+)/', function (array $associationMatches) use ($matches) {
						return $matches[ $associationMatches[1] ];
					}, $associatedVariable);
				}
				unset($associatedVariable);
			}
		}

		if (!isset($mergedOptions['type'])) {
			$mergedOptions['type'] = ScssVariableType::String;
		}

		$mergedOptions['units'] ??= null;
		/** @noinspection PhpInArrayCanBeReplacedWithComparisonInspection */
		if (in_array($mergedOptions['type'], [ScssVariableType::Number])) {
			$mergedOptions['units'] = Hash::merge($units, $mergedOptions['units'] ?? []);
			$mergedOptions['units'] = array_filter($mergedOptions['units'], function ($unit, $key) use ($mergedOptions) {
				if (isset($mergedOptions['forcedUnit'])) {
					return $key === $mergedOptions['forcedUnit'];
				}

				return !empty($unit);
			}, ARRAY_FILTER_USE_BOTH);
		}

		return $mergedOptions;
	}


	/**
	 * @param string $key
	 * @return bool
	 */
	protected function variableIsBlocklisted(string $key): bool {
		$blocklistedVariables = $this->getConfig('blocklistedVariables', []);

		if (in_array($key, $blocklistedVariables)) {
			return true;
		}
		elseif (array_any($blocklistedVariables, fn($blocklistedVariable) => preg_match('/^' . $blocklistedVariable . '$/', $key))) {
			return true;
		}

		return false;
	}
}
