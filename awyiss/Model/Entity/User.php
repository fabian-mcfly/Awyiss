<?php declare(strict_types=1);


namespace Awyiss\Model\Entity;


use Authentication\IdentityInterface;
use Authentication\PasswordHasher\DefaultPasswordHasher;
use Awyiss\Authorization\IdentityPermissionsInterface;
use Awyiss\Authorization\Permission\PermissionCollection;
use Awyiss\Model\Entity;
use Cake\Datasource\FactoryLocator;
use Cake\Event\EventDispatcherTrait;
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
	 * @var \Awyiss\Authorization\Permission\PermissionCollection|null
	 */
	protected ?PermissionCollection $permissionCollection;
	/**
	 * @inheritDoc
	 */
	protected static array $fieldMap = [
		'failed_attempts' => 'failedAttempts',
		'last_login' => 'lastLogin',
		'created_by' => 'createdBy',
		'created_on' => 'createdOn',
		'changed_by' => 'changedBy',
		'changed_on' => 'changedOn',
		'deleted_by' => 'deletedBy',
		'deleted_on' => 'deletedOn',
	];


	/**
	 * Retreives the unique identifier of this identity
	 *
	 * @see IdentityInterface::getIdentifier
	 */
	public function getIdentifier(): ?int {
		return $this->id;
	}


	/**
	 * Retreive the data of this identity. Required by IdentityInterface
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
		if (!isset($this->permissionCollection)) {
			$lo_event = $this->dispatchEvent('Authorization.requestAuthorizationService', [], $this);
			/** @var ?\Awyiss\Authorization\AuthorizationService $lo_authorizationService */
			$lo_authorizationService = $lo_event->getResult();

			if (!$lo_authorizationService) {
				throw new RuntimeException(sprintf('Could not retreive `AuthorizationService` in `%s`.', static::class));
			}

			$la_usergroups = $this->getUsergroups() ?? [];

			/**
			 * This little magic trick flattens all usergroup_permissions in all usergroups into one array we can iterate.
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
		}


		return $this->permissionCollection;
	}


	/**
	 * @inheritDoc
	 */
	public function scopeIsAccessible(string $as_scope, array $aa_additionalData = [], array|string ...$ax_identifier): bool {
		$lo_permissionCollection = $this->getPermissionCollection();


		return $lo_permissionCollection->scopeIsAccessible($as_scope, $aa_additionalData, ...$ax_identifier);
	}


	/**
	 * Returns an array of Usergroup-entities
	 *
	 * @return array<Usergroup>
	 */
	public function getUsergroups(): array {
		if (!isset($this->usergroups)) {
			/** @var \Awyiss\Model\Table\UsersTable $lo_table */
			$lo_table = FactoryLocator::get('Table')->get($this->getSource());

			$lo_table->loadInto($this, [
				'Usergroups' => [
					'finder' => 'active', //Only find active groups.
					'UsergroupPermissions',
				],
			]);
		}


		return $this->usergroups;
	}


	/**
	 * Reset the permission collection when usergroups change
	 *
	 * @param array|null $aa_usergroups
	 * @return array|null
	 * @see \Awyiss\Model\Entity\User::$usergroups
	 */
	protected function _setUsergroups(?array $aa_usergroups): ?array {
		unset($this->permissionCollection);


		return $aa_usergroups;
	}


	/**
	 * If the provided password is not an empty string, hash it.
	 * Otherwise, set it to null
	 *
	 * @param string|null $as_password
	 * @return string|null
	 * @see \Awyiss\Model\Entity\User::$password
	 */
	protected function _setPassword(?string $as_password): ?string {
		if (empty($as_password)) {
			return null;
		}

		//Automatically hash passwords when they are changed.
		$lo_hasher = new DefaultPasswordHasher();


		return $lo_hasher->hash($as_password);
	}
}
