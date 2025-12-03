<?php declare(strict_types=1);


namespace Awyiss\Middleware;


use Awyiss\Awyiss;
use Awyiss\Core\App;
use Awyiss\Utility\Design\ScssFilesCollection;
use Cake\Core\Configure;
use Cake\Datasource\FactoryLocator;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use ScssPhp\ScssPhp\Exception\SassException;


/**
 * The DesignMiddleware class is a part of the Awyiss\Middleware namespace.
 * It implements the MiddlewareInterface and is responsible for handling the design-related aspects of the application.
 */
class DesignMiddleware implements MiddlewareInterface {
	/**
	 * @var string|null
	 */
	protected static ?string $compilerClass;
	/**
	 * @var array
	 */
	protected array $designVariables = [];


	/**
	 * The process method checks if SCSS files need to be compiled.
	 *
	 * @param ServerRequestInterface $request The request to be processed
	 * @param RequestHandlerInterface $handler The next handler in the middleware stack
	 * @return ResponseInterface The response from the handler
	 * @throws \ScssPhp\ScssPhp\Exception\SassException
	 */
	public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface {
		// Determine the environment the application is running in
		$configEnv = defined('CONFIG_ENV') ? CONFIG_ENV : 'production';

		// Is autoCompile set to true in the configuration?
		$shouldCompile = Configure::read('Design.autoCompile');
		// Should not compile if the CONFIG_ENV resembles a production environment
		$shouldCompile = $showExceptions = $shouldCompile && !in_array($configEnv, ['production', 'prod', 'live']);

		// Check if the SCSS files must be compiled
		$mustCompile = false;

		// Check if the request is allowed to compile SCSS files
		$allowCompile = Configure::read('Design.allowCompile');
		if (is_callable($allowCompile)) {
			$allowCompile = $allowCompile($request);
		}

		// Check if the request has a query parameter to compile SCSS files
		$queryParams = $request->getQueryParams();
		if ($allowCompile && ($queryParams['compileScss'] ?? false) === 'true') {
			$mustCompile = true;
		}

		// If the SCSS files need to be compiled, compile them
		if ($shouldCompile || $mustCompile) {
			$this->compileScss($mustCompile, null, $showExceptions);
		}

		// Add the 'design' attribute to the request
		$request = $request->withAttribute('design', $this);

		return $handler->handle($request);
	}


	/**
	 * Discover SCSS files in the realm and compile them.
	 * If `$mustCompile` is true, the SCSS files will be compiled regardless of the last modified date.
	 * Otherwise, the SCSS files will be compiled if they are newer than the compiled CSS files.
	 *
	 * @param bool $mustCompile
	 * @param string|null $realm
	 * @param bool $showExceptions
	 * @return void
	 * @throws \ScssPhp\ScssPhp\Exception\SassException
	 */
	public function compileScss(bool $mustCompile = false, ?string $realm = null, bool $showExceptions = false): void {
		/** @var class-string<\Awyiss\Utility\Design\ScssCompiler> $compilerClass */
		$compilerClass = static::getCompilerClass();

		// Set the exception handling for the ScssCompiler
		$compilerClass::showExceptions($showExceptions);

		// Discover the SCSS files in the realm
		try {
			$files = $compilerClass::discoverRealmFiles($realm ?? Awyiss::getRealm());
		}
		catch (InvalidArgumentException) {
			return;
		}

		/**
		 * If the SCSS should be compiled, but not must compile, then
		 * filter out files that are older than the compiled CSS files.
		 */
		if (!$mustCompile) {
			$files = $this->filterOldFiles($files);
		}

		if (!$files) {
			return;
		}

		try {
			// Compile the SCSS files
			$result = $compilerClass::compileFolders($files, $this->getDesignVariables($realm ?? Awyiss::getRealm()));
		}
		catch (SassException $ex) {
			$this->resetFileTimes($files);

			throw $ex;
		}

		// Reset the last modified times of the files if the result contains at least one `false`
		if (in_array(false, $result, true)) {
			$this->resetFileTimes($files);
		}
	}


	/**
	 * Returns an array of variables, set via DesignController, that can be used in the SCSS files.
	 *
	 * @param string $realm
	 * @return array
	 */
	public function getDesignVariables(string $realm = Awyiss::REALM_FRONTEND): array {
		if (isset($this->designVariables[ $realm ])) {
			return $this->designVariables[ $realm ];
		}

		// Do not load design variables for the backend
		if ($realm === Awyiss::REALM_BACKEND) {
			return [];
		}

		$designTable = FactoryLocator::get('Table')->get('Designs');
		/** @var \Awyiss\Model\Entity\Design $design */
		$design = $designTable->find()->where(['in_use' => true])->first();

		if (!$design) {
			$this->designVariables[ $realm ] = [];
			return [];
		}

		$this->designVariables[ $realm ] = $design->settings ?? [];

		return $this->designVariables[ $realm ];
	}


	/**
	 * Resets the design variables.
	 * This is useful when the design variables need to be reloaded.
	 *
	 * @return void
	 */
	public function resetDesignVariables(): void {
		$this->designVariables = [];
	}


	/**
	 * Returns an array of files, filtered to exclude files that are older than the compiled CSS files.
	 *
	 * @param array $files
	 * @return array
	 */
	protected function filterOldFiles(array $files): array {
		$filteredFiles = [];

		/** @var class-string<\Awyiss\Utility\Design\ScssCompiler> $compilerClass */
		$compilerClass = static::getCompilerClass();

		/** @var \Awyiss\Utility\Design\ScssFilesCollection $folderFiles */
		foreach ($files as $path => $folderFiles) {
			// Get a collection of css files in the sibling directory of ScssFilesCollection::$folderPath
			$cssFiles = $compilerClass::discoverFiles(dirname($folderFiles->getFolderPath()) . DS . 'css');

			// If the css files are newer than the scss files, return null.
			if (
				$cssFiles->getLastModified() &&
				$folderFiles->getLastModified() &&
				$cssFiles->getLastModified()->greaterThan($folderFiles->getLastModified())
			) {
				continue;
			}

			$filteredFiles[ $path ] = $folderFiles;
		}

		return $filteredFiles;
	}


	/**
	 * Resets the last modified times of the CSS files to the last modified time of the SCSS files.
	 * This is useful when compilation fails for some files, as the newest css files will be used
	 * to determine if the SCSS files need to be recompiled.
	 *
	 * @param array $files
	 * @return void
	 */
	protected function resetFileTimes(array $files): void {
		foreach ($files as $folderPath => $folderFiles) {
			// If the value is not an instance of ScssFilesCollection, skip it.
			if (!$folderFiles instanceof ScssFilesCollection) {
				continue;
			}

			$lastModified = $folderFiles->getLastModified()?->subSeconds(10)->timestamp;

			foreach ($folderFiles->getMainFiles() as $file) {
				// Set the css file path based on the scss file
				$cssFilename = substr($file->getFilename(), 0, -4) . 'css';

				// Replace 'scss' with 'css' in the file path to get the css folder path
				$cssFolderPath = rtrim(str_replace($folderPath . 'scss', $folderPath . 'css', $file->getPath()), DS) . DS;

				if (file_exists($cssFolderPath . $cssFilename)) {
					touch($cssFolderPath . $cssFilename, $lastModified);
				}
			}
		}
	}


	/**
	 * @return class-string<\Awyiss\Utility\Design\ScssCompiler>
	 */
	protected static function getCompilerClass(): string {
		if (isset(static::$compilerClass)) {
			return static::$compilerClass;
		}

		static::$compilerClass = App::className('ScssCompiler', 'Utility/Design');

		return static::$compilerClass;
	}
}
