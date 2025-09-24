<?php declare(strict_types=1);


namespace Awyiss\Utility\Design;


use Awyiss\Awyiss;
use Awyiss\Core\App;
use Awyiss\Utility\Content\AwyissColumnSystem;
use Awyiss\Utility\Inflector;
use Cake\Core\Configure;
use Cake\Log\Log;
use Exception;
use InvalidArgumentException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ScssPhp\ScssPhp\CompilationResult;
use ScssPhp\ScssPhp\Compiler;
use ScssPhp\ScssPhp\OutputStyle;
use ScssPhp\ScssPhp\Value\SassString;
use ScssPhp\ScssPhp\ValueConverter;
use SplFileInfo;


/**
 * Handles SCSS to CSS compilation using ScssPhp.
 */
class ScssCompiler {
	/**
	 * @var \ScssPhp\ScssPhp\Compiler
	 */
	protected static Compiler $compiler;
	/**
	 * Whether to show compile exceptions
	 *
	 * @var bool $showExceptions
	 */
	protected static bool $showExceptions = false;


	/**
	 * Discovers all .scss files in a given realm and returns an array of ScssFilesCollection objects.
	 * Main files are ones that do not start with an underscore.
	 *
	 * @param string|null $realm The realm to be searched. This can be either Awyiss::REALM_FRONTEND or Awyiss::REALM_BACKEND.
	 * @return array{string: \Awyiss\Utility\Design\ScssFilesCollection} An associative array where the keys are folder paths and the values are ScssFilesCollection objects
	 *     containing all .scss files and the latest file modification time.
	 * @throws InvalidArgumentException If the given realm is not valid.
	 */
	public static function discoverRealmFiles(?string $realm): array {
		// Get the list of valid realms
		$la_realms = Awyiss::getRealms();

		// Check if the given realm is valid. If not, throw an exception.
		if (!in_array($realm, $la_realms)) {
			throw new InvalidArgumentException(sprintf('The given realm `%s` is invalid.', $realm));
		}

		// Initialize the array of realm folders
		$la_realmFolders = Configure::read('App.paths.assets');
		$la_realmFolders = $la_realmFolders[ $realm ] ?? [];

		// Initialize the array of realm files
		$la_realmFiles = [];
		// For each realm folder, discover the SCSS files and add them to the realm files array
		foreach ($la_realmFolders as $ls_folderPath) {
			$la_realmFiles[ $ls_folderPath ] = static::discoverFiles($ls_folderPath . 'scss');
		}

		// Return the array of realm files
		return $la_realmFiles;
	}


	/**
	 * Discovers all files in a given directory and returns a ScssFilesCollection object.
	 * Main files are ones that do not start with an underscore.
	 *
	 * @param string $folderPath The path to the directory to be searched.
	 * @return \Awyiss\Utility\Design\ScssFilesCollection A ScssFilesCollection object containing all .scss files and the latest file modification time.
	 */
	public static function discoverFiles(string $folderPath): ScssFilesCollection {
		// Create a new ScssFilesCollection object.
		$lo_filesCollection = new ScssFilesCollection($folderPath);

		// Check if the given directory exists. If not, return the empty ScssFilesCollection object.
		if (!is_dir($folderPath)) {
			return $lo_filesCollection;
		}

		// Set the extension to the last directory in folderPath
		$ls_extension = basename($folderPath);

		// Create a RecursiveDirectoryIterator and a RecursiveIteratorIterator to traverse the directory.
		$lo_iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($folderPath));
		$la_files = [];
		/**
		 * Iterate over each file in the directory.
		 *
		 * @var \SplFileInfo $lo_file
		 */
		foreach ($lo_iterator as $lo_file) {
			// If the file is of the same type as the directory name, add it to the ScssFilesCollection object.
			if ($lo_file->isFile() && $lo_file->getExtension() === $ls_extension) {
				$la_files[] = $lo_file;
			}
		}

		// Sort files by filename
		usort($la_files, fn (SplFileInfo $a, SplFileInfo $b) => strnatcasecmp($a->getRealPath(), $b->getRealPath()));

		// Add each file to the ScssFilesCollection object.
		foreach ($la_files as $lo_file) {
			$lo_filesCollection->addFile($lo_file);
		}

