<?php declare(strict_types=1);


namespace Awyiss\Utility\Design;


use Awyiss\Utility\Inflector;
use Cake\Core\InstanceConfigTrait;
use Cake\Utility\Hash;
use InvalidArgumentException;
use ScssPhp\ScssPhp\Ast\Sass\Expression;
use ScssPhp\ScssPhp\Ast\Sass\Expression\ColorExpression;
use ScssPhp\ScssPhp\Ast\Sass\Expression\FunctionExpression;
use ScssPhp\ScssPhp\Ast\Sass\Expression\ListExpression;
use ScssPhp\ScssPhp\Ast\Sass\Expression\NumberExpression;
use ScssPhp\ScssPhp\Ast\Sass\Expression\StringExpression;
use ScssPhp\ScssPhp\Ast\Sass\Expression\VariableExpression;
use ScssPhp\ScssPhp\Ast\Sass\Statement\Stylesheet;
use ScssPhp\ScssPhp\Ast\Sass\Statement\VariableDeclaration;


/**
 * SCSS variable provider
 */
class ScssVariableProvider {
	use InstanceConfigTrait;


	/**
	 * @var array $_defaultConfig Default configuration
	 */
	protected array $_defaultConfig = [];
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
			/** @noinspection PhpVariableNamingConventionInspection */
			$scssFiles = $config['scssFiles'];
		}

		/** @noinspection PhpVariableNamingConventionInspection */
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
		$la_vars = [];

		foreach ($this->scssFiles as $ls_scssFile) {
			if (
				!str_ends_with($ls_scssFile, '.scss') ||
				!file_exists($ls_scssFile) ||
				!is_file($ls_scssFile)
			) {
				throw new InvalidArgumentException(sprintf('The SCSS file `%s` does not exist.', $ls_scssFile));
			}

			$lo_stylesheet = Stylesheet::parseScss(file_get_contents($ls_scssFile));

			foreach ($lo_stylesheet->getChildren() as $lo_var) {
				if (!is_a($lo_var, VariableDeclaration::class)) {
					continue;
				}

				if (!$lo_var->isGuarded()) {
					continue;
				}

				$la_vars[ $lo_var->getName() ] = $lo_var->getExpression();
			}
		}

		return $la_vars;
	}


	/**
	 * @return array
	 */
	public function getNormalizedInternalVariables(): array {
		$la_internalVariables = $this->getInternalVariables();

		foreach ($la_internalVariables as $ls_key => $ls_value) {
			$lb_isBlocklisted = $this->variableIsBlocklisted($ls_key);

			if ($lb_isBlocklisted) {
				unset($la_internalVariables[ $ls_key ]);
				continue;
			}

			$la_options = $this->normalizeValue($ls_value);

			if ($la_options === null) {
				unset($la_internalVariables[ $ls_key ]);
				continue;
			}

			$la_options = $this->mergeOptions($ls_key, $la_options);

			$la_internalVariables[ $ls_key ] = $la_options;
		}

		return $la_internalVariables;
	}


	/**
	 * @param FunctionExpression $function
	 * @return string
	 */
	protected function normalizeFunctionCall(FunctionExpression $function): string {
		$ls_functionName = $function->getName();
		$la_arguments = $function->getArguments()->getPositional();

		$la_arguments = array_map(function (Expression $argument) {
			$la_options = $this->normalizeValue($argument);

			if ($la_options === null) {
				return '';
			}

			$lx_value = $la_options['value'];
			if ($la_options['unit']) {
				$lx_value .= $la_options['unit'];
			}

			if ($la_options['quotes']) {
				$lx_value = $la_options['quotes'] . $lx_value . $la_options['quotes'];
			}

			return $lx_value;
		}, $la_arguments);

		return sprintf('%s(%s)', $ls_functionName, implode(', ', $la_arguments));
	}


	/**
	 * Normalizes the value of a variable and returns the options.
	 *
	 * @param mixed $value
	 * @return array|null
	 */
	protected function normalizeValue(mixed $value): ?array {
		$la_options = [
			'unit' => null,
			'quotes' => null,
			'value' => null,
		];

		if (is_a($value, ColorExpression::class)) {
			/** @noinspection PhpPossiblePolymorphicInvocationInspection */
			$la_options['value'] = $value->getValue()->getFormat()->getOriginal();
		}
		elseif (is_a($value, NumberExpression::class)) {
			$la_options['value'] = $value->getValue();
			$la_options['unit'] = $value->getUnit();
		}
		elseif (is_a($value, StringExpression::class)) {
			$la_options['value'] = implode('', $value->getText()->getContents() ?? []);
			$la_options['quotes'] = $value->hasQuotes() ? '\'' : null;
		}
		elseif (is_a($value, ListExpression::class)) {
			$la_options['value'] = implode($value->getSeparator()->getSeparator(), array_map(function (Expression $item) {
				$la_options = $this->normalizeValue($item);

				if ($la_options['quotes']) {
					$la_options['value'] = $la_options['quotes'] . $la_options['value'] . $la_options['quotes'];
				}

				return $la_options['value'];
			}, $value->getContents()));
		}
		elseif (is_a($value, FunctionExpression::class)) {
			$la_options['value'] = $this->normalizeFunctionCall($value);
		}
		elseif (is_a($value, VariableExpression::class)) {
			$la_options['value'] = '$' . $value->getName();
		}
		elseif (is_object($value) && method_exists($value, 'getValue')) {
			$la_options['value'] = $value->getValue();
		}
		elseif (is_object($value) && method_exists($value, '__toString')) {
			$la_options['value'] = (string)$value;
		}
		elseif (is_scalar($value)) {
			$la_options['value'] = $value;
		}
		else {
			throw new InvalidArgumentException(sprintf('Unsupported value type: %s', gettype($value)));
		}

		return $la_options;
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
		$la_units = $this->getConfig('units');
		$la_variableMapping = $this->getConfig('variableMapping');

		$la_options = $options;
		if (isset($la_variableMapping[ $key ])) {
			$la_options = Hash::merge($la_variableMapping[ $key ], $options);
		}
		else {
			$la_patternOptions = [];
			foreach ($la_variableMapping as $ls_pattern => $la_mapping) {
				if (preg_match('/^' . $ls_pattern . '$/', $key, $la_matches)) {
					$la_patternOptions = $la_mapping;
					break;
				}
			}

			if ($la_patternOptions) {
				$la_options = Hash::merge($la_patternOptions, $options);
			}

			if (isset($la_options['group']) && !empty($la_matches)) {
				$la_options['group'] = preg_replace_callback('/\$(\d+)/', function (array $matches) use ($la_matches) {
					return Inflector::underscore($la_matches[ $matches[1] ]);
				}, $la_options['group']);
			}

			if (isset($la_options['associatedVariables']) && !empty($la_matches)) {
				foreach ($la_options['associatedVariables'] as &$ls_associatedVariable) {
					$ls_associatedVariable = preg_replace_callback('/\$(\d+)/', function (array $matches) use ($la_matches) {
						return $la_matches[ $matches[1] ];
					}, $ls_associatedVariable);
				}
				unset($ls_associatedVariable);
			}
		}

		if (!isset($la_options['type'])) {
			$la_options['type'] = ScssVariableType::String;
		}

		$la_options['units'] ??= null;
		/** @noinspection PhpInArrayCanBeReplacedWithComparisonInspection */
		if (in_array($la_options['type'], [ScssVariableType::Number])) {
			$la_options['units'] = Hash::merge($la_units, $la_options['units'] ?? []);
			$la_options['units'] = array_filter($la_options['units'], function ($unit, $key) use ($la_options) {
				if (isset($la_options['forcedUnit'])) {
					return $key === $la_options['forcedUnit'];
				}

				return !empty($unit);
			}, ARRAY_FILTER_USE_BOTH);
		}

		return $la_options;
	}


	/**
	 * @param string $key
	 * @return bool
	 */
	protected function variableIsBlocklisted(string $key): bool {
		$la_blocklistedVariables = $this->getConfig('blocklistedVariables', []);

		if (in_array($key, $la_blocklistedVariables)) {
			return true;
		}
		else {
			foreach ($la_blocklistedVariables as $ls_blocklistedVariable) {
				if (preg_match('/^' . $ls_blocklistedVariable . '$/', $key)) {
					return true;
				}
			}
		}

		return false;
	}
}
