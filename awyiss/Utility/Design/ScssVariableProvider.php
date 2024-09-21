<?php declare(strict_types=1);


namespace Awyiss\Utility\Design;


use Awyiss\Utility\Inflector;
use Cake\Core\InstanceConfigTrait;
use Cake\Utility\Hash;
use InvalidArgumentException;
use ScssPhp\ScssPhp\Compiler;
use ScssPhp\ScssPhp\Compiler\Environment;
use ScssPhp\ScssPhp\Formatter\OutputBlock;
use ScssPhp\ScssPhp\Node\Number;
use ScssPhp\ScssPhp\Type;


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
	 * @return \ScssPhp\ScssPhp\Compiler
	 * @noinspection PhpDocFinalChecksInspection
	 */
	protected function getCompiler(): Compiler {
		return new class extends Compiler {
			/**
			 * @var array $internalVars The internal variables
			 */
			protected array $internalVars = [];
			/**
			 * @var array $defaultVarNames The names of variables that are marked as default
			 */
			protected array $defaultVarNames = [];


			/**
			 * @inheritDoc
			 */
			protected function compileChild($child, OutputBlock $out) {
				if ($child[0] === Type::T_ASSIGN) {
					[, $la_name] = $child;
					if ($la_name[0] === Type::T_VARIABLE) {
						$la_flags = $child[3] ?? [];
						$lb_isDefault = in_array('!default', $la_flags);

						if ($lb_isDefault) {
							// Remember the name of the variable in case it is marked as default
							$this->defaultVarNames[] = $la_name[1];
						}
					}
				}

				return parent::compileChild($child, $out);
			}


			/**
			 * @inheritDoc
			 */
			protected function set($name, $value, $shadow = false, ?Environment $env = null, $valueUnreduced = null): void {
				$lo_env = $env;

				if (!isset($lo_env)) {
					$lo_env = $this->getStoreEnv();
				}

				parent::set($name, $value, $shadow, $lo_env, $valueUnreduced);

				/*
				 * Store the internal variables but only if they are marked as default
				 * Non-default variables cannot be overridden by the user
				 */
				if (in_array($name, $this->defaultVarNames)) {
					$this->internalVars[ $name ] = $lo_env->storeUnreduced[ str_replace('-', '_', $name) ];
				}
			}


			/**
			 * Returns all internal variables.
			 *
			 * @return array
			 */
			public function getInternalVariables(): array {
				return $this->internalVars;
			}
		};
	}


	/**
	 * @return array
	 * @throws \ScssPhp\ScssPhp\Exception\SassException
	 */
	public function getInternalVariables(): array {
		$lo_compiler = $this->getCompiler();

		foreach ($this->scssFiles as $ls_scssFile) {
			if (
				!str_ends_with($ls_scssFile, '.scss') ||
				!file_exists($ls_scssFile) ||
				!is_file($ls_scssFile)
			) {
				throw new InvalidArgumentException(sprintf('The SCSS file `%s` does not exist.', $ls_scssFile));
			}

			$lo_compiler->addImportPath(dirname($ls_scssFile));
			// Suppressing the error isn't ideal, but the library has a flaw with attribute selectors.
			@$lo_compiler->compileFile($ls_scssFile); // phpcs:ignore Generic.PHP.NoSilencedErrors.Discouraged
		}

		/** @noinspection PhpPossiblePolymorphicInvocationInspection */
		return $lo_compiler->getInternalVariables();
	}


	/**
	 * @return array
	 * @throws \ScssPhp\ScssPhp\Exception\SassException
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
	 * @param array $value
	 * @return string
	 */
	protected function normalizeFunctionCall(array $value): string {
		$ls_functionName = $value[1];
		$la_arguments = $value[2] ?? [];

		$la_arguments = array_map(function (array $argument): string {
			$la_options = $this->normalizeValue($argument[1]);

			if ($la_options === null) {
				return '';
			}

			$ls_value = $la_options['value'];
			if ($la_options['unit']) {
				$ls_value .= $la_options['unit'];
			}

			if ($la_options['quotes']) {
				$ls_value = $la_options['quotes'] . $ls_value . $la_options['quotes'];
			}

			return $ls_value;
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

		if (is_a($value, Number::class)) {
			$la_options['value'] = $value->getDimension();
			$la_options['unit'] = $value->getNumeratorUnits()[0] ?? null;
		}
		elseif (is_array($value)) {
			switch ($value[0]) {
				case 'color':
					$la_options['value'] = $value;
					// Remove the first element as it is the type
					array_shift($la_options['value']);

					if (count($la_options['value']) === 4) {
						$la_options['value'] = sprintf('rgb(%s, %s, %s, %s)', ...$la_options['value']);
					}
					else {
						$la_options['value'] = sprintf('rgb(%s, %s, %s)', ...$la_options['value']);
					}

					break;
				case 'fncall':
					$la_options['value'] = $this->normalizeFunctionCall($value);
					break;
				case 'keyword':
					$la_options['value'] = $value[1] ?? null;
					break;
				case 'list':
					$la_options['value'] = implode($value[1] ?: ' ', array_map(function ($item) {
						return $item[0] === 'keyword' ? $item[1] : '';
					}, $value[2] ?? []));
					break;
				case 'string':
					$la_options['value'] = implode('', $value[2] ?? []);
					$la_options['quotes'] = $value[1] ?? '\'';
					break;
				case 'var':
					$la_options['value'] = '$' . $value[1];
					break;
				default:
					$la_options['value'] = $value;
			}
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
