<?php declare(strict_types=1);


namespace Awyiss\Middleware;


use Awyiss\Awyiss;
use Exception;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;


/**
 * Middleware to load the configuration for the realm the middleware was loaded with,
 * as well as the selected frontend language
 */
class ConfigMiddleware implements MiddlewareInterface {
	/**
	 * Load the configuration for the realm the middleware was loaded with, as well as the selected frontend language
	 *
	 * @param \Psr\Http\Message\ServerRequestInterface $request
	 * @param \Psr\Http\Server\RequestHandlerInterface $handler
	 * @return \Psr\Http\Message\ResponseInterface
	 * @throws \Exception
	 */
	public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface {
		$frontendLanguage = LocaleMiddleware::getLanguage()?->shortcode;
		$backendLanguage = LocaleMiddleware::getLanguage(Awyiss::REALM_BACKEND)?->shortcode;

		if (!$frontendLanguage) {
			throw new Exception('No frontend language found');
		}

		if (!$backendLanguage) {
			throw new Exception('No backend language found');
		}

		// Load the configuration as soon as possible
		Awyiss::loadConfiguration($frontendLanguage, $backendLanguage);

		return $handler->handle($request);
	}
}
