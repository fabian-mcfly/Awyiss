<?php declare(strict_types=1);


namespace Awyiss\Authorization;


use Psr\Http\Message\ServerRequestInterface;


/**
 * Provides access to an instance of `\Awyiss\Authorization\AuthorizationService`
 *
 * @see \Awyiss\Authorization\AuthorizationService
 */
class Authorization implements AuthorizationServiceProviderInterface  {
	protected string $type;


	/**
	 * Set the type only when creating a class instance
	 *
	 * @param string $as_type
	 */
	public function __construct (string $as_type) {
		$this->type = $as_type;
	}


	/**
	 * @inheritDoc
	 */
	public function getAuthorizationService (ServerRequestInterface $ao_request): AuthorizationServiceInterface {
		return new AuthorizationService($this->type);
	}
}