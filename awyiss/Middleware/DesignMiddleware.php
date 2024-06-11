<?php declare(strict_types=1);


namespace Awyiss\Middleware;


use Awyiss\Awyiss;
use Awyiss\Utility\Design\ScssCompiler;
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
		$lb_showExepctions = !in_array($ls_configEnv, ['production', 'prod', 'live']);
		$lb_mustCompile = $lb_showExepctions;

		// Check if the request has a query parameter to compile SCSS files
		$la_queryParams = $request->getQueryParams();
		if (($la_queryParams['compileScss'] ?? false) === 'true') {
			$lb_mustCompile = true;
		}

		// If the SCSS files need to be compiled, compile them
		if ($lb_mustCompile) {
			$this->compileScss($lb_showExepctions);
		}

		// Add the 'design' attribute to the request
		$lo_request = $request->withAttribute('design', $this);

		return $handler->handle($lo_request);
	}


	/**
	 * The compileScss method is responsible for compiling the SCSS files in the realm.
	 * It uses the ScssCompiler to discover the SCSS files in the realm and compile them.
	 * The method takes a boolean parameter to determine if exceptions should be shown.
	 *
	 * @param bool $showExepctions
	 * @param string|null $realm
	 * @return void
	 * @throws \ScssPhp\ScssPhp\Exception\SassException
	 */
	public function compileScss(bool $showExepctions, ?string $realm = null): void {
		// Set the exception handling for the ScssCompiler
		ScssCompiler::showExceptions($showExepctions);

		// Discover the SCSS files in the realm
		$la_files = ScssCompiler::discoverRealmFiles($realm ?? Awyiss::getRealm());

		// Compile the SCSS files
		ScssCompiler::compileFolders($la_files);
	}
}
