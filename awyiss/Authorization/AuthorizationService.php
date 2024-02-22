<?php declare(strict_types=1);


namespace Awyiss\Authorization;


use Authentication\AuthenticationServiceInterface;
use Awyiss\Authorization\Policy\AbstractGenericPolicy;
use Awyiss\Authorization\Policy\Backend\GenericDatatablesPolicy;
use Awyiss\Authorization\Policy\Backend\GenericPagesPolicy;
use Awyiss\Authorization\Policy\PolicyInterface;
use Awyiss\Core\App;
use Awyiss\Model\Entity\Datatable;
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

		$this->findPolicy('*', $ls_realm);


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
			$this->findPolicy($ls_scope, $ls_realm);
		}

		return $this->policies[ $ls_realm ][ $ls_scope ] ?? null;
	}


	/**
	 * @param string $as_scope
	 * @param string $as_realm
	 * @return array<string, \Awyiss\Authorization\Policy\AbstractGenericPolicy|class-string<\Awyiss\Authorization\Policy\PolicyInterface>>
	 * @throws \ReflectionException
	 */
	protected function findPolicy(string $as_scope, string $as_realm): void {
		$ls_scope = $as_scope;
		if ($ls_scope !== '*') {
			$ls_scope = Inflector::camelize($ls_scope);
		}

		$la_paths = [
			'\\' . CUSTOM_NAMESPACE . '\Authorization\Policy\\' . $as_realm . '\\' => implode(DS, [
				ROOT, CUSTOM_DIR,
				'Authorization',
				'Policy',
				$as_realm,
				$ls_scope . 'Policy.php',
			]),
			'\Awyiss\Authorization\Policy\\' . $as_realm . '\\' => implode(DS, [ROOT, APP_DIR, 'Authorization', 'Policy', $as_realm, $ls_scope . 'Policy.php']),
		];

		foreach ($la_paths as $ls_namespace => $ls_path) {
			foreach (glob($ls_path) as $ls_filePath) {
				$ls_policyName = substr($ls_filePath, strrpos($ls_filePath, DS) + 1, -4);
				if ($ls_scope === '*' && in_array($ls_policyName, ['GenericDatatablesPolicy', 'GenericPagesPolicy'])) {
					continue;
				}

				$ls_policyClass = $ls_namespace . $ls_policyName;
				/** @var PolicyInterface $ls_policyClass */
				$ls_policyScope = $ls_policyClass::getScope();

				if (isset($this->policies[ $as_realm ][ $ls_policyScope ])) {
					continue;
				}

				$lo_reflection = new ReflectionClass($ls_policyClass);

				if (!$lo_reflection->implementsInterface(PolicyInterface::class)) {
					throw new RuntimeException(sprintf('The provided Policy class `%s` does not implement the `%s` interface.', $ls_policyClass, PolicyInterface::class));
				}

				$this->policies[ $as_realm ][ $ls_policyScope ] = $ls_policyClass;
			}
		}


		/** @var class-string<\Awyiss\Model\Enum\PageRoleEnumInterface> $ls_pageRoleEnum */
		$ls_pageRoleEnum = App::className('PageRole', 'Model/Enum');
		foreach ($ls_pageRoleEnum::cases() as $le_pageRole) {
			$ls_policyScope = static::sanitizeScope($le_pageRole->name);

			if (isset($this->policies[ $as_realm ][ $ls_policyScope ])) {
				continue;
			}

			$this->policies[ $as_realm ][ $ls_policyScope ] = new GenericPagesPolicy($ls_policyScope);
		}


		if (!isset($this->datatables)) {
			//Get all datatables from the database because we want them to have a generic policy too
			/** @var \Awyiss\Model\Table\DatatablesTable $lo_table */
			$lo_table = FactoryLocator::get('Table')->get('Datatables');
			$this->datatables = $lo_table->findAllAndCache()->reject(function (Datatable $ao_datatable) {
				return $ao_datatable->active === false;
			})->indexBy(function (Datatable $ao_datatable) {
				return static::sanitizeScope($ao_datatable->identifier);
			})->filter(function (Datatable $ao_datatable) {
				/** @var \Awyiss\Model\Table $lo_datatableTable */
				$lo_datatableTable = FactoryLocator::get('Table')->get(Inflector::camelize($ao_datatable->identifier));
				return $lo_datatableTable::ATTRIBUTABLE;
			})->map(function (Datatable $ao_datatable) {
				return new GenericDatatablesPolicy($ao_datatable->identifier);
			})->toArray();

			$this->policies[ $as_realm ] += $this->datatables;
		}
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
