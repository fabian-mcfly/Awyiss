<?php declare(strict_types=1);


namespace Awyiss\Middleware;


use Awyiss\Authorization\AuthorizationServiceInterface;
use Awyiss\Authorization\AuthorizationServiceProviderInterface;
use Cake\Core\InstanceConfigTrait;
use InvalidArgumentException;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use RuntimeException;


class AuthorizationMiddleware implements MiddlewareInterface {
	use InstanceConfigTrait;


	protected $_defaultConfig = [];


	/**
	 * Authentication service or application instance.
	 */
	protected \Awyiss\Authorization\Authorization $lo_subject;


	/**
	 * Constructor
	 *
	 * @param AuthorizationServiceInterface|AuthorizationServiceProviderInterface $ao_subject Authorization service or application instance.
	 * @param array $aa_config Array of configuration settings.
	 *
	 * @throws \InvalidArgumentException When invalid subject has been passed.
	 */
	public function __construct ($ao_subject, $aa_config = NULL) {
		$this->setConfig($aa_config);

		if ( ! ($ao_subject instanceof AuthorizationServiceInterface) && ! ($ao_subject instanceof AuthorizationServiceProviderInterface)) {
			$la_expected = implode('` or `', [
				AuthorizationServiceInterface::class,
				AuthorizationServiceProviderInterface::class,
			]);
			$ls_type = is_object($ao_subject) ? get_class($ao_subject) : gettype($ao_subject);
			$ls_message = sprintf('Subject must be an instance of `%s`, `%s` given.', $la_expected, $ls_type);

			throw new InvalidArgumentException($ls_message);
		}

		$this->lo_subject = $ao_subject;
	}


	public function process (\Psr\Http\Message\ServerRequestInterface $ao_request, \Psr\Http\Server\RequestHandlerInterface $ao_handler): \Psr\Http\Message\ResponseInterface {
		$lo_service = $this->getAuthorizationService($ao_request);
		$lo_service->setAuthenticationService($ao_request->getAttribute('authentication'));

		$lo_request = $ao_request->withAttribute('authorization', $lo_service);

		$lo_response = $ao_handler->handle($lo_request);

		return $lo_response;
	}


	/**
	 * Returns AuthorizationServiceInterface instance.
	 *
	 * @param \Psr\Http\Message\ServerRequestInterface $ao_request Server request.
	 * @return AuthorizationServiceInterface
	 * @throws \RuntimeException When authentication method has not been defined.
	 */
	protected function getAuthorizationService(ServerRequestInterface $ao_request): AuthorizationServiceInterface {
		$lo_subject = $this->lo_subject;

		if ($lo_subject instanceof AuthorizationServiceProviderInterface) {
			$lo_subject = $lo_subject->getAuthorizationService($ao_request);
		}

		if ( ! $lo_subject instanceof AuthorizationServiceInterface) {
			$ls_type = is_object($lo_subject) ? get_class($lo_subject) : gettype($lo_subject);
			$ls_message = sprintf('Service provided by a subject must be an instance of `%s`, `%s` given.', AuthorizationServiceInterface::class, $ls_type);

			throw new RuntimeException($ls_message);
		}


		return $lo_subject;
	}
}