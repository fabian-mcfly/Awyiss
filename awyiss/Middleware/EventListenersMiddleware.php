<?php declare(strict_types=1);


namespace Awyiss\Middleware;


use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;


class EventListenersMiddleware implements MiddlewareInterface {
	protected string $type;


	/**
	 * @throws \ReflectionException
	 */
	public function __construct (string $as_type) {
		$this->type = $as_type;

		\Awyiss\Event\EventListenersProvider::loadListener('general_events', $this->type);
	}


	/**
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function process (ServerRequestInterface $ao_request, RequestHandlerInterface $ao_handler): ResponseInterface {
		$lo_request = $ao_request;


		return $ao_handler->handle($lo_request);
	}
}