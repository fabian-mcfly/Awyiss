<?php declare(strict_types=1);


namespace Awyiss\Authorization;


use Authentication\AuthenticationServiceInterface;
use Awyiss\Authorization\Policy\PolicyInterface;
use RuntimeException;


class AuthorizationService implements AuthorizationServiceInterface {
	private array $la_policies = [];
	private ?AuthenticationServiceInterface $lo_authenticationService = NULL;
	private string $ls_permissionsPropertyName = 'usergroups_permissions';
	private string $ls_type;


	public function __construct (string $as_type) {
		$this->ls_type = \Cake\Utility\Inflector::camelize($as_type);
	}


	public function setAuthenticationService (AuthenticationServiceInterface $ao_authenticationService) {
		$this->lo_authenticationService = $ao_authenticationService;
	}


	public function getAuthenticationService (): ?AuthenticationServiceInterface {
		return $this->lo_authenticationService;
	}


	public function getType (): string {
		return $this->ls_type;
	}


	public function getPolicies (string $as_type = NULL): array {
		$ls_type = $as_type ? \Cake\Utility\Inflector::camelize($as_type) : $this->ls_type;

		if (!isset($this->la_policies[ $ls_type ])) {
			$this->la_policies[ $ls_type ] = $this->_findPolicy('*', $ls_type);
		}

		return $this->la_policies[ $ls_type ] ?? [];
	}


	public function getPolicy (string $as_scope, ?string $as_type = NULL): ?string {
		$ls_type = $as_type ? \Cake\Utility\Inflector::camelize($as_type) : $this->ls_type;

		if (!isset($this->la_policies[ $ls_type ])) {
			$this->la_policies[ $ls_type ] = [];
		}

		if (empty($this->la_policies[ $ls_type ][ $as_scope ])) {
			$this->la_policies[ $ls_type ] += $this->_findPolicy($as_scope, $ls_type);
		}

		return $this->la_policies[ $ls_type ][ $as_scope ] ?? NULL;
	}


	protected function _findPolicy (string $as_scope, string $as_type) {
		$la_policies = [];
		$ls_scope = \Cake\Utility\Inflector::camelize($as_scope);
		$ls_type = \Cake\Utility\Inflector::camelize($as_type);

		$la_paths = [
			'\\' . CUSTOM_NAMESPACE . '\Authorization\Policy\\' . $ls_type . '\\' => implode(DS, [ROOT, CUSTOM_DIR, 'Authorization', 'Policy', $ls_type, $ls_scope . 'Policy.php',]),
			'\Awyiss\Authorization\Policy\\' . $ls_type . '\\' => implode(DS, [ROOT, APP_DIR, 'Authorization', 'Policy', $ls_type, $ls_scope . 'Policy.php']),
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

				$lo_reflection = new \ReflectionClass($ls_policyClass);

				if ( ! $lo_reflection->implementsInterface(PolicyInterface::class)) {
					throw new RuntimeException(sprintf('The provided Policy class `%s` does not implement the `%s` interface.', $ls_policyClass, PolicyInterface::class));
				}

				$la_policies[ $ls_scope ] = $ls_policyClass;
			}
		}

		return $la_policies;
	}
}