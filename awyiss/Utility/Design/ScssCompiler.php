<?php declare(strict_types=1);


namespace Awyiss\Utility\Design;


use Awyiss\Awyiss;
use Awyiss\Core\App;
use Awyiss\Utility\Content\ColumnSystem\AwyissColumnSystem;
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
	 * @return array{string: \Awyiss\Utility\Design\ScssFilesCollection} An associative array where the keys are folder paths
	 *  and the values are ScssFilesCollection objects containing all .scss files and the latest file modification time.
	 * @throws InvalidArgumentException If the given realm is not valid.
	 */
	public static function discoverRealmFiles(?string $realm): array {
		// Get the list of valid realms
		$realms = Awyiss::getRealms();

		// Check if the given realm is valid. If not, throw an exception.
		if (!in_array($realm, $realms)) {
			throw new InvalidArgumentException(sprintf('The given realm `%s` is invalid.', $realm));
		}

		// Initialize the array of realm folders
		$realmFolders = Configure::read('App.paths.assets');
		$realmFolders = $realmFolders[ $realm ] ?? [];

		// Initialize the array of realm files
		$realmFiles = [];
		// For each realm folder, discover the SCSS files and add them to the realm files array
		foreach ($realmFolders as $folderPath) {
			$realmFiles[ $folderPath ] = static::discoverFiles($folderPath . 'scss');
		}

		// Return the array of realm files
		return $realmFiles;
	}


	/**
	 * Discovers all files in a given directory and returns a ScssFilesCollection object.
	 * Main files are ones that do not start with an underscore.
	 *
	 * @param string $folderPath The path to the directory to be searched.
	 * @return \Awyiss\Utility\Design\ScssFilesCollection A ScssFilesCollection object containing all .scss files
	 *  and the latest file modification time.
	 */
	public static function discoverFiles(string $folderPath): ScssFilesCollection {
		// Create a new ScssFilesCollection object.
		$filesCollection = new ScssFilesCollection($folderPath);

		// Check if the given directory exists. If not, return the empty ScssFilesCollection object.
		if (!is_dir($folderPath)) {
			return $filesCollection;
		}

		// Set the extension to the last directory in folderPath
		$extension = basename($folderPath);

		// Create a RecursiveDirectoryIterator and a RecursiveIteratorIterator to traverse the directory.
		$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($folderPath));
		$files = [];
		/**
		 * Iterate over each file in the directory.
		 *
		 * @var \SplFileInfo $file
		 */
		foreach ($iterator as $file) {
			// If the file is of the same type as the directory name, add it to the ScssFilesCollection object.
			if ($file->isFile() && $file->getExtension() === $extension) {
				$files[] = $file;
			}
		}

		// Sort files by filename
		usort($files, fn(SplFileInfo $a, SplFileInfo $b) => strnatcasecmp($a->getRealPath(), $b->getRealPath()));

		// Add each file to the ScssFilesCollection object.
		foreach ($files as $file) {
			$filesCollection->addFile($file);
		}

		// Return the ScssFilesCollection object.
		return $filesCollection;
	}


	/**
	 * Compiles the SCSS files into CSS.
	 *
	 * @param \Awyiss\Utility\Design\ScssFilesCollection $files A collection of SCSS files to be compiled.
	 * @param string $basePath The base path for the SCSS files.
	 * @param array $vars An array of variables to be passed to the SCSS compiler.
	 * @param bool $returnCss If true, returns the compiled CSS content; otherwise, writes it to the file system.
	 * @return array|null An array of compiled CSS content if $returnCss is true, null if no files are found or
	 *  if the CSS files are newer than the SCSS files.
	 * @throws \ScssPhp\ScssPhp\Exception\SassException
	 */
	public static function compile(ScssFilesCollection $files, string $basePath, array $vars = [], bool $returnCss = false): ?array {
		// If no files are found, return null.
		if (!$files->getFiles()) {
			return null;
		}

		/** @var class-string<\Awyiss\Utility\Design\ScssVariableProvider> $className */
		$scssVariableProviderClass = App::className('ScssVariableProvider', 'Utility/Design');
		$scssVariableProvider = new $scssVariableProviderClass(Configure::read('Design'));

		// Compile all main files from the ScssFilesCollection object
		$compiledCss = [];
		foreach ($files->getMainFiles() as $file) {
			$scssVariableProvider->setScssFiles([$file->getPathname()]);

			$internalVariables = $scssVariableProvider->getInternalVariables();
			$includeColumnSystem = isset($internalVariables['includeColumnSystem'])
				&& $internalVariables['includeColumnSystem']->getValue() === true;

			$compiledCss[] = self::compileScss($file, $basePath, $vars, $returnCss, $includeColumnSystem);
		}

		// Return the compiled CSS content
		return $compiledCss;
	}


	/**
	 * Compiles SCSS files in multiple folders into CSS.
	 *
	 * @param array<string, \Awyiss\Utility\Design\ScssFilesCollection> $folders An associative array where the keys are folder paths
	 *  and the values are ScssFilesCollection objects.
	 * @param array $vars An array of variables to be passed to the SCSS compiler. Defaults to an empty array.
	 * @param bool $returnCss If true, returns the compiled CSS content; otherwise, writes it to the file system. Defaults to false.
	 * @return array An associative array where the keys are folder paths and the values are the results of the compilation.
	 *  If $returnCss is true, the values are arrays of compiled CSS content; otherwise, they are null.
	 * @throws \ScssPhp\ScssPhp\Exception\SassException
	 */
	public static function compileFolders(array $folders, array $vars = [], bool $returnCss = false): array {
		$return = [];

		foreach ($folders as $folderPath => $files) {
			// If the value is not an instance of ScssFilesCollection, skip it.
			if (!$files instanceof ScssFilesCollection) {
				continue;
			}

			// Compile the SCSS files in the folder and store the result in the return array.
			$return[ $folderPath ] = static::compile($files, $folderPath, $vars, $returnCss);
		}

		// Return the array of compilation results.
		return $return;
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
	public static function compileScss(
		SplFileInfo $file,
		string $basePath,
		array $vars,
		bool $returnCss,
		bool $includeColumnSystem = false
	): CompilationResult|string|false {
		// Make sure it's a .scss file.
		if ($file->getExtension() !== 'scss') {
			throw new InvalidArgumentException(sprintf('The file `%s` is not a valid SCSS file.', $file->getBasename()));
		}

		if (!file_exists($file->getPathname())) {
			throw new InvalidArgumentException(sprintf('The SCSS file `%s` does not exist.', $file->getBasename()));
		}

		// Normalize variables
		if ($vars) {
			$vars = static::normalizeVariables($vars);
		}

		// Instantiate the SCSS compiler and configure it.
		$scssCompiler = static::getCompiler();

		// Set import paths and variables for the compilation process.
		$scssCompiler->setImportPaths(dirname($file->getPathname()));
		$scssCompiler->replaceVariables(array_map(function ($value) {
			if ($value === '') {
				return new SassString('');
			}

			if (is_string($value) && str_starts_with($value, 'clamp(')) {
				return new SassString($value);
			}

			return ValueConverter::parseValue((string)$value);
		}, $vars));

		// Set the css file path based on the scss file
		$cssFilename = substr($file->getFilename(), 0, -4) . 'css';

		// Replace 'scss' with 'css' in the file path to get the css folder path
		$cssFolderPath = rtrim(str_replace($basePath . 'scss', $basePath . 'css', $file->getPath()), DS) . DS;

		static::$compiler->addVariables([
			'awyissVersion' => ValueConverter::fromPhp(Awyiss::VERSION),
			'awyissVersionMajor' => ValueConverter::fromPhp(explode('.', Awyiss::VERSION)[0]),
			'awyissVersionName' => ValueConverter::fromPhp(Inflector::dasherize(Awyiss::VERSION_NAME)),
		]);

		/** @noinspection PhpParamsInspection */
		static::$compiler->addVariables(static::getColumnSystemVariables());

		$sourceRoot = '..' . DS;
		$subDir = trim(substr($cssFolderPath, strlen($basePath)), DS);
		if (str_contains($subDir, DS)) {
			$directoryCount = substr_count($subDir, DS);
			$sourceRoot .= str_repeat('..' . DS, $directoryCount);
		}

		// Set the source map options if the CSS content is not returned.
		if (!$returnCss) {
			static::$compiler->setSourceMap(Compiler::SOURCE_MAP_FILE);

			static::$compiler->setSourceMapOptions([
				// Relative url path of .css file
				'sourceMapURL' => $cssFilename . '.map',
				// Difference between file & url locations, removed from ALL source files in .map
				'sourceMapFilename' => $cssFilename,
				'sourceMapBasepath' => $basePath,
				'sourceRoot' => $sourceRoot,
			]);
		}
		else {
			static::$compiler->setSourceMap(Compiler::SOURCE_MAP_NONE);
		}

		$fileContents = '';

		/**
		 * `Backend` is hardcoded here since both frontend and backend use the same column system.
		 *
		 * @var \Awyiss\Utility\Content\ColumnSystem\ColumnSystemInterface $columnSystemClassName
		 */
		$columnSystemClassName = Configure::read('Awyiss.Contents.Backend.columnSystem.className', AwyissColumnSystem::class);
		$columnSystemFilePaths = $columnSystemClassName::getScssFilePaths();
		if ($includeColumnSystem && !empty($columnSystemFilePaths['pre'])) {
			foreach ($columnSystemFilePaths['pre'] as $columnSystemFilePath) {
				$scssCompiler->addImportPath(dirname($columnSystemFilePath));
				$fileContents .= sprintf('@import \'%s\';' . PHP_EOL, basename($columnSystemFilePath));
			}
		}

		try {
			$fileContents .= file_get_contents($file->getPathname());

			if ($includeColumnSystem && !empty($columnSystemFilePaths['pre'])) {
				foreach ($columnSystemFilePaths['post'] as $columnSystemFilePath) {
					$scssCompiler->addImportPath(dirname($columnSystemFilePath));
					$fileContents .= sprintf('@import \'%s\';' . PHP_EOL, basename($columnSystemFilePath));
				}
			}

			$compilationResult = $scssCompiler->compileString($fileContents, $file->getPathname());

			if ($returnCss) {
				// If caller requests the CSS content, return it directly.
				return $compilationResult->getCss();
			}
			else {
				// Check if the directory exists, if not create it.
				if (!is_dir($cssFolderPath)) {
					mkdir($cssFolderPath, 0755, true);
				}

				// Write the compiled CSS content and the source map to the file system.
				file_put_contents($cssFolderPath . $cssFilename, $compilationResult->getCss());
				file_put_contents($cssFolderPath . $cssFilename . '.map', $compilationResult->getSourceMap());

				// If a minified version of the compiled file exists, remove it
				$minifiedCssFilename = substr($cssFilename, 0, -4) . '.min.css';
				if (file_exists($cssFolderPath . $minifiedCssFilename)) {
					unlink($cssFolderPath . $minifiedCssFilename);
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
		return $compilationResult;
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
		 * @var \Awyiss\Utility\Content\ColumnSystem\ColumnSystemInterface $columnSystemClassName
		 */
		$columnSystemClassName = Configure::read('Awyiss.Contents.Backend.columnSystem.className', AwyissColumnSystem::class);
		$columnSystemClassName::setMaxDenominator(Configure::read('Awyiss.Contents.Backend.columnSystem.maxColumns', 5));

		$widths = [];
		$indents = [];
		foreach ($columnSystemClassName::getColumnWidths() as $column) {
			$widths[] = [
				'class' => $column->getCssClass(),
				'numerator' => $column->getNumerator(),
				'denominator' => $column->getDenominator(),
				'percentage' => $column->getFactor() * 100,
			];
		}

		foreach ($columnSystemClassName::getColumnIndents() as $column) {
			$indents[] = [
				'class' => $column->getCssClass(),
				'numerator' => $column->getNumerator(),
				'denominator' => $column->getDenominator(),
				'percentage' => $column->getFactor() * 100,
			];
		}

		return [
			'columnSystem' => ValueConverter::fromPhp($columnSystemClassName::getName()),
			'columnWidths' => ValueConverter::fromPhp($widths),
			'columnIndents' => ValueConverter::fromPhp($indents),
			'maxColumns' => ValueConverter::fromPhp($columnSystemClassName::getMaxDenominator()),
		];
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
		$normalizedVariables = [];

		foreach ($variables as $key => $value) {
			if (str_ends_with($key, 'Unit')) {
				continue;
			}

			if (!is_array($value)) {
				$normalizedVariables[ $key ] = $value;

				if (!empty($value) && isset($variables[ $key . 'Unit' ])) {
					$normalizedVariables[ $key ] .= $variables[ $key . 'Unit' ];
				}

				continue;
			}

			if (!isset($value['font'])) {
				$normalizedVariables[ $key ] = implode(' ', $value);
				continue;
			}

			$normalizedVariables[ $key ] = !empty($value['font']['name']) ? 'inspect(' . $value['font']['name'] . ')' : '';
		}

		return $normalizedVariables;
	}
}
