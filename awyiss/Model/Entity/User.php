<?php declare(strict_types=1);


namespace Awyiss\Model\Entity;


use Authentication\IdentityInterface;
use Authentication\PasswordHasher\DefaultPasswordHasher;
use Awyiss\Authorization\AccessCollection;
use Awyiss\Authorization\IdentityPermissionsInterface;
use Cake\Datasource\FactoryLocator;


/**
 * User Entity
 *
 * @property int $id
 * @property string $username
 * @property string|null $password
 * @property int $failed_attempts
 * @property \Cake\I18n\FrozenTime|null $last_login
 * @property string|null $firstname
 * @property string|null $lastname
 * @property string|null $email
 * @property bool $active
 * @property bool $deleted
 * @property int|null $created_by
 * @property \Cake\I18n\FrozenTime|null $created_on
 * @property int|null $changed_by
 * @property \Cake\I18n\FrozenTime|null $changed_on
 * @property int|null $deleted_by
 * @property \Cake\I18n\FrozenTime|null $deleted_on
 * @property \Awyiss\Model\Entity\Usergroup[] $usergroups
 */
class User extends \Awyiss\Model\Entity implements IdentityPermissionsInterface, IdentityInterface {
	use \Cake\ORM\Locator\LocatorAwareTrait;


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
	protected ?AccessCollection $accesCollection;


	/**
	 * Authentication\IdentityInterface method
	 */
	public function getIdentifier (): ?int {
		return $this->id;
	}


	/**
	 * Authentication\IdentityInterface method
	 */
	public function getOriginalData (): self {
		return $this;
	}


	/**
	 * Returns the AccessCollection that contains all set permissions for this user
	 *
	 * @return \Awyiss\Authorization\AccessCollection
	 */
	public function getAccess (): AccessCollection {
		if ( ! isset($this->accesCollection)) {
			$this->accesCollection = new AccessCollection();
			$la_usergroups = $this->getUsergroups() ?? [];
			/** @var \Awyiss\Model\Entity\UsergroupPermission $lo_usergrousPermissions */
			foreach (array_merge(...array_column($la_usergroups, 'usergroup_permissions')) as $lo_usergrousPermissions) {
				$this->accesCollection->add($lo_usergrousPermissions->scope, $lo_usergrousPermissions->identifier, $lo_usergrousPermissions->access, $lo_usergrousPermissions->settings);
			}
		}

		return $this->accesCollection;
	}


	/**
	 * Returns an array of Usergroup-entities
	 *
	 * @return \Awyiss\Model\Entity\Usergroup[]
	 */
	public function getUsergroups (): array {
		//if (!$this->usergroups)  {
		if ( ! isset($this->usergroups)) {
			/** @var \Awyiss\Model\Table\UsergroupsUsersTable $lo_usergroupsUsers */
			$lo_usergroupsUsers = FactoryLocator::get('Table')->get('UsergroupsUsers');
			$lo_usergroupsUsers->skipAccessCheckOnce();

			/** @var self $lo_self */
			$lo_self = FactoryLocator::get('Table')->get($this->getSource())->get($this->id, [
				'contain' => [
					'Usergroups' => [
						'UsergroupPermissions' => [
							'finder' => ['all' => ['access' => ['skip' => TRUE]]],
						],
						'finder' => ['all' => ['access' => ['skip' => TRUE]]],
					],
				],
				'access' => ['skip' => TRUE],
			]);

			$this->usergroups = $lo_self->usergroups ?? [];
		}

		return $this->usergroups;
	}


	/**
	 * @noinspection PhpUnused
	 */
	protected function _setEmail (string $ax_email): ?string {
		if (empty($ax_email)) {
			return NULL;
		}

		return $ax_email;
	}


	/**
	 * @noinspection PhpUnused
	 */
	protected function _setPassword (string $as_password): ?string {
		// Automatically hash passwords when they are changed.
		if ( ! empty($as_password)) {
			$lo_hasher = new DefaultPasswordHasher();

			return $lo_hasher->hash($as_password);
		}

		return NULL;
	}


	public function __wakeup () {
		/*if ($this->get('last_login') < \Cake\I18n\FrozenTime::now()->subMinutes(2)) {
			$this->usergroups = NULL;
		}*/
		$this->usergroups = NULL;
		$this->accesCollection = NULL;
	}
}
