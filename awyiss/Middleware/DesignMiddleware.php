<?php declare(strict_types=1);


namespace Awyiss\Middleware;


use Awyiss\Awyiss;
use Awyiss\Utility\Design\ScssCompiler;
use Awyiss\Utility\Design\ScssFilesCollection;
use Cake\Datasource\FactoryLocator;
use Exception;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;


/**
 * The DesignMiddleware class is a part of the Awyiss\Middleware namespace.
 * It implements the MiddlewareInterface and is responsible for handling the design-related aspects of the application.
 */
class DesignMiddleware implements MiddlewareInterface {
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

		// Determine if the environment resembles a production environment
		$lb_shouldCompile = $lb_showExceptions = !in_array($ls_configEnv, ['production', 'prod', 'live']);

		// Check if the request has a query parameter to compile SCSS files
		$lb_mustCompile = false;
		$la_queryParams = $request->getQueryParams();
		if (($la_queryParams['compileScss'] ?? false) === 'true') {
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
	 */
	public function compileScss(bool $mustCompile = false, ?string $realm = null, bool $showExceptions = false): void {
		// Set the exception handling for the ScssCompiler
		ScssCompiler::showExceptions($showExceptions);

		// Discover the SCSS files in the realm
		$la_files = ScssCompiler::discoverRealmFiles($realm ?? Awyiss::getRealm());

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
			$la_result = ScssCompiler::compileFolders($la_files, $this->getDesignVariables());
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
	 * @return array
	 */
	public function getDesignVariables(): array {
		if (isset($this->designVariables)) {
			return $this->designVariables;
		}

		$lo_designTable = FactoryLocator::get('Table')->get('Designs');
		/** @var \Awyiss\Model\Entity\Design $lo_design */
		$lo_design = $lo_designTable->find()->where(['in_use' => true])->first();

		if (!$lo_design) {
			return [];
		}

		$la_variables = [];

		foreach ($lo_design->settings as $ls_key => $lx_value) {
			if (str_ends_with($ls_key, 'Unit')) {
				continue;
			}

			$la_variables[ $ls_key ] = $lx_value;

			if (!empty($lx_value) && isset($lo_design->settings[ $ls_key . 'Unit' ])) {
				$la_variables[ $ls_key ] .= $lo_design->settings[ $ls_key . 'Unit' ];
			}
		}

		$this->designVariables = $la_variables;

		return $la_variables;
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

		/** @var \Awyiss\Utility\Design\ScssFilesCollection$lo_files */
		foreach ($files as $ls_path => $lo_files) {
			// Get a collection of css files in the sibling directory of ScssFilesCollection::$folderPath
			$lo_cssFiles = ScssCompiler::discoverFiles(dirname($lo_files->getFolderPath()) . DS . 'css');

			// If the css files are newer than the scss files, return null.
			if ($lo_cssFiles->getLastModified() && $lo_cssFiles->getLastModified()->greaterThan($lo_files->getLastModified())) {
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

			$li_lastModified = $lo_files->getLastModified()->subSeconds(10)->timestamp;

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
}
