<?php declare(strict_types=1);


namespace Awyiss\Authorization;


use Authentication\AuthenticationServiceInterface;
use Awyiss\Authorization\Policy\PolicyInterface;
use Cake\Utility\Inflector;
use ReflectionClass;
use RuntimeException;


/**
 * Provides access to an instance of `AuthenticationService` and allows retreiving policies
 *
 * @see \Authentication\AuthenticationServiceInterface
 */
class AuthorizationService implements AuthorizationServiceInterface {
	protected array $policies = [];
	protected ?AuthenticationServiceInterface $authenticationService = NULL;
	protected string $type;


	/**
	 * @inheritDoc
	 */
	public function __construct (string $as_type) {
		$this->type = Inflector::camelize($as_type);
	}


	/**
	 * @inheritDoc
	 */
	public function getAuthenticationService (): ?AuthenticationServiceInterface {
		return $this->authenticationService;
	}


	/**
	 * @inheritDoc
	 */
	public function setAuthenticationService (AuthenticationServiceInterface $ao_authenticationService): static {
		$this->authenticationService = $ao_authenticationService;

		return $this;
	}


	/**
	 * @inheritDoc
	 */
	public function getType (): string {
		return $this->type;
	}


	/**
	 * @inheritDoc
	 * @throws \ReflectionException
	 */
	public function getPolicies (string $as_type = NULL): array {
		$ls_type = $as_type ? Inflector::camelize($as_type) : $this->type;

		//if (!isset($this->policies[ $ls_type ])) {
		$this->policies[ $ls_type ] = $this->findPolicy('*', $ls_type);
		//}

		return $this->policies[ $ls_type ] ?? [];
	}


	/**
	 * @inheritDoc
	 *
	 * @throws \ReflectionException
	 */
	public function getPolicy (string $as_name, ?string $as_type = NULL): ?string {
		$ls_type = $as_type ? Inflector::camelize($as_type) : $this->type;

		if (!isset($this->policies[ $ls_type ])) {
			$this->policies[ $ls_type ] = [];
		}

		if (empty($this->policies[ $ls_type ][ $as_name ])) {
			$this->policies[ $ls_type ] += $this->findPolicy($as_name, $ls_type);
		}

		return $this->policies[ $ls_type ][ $as_name ] ?? NULL;
	}


	/**
	 * @param string $as_name
	 * @param string $as_type
	 *
	 * @return array<string, class-string<\Awyiss\Authorization\Policy\PolicyInterface>>
	 * @throws \ReflectionException
	 */
	protected function findPolicy (string $as_name, string $as_type): array {
		$la_policies = [];
		$ls_name = Inflector::camelize($as_name);
		$ls_type = Inflector::camelize($as_type);

		$la_paths = [
			'\\' . CUSTOM_NAMESPACE . '\Authorization\Policy\\' . $ls_type . '\\' => implode(DS, [ROOT, CUSTOM_DIR, 'Authorization', 'Policy', $ls_type, $ls_name . 'Policy.php',]),
			'\Awyiss\Authorization\Policy\\' . $ls_type . '\\' => implode(DS, [ROOT, APP_DIR, 'Authorization', 'Policy', $ls_type, $ls_name . 'Policy.php']),
		];

		foreach ($la_paths as $ls_namespace => $ls_path) {
			foreach (glob($ls_path) as $ls_filePath) {
				$ls_policyName = substr($ls_filePath, strrpos($ls_filePath, DS) + 1, -4);
				$ls_policyClass = $ls_namespace . $ls_policyName;
				/** @var PolicyInterface $ls_policyClass */
				$ls_scope = $ls_policyClass::getScope();

				if (isset($la_policies[ $ls_scope ])) {
					continue;
				}

				$lo_reflection = new ReflectionClass($ls_policyClass);

				if ( ! $lo_reflection->implementsInterface(PolicyInterface::class)) {
					throw new RuntimeException(sprintf('The provided Policy class `%s` does not implement the `%s` interface.', $ls_policyClass, PolicyInterface::class));
				}

				$la_policies[ $ls_scope ] = $ls_policyClass;
			}
		}

		return $la_policies;
	}
}