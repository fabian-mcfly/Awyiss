<?php declare(strict_types=1);


namespace Awyiss\Authorization;


use Authentication\AuthenticationServiceInterface;
use Awyiss\Authorization\Policy\PolicyInterface;


interface AuthorizationServiceInterface {
	public function __construct (string $as_type);


	public function setAuthenticationService (AuthenticationServiceInterface $ao_authenticationService);


	public function getAuthenticationService (): ?AuthenticationServiceInterface;


	public function getType (): string;


	public function getPolicies (string $as_type = NULL): array;


	public function getPolicy (string $as_scope, ?string $as_type = NULL): ?string;
}