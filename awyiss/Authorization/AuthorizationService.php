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
		$realm = $realm ?: $this->realm;

		$this->findPolicy('*', $realm);


		return $this->policies[ $realm ] ?? [];
	}


	/**
	 * @inheritDoc
	 */
	public function getPolicy(string $scope, ?string $realm = null): AbstractGenericPolicy|string|null {
		$realm = $realm ?: $this->realm;
		$scope = static::sanitizeScope($scope);

		if (!isset($this->policies[ $realm ])) {
			$this->policies[ $realm ] = [];
		}

		if (empty($this->policies[ $realm ][ $scope ])) {
			$this->findPolicy($scope, $realm);
		}

		return $this->policies[ $realm ][ $scope ] ?? null;
	}


	/**
	 * @param string $scope
	 * @param string $realm
	 * @return void
	 */
	protected function findPolicy(string $scope, string $realm): void {
		$classes = App::classes(
			$scope,
			'Authorization/Policy/' . $realm,
			'Policy',
			PolicyInterface::class,
			null,
			['GenericDatatablesPolicy', 'GenericPagesPolicy']
		);

		/** @var class-string<\Awyiss\Authorization\Policy\PolicyInterface> $className */
		foreach ($classes as $className) {
			$classScope = static::sanitizeScope($className::getScope());
			$this->policies[ $realm ][ $classScope ] ??= $className;
		}

		$classScope = null;
		$className = $scope;
		if ($className !== '*') {
			$classScope = static::sanitizeScope($scope);
			$className = Inflector::camelize($classScope);
		}

		/** @var class-string<\Awyiss\Model\Enum\PageRoleEnumInterface> $pageRoleEnum */
		$pageRoleEnum = App::className('PageRole', 'Model/Enum');
		foreach ($pageRoleEnum::cases() as $pageRole) {
			$policyScope = static::sanitizeScope($pageRole->name);

			if (
				// Skip if the policy is already set
				isset($this->policies[ $realm ][ $policyScope ]) ||
				(
					// or if the policy scope is not the same as the provided scope
					$className !== '*' &&
					$policyScope !== $classScope
				)
			) {
				continue;
			}

			$this->policies[ $realm ][ $policyScope ] = new GenericPagesPolicy($policyScope);
		}


		if (!isset($this->datatables)) {
			/**
			 * Get all datatables from the database because we want them to have a generic policy too
			 *
			 * @var \Awyiss\Model\Table\DatatablesTable $table
			 */
			$table = FactoryLocator::get('Table')->get('Datatables');
			$this->datatables = $table->findAllAndCache()->indexBy(function (Datatable $datatable) {
				return static::sanitizeScope($datatable->identifier);
			})->map(function (Datatable $datatable) {
				return new GenericDatatablesPolicy($datatable->identifier);
			})->toArray();
		}


		if ($classScope) {
			if (
				!isset($this->policies[ $realm ][ $classScope ]) &&
				isset($this->datatables[ $classScope ])
			) {
				$this->policies[ $realm ][ $classScope ] = $this->datatables[ $classScope ];
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
		$scope = Text::slug($scope, '_');
		$scope = Inflector::singularize($scope);
		$scope = Inflector::pluralize($scope);


		return Inflector::underscore($scope);
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
