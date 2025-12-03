<?php declare(strict_types=1);


namespace Awyiss\Middleware;


use Awyiss\Authorization\AuthorizationServiceInterface;
use Awyiss\Authorization\AuthorizationServiceProviderInterface;
use Awyiss\Event\EventDispatcherTrait;
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
	/**
	 * Default config
	 *
	 * @var array<string, mixed>
	 */
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
	 */
	public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface {
		$authorizationService = $this->getAuthorizationService($request);
		$authorizationService->setAuthenticationService($request->getAttribute('authentication'));

		$request = $request->withAttribute('authorization', $authorizationService);

		return $handler->handle($request);
	}


	/**
	 * Returns AuthorizationServiceInterface instance.
	 *
	 * @param ServerRequestInterface $request Server request.
	 * @return AuthorizationServiceInterface
	 * @throws RuntimeException When authentication method has not been defined.
	 */
	protected function getAuthorizationService(ServerRequestInterface $request): AuthorizationServiceInterface {
		$subject = $this->subject;

		if ($subject instanceof AuthorizationServiceProviderInterface) {
			$subject = $subject->getAuthorizationService($request);
		}

		if (!$subject instanceof AuthorizationServiceInterface) {
			throw new RuntimeException(
				sprintf('Service provided by a subject must be an instance of `%s`, `%s` given.', AuthorizationServiceInterface::class, gettype($subject))
			);
		}


		return $subject;
	}
}
