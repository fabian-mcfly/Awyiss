<?php declare(strict_types=1);


namespace Awyiss\Authorization;


use Psr\Http\Message\ServerRequestInterface;


/**
 * AuthorizationServiceProviderInterface
 */
interface AuthorizationServiceProviderInterface {
	/**
	 * Returns an authorization service instance.
	 *
	 * @param ServerRequestInterface $request Request
	 * @return AuthorizationServiceInterface
	 */
	public function getAuthorizationService(ServerRequestInterface $request): AuthorizationServiceInterface;
}
