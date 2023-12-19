<?php declare(strict_types=1);


namespace Awyiss\Model\Entity;


use Authentication\IdentityInterface;
use Authentication\PasswordHasher\DefaultPasswordHasher;
use Awyiss\Authorization\AccessCollection;
use Awyiss\Authorization\IdentityPermissionsInterface;


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
	/**
	 * Fields that can be mass assigned using newEntity() or patchEntity().
	 *
	 * Note that when '*' is set to true, this allows all unspecified fields to
	 * be mass assigned. For security purposes, it is advised to set '*' to false
	 * (or remove it), and explicitly make individual fields accessible as needed.
	 *
	 * @var array
	 */
	protected $_accessible = [
		'username' => TRUE,
		'password' => TRUE,
		'firstname' => TRUE,
		'lastname' => TRUE,
		'email' => TRUE,
		'usergroups' => TRUE,
	];
	/**
	 * Fields that are excluded from JSON versions of the entity.
	 *
	 * @var array
	 */
	protected $_hidden = [
		'password',
	];
	private ?AccessCollection $lo_accesses = NULL;


	/**
	 * Authentication\IdentityInterface method
	 */
	public function getIdentifier () {
		return $this->id;
	}


	/**
	 * Authentication\IdentityInterface method
	 */
	public function getOriginalData () {
		return $this;
	}


	public function getUsergroups (): ?array {
		//if (!$this->usergroups)  {
		if ($this->usergroups === NULL)  {
			/** @var self $lo_self */
			$lo_self = \Cake\Datasource\FactoryLocator::get('Table')->get($this->getSource())->get($this->id, ['contain' => ['Usergroups.UsergroupsPermissions']]);
			$this->usergroups = $lo_self->usergroups;
		}

		return $this->usergroups;
	}


	public function getAccess (): AccessCollection {
		$this->lo_accesses = NULL;
		if ($this->lo_accesses === NULL) {
			$this->lo_accesses = new AccessCollection();

			$la_usergroups = $this->getUsergroups();
			/** @var \Awyiss\Model\Entity\UsergroupsPermission $lo_usergrousPermissions */
			foreach (array_merge(...array_column($la_usergroups, 'usergroups_permissions')) as $lo_usergrousPermissions) {
				$this->lo_accesses->add($lo_usergrousPermissions->scope, $lo_usergrousPermissions->identifier, $lo_usergrousPermissions->access, $lo_usergrousPermissions->settings);
			}
		}

		return $this->lo_accesses;
	}


	protected function _setEmail ($value) {
		if ($value === '') {
			$value = NULL;
		}

		return $value;
	}


	// Automatically hash passwords when they are changed.
	protected function _setPassword (string $as_password) {
		if (!empty($as_password)) {
			$lo_hasher = new DefaultPasswordHasher();

			return $lo_hasher->hash($as_password);
		}
	}
}
