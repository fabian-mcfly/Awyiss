<?php declare(strict_types=1);


namespace Awyiss\Authorization;


use Authentication\AuthenticationServiceInterface;
use Awyiss\Authorization\Policy\PolicyInterface;
use Cake\Utility\Inflector;
use Cake\Utility\Text;
use ReflectionClass;
use RuntimeException;


/**
 * Provides access to an instance of `AuthenticationService` and allows retreiving policies
 *
 * @see AuthorizationServiceInterface
 */
class AuthorizationService implements AuthorizationServiceInterface {
	/**
	 * @var array
	 */
	protected array $policies = [];
	/**
	 * @var AuthenticationServiceInterface|null
	 */
	protected ?AuthenticationServiceInterface $authenticationService = null;
	/**
	 * @var string
	 */
	protected string $realm;


	/**
	 * @inheritDoc
	 */
	public function __construct(string $as_realm) {
		$this->realm = $as_realm;
	}


	/**
	 * @inheritDoc
	 */
	public function getAuthenticationService(): ?AuthenticationServiceInterface {
		return $this->authenticationService;
	}


	/**
	 * @inheritDoc
	 */
	public function setAuthenticationService(AuthenticationServiceInterface $ao_authenticationService): static {
		$this->authenticationService = $ao_authenticationService;


		return $this;
	}


	/**
	 * @inheritDoc
	 */
	public function getRealm(): string {
		return $this->realm;
	}


	/**
	 * @inheritDoc
	 * @throws \ReflectionException
	 */
	public function getPolicies(?string $as_realm = null): array {
		$ls_realm = $as_realm ?: $this->realm;

		//if (!isset($this->policies[ $ls_realm ])) {
		$this->policies[ $ls_realm ] = $this->findPolicy('*', $ls_realm);


		//}

		return $this->policies[ $ls_realm ] ?? [];
	}


	/**
	 * @inheritDoc
	 * @throws \ReflectionException
	 */
	public function getPolicy(string $as_scope, ?string $as_realm = null): ?string {
		$ls_realm = $as_realm ?: $this->realm;
		$ls_scope = static::sanitizeScope($as_scope);

		if (!isset($this->policies[ $ls_realm ])) {
			$this->policies[ $ls_realm ] = [];
		}

		if (empty($this->policies[ $ls_realm ][ $ls_scope ])) {
			$this->policies[ $ls_realm ] += $this->findPolicy($ls_scope, $ls_realm);
		}


		return $this->policies[ $ls_realm ][ $ls_scope ] ?? null;
	}


	/**
	 * @param string $as_scope
	 * @param string $as_realm
	 * @return array<string, class-string<PolicyInterface>>
	 * @throws \ReflectionException
	 */
	protected function findPolicy(string $as_scope, string $as_realm): array {
		$la_policies = [];

		$la_paths = [
			'\\' . CUSTOM_NAMESPACE . '\Authorization\Policy\\' . $as_realm . '\\' => implode(
				DS,
				[ROOT, CUSTOM_DIR, 'Authorization', 'Policy', $as_realm, $as_scope . 'Policy.php',]
			),
			'\Awyiss\Authorization\Policy\\' . $as_realm . '\\' => implode(DS, [ROOT, APP_DIR, 'Authorization', 'Policy', $as_realm, $as_scope . 'Policy.php']),
		];

		foreach ($la_paths as $ls_namespace => $ls_path) {
			foreach (glob($ls_path) as $ls_filePath) {
				$ls_policyName = substr($ls_filePath, strrpos($ls_filePath, DS) + 1, -4);
				if (str_starts_with($ls_policyName, '_')) {
					continue;
				}

				$ls_policyClass = $ls_namespace . $ls_policyName;
				/** @var PolicyInterface $ls_policyClass */
				$ls_scope = $ls_policyClass::getScope();

				if (isset($la_policies[ $ls_scope ])) {
					continue;
				}

				$lo_reflection = new ReflectionClass($ls_policyClass);

				if (!$lo_reflection->implementsInterface(PolicyInterface::class)) {
					throw new RuntimeException(sprintf('The provided Policy class `%s` does not implement the `%s` interface.', $ls_policyClass, PolicyInterface::class));
				}

				$la_policies[ $ls_scope ] = $ls_policyClass;
			}
		}


		return $la_policies;
	}


	/**
	 * Sanitize the provided scope by removing all non-ascii characters
	 * Returns a camelBacked string
	 *
	 * @param string $as_scope
	 * @return string
	 */
	public static function sanitizeScope(string $as_scope): string {
		return Inflector::camelize(Inflector::pluralize(Text::slug($as_scope, '_')));
	}


	/**
	 * Sanitize the provided identifier by removing all non-ascii characters
	 * Returns a camelBacked string
	 *
	 * @param string $as_identifier
	 * @return string
	 */
	public static function sanitizeIdentifier(string $as_identifier): string {
		return Inflector::variable(Text::slug($as_identifier, '_'));
	}
}
