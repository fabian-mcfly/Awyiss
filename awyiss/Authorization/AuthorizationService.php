<?php declare(strict_types=1);


namespace Awyiss\Authorization;


use Authentication\AuthenticationServiceInterface;
use Awyiss\Authorization\Policy\AbstractGenericPolicy;
use Awyiss\Authorization\Policy\Backend\GenericDatatablesPolicy;
use Awyiss\Authorization\Policy\Backend\GenericPagesPolicy;
use Awyiss\Authorization\Policy\PolicyInterface;
use Awyiss\Core\App;
use Awyiss\Model\Entity\Datatable;
use Awyiss\Utility\Inflector;
use Cake\Datasource\FactoryLocator;
use Cake\Utility\Text;


/**
 * Provides access to an instance of `AuthenticationService` and allows retrieving policies
 *
 * @see AuthorizationServiceInterface
 */
class AuthorizationService implements AuthorizationServiceInterface {
	/**
	 * @var array<string, \Awyiss\Model\Entity\Datatable>
	 */
	protected array $datatables;
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
	public function __construct(string $realm) {
		$this->realm = $realm;
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
	public function setAuthenticationService(AuthenticationServiceInterface $authenticationService): static {
		$this->authenticationService = $authenticationService;


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
	 */
	public function getPolicies(?string $realm = null): array {
		$ls_realm = $realm ?: $this->realm;

		$this->findPolicy('*', $ls_realm);


		return $this->policies[ $ls_realm ] ?? [];
	}


	/**
	 * @inheritDoc
	 */
	public function getPolicy(string $scope, ?string $realm = null): AbstractGenericPolicy|string|null {
		$ls_realm = $realm ?: $this->realm;
		$ls_scope = static::sanitizeScope($scope);

		if (!isset($this->policies[ $ls_realm ])) {
			$this->policies[ $ls_realm ] = [];
		}

		if (empty($this->policies[ $ls_realm ][ $ls_scope ])) {
			$this->findPolicy($ls_scope, $ls_realm);
		}

		return $this->policies[ $ls_realm ][ $ls_scope ] ?? null;
	}


	/**
	 * @param string $scope
	 * @param string $realm
	 * @return array<string, \Awyiss\Authorization\Policy\AbstractGenericPolicy|class-string<\Awyiss\Authorization\Policy\PolicyInterface>>
	 */
	protected function findPolicy(string $scope, string $realm): void {
		$la_classes = App::classes(
			$scope,
			'Authorization/Policy/' . $realm,
			'Policy',
			PolicyInterface::class,
			null,
			['GenericDatatablesPolicy', 'GenericPagesPolicy']
		);

		/** @var class-string<\Awyiss\Authorization\Policy\PolicyInterface> $ls_className */
		foreach ($la_classes as $ls_className) {
			$ls_scope = static::sanitizeScope($ls_className::getScope());
			$this->policies[ $realm ][ $ls_scope ] ??= $ls_className;
		}

		$ls_scope = null;
		$ls_className = $scope;
		if ($ls_className !== '*') {
			$ls_scope = static::sanitizeScope($scope);
			$ls_className = Inflector::camelize($ls_scope);
		}

		/** @var class-string<\Awyiss\Model\Enum\PageRoleEnumInterface> $ls_pageRoleEnum */
		$ls_pageRoleEnum = App::className('PageRole', 'Model/Enum');
		foreach ($ls_pageRoleEnum::cases() as $le_pageRole) {
			$ls_policyScope = static::sanitizeScope($le_pageRole->name);

			if (
				// Skip if the policy is already set
				isset($this->policies[ $realm ][ $ls_policyScope ]) ||
				(
					// or if the policy scope is not the same as the provided scope
					$ls_className !== '*' &&
					$ls_policyScope !== $ls_scope
				)
			) {
				continue;
			}

			$this->policies[ $realm ][ $ls_policyScope ] = new GenericPagesPolicy($ls_policyScope);
		}


		if (!isset($this->datatables)) {
			/**
			 * Get all datatables from the database because we want them to have a generic policy too
			 *
			 * @var \Awyiss\Model\Table\DatatablesTable $lo_table
			 */
			$lo_table = FactoryLocator::get('Table')->get('Datatables');
			$this->datatables = $lo_table->findAllAndCache()->indexBy(function (Datatable $datatable) {
				return static::sanitizeScope($datatable->identifier);
			})->map(function (Datatable $datatable) {
				return new GenericDatatablesPolicy($datatable->identifier);
			})->toArray();
		}


		if ($ls_scope) {
			if (
				!isset($this->policies[ $realm ][ $ls_scope ]) &&
				isset($this->datatables[ $ls_scope ])
			) {
				$this->policies[ $realm ][ $ls_scope ] = $this->datatables[ $ls_scope ];
			}
		}
		else {
			$this->policies[ $realm ] += $this->datatables;
		}
	}


	/**
	 * Sanitize the provided scope by removing all non-ascii characters
	 * Returns an underscored string
	 *
	 * @param string $scope
	 * @return string
	 */
	public static function sanitizeScope(string $scope): string {
		$ls_scope = Text::slug($scope, '_');
		$ls_scope = Inflector::singularize($ls_scope);
		$ls_scope = Inflector::pluralize($ls_scope);


		return Inflector::underscore($ls_scope);
	}


	/**
	 * Sanitize the provided identifier by removing all non-ascii characters
	 * Returns a camelBacked string
	 *
	 * @param string $identifier
	 * @return string
	 */
	public static function sanitizeIdentifier(string $identifier): string {
		return Inflector::variable(Text::slug($identifier, '_'));
	}
}
