<?php declare(strict_types=1);


namespace Awyiss\Authorization;


use Authentication\AuthenticationServiceInterface;


/**
 * Interface with method signatures that
 * - provide access to an instance of AuthenticationService
 * - allows retreiving policies
 */
interface AuthorizationServiceInterface {
	/**
	 * Set the type only when creating a class instance
	 *
	 * @param string $as_type
	 */
	public function __construct (string $as_type);


	/**
	 * Return the AuthenticationServiceInterface
	 *
	 * @return null|\Authentication\AuthenticationServiceInterface
	 */
	public function getAuthenticationService (): ?AuthenticationServiceInterface;


	/**
	 * Set the AuthenticationServiceInterface
	 *
	 * @param \Authentication\AuthenticationServiceInterface $ao_authenticationService
	 *
	 * @return $this
	 */
	public function setAuthenticationService (AuthenticationServiceInterface $ao_authenticationService): static;


	/**
	 * Returns the type the class was loaded with
	 *
	 * @return string
	 */
	public function getType (): string;


	/**
	 * Returns an array containing all Policies found for the given type (sub-namespace)
	 *
	 * @param string|NULL $as_type
	 *
	 * @return array<string, class-string<\Awyiss\Authorization\Policy\PolicyInterface>>
	 */
	public function getPolicies (string $as_type = NULL): array;


	/**
	 * Returns the FQCN of the Policy with the given name for the given type (sub-namespace), if it exists.
	 *
	 * It looks for such a policy in the custom_namespace before trying the Awyiss namespace:
	 *
	 * - \\`CUSTOM_NAMESPACE`\Authorization\Policy\\`$as_type`\\`$as_name`Policy
	 *
	 * - \Awyiss\Authorization\Policy\\`$as_type`\\`$as_name`Policy
	 *
	 * @param string $as_name
	 * @param null|string $as_type
	 *
	 * @return null|class-string<\Awyiss\Authorization\Policy\PolicyInterface>
	 */
	public function getPolicy (string $as_name, ?string $as_type = NULL): ?string;
}