		// Return the ScssFilesCollection object.
		return $lo_filesCollection;
	}


	/**
	 * Compiles the SCSS files into CSS.
	 *
	 * @param \Awyiss\Utility\Design\ScssFilesCollection $files A collection of SCSS files to be compiled.
	 * @param string $basePath The base path for the SCSS files.
	 * @param array $vars An array of variables to be passed to the SCSS compiler.
	 * @param bool $returnCss If true, returns the compiled CSS content; otherwise, writes it to the file system.
	 * @return array|null An array of compiled CSS content if $returnCss is true, null if no files are found or if the CSS files are newer than the SCSS files.
	 * @throws \ScssPhp\ScssPhp\Exception\SassException
	 */
	public static function compile(ScssFilesCollection $files, string $basePath, array $vars = [], bool $returnCss = false): ?array {
		// If no files are found, return null.
		if (!$files->getFiles()) {
			return null;
		}

		/** @var class-string<\Awyiss\Utility\Design\ScssVariableProvider> $ls_className */
		$ls_scssVariableProviderClass = App::className('ScssVariableProvider', 'Utility/Design');
		$lo_scssVariableProvider = new $ls_scssVariableProviderClass(Configure::read('Design'));

		// Compile all main files from the ScssFilesCollection object
		$la_compiledCss = [];
		foreach ($files->getMainFiles() as $lo_file) {
			$lo_scssVariableProvider->setScssFiles([$lo_file->getPathname()]);

			$la_internalVariables = $lo_scssVariableProvider->getInternalVariables();
			$lb_includeColumnSystem = isset($la_internalVariables['includeColumnSystem']) && $la_internalVariables['includeColumnSystem']->getValue() === true;

			$la_compiledCss[] = self::compileScss($lo_file, $basePath, $vars, $returnCss, $lb_includeColumnSystem);
		}

		// Return the compiled CSS content
		return $la_compiledCss;
	}


	/**
	 * Compiles SCSS files in multiple folders into CSS.
	 *
	 * @param array<string, \Awyiss\Utility\Design\ScssFilesCollection> $folders An associative array where the keys are folder paths and the values are ScssFilesCollection
	 *     objects.
	 * @param array $vars An array of variables to be passed to the SCSS compiler. Defaults to an empty array.
	 * @param bool $returnCss If true, returns the compiled CSS content; otherwise, writes it to the file system. Defaults to false.
	 * @return array An associative array where the keys are folder paths and the values are the results of the compilation. If $returnCss is true, the values are arrays of
	 *     compiled CSS content; otherwise, they are null.
	 * @throws \ScssPhp\ScssPhp\Exception\SassException
	 */
	public static function compileFolders(array $folders, array $vars = [], bool $returnCss = false): array {
		$la_return = [];

		foreach ($folders as $ls_folderPath => $lo_files) {
			// If the value is not an instance of ScssFilesCollection, skip it.
			if (!$lo_files instanceof ScssFilesCollection) {
				continue;
			}

			// Compile the SCSS files in the folder and store the result in the return array.
			$la_return[ $ls_folderPath ] = static::compile($lo_files, $ls_folderPath, $vars, $returnCss);
		}

		// Return the array of compilation results.
		return $la_return;
	}


	/**
	 * Sets whether to show exceptions during SCSS compilation.
	 *
	 * @param bool $showExepctions If true, exceptions during SCSS compilation are shown; otherwise, they are not shown.
	 * @return void
	 */
	public static function showExceptions(bool $showExepctions): void {
		static::$showExceptions = $showExepctions;
	}


	/**
	 * @return \ScssPhp\ScssPhp\Compiler
	 */
	protected static function getCompiler(): Compiler {
		// If the compiler is not instantiated, create a new instance and configure it.
		if (!isset(static::$compiler)) {
			static::$compiler = new Compiler();

			// Set the output style to expanded by default.
			static::$compiler->setOutputStyle(OutputStyle::EXPANDED);
		}

		return static::$compiler;
	}


	/**
	 * Performs the compilation of SCSS files into CSS.
	 *
	 * @param \SplFileInfo $file
	 * @param string $basePath
	 * @param array $vars The variables to be passed to the SCSS compiler.
	 * @param bool $returnCss If true, returns the compiled CSS content; otherwise, writes it to the file system.
	 * @param bool $includeColumnSystem
	 * @return \ScssPhp\ScssPhp\CompilationResult|string|false
	 * @throws \ScssPhp\ScssPhp\Exception\SassException
	 * @throws \Exception
	 */
	public static function compileScss(SplFileInfo $file, string $basePath, array $vars, bool $returnCss, bool $includeColumnSystem = false): CompilationResult|string|false {
		// Make sure it's a .scss file.
		if ($file->getExtension() !== 'scss') {
			throw new InvalidArgumentException(sprintf('The file `%s` is not a valid SCSS file.', $file->getBasename()));
		}

		if (!file_exists($file->getPathname())) {
			throw new InvalidArgumentException(sprintf('The SCSS file `%s` does not exist.', $file->getBasename()));
		}

		// Normalize variables
		if ($vars) {
			/** @noinspection PhpVariableNamingConventionInspection */
			$vars = static::normalizeVariables($vars);
		}

		// Instantiate the SCSS compiler and configure it.
		$lo_scssCompiler = static::getCompiler();

		// Set import paths and variables for the compilation process.
		$lo_scssCompiler->setImportPaths(dirname($file->getPathname()));
		$lo_scssCompiler->replaceVariables(array_map(function ($value) {
			if ($value === '') {
				return new SassString('');
			}

			if (is_string($value) && str_starts_with($value, 'clamp(')) {
				return new SassString($value);
			}

			return ValueConverter::parseValue((string)$value);
		}, $vars));

		// Set the css file path based on the scss file
		$ls_cssFilename = substr($file->getFilename(), 0, -4) . 'css';

		// Replace 'scss' with 'css' in the file path to get the css folder path
		$ls_cssFolderPath = rtrim(str_replace($basePath . 'scss', $basePath . 'css', $file->getPath()), DS) . DS;

		static::$compiler->addVariables([
			'awyissVersion' => ValueConverter::fromPhp(Awyiss::VERSION),
			'awyissVersionMajor' => ValueConverter::fromPhp(explode('.', Awyiss::VERSION)[0]),
			'awyissVersionName' => ValueConverter::fromPhp(Inflector::dasherize(Awyiss::VERSION_NAME)),
		]);

		/** @noinspection PhpParamsInspection */
		static::$compiler->addVariables(static::getColumnSystemVariables());

		$ls_sourceRoot = '../';
		$ls_subDir = trim(substr($ls_cssFolderPath, strlen($basePath)), DS);
		if (str_contains($ls_subDir, DS)) {
			$li_directoryCount = substr_count($ls_subDir, DS);
			$ls_sourceRoot .= str_repeat('../', $li_directoryCount);
		}

		// Set the source map options if the CSS content is not returned.
		if (!$returnCss) {
			static::$compiler->setSourceMap(Compiler::SOURCE_MAP_FILE);

			static::$compiler->setSourceMapOptions([
				'sourceMapURL' => $ls_cssFilename . '.map',
				'sourceMapFilename' => $ls_cssFilename, // Relative url path of .css file
				'sourceMapBasepath' => $basePath, // Difference between file & url locations, removed from ALL source files in .map
				'sourceRoot' => $ls_sourceRoot,
			]);
		}
		else {
			static::$compiler->setSourceMap(Compiler::SOURCE_MAP_NONE);
		}

		$ls_fileContents = '';

		/**
		 * `Backend` is hardcoded here since both frontend and backend use the same column system.
		 *
		 * @var \Awyiss\Utility\Content\ColumnSystemInterface $ls_columnSystemClassName
		 */
		$ls_columnSystemClassName = Configure::read('Awyiss.Contents.Backend.columnSystem.className', AwyissColumnSystem::class);
		$la_columnSystemFilePaths = $ls_columnSystemClassName::getScssFilePaths();
		if ($includeColumnSystem && !empty($la_columnSystemFilePaths['pre'])) {
			foreach ($la_columnSystemFilePaths['pre'] as $ls_columnSystemFilePath) {
				$lo_scssCompiler->addImportPath(dirname($ls_columnSystemFilePath));
				$ls_fileContents .= sprintf('@import \'%s\';' . PHP_EOL, basename($ls_columnSystemFilePath));
			}
		}

		try {
			$ls_fileContents .= file_get_contents($file->getPathname());

			if ($includeColumnSystem && !empty($la_columnSystemFilePaths['pre'])) {
				foreach ($la_columnSystemFilePaths['post'] as $ls_columnSystemFilePath) {
					$lo_scssCompiler->addImportPath(dirname($ls_columnSystemFilePath));
					$ls_fileContents .= sprintf('@import \'%s\';' . PHP_EOL, basename($ls_columnSystemFilePath));
				}
			}

			$lo_compilationResult = $lo_scssCompiler->compileString($ls_fileContents, $file->getPathname());

			if ($returnCss) {
				// If caller requests the CSS content, return it directly.
				return $lo_compilationResult->getCss();
			}
			else {
				// Check if the directory exists, if not create it.
				if (!is_dir($ls_cssFolderPath)) {
					mkdir($ls_cssFolderPath, 0755, true);
				}

				// Write the compiled CSS content and the source map to the file system.
				file_put_contents($ls_cssFolderPath . $ls_cssFilename, $lo_compilationResult->getCss());
				file_put_contents($ls_cssFolderPath . $ls_cssFilename . '.map', $lo_compilationResult->getSourceMap());

				// If a minified version of the compiled file exists, remove it
				$ls_minifiedCssFilename = substr($ls_cssFilename, 0, -4) . '.min.css';
				if (file_exists($ls_cssFolderPath . $ls_minifiedCssFilename)) {
					unlink($ls_cssFolderPath . $ls_minifiedCssFilename);
				}
			}
		}
		catch (Exception $ex) {
			// Write the debug log
			Log::error('Cannot compile SCSS file `' . $file->getBasename() . '`: ' . $ex->getMessage());

			// If caller requests to show exceptions, handle the exception accordingly.
			if (static::$showExceptions) {
				// If debug is enabled, throw the exception.
				if (Configure::read('debug')) {
					throw $ex;
				}

				// Otherwise show a short message.
				echo 'Cannot compile SCSS file `' . $file->getBasename() . '`';
			}

			return false;
		}

		// Return the compilation result.
		return $lo_compilationResult;
	}


	/**
	 * Returns the column system variables.
	 *
	 * @return array<string, array<array<string, string|int>>>
	 */
	protected static function getColumnSystemVariables(): array {
		/**
		 * `Backend` is hardcoded here since both frontend and backend use the same column system.
		 *
		 * @var \Awyiss\Utility\Content\ColumnSystemInterface $ls_columnSystemClassName
		 */
		$ls_columnSystemClassName = Configure::read('Awyiss.Contents.Backend.columnSystem.className', AwyissColumnSystem::class);
		$ls_columnSystemClassName::setMaxDenominator(Configure::read('Awyiss.Contents.Backend.columnSystem.maxColumns', 5));

		$la_widths = [];
		$la_indents = [];
		foreach ($ls_columnSystemClassName::getColumnWidths() as $lo_column) {
			$la_widths[] = [
				'class' => $lo_column->getCssClass(),
				'numerator' => $lo_column->getNumerator(),
				'denominator' => $lo_column->getDenominator(),
				'percentage' => $lo_column->getFactor() * 100,
			];
		}

		foreach ($ls_columnSystemClassName::getColumnIndents() as $lo_column) {
			$la_indents[] = [
				'class' => $lo_column->getCssClass(),
				'numerator' => $lo_column->getNumerator(),
				'denominator' => $lo_column->getDenominator(),
				'percentage' => $lo_column->getFactor() * 100,
			];
		}

		return [
			'columnSystem' => ValueConverter::fromPhp($ls_columnSystemClassName::getName()),
			'columnWidths' => ValueConverter::parseValue(static::arrayToScssMap($la_widths)),
			'columnIndents' => ValueConverter::parseValue(static::arrayToScssMap($la_indents)),
			'maxColumns' => ValueConverter::fromPhp($ls_columnSystemClassName::getMaxDenominator()),
		];
	}


	/**
	 * Converts an array to a SCSS map.
	 *
	 * @param array $array
	 * @return string
	 */
	protected static function arrayToScssMap(array $array): string {
		$ls_result = '(';

		foreach ($array as $lx_key => $lx_value) {
			if (is_array($lx_value)) {
				$lx_value = static::arrayToScssMap($lx_value); // Recursive call for nested arrays
			}

			if (gettype($lx_key) === 'string') {
				$ls_result .= '"' . $lx_key . '": ' . $lx_value . ', ';
			}
			else {
				$ls_result .= $lx_value . ', ';
			}
		}

		return rtrim($ls_result, ', ') . ')';
	}


	/**
	 * Normalizes SCSS variables by transforming PHP values into SCSS-compatible format.
	 *
	 * This method processes variables in three ways:
	 * 1. Scalar values: Appends corresponding unit values (e.g., 'fontSize' + 'fontSizeUnit')
	 * 2. Array values without 'font' key: Joins array elements with spaces
	 * 3. Font arrays: Extracts font name and wraps it in SCSS inspect() function
	 *
	 * Variables ending with 'Unit' are skipped as they are used to append units to other variables.
	 *
	 * @param array $variables
	 * @return array
	 */
	public static function normalizeVariables(array $variables): array {
		$la_variables = [];

		foreach ($variables as $ls_key => $lx_value) {
			if (str_ends_with($ls_key, 'Unit') || str_ends_with($ls_key, '_unit')) {
				continue;
			}

			if (!is_array($lx_value)) {
				$la_variables[ $ls_key ] = $lx_value;

				if (!empty($lx_value) && isset($variables[ $ls_key . 'Unit' ])) {
					$la_variables[ $ls_key ] .= $variables[ $ls_key . 'Unit' ];
				}

				if (!empty($lx_value) && isset($variables[ $ls_key . '_unit' ])) {
					$la_variables[ $ls_key ] .= $variables[ $ls_key . '_unit' ];
				}

				continue;
			}

			if (!isset($lx_value['font'])) {
				$la_variables[ $ls_key ] = implode(' ', $lx_value);
				continue;
			}

			$la_variables[ $ls_key ] = !empty($lx_value['font']['name']) ? 'inspect(' . $lx_value['font']['name'] . ')' : '';
		}

		return $la_variables;
	}
}
