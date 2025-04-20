<?php declare(strict_types=1);


namespace Awyiss\Controller\Component;


use Awyiss\Authentication\IdentityAwareTrait;
use Awyiss\Model\Entity\Lock;
use Awyiss\Model\Table\LocksTable;
use Cake\Controller\Component;
use Cake\Controller\ComponentRegistry;
use Cake\Core\Configure;
use Cake\I18n\DateTime;
use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\Utility\Hash;
use Cake\Utility\Text;


/**
 * LockComponent
 * This component is used to manage locks in the application.
 * Each opened edit session is stored in the database and can be used to prevent
 * multiple users from editing the same entity at the same time.
 * After a configured timeout, the lock is automatically released.
 * Users can also ask for a lock to be released.
 */
class LockComponent extends Component {
	use IdentityAwareTrait;
	use LocatorAwareTrait;


	/**
	 * @inheritDoc
	 * @var array<string, mixed>
	 */
	protected array $_defaultConfig = [
		'autoload' => ['edit'], //can be a boolean value or an array containing all action names for which the locks should get set automatically
		'enabled' => true,
		'urlParam' => 'id', //the url parameter that contains the id of the entity
		'tableName' => null,
		'timeout' => 120, //timeout in seconds
	];
	/**
	 * @var \Awyiss\Model\Table\LocksTable
	 */
	protected LocksTable $locksTable;


	/**
	 * @inheritDoc
	 */
	public function __construct(ComponentRegistry $registry, array $config = []) {
		// Merge the default config with the config from the system
		$this->_defaultConfig = Hash::merge($this->_defaultConfig, Configure::read('Awyiss.System.Backend.lock', []));

		parent::__construct($registry, $config);
	}


	/**
	 * Called after `Controller::beforeFilter()` method, and before the controller action is called.
	 *
	 * @return void
	 */
	public function startup(): void {
		if ($this->getConfig('tableName') === null) {
			$this->setConfig('tableName', $this->getController()->getName());
		}

		/** @noinspection PhpFieldAssignmentTypeMismatchInspection */
		$this->locksTable = $this->fetchTable('Locks');

		$lo_session = $this->getController()->getRequest()->getSession();
		if (!$lo_session->read('Backend.lockIdentifier')) {
			$lo_session->write('Backend.lockIdentifier', Text::uuid());
		}
	}


	/**
	 * @return void
	 */
	public function beforeRender(): void {
		if (!$this->getConfig('enabled')) {
			return;
		}

		$lo_controller = $this->getController();
		$ls_action = $lo_controller->getRequest()->getParam('action');
		$lx_autoload = $this->getConfig('autoload');

		// Delete locks that timed out
		$this->deleteTimedOutLocks();

		//Shall we autoload the records?
		if (
			$lx_autoload === false ||
			(
				$lx_autoload !== true &&
				(
					!is_array($lx_autoload) ||
					!in_array($ls_action, $lx_autoload)
				) &&
				(
					!is_string($lx_autoload) ||
					$ls_action !== $lx_autoload
				)
			)
		) {
			return;
		}

		$li_id = (int)$lo_controller->getRequest()->getQuery($this->getConfig('urlParam'));

		if (!$li_id) {
			return;
		}

		$lo_lock = $this->createLock($li_id);

		$lo_controller->set('_lock', [
			'controller' => Text::slug($lo_controller->getName()),
			'entityId' => $li_id,
			'lock' => $lo_lock,
			'lockedUntil' => $lo_lock->createdOn->modify('+' . $this->getConfig('timeout') . ' seconds'),
			'isOwnLock' => $this->isOwnLock($lo_lock),
		]);
	}


	/**
	 * @param \Awyiss\Model\Entity\Lock $lock
	 * @return bool
	 */
	public function isOwnLock(Lock $lock): bool {
		return $lock->isOwnLock();
	}


	/**
	 * Creates a lock for the current user and the current entity.
	 *
	 * @param int $id
	 * @return \Awyiss\Model\Entity\Lock|false
	 */
	public function createLock(int $id): Lock|false {
		if (!$this->getConfig('enabled')) {
			return false;
		}

		$lo_lock = $this->findLock($id);

		if (!$lo_lock) {
			$lo_lock = $this->locksTable->newDefaultEntity();

			$this->locksTable->patchEntity($lo_lock, [
				'scope' => $this->getConfig('tableName'),
				'foreign_key' => $id,
				'unique_id' => $this->getController()->getRequest()->getSession()->read('Backend.lockIdentifier'),
			]);

			if ($this->locksTable->save($lo_lock)) {
				return $lo_lock;
			}

			return false;
		}

		// Update the lock if it is older than the timeout
		if ($this->isOwnLock($lo_lock)) {
			$lo_lock->set('createdOn', new DateTime());

			if (!$this->locksTable->save($lo_lock)) {
				return false;
			}
		}

		return $lo_lock;
	}


	/**
	 * @param int $id
	 * @param string|null $lockedUntil
	 * @return \Awyiss\Model\Entity\Lock|bool
	 */
	public function releaseLock(int $id, ?string $lockedUntil): Lock|bool {
		if (!$this->getConfig('enabled')) {
			return false;
		}

		$lo_lock = $this->findLock($id, true, $lockedUntil);

		if (!$lo_lock) {
			return false;
		}

		if ($this->locksTable->delete($lo_lock)) {
			return true;
		}

		return $lo_lock;
	}


	/**
	 * Return the ID of the currently set identity
	 *
	 * @return ?int
	 */
	protected function getIdentityId(): ?int {
		$lo_identity = $this->getIdentity();

		return $lo_identity?->getIdentifier();
	}


	/**
	 * Finds a lock for the given entity ID in the current scope
	 *
	 * If $ownLock is set to true, only locks created by the current user are returned.
	 * If $ownLock is set to false, only locks created by other users are returned.
	 * If $ownLock is null, all locks are returned.
	 *
	 * @param int $id
	 * @param bool|null $ownLock
	 * @param string|null $createdOn
	 * @return \Awyiss\Model\Entity\Lock|null
	 */
	public function findLock(int $id, ?bool $ownLock = null, ?string $createdOn = null): ?Lock {
		$lo_controller = $this->getController();
		$lo_session = $lo_controller->getRequest()->getSession();

		$la_where = [
			'scope' => $this->getConfig('tableName'),
			'foreign_key' => $id,
		];

		if ($ownLock !== null) {
			$la_where['unique_id' . ($ownLock ? '' : ' !=') ] = $lo_session->read('Backend.lockIdentifier');
			$la_where['created_by' . ($ownLock ? '' : ' !=') ] = $this->getIdentityId();
		}

		if ($createdOn) {
			$la_where['created_on <='] = $createdOn;
		}


		return $this->locksTable->find()->where($la_where)->contain(['CreatedByUser'])->first();
	}


	/**
	 * @return void
	 */
	protected function deleteTimedOutLocks(): void {
		$this->locksTable->deleteAll([
			'created_on <' => (new DateTime())->modify('-' . $this->getConfig('timeout') . ' seconds'),
		]);
	}
}
