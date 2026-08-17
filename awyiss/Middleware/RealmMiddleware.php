<?php declare(strict_types=1);


namespace Awyiss\Middleware;


use Awyiss\Awyiss;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;


/**
 * Middleware to set the realm for the request
 */
class RealmMiddleware implements MiddlewareInterface {
	/**
	 * @var string
	 */
	protected string $realm;


	/**
	 * @param string|null $realm
	 */
	public function __construct(?string $realm = null) {
		$this->realm = $realm;
	}


	/**
	 * @inheritDoc
	 */
	public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface {
		Awyiss::setRealm($this->realm);

		return $handler->handle($request);
	}
}
