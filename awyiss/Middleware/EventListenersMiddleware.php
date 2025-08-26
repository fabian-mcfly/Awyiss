<?php declare(strict_types=1);


namespace Awyiss\Middleware;


use Awyiss\Awyiss;
use Awyiss\Event\EventListenersProvider;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;


/**
 * Middleware that loads the GeneralEventsListener for the realm the middleware was loaded with
 */
class EventListenersMiddleware implements MiddlewareInterface {
	/**
	 * @inheritDoc
	 */
	public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface {
		EventListenersProvider::loadListener('general_events', Awyiss::getRealm());

		$lo_request = $request->withAttribute('eventListeners', $this);

		return $handler->handle($lo_request);
	}
}
