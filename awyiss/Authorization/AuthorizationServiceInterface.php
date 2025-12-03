<?php declare(strict_types=1);


namespace Awyiss\Authorization;


use Authentication\AuthenticationServiceInterface;
use Awyiss\Authorization\Policy\AbstractGenericPolicy;


/**
 * Interface with method signatures that
 * - provide access to an instance of AuthenticationService
 * - allows retrieving policies
 */
interface AuthorizationServiceInterface {
	/**
	 * Set the realm only when creating a class instance
	 *
	 * @param string $realm
	 */
	public function __construct(string $realm);


	/**
	 * Return the AuthenticationServiceInterface
	 *
	 * @return AuthenticationServiceInterface|null
	 */
	public function getAuthenticationService(): ?AuthenticationServiceInterface;


	/**
	 * Set the AuthenticationServiceInterface
	 *
	 * @param AuthenticationServiceInterface $authenticationService
	 * @return $this
	 */
	public function setAuthenticationService(AuthenticationServiceInterface $authenticationService): static;


	/**
	 * Returns the realm the class was loaded with
	 *
	 * @return string
	 */
	public function getRealm(): string;


	/**
	 * Returns an array containing all Policies found for the given realm (sub-namespace)
	 *
	 * @param string|null $realm
	 * @return array<string, class-string<\Awyiss\Authorization\Policy\PolicyInterface>>
	 */
	public function getPolicies(?string $realm = null): array;


	/**
	 * Returns the FQCN of the Policy with the given scope for the given realm (sub-namespace), if it exists.
	 * It looks for such a policy in the custom_namespace before trying the Awyiss namespace:
	 * - \\`CUSTOM_NAMESPACE`\Authorization\Policy\\`$realm`\\`$scope`Policy
	 * - \Awyiss\Authorization\Policy\\`$realm`\\`$scope`Policy
	 *
	 * @param string $scope
	 * @param string|null $realm
	 * @return \Awyiss\Authorization\Policy\AbstractGenericPolicy|class-string<\Awyiss\Authorization\Policy\PolicyInterface>|null
	 */
	public function getPolicy(string $scope, ?string $realm = null): AbstractGenericPolicy|string|null;
}
