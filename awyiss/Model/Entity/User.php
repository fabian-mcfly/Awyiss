<?php declare(strict_types=1);


namespace Awyiss\Model\Entity;


use Authentication\IdentityInterface;
use Authentication\PasswordHasher\DefaultPasswordHasher;
use Awyiss\Authorization\IdentityPermissionsInterface;
use Awyiss\Authorization\Permission\Permission;
use Awyiss\Authorization\Permission\PermissionAccess;
use Awyiss\Authorization\Permission\PermissionCollection;
use Awyiss\Awyiss;
use Awyiss\Configuration\ConfigOptionsProvider;
use Awyiss\Event\EventDispatcherTrait;
use Awyiss\Model\Entity;
use Awyiss\Routing\Router;
use Cake\Core\Configure;
use Cake\Datasource\FactoryLocator;
use Cake\Utility\Hash;
use Cake\Utility\Security;
use RuntimeException;


/**
 * User Entity
 *
 * @property int $id
 * @property string|null $username
 * @property string|null $password
 * @property bool $twoFactorEnabled
 * @property string|null $twoFactorSecret
 * @property int $failedAttempts
 * @property \Cake\I18n\DateTime|null $lastLogin
 * @property string|null $firstname
 * @property string|null $lastname
 * @property string|null $email
 * @property bool $active
 * @property bool $deleted
 * @property int|null $createdBy
 * @property \Cake\I18n\DateTime|null $createdOn
 * @property int|null $changedBy
 * @property \Cake\I18n\DateTime|null $changedOn
 * @property int|null $deletedBy
 * @property \Cake\I18n\DateTime|null $deletedOn
 * @property \Awyiss\Model\Entity\Usergroup[] $usergroups
 */
class User extends Entity implements IdentityPermissionsInterface, IdentityInterface {
	use EventDispatcherTrait;


	/**
	 * @inheritDoc
	 */
	protected array $_accessible = [ // phpcs:ignore
		'username' => true,
		'password' => true,
		'firstname' => true,
		'lastname' => true,
		'email' => true,
		'active' => true,
		'twoFactorEnabled' => true,
		'usergroups' => true,
	];
	/**
	 * @inheritDoc
	 */
	protected array $_hidden = [ // phpcs:ignore
		'password',
		'twoFactorSecret',
	];
	/**
	 * @var array|null
	 */
	protected ?array $userConfiguration = null;
	/**
	 * @var \Awyiss\Authorization\Permission\PermissionCollection|null
	 */
	protected ?PermissionCollection $permissionCollection = null;


	/**
	 * Retrieves the unique identifier of this identity
	 *
	 * @see \Authentication\IdentityInterface::getIdentifier
	 */
	public function getIdentifier(): ?int {
		return $this->id;
	}


	/**
	 * Retrieve the data of this identity. Required by IdentityInterface
	 *
	 * @see \Authentication\IdentityInterface::getOriginalData
	 */
	public function getOriginalData(): static {
		return $this;
	}


	/**
	 * Returns the PermissionCollection that contains all set permissions for this user
	 *
	 * @return \Awyiss\Authorization\Permission\PermissionCollection
	 */
	public function getPermissionCollection(): PermissionCollection {
		if (isset($this->permissionCollection)) {
			return $this->permissionCollection;
		}

		$authorizationService = Router::getRequest()->getAttribute('authorization');

		if (!$authorizationService) {
			throw new RuntimeException(sprintf('Could not retreive `AuthorizationService` in `%s`.', static::class));
		}

		$usergroups = $this->getUsergroups() ?? [];

		/**
		 * This little magic trick flattens all usergroup permissions in all usergroups into one array we can iterate.
		 *
		 * $usergroups = [
		 *    'usergroup1' => [
		 *        ...
		 *        'usergroupPermissions' => [permission1.1, permission1.2, permission1.3, permission1.4],
		 *     ...
		 *    ],
		 *    'usergroup2' => [
		 *        ...
		 *        'usergroupPermissions' => [permission2.1, permission2.2],
		 *        ...
		 *    ],
		 *    'usergroup3' => [
		 *        ...
		 *        'usergroupPermissions' => [permission3.1, permission3.2],
		 *        ...
		 *    ],
		 * ];
		 *
		 * The call of array_column returns all values for 'usergroupPermissions' in all elements of $usergroups:
		 * [[permission1.1, permission1.2, permission1.3, permission1.4], [permission2.1, permission2.2], [permission3.1, permission3.2]]
		 *
		 * Calling array_merge with ... expands each child array and then flattens all.
		 * [permission1.1, permission1.2, permission1.3, permission1.4, permission2.1, permission2.2, permission3.1, permission3.2]
		 *
		 * This line saves one foreach. That foreach would save the comment above, though.
		 *
		 * @noinspection GrazieInspection
		 */
		$this->permissionCollection = new PermissionCollection(
			$authorizationService,
			array_merge(...array_column($usergroups, 'usergroupPermissions'))
		);

		foreach (['read', 'create', 'update', 'delete'] as $identifier) {
			$this->permissionCollection->add(Permission::createFromArray([
				'scope' => 'UserConfiguration',
				'identifier' => $identifier,
				'access' => PermissionAccess::Granted,
			]));
		}

		return $this->permissionCollection;
	}


