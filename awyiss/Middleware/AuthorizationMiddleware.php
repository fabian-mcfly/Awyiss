<?php declare(strict_types=1);


namespace Awyiss\Middleware;


use Awyiss\Authorization\AuthorizationServiceInterface;
use Awyiss\Authorization\AuthorizationServiceProviderInterface;
use Cake\Core\InstanceConfigTrait;
use Cake\Event\EventDispatcherTrait;
use Awyiss\Routing\Router;
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
	 * @param AuthorizationServiceInterface|AuthorizationServiceProviderInterface $ao_subject Authorization service or application instance.
	 * @param NULL|array $aa_config Array of configuration settings.
	 *
	 * @throws \InvalidArgumentException When invalid subject has been passed.
	 */
	public function __construct (AuthorizationServiceProviderInterface|AuthorizationServiceInterface $ao_subject, array $aa_config = NULL) {
		$this->setConfig($aa_config ?? []);

		$this->subject = $ao_subject;
	}


	/**
	 * @inheritDoc
	 *
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function process (ServerRequestInterface $ao_request, RequestHandlerInterface $ao_handler): ResponseInterface {
		$lo_service = $this->getAuthorizationService($ao_request);
		$lo_service->setAuthenticationService($ao_request->getAttribute('authentication'));

		$lo_request = $ao_request->withAttribute('authorization', $lo_service);
		/** @noinspection PhpParamsInspection */
		Router::setRequest($lo_request);

		$this->dispatchEvent('AuthorizationMiddleware.afterProcess', [
			'authorizationService' => $lo_service,
		], $this);

		return $ao_handler->handle($lo_request);
	}


	/**
	 * Returns AuthorizationServiceInterface instance.
	 *
	 * @param ServerRequestInterface $ao_request Server request.
	 * @return AuthorizationServiceInterface
	 * @throws RuntimeException When authentication method has not been defined.
	 */
	protected function getAuthorizationService(ServerRequestInterface $ao_request): AuthorizationServiceInterface {
		$lo_subject = $this->subject;

		if ($lo_subject instanceof AuthorizationServiceProviderInterface) {
			$lo_subject = $lo_subject->getAuthorizationService($ao_request);
		}

		if ( ! $lo_subject instanceof AuthorizationServiceInterface) {
			throw new RuntimeException(sprintf('Service provided by a subject must be an instance of `%s`, `%s` given.', AuthorizationServiceInterface::class, gettype($lo_subject)));
		}


		return $lo_subject;
	}
}
