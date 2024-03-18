<?php declare(strict_types=1);


namespace Awyiss\Middleware;


use Awyiss\Event\EventListenersProvider;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;


/**
 * Middleware that loads the GeneralEventsListener for the realm the middleware was loaded with
 */
class EventListenersMiddleware implements MiddlewareInterface {
	protected ?string $realm = null;


	/**
	 * @param string|null $as_realm
	 */
	public function __construct(?string $as_realm = null) {
		$this->realm = $as_realm;
	}


	/**
	 * @inheritDoc
	 * @throws \ReflectionException
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function process(ServerRequestInterface $ao_request, RequestHandlerInterface $ao_handler): ResponseInterface {
		EventListenersProvider::loadListener('general_events', $this->getRealm());

		$lo_request = $ao_request->withAttribute('eventListeners', $this);


		return $ao_handler->handle($lo_request);
	}


	/**
	 * @return string|null
	 */
	public function getRealm(): ?string {
		return $this->realm;
	}


	/**
	 * @param string $realm
	 * @return EventListenersMiddleware
	 * @noinspection PhpUnused
	 */
	public function setRealm(string $realm): static {
		$this->realm = $realm;


		return $this;
	}
}