	/**
	 * @return $this
	 */
	public function unsetPermissionCollection(): static {
		$this->permissionCollection = null;

		return $this;
	}


	/**
	 * @inheritDoc
	 */
	public function scopeIsAccessible(string $scope, array $additionalData = [], array|string ...$identifier): bool {
		$permissionCollection = $this->getPermissionCollection();

		return $permissionCollection->scopeIsAccessible($scope, $additionalData, ...$identifier);
	}


	/**
	 * @return array
	 */
	public function getConfiguration(): array {
		if (isset($this->userConfiguration)) {
			return $this->userConfiguration;
		}

		$table = FactoryLocator::get('Table')->get('UserConfiguration');
		$records = $table
			->find()
			->where(['userId' => $this->id])
			->all()
		;

		$this->userConfiguration = $records
			->groupBy(function (UserConfiguration $entity) {
				return ConfigOptionsProvider::sanitizeScope($entity->scope);
			})
			->map(function (array $records) {
				return Hash::expand(
					collection($records)
						->indexBy(function (UserConfiguration $entity) {
							$identifiers = array_map(function (string $identifier) {
								return ConfigOptionsProvider::sanitizeIdentifier($identifier);
							}, explode('.', $entity->identifier));


							return implode('.', $identifiers);
						})
						->map(function (UserConfiguration $entity) {
							return ConfigOptionsProvider::typecastConfigValue(
								$entity->scope,
								Awyiss::REALM_BACKEND,
								$entity->identifier,
								$entity->value
							);
						})
						->toArray()
				);
			})
			->toArray()
		;

		return $this->userConfiguration;
	}


	/**
	 * @return void
	 */
	public function resetConfiguration(): void {
		$this->userConfiguration = null;
	}


	/**
	 * Returns an array of Usergroup-entities
	 *
	 * @return array<Usergroup>
	 */
	public function getUsergroups(): array {
		if (isset($this->usergroups)) {
			return $this->usergroups;
		}

		/** @var \Awyiss\Model\Table\UsersTable $table */
		$table = FactoryLocator::get('Table')->get($this->getSource());

		$table->loadInto($this, [
			'Usergroups' => [
				//Only find active groups.
				'finder' => 'active',
				'UsergroupPermissions',
			],
		]);

		return $this->usergroups;
	}


	/**
	 * Returns the username of the user,
	 * prefixed with 'inactive' if the user is not active
	 *
	 * @return string
	 */
	protected function _getLabel(): string {
		$inactive = '';

		if (key_exists('active', $this->_fields) && empty($this->active)) {
			$inactive = __d('Users', 'inactive') . ' ';
		}


		return $inactive . $this->username;
	}


	/**
	 * Reset the permission collection when usergroups change
	 *
	 * @param array|null $usergroups
	 * @return array|null
	 * @see \Awyiss\Model\Entity\User::$usergroups
	 */
	protected function _setUsergroups(?array $usergroups): ?array {
		$this->permissionCollection = null;


		return $usergroups;
	}


	/**
	 * If the provided password is not an empty string, hash it.
	 * Otherwise, set it to null
	 *
	 * @param string|null $password
	 * @return string|null
	 * @see \Awyiss\Model\Entity\User::$password
	 */
	protected function _setPassword(?string $password): ?string {
		if (empty($password)) {
			return null;
		}

		$passwordHasher = new DefaultPasswordHasher();
		$passwordHasher->setConfig('hashOptions', [
			'cost' => 14,
		]);

		if (Configure::read('Security.prehashPassword', false) && Security::getSalt()) {
			$password = hash_hmac('sha256', $password, Security::getSalt());
		}

		// Automatically hash passwords when they are changed.
		return $passwordHasher->hash($password);
	}
}
