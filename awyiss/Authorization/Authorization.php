<?php declare(strict_types=1);


namespace Awyiss\Authorization;


use Psr\Http\Message\ServerRequestInterface;


class Authorization implements AuthorizationServiceProviderInterface  {
	protected string $type;


	public function __construct (string $as_type) {
		$this->type = $as_type;
	}


	/**
	 * @param \Psr\Http\Message\ServerRequestInterface $ao_request
	 *
	 * @return \Awyiss\Authorization\AuthorizationServiceInterface
	 */
	public function getAuthorizationService (ServerRequestInterface $ao_request): AuthorizationServiceInterface {
		return new \Awyiss\Authorization\AuthorizationService($this->type);
	}
}