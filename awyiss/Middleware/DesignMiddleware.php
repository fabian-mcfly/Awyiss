<?php declare(strict_types=1);


namespace Awyiss\Middleware;


use Awyiss\Awyiss;
use Awyiss\Utility\Design\ScssCompiler;
use Cake\Datasource\FactoryLocator;
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
			$this->compileScss($lb_mustCompile, $lb_showExceptions);
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
	 * @param bool $showExceptions
	 * @param string|null $realm
	 * @return void
	 * @throws \ScssPhp\ScssPhp\Exception\SassException
	 */
	public function compileScss(bool $mustCompile = false, bool $showExceptions = false, ?string $realm = null): void {
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

		// Compile the SCSS files
		ScssCompiler::compileFolders($la_files, $this->getDesignVariables());
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

			if (isset($lo_design->settings[ $ls_key . 'Unit' ])) {
				$la_variables[ $ls_key ] .= $lo_design->settings[ $ls_key . 'Unit' ];
			}
		}

		$this->designVariables = $la_variables;

		return $la_variables;
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
}
