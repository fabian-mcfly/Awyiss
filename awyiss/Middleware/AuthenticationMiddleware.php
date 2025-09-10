<?php declare(strict_types=1);


namespace Awyiss\Middleware;


use Authentication\Middleware\AuthenticationMiddleware as BaseAuthenticationMiddleware;
use Awyiss\Awyiss;
use Awyiss\Event\EventListenersProvider;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;


/**
 * Authentication Middleware
 */
class AuthenticationMiddleware extends BaseAuthenticationMiddleware {
	/**
	 * @inheritDoc
	 */
	public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface {
		EventListenersProvider::loadListener('authentication', Awyiss::getRealm());

		return parent::process($request, $handler);
	}
}
