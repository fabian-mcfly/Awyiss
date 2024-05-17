<?php declare(strict_types=1);


namespace Awyiss\Middleware;


use Awyiss\Awyiss;
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
	 * @var string
	 */
	protected string $realm;


	/**
	 * @param string $realm
	 */
	public function __construct(string $realm) {
		$this->realm = $realm;
	}


	/**
	 * Load the configuration for the realm the middleware was loaded with, as well as the selected frontend language
	 *
	 * @param \Psr\Http\Message\ServerRequestInterface $request
	 * @param \Psr\Http\Server\RequestHandlerInterface $handler
	 * @return \Psr\Http\Message\ResponseInterface
	 * @throws \Exception
	 */
	public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface {
		Awyiss::setRealm($this->realm);

		// Load the configuration as soon as possible
		Awyiss::loadConfiguration(
			LocaleMiddleware::getLanguage()->shortcode,
			LocaleMiddleware::getLanguage($this->realm)->shortcode,
		);

		return $handler->handle($request);
	}
}
