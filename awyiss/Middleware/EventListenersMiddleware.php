<?php declare(strict_types=1);


namespace Awyiss\Middleware;


use Awyiss\Event\EventListenersProvider;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;


/**
 * Middleware that loads the GeneralEventsListener for the type the middleware was loaded with
 */
class EventListenersMiddleware implements MiddlewareInterface {
	protected string $type;


	/**
	 * @throws \ReflectionException
	 */
	public function __construct (string $as_type) {
		$this->type = $as_type;

		EventListenersProvider::loadListener('general_events', $this->type);
	}


	/**
	 * @inheritDoc
	 *
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function process (ServerRequestInterface $ao_request, RequestHandlerInterface $ao_handler): ResponseInterface {
		$lo_request = $ao_request;

		return $ao_handler->handle($lo_request);
	}
}