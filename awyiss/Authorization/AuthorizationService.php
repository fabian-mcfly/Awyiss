<?php declare(strict_types=1);


namespace Awyiss\Authorization;


use Authentication\AuthenticationServiceInterface;
use Awyiss\Authorization\Policy\AbstractGenericPolicy;
use Awyiss\Authorization\Policy\Backend\GenericPagesPolicy;
use Awyiss\Authorization\Policy\PolicyInterface;
use Awyiss\Core\App;
use Cake\Datasource\FactoryLocator;
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
	public function getPolicy(string $as_scope, ?string $as_realm = null): AbstractGenericPolicy|string|null {
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
	 * @return array<string, \Awyiss\Authorization\Policy\AbstractGenericPolicy|class-string<\Awyiss\Authorization\Policy\PolicyInterface>>
	 * @throws \ReflectionException
	 */
	protected function findPolicy(string $as_scope, string $as_realm): array {
		$la_policies = [];

		$ls_scope = $as_scope;
		if ($ls_scope !== '*') {
			$ls_scope = Inflector::camelize($ls_scope);
		}

		$la_paths = [
			'\\' . CUSTOM_NAMESPACE . '\Authorization\Policy\\' . $as_realm . '\\' => implode(
				DS,
				[ROOT, CUSTOM_DIR, 'Authorization', 'Policy', $as_realm, $ls_scope . 'Policy.php',]
			),
			'\Awyiss\Authorization\Policy\\' . $as_realm . '\\' => implode(DS, [ROOT, APP_DIR, 'Authorization', 'Policy', $as_realm, $ls_scope . 'Policy.php']),
		];

		foreach ($la_paths as $ls_namespace => $ls_path) {
			foreach (glob($ls_path) as $ls_filePath) {
				$ls_policyName = substr($ls_filePath, strrpos($ls_filePath, DS) + 1, -4);
				if (str_starts_with($ls_policyName, '_') || ($ls_scope === '*' && $ls_policyName === 'GenericPagesPolicy')) {
					continue;
				}

				$ls_policyClass = $ls_namespace . $ls_policyName;
				/** @var PolicyInterface $ls_policyClass */
				$ls_policyScope = $ls_policyClass::getScope();

				if (isset($la_policies[ $ls_policyScope ])) {
					continue;
				}

				$lo_reflection = new ReflectionClass($ls_policyClass);

				if (!$lo_reflection->implementsInterface(PolicyInterface::class)) {
					throw new RuntimeException(sprintf('The provided Policy class `%s` does not implement the `%s` interface.', $ls_policyClass, PolicyInterface::class));
				}

				$la_policies[ $ls_policyScope ] = $ls_policyClass;
			}
		}


		if ($as_scope === '*') {
			/** @var \Awyiss\Model\Table\PageRolesTable $lo_pageRolesTable */
			$lo_pageRolesTable = FactoryLocator::get('Table')->get('PageRoles');
			/** @var \Awyiss\Model\Entity\PageRole $lo_pageRole */
			foreach ($lo_pageRolesTable->find()->where(['identifier !=' => 'page'])->select('identifier') as $lo_pageRole) {
				$ls_policyScope = static::sanitizeScope($lo_pageRole->identifier);

				if (isset($la_configurations[ $ls_policyScope ])) {
					continue;
				}

				$la_policies[ $ls_policyScope ] = new GenericPagesPolicy($ls_policyScope);
			}
		}
		elseif (!isset($la_policies[ $as_scope ])) {
			/** @var class-string<\Awyiss\Database\Type\PageRoleEnumInterface> $ls_pageRoleEnum */
			$ls_pageRoleEnum = App::className('PageRole', 'Model/Enum');

			if ($ls_pageRoleEnum::tryFromName($as_scope)) {
				$la_policies[ $as_scope ] = new GenericPagesPolicy($as_scope);
			}
		}


		return $la_policies;
	}


	/**
	 * Sanitize the provided scope by removing all non-ascii characters
	 * Returns an underscored string
	 *
	 * @param string $as_scope
	 * @return string
	 */
	public static function sanitizeScope(string $as_scope): string {
		$ls_scope = Text::slug($as_scope, '_');
		$ls_scope = Inflector::singularize($ls_scope);
		$ls_scope = Inflector::pluralize($ls_scope);


		return Inflector::underscore($ls_scope);
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
