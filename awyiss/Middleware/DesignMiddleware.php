<?php declare(strict_types=1);


namespace Awyiss\Middleware;


use Awyiss\Awyiss;
use Awyiss\Core\App;
use Awyiss\Utility\Design\ScssFilesCollection;
use Cake\Core\Configure;
use Cake\Datasource\FactoryLocator;
use Exception;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;


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
	protected array $designVariables;


	/**
	 * The process method is responsible for handling the request and returning a response.
	 * It checks if the environment is a production environment and if SCSS files need to be compiled.
	 * If the SCSS files need to be compiled, it uses the ScssCompiler to compile them.
	 * It then adds the 'design' attribute to the request and passes the request to the next handler.
	 *
	 * @param ServerRequestInterface $request The request to be processed
	 * @param RequestHandlerInterface $handler The next handler in the middleware stack
	 * @return ResponseInterface The response from the handler
	 * @throws \ScssPhp\ScssPhp\Exception\SassException
	 */
	public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface {
		// Determine the environment the application is running in
		$ls_configEnv = defined('CONFIG_ENV') ? CONFIG_ENV : 'production';

		// Is autoCompile set to true in the configuration?
		$lb_shouldCompile = Configure::read('Design.autoCompile');
		// Determine if the environment resembles a production environment
		$lb_shouldCompile = $lb_showExceptions = $lb_shouldCompile && !in_array($ls_configEnv, ['production', 'prod', 'live']);

		// Check if the SCSS files must be compiled
		$lb_mustCompile = false;

		// Check if the request is allowed to compile SCSS files
		$lb_allowCompile = Configure::read('Design.allowCompile');
		if (is_callable($lb_allowCompile)) {
			$lb_allowCompile = $lb_allowCompile($request);
		}

		// Check if the request has a query parameter to compile SCSS files
		$la_queryParams = $request->getQueryParams();
		if ($lb_allowCompile && ($la_queryParams['compileScss'] ?? false) === 'true') {
			$lb_mustCompile = true;
		}

		// If the SCSS files need to be compiled, compile them
		if ($lb_shouldCompile || $lb_mustCompile) {
			$this->compileScss($lb_mustCompile, null, $lb_showExceptions);
		}

		// Add the 'design' attribute to the request
		$lo_request = $request->withAttribute('design', $this);

		return $handler->handle($lo_request);
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
	 * @throws \Exception
	 */
	public function compileScss(bool $mustCompile = false, ?string $realm = null, bool $showExceptions = false): void {
		/** @var class-string<\Awyiss\Utility\Design\ScssCompiler> $ls_compilerClass */
		$ls_compilerClass = static::getCompilerClass();

		// Set the exception handling for the ScssCompiler
		$ls_compilerClass::showExceptions($showExceptions);

		// Discover the SCSS files in the realm
		try {
			$la_files = $ls_compilerClass::discoverRealmFiles($realm ?? Awyiss::getRealm());
		}
		catch (InvalidArgumentException) {
			return;
		}

		/*
		 * If the SCSS should be compiled, but must not be compiled,
		 * filter out files that are older than the compiled CSS files.
		 */
		if (!$mustCompile) {
			$la_files = $this->filterOldFiles($la_files);
		}

		if (!$la_files) {
			return;
		}

		try {
			// Compile the SCSS files
			$la_result = $ls_compilerClass::compileFolders($la_files, $this->getDesignVariables($realm ?? Awyiss::getRealm()));
		}
		catch (Exception $ex) {
			$this->resetFileTimes($la_files);

			throw $ex;
		}

		// Reset the last modified times of the files if the result contains at least one `false`
		if (in_array(false, $la_result, true)) {
			$this->resetFileTimes($la_files);
		}
	}


	/**
	 * Returns an array of variables, set via DesignController, that can be used in the SCSS files.
	 *
	 * @param string $realm
	 * @return array
	 */
	public function getDesignVariables(string $realm = Awyiss::REALM_FRONTEND): array {
		if (isset($this->designVariables)) {
			return $this->designVariables;
		}

		// Do not load design variables for the backend
		if ($realm === Awyiss::REALM_BACKEND) {
			return [];
		}

		$lo_designTable = FactoryLocator::get('Table')->get('Designs');
		/** @var \Awyiss\Model\Entity\Design $lo_design */
		$lo_design = $lo_designTable->find()->where(['in_use' => true])->first();

		if (!$lo_design) {
			$this->designVariables = [];
			return [];
		}

		$this->designVariables = $lo_design->settings ?? [];

		return $this->designVariables;
	}


	/**
	 * Resets the design variables.
	 * This is useful when the design variables need to be reloaded.
	 *
	 * @return void
	 */
	public function resetDesignVariables(): void {
		unset($this->designVariables);
	}


	/**
	 * Returns an array of files, filtered to exclude files that are older than the compiled CSS files.
	 *
	 * @param array $files
	 * @return array
	 */
	protected function filterOldFiles(array $files): array {
		$la_files = [];

		/** @var class-string<\Awyiss\Utility\Design\ScssCompiler> $ls_compilerClass */
		$ls_compilerClass = static::getCompilerClass();

		/** @var \Awyiss\Utility\Design\ScssFilesCollection$lo_files */
		foreach ($files as $ls_path => $lo_files) {
			// Get a collection of css files in the sibling directory of ScssFilesCollection::$folderPath
			$lo_cssFiles = $ls_compilerClass::discoverFiles(dirname($lo_files->getFolderPath()) . DS . 'css');

			// If the css files are newer than the scss files, return null.
			if (
				$lo_cssFiles->getLastModified() &&
				$lo_files->getLastModified() &&
				$lo_cssFiles->getLastModified()->greaterThan($lo_files->getLastModified())
			) {
				continue;
			}

			$la_files[ $ls_path ] = $lo_files;
		}

		return $la_files;
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
		foreach ($files as $ls_folderPath => $lo_files) {
			// If the value is not an instance of ScssFilesCollection, skip it.
			if (!$lo_files instanceof ScssFilesCollection) {
				continue;
			}

			$li_lastModified = $lo_files->getLastModified()?->subSeconds(10)->timestamp;

			foreach ($lo_files->getMainFiles() as $lo_file) {
				// Set the css file path based on the scss file
				$ls_cssFilename = substr($lo_file->getFilename(), 0, -4) . 'css';

				// Replace 'scss' with 'css' in the file path to get the css folder path
				$ls_cssFolderPath = rtrim(str_replace($ls_folderPath . 'scss', $ls_folderPath . 'css', $lo_file->getPath()), DS) . DS;

				if (file_exists($ls_cssFolderPath . $ls_cssFilename)) {
					touch($ls_cssFolderPath . $ls_cssFilename, $li_lastModified);
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

		/** @var class-string<\Awyiss\Utility\Design\ScssCompiler> $ls_className */
		static::$compilerClass = App::className('ScssCompiler', 'Utility/Design');

		return static::$compilerClass;
	}
}
