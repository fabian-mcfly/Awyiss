<?php declare(strict_types=1);


namespace Awyiss\Authorization;


use Psr\Http\Message\ServerRequestInterface;


/**
 * Provides access to an instance of `\Awyiss\Authorization\AuthorizationService`
 *
 * @see AuthorizationService
 */
class Authorization implements AuthorizationServiceProviderInterface  {
	/**
	 * @var string
	 */
	protected string $realm;


	/**
	 * Set the realm only when creating a class instance
	 *
	 * @param string $as_realm
	 */
	public function __construct (string $as_realm) {
		$this->realm = $as_realm;
	}


	/**
	 * @inheritDoc
	 */
	public function getAuthorizationService (ServerRequestInterface $ao_request): AuthorizationServiceInterface {
		return new AuthorizationService($this->realm);
	}
}