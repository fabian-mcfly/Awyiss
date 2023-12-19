<?php declare(strict_types=1);


namespace Awyiss\Authorization;


use Authentication\AuthenticationServiceInterface;
use Authentication\AuthenticationServiceProviderInterface;
use Psr\Http\Message\ServerRequestInterface;


class Authorization implements AuthorizationServiceProviderInterface  {
	private string $ls_type;


	public function __construct (string $as_type) {
		$this->ls_type = $as_type;
	}


	/**
	 * @param \Psr\Http\Message\ServerRequestInterface $ao_request
	 *
	 * @return \Awyiss\Authorization\AuthorizationServiceInterface
	 */
	public function getAuthorizationService (ServerRequestInterface $ao_request): AuthorizationServiceInterface {
		return new \Awyiss\Authorization\AuthorizationService($this->ls_type);
	}
}