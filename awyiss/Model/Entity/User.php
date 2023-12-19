<?php declare(strict_types=1);


namespace Awyiss\Model\Entity;


use Authentication\IdentityInterface;
use Authentication\PasswordHasher\DefaultPasswordHasher;
use Awyiss\Authorization\IdentityPermissionsInterface;
use Awyiss\Authorization\Permission\PermissionCollection;
use Awyiss\Model\Entity;
use Cake\Datasource\FactoryLocator;
use Cake\Event\Event;
use Cake\Event\EventDispatcherTrait;
use Cake\Event\EventManager;
use RuntimeException;


/**
 * User Entity
 *
 * @property int $id
 * @property string $username
 * @property string|NULL $password
 * @property int $failed_attempts
 * @property \Cake\I18n\FrozenTime|NULL $last_login
 * @property string|NULL $firstname
 * @property string|NULL $lastname
 * @property string|NULL $email
 * @property bool $active
 * @property bool $deleted
 * @property int|NULL $created_by
 * @property \Cake\I18n\FrozenTime|NULL $created_on
 * @property int|NULL $changed_by
 * @property \Cake\I18n\FrozenTime|NULL $changed_on
 * @property int|NULL $deleted_by
 * @property \Cake\I18n\FrozenTime|NULL $deleted_on
 * @property \Awyiss\Model\Entity\Usergroup[] $usergroups
 */
class User extends Entity implements IdentityPermissionsInterface, IdentityInterface {
	use EventDispatcherTrait;

	/**
	 * @inheritDoc
	 */
	protected $_accessible = [
		'username' => TRUE,
		'password' => TRUE,
		'firstname' => TRUE,
		'lastname' => TRUE,
		'email' => TRUE,
		'active' => TRUE,
		'usergroups' => TRUE,
	];
	/**
	 * @inheritDoc
	 */
	protected $_hidden = [
		'password',
	];
	protected ?PermissionCollection $permissionCollection;


	/**
	 * Retreives the unique identifier of this identity
	 *
	 * @see \Authentication\IdentityInterface::getIdentifier()
	 */
	public function getIdentifier (): ?int {
		return $this->id;
	}


	/**
	 * Retreive the data of this identity. Required by IdentityInterface
	 *
	 * @see \Authentication\IdentityInterface::getOriginalData()
	 */
	public function getOriginalData (): static {
		return $this;
	}


	/**
	 * Returns the PermissionCollection that contains all set permissions for this user
	 *
	 * @return \Awyiss\Authorization\Permission\PermissionCollection
	 */
	public function getPermissionCollection (): PermissionCollection {
		if ( ! isset($this->permissionCollection)) {
			$lo_event = $this->dispatchEvent('Authorization.requestAuthorizationService', [], $this);

			/** @var ?\Awyiss\Authorization\AuthorizationService $lo_authorizationService */
			$lo_authorizationService = $lo_event->getResult();
			if ( ! $lo_authorizationService) {
				throw new RuntimeException(sprintf('Could not retreive `AuthorizationService` in `%s`.', static::class));
			}

			$la_usergroups = $this->getUsergroups() ?? [];
			/**
			 * This little magic trick flattens all usergroup_permissions in all usergroups into one array we can iterate.
			 *
			 * $la_usergroups = [
			 * 	'usergroup1' => [
			 * 		...
			 * 		'usergroup_permissions' => [permission1.1, permission1.2, permission1.3, permission1.4],
			 * 	 ...
			 * 	],
			 * 	'usergroup2' => [
			 * 		...
			 * 		'usergroup_permissions' => [permission2.1, permission2.2],
			 * 		...
			 * 	],
			 * 	'usergroup3' => [
			 * 		...
			 * 		'usergroup_permissions' => [permission3.1, permission3.2, permission3.3],
			 * 		...
			 * 	],
			 * ];
			 *
			 * The call of array_column returns all values for 'usergroup_permissions' in all elements of $la_usergroups:
			 * [[permission1.1, permission1.2, permission1.3, permission1.4], [permission2.1, permission2.2], [permission3.1, permission3.2, permission3.3]]
			 *
			 * Calling array_merge with ... expands each child array and then flattens all.
			 * [permission1.1, permission1.2, permission1.3, permission1.4, permission2.1, permission2.2, permission3.1, permission3.2, permission3.3]
			 *
			 * This line saves one foreach. Another foreach would save the comment above, though.
			 *
			 * @var \Awyiss\Model\Entity\UsergroupPermission $lo_usergrousPermissions
			 */
			$this->permissionCollection = new PermissionCollection($lo_authorizationService, array_merge(...array_column($la_usergroups, 'usergroup_permissions')));
		}

		return $this->permissionCollection;
	}


	/**
	 * Returns an array of Usergroup-entities
	 *
	 * @return \Awyiss\Model\Entity\Usergroup[]
	 */
	public function getUsergroups (): array {
		if ( ! isset($this->usergroups)) {
			/** @var \Awyiss\Model\Table\UsergroupsUsersTable $lo_usergroupsUsers */
			$lo_usergroupsUsers = FactoryLocator::get('Table')->get('UsergroupsUsers');
			$lo_usergroupsUsers->skipAuthorizationCheckOnce();

			/** @var self $lo_self */
			$lo_self = FactoryLocator::get('Table')->get($this->getSource())->get($this->id, [
				'authorization' => ['skip' => TRUE],
				'contain' => [
					'Usergroups' => [
						'UsergroupPermissions' => [
							'finder' => ['all' => ['authorization' => ['skip' => TRUE]]],
						],
						'finder' => ['active' => ['authorization' => ['skip' => TRUE]]], //Only find active groups.
					],
				],
				'finder' => 'active',
			]);

			$this->usergroups = $lo_self->usergroups ?? [];
		}

		return $this->usergroups;
	}


	/**
	 * If the provided email is an empty string, set the email property to NULL.
	 *
	 * @noinspection PhpUnused
	 */
	protected function _setEmail (string $ax_email): ?string {
		if (empty($ax_email)) {
			return NULL;
		}

		return $ax_email;
	}


	/**
	 * If the provided password is not an empty string, hash it.
	 * Otherwise, set it to NULL
	 *
	 * @noinspection PhpUnused
	 */
	protected function _setPassword (string $as_password): ?string {
		//Automatically hash passwords when they are changed.
		if ( ! empty($as_password)) {
			$lo_hasher = new DefaultPasswordHasher();

			return $lo_hasher->hash($as_password);
		}

		return NULL;
	}


	/**
	 * When using unserialize() on a serialized instance of this entity, unset the usergroups and the permission collection
	 * so that they will be fetched from the database.
	 *
	 * This avoids having a user with outdated permissions.
	 *
	 * @return void
	 */
	public function __wakeup () {
		$this->usergroups = NULL;
		$this->permissionCollection = NULL;
	}
}
