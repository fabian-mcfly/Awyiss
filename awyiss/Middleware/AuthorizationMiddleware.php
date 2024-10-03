<?php declare(strict_types=1);


namespace Awyiss\Middleware;


use Awyiss\Authorization\AuthorizationServiceInterface;
use Awyiss\Authorization\AuthorizationServiceProviderInterface;
use Awyiss\Awyiss;
use Awyiss\Event\EventDispatcherTrait;
use Awyiss\Event\EventListenersProvider;
use Awyiss\Routing\Router;
use Cake\Core\InstanceConfigTrait;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;


/**
 * Middleware that adds an `AuthorizationServiceInterface` to the request
 *
 * @see AuthorizationServiceInterface
 */
class AuthorizationMiddleware implements MiddlewareInterface {
	use EventDispatcherTrait;
	use InstanceConfigTrait;


	/**
	 * Authentication service or application instance.
	 */
	protected AuthorizationServiceProviderInterface|AuthorizationServiceInterface $subject;
	protected array $_defaultConfig = [];


	/**
	 * Constructor
	 *
	 * @param AuthorizationServiceInterface|AuthorizationServiceProviderInterface $subject Authorization service or application instance.
	 * @param array|null $config Array of configuration settings.
	 */
	public function __construct(AuthorizationServiceProviderInterface|AuthorizationServiceInterface $subject, ?array $config = null) {
		$this->setConfig($config ?? []);

		$this->subject = $subject;
	}


	/**
	 * @inheritDoc
	 * @throws \ReflectionException
	 */
	public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface {
		EventListenersProvider::loadListener('authorization', Awyiss::getRealm());

		$lo_service = $this->getAuthorizationService($request);
		$lo_service->setAuthenticationService($request->getAttribute('authentication'));

		$lo_request = $request->withAttribute('authorization', $lo_service);
		/** @noinspection PhpParamsInspection */
		Router::setRequest($lo_request);

		$this->dispatchEvent('Authorization.afterMiddlewareProcess', [
			'authorizationService' => $lo_service,
		], $this);


		return $handler->handle($lo_request);
	}


	/**
	 * Returns AuthorizationServiceInterface instance.
	 *
	 * @param ServerRequestInterface $request Server request.
	 * @return AuthorizationServiceInterface
	 * @throws RuntimeException When authentication method has not been defined.
	 */
	protected function getAuthorizationService(ServerRequestInterface $request): AuthorizationServiceInterface {
		$lo_subject = $this->subject;

		if ($lo_subject instanceof AuthorizationServiceProviderInterface) {
			$lo_subject = $lo_subject->getAuthorizationService($request);
		}

		if (!$lo_subject instanceof AuthorizationServiceInterface) {
			throw new RuntimeException(
				sprintf('Service provided by a subject must be an instance of `%s`, `%s` given.', AuthorizationServiceInterface::class, gettype($lo_subject))
			);
		}


		return $lo_subject;
	}
}
