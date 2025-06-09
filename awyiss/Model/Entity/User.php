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
use Cake\Datasource\FactoryLocator;
use Cake\Utility\Hash;
use RuntimeException;


/**
 * User Entity
 *
 * @property int $id
 * @property string|null $username
 * @property string|null $password
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
	protected array $_accessible = [
		'username' => true,
		'password' => true,
		'firstname' => true,
		'lastname' => true,
		'email' => true,
		'active' => true,
		'usergroups' => true,
	];
	/**
	 * @inheritDoc
	 */
	protected array $_hidden = [
		'password',
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
	 * @inheritDoc
	 */
	protected static array $fieldMap = [
		'failed_attempts' => 'failedAttempts',
	];


	/**
	 * Retrieves the unique identifier of this identity
	 *
	 * @see IdentityInterface::getIdentifier
	 */
	public function getIdentifier(): ?int {
		return $this->id;
	}


	/**
	 * Retrieve the data of this identity. Required by IdentityInterface
	 *
	 * @see IdentityInterface::getOriginalData
	 */
	public function getOriginalData(): static {
		return $this;
	}


	/**
	 * Returns the PermissionCollection that contains all set permissions for this user
	 *
	 * @return PermissionCollection
	 */
	public function getPermissionCollection(): PermissionCollection {
		if (isset($this->permissionCollection)) {
			return $this->permissionCollection;
		}

		$lo_event = $this->dispatchEvent('Authorization.requestAuthorizationService', [], $this);
		/** @var ?\Awyiss\Authorization\AuthorizationService $lo_authorizationService */
		$lo_authorizationService = $lo_event->getResult();

		if (!$lo_authorizationService) {
			throw new RuntimeException(sprintf('Could not retreive `AuthorizationService` in `%s`.', static::class));
		}

		$la_usergroups = $this->getUsergroups() ?? [];

		/**
		 * This little magic trick flattens all usergroup permissions in all usergroups into one array we can iterate.
		 *
		 * $la_usergroups = [
		 *    'usergroup1' => [
		 *        ...
		 *        'usergroup_permissions' => [permission1.1, permission1.2, permission1.3, permission1.4],
		 *     ...
		 *    ],
		 *    'usergroup2' => [
		 *        ...
		 *        'usergroup_permissions' => [permission2.1, permission2.2],
		 *        ...
		 *    ],
		 *    'usergroup3' => [
		 *        ...
		 *        'usergroup_permissions' => [permission3.1, permission3.2, permission3.3],
		 *        ...
		 *    ],
		 * ];
		 *
		 * The call of array_column returns all values for 'usergroup_permissions' in all elements of $la_usergroups:
		 * [[permission1.1, permission1.2, permission1.3, permission1.4], [permission2.1, permission2.2], [permission3.1, permission3.2, permission3.3]]
		 *
		 * Calling array_merge with ... expands each child array and then flattens all.
		 * [permission1.1, permission1.2, permission1.3, permission1.4, permission2.1, permission2.2, permission3.1, permission3.2, permission3.3]
		 *
		 * This line saves one foreach. That foreach would save the comment above, though.
		 *
		 * @var UsergroupPermission $lo_usergrousPermissions
		 */
		$this->permissionCollection = new PermissionCollection($lo_authorizationService, array_merge(...array_column($la_usergroups, 'usergroup_permissions')));

		foreach (['read', 'create', 'update', 'delete'] as $ls_identifier) {
			$this->permissionCollection->add(Permission::createFromArray([
				'scope' => 'user_configuration',
				'identifier' => $ls_identifier,
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
		$lo_permissionCollection = $this->getPermissionCollection();

		return $lo_permissionCollection->scopeIsAccessible($scope, $additionalData, ...$identifier);
	}


	/**
	 * @return array
	 */
	public function getConfiguration(): array {
		if (isset($this->userConfiguration)) {
			return $this->userConfiguration;
		}

		$lo_table = FactoryLocator::get('Table')->get('UserConfiguration');
		$lo_records = $lo_table->find()->where(['user_id' => $this->id])->all();

		$this->userConfiguration = $lo_records->groupBy(function (UserConfiguration $entity) {
			return ConfigOptionsProvider::sanitizeScope($entity->scope);
		})->map(function (array $records) {
			return Hash::expand(collection($records)->indexBy(function (UserConfiguration $entity) {
				$la_identifier = array_map(function (string $identifier) {
					return ConfigOptionsProvider::sanitizeIdentifier($identifier);
				}, explode('.', $entity->identifier));


				return implode('.', $la_identifier);
			})->map(function (UserConfiguration $entity) {
				return ConfigOptionsProvider::typecastConfigValue(
					$entity->scope,
					Awyiss::REALM_BACKEND,
					$entity->identifier,
					$entity->value
				);
			})->toArray());
		})->toArray();

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

		/** @var \Awyiss\Model\Table\UsersTable $lo_table */
		$lo_table = FactoryLocator::get('Table')->get($this->getSource());

		$lo_table->loadInto($this, [
			'Usergroups' => [
				'finder' => 'active', //Only find active groups.
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
		$ls_inactive = '';

		if (key_exists('active', $this->_fields) && empty($this->active)) {
			$ls_inactive = __d('users', 'inactive') . ' ';
		}


		return $ls_inactive . $this->username;
	}


	/**
	 * Reset the permission collection when usergroups change
	 *
	 * @param array|null $usergroups
	 * @return array|null
	 * @see \Awyiss\Model\Entity\User::$usergroups
	 * @noinspection PhpUnused
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
	 * @noinspection PhpUnused
	 */
	protected function _setPassword(?string $password): ?string {
		if (empty($password)) {
			return null;
		}

		//Automatically hash passwords when they are changed.
		$lo_hasher = new DefaultPasswordHasher();


		return $lo_hasher->hash($password);
	}
}
