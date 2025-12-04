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
	protected array $_defaultConfig = [ // phpcs:ignore
		'autoload' => ['edit'], //can be a boolean value or an array containing all action names for which the locks should get set automatically
		'enabled' => true,
		'urlParam' => 'id', //the url parameter that contains the id of the entity
		'tableName' => null,
		'timeout' => 1200, //timeout in seconds
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

		$this->locksTable = $this->fetchTable('Locks');

		$session = $this->getController()->getRequest()->getSession();
		if (!$session->read('Backend.lockIdentifier')) {
			$session->write('Backend.lockIdentifier', Text::uuid());
		}
	}


	/**
	 * @return void
	 */
	public function beforeRender(): void {
		// Delete locks that timed out
		$this->deleteTimedOutLocks();

		$controller = $this->getController();
		$action = $controller->getRequest()->getParam('action');
		$autoload = $this->getConfig('autoload');

		//Shall we autoload the records?
		if (
			$autoload === false ||
			(
				$autoload !== true &&
				(
					!is_array($autoload) ||
					!in_array($action, $autoload)
				) &&
				(
					!is_string($autoload) ||
					$action !== $autoload
				)
			)
		) {
			return;
		}

		$id = (int)$controller->getRequest()->getQuery($this->getConfig('urlParam'));

		if (!$id) {
			return;
		}

		$lock = $this->createLock($id);

		if (!$lock) {
			return;
		}

		$controller->set('_lock', [
			'controller' => Text::slug($controller->getName()),
			'entityId' => $id,
			'lock' => $lock,
			'lockedUntil' => $lock->createdOn->modify('+' . $this->getConfig('timeout') . ' seconds'),
			'isOwnLock' => $this->isOwnLock($lock),
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
		// Delete locks that timed out
		$this->deleteTimedOutLocks();

		$lock = $this->findLock($id);

		if ($this->getConfig('enabled') && !$lock) {
			$lock = $this->locksTable->newDefaultEntity();

			$this->locksTable->patchEntity($lock, [
				'scope' => $this->getConfig('tableName'),
				'foreignKey' => $id,
				'uniqueId' => $this->getController()->getRequest()->getSession()->read('Backend.lockIdentifier'),
				'createdOn' => new DateTime(),
				'createdBy' => $this->getIdentityId(),
			], ['accessibleFields' => ['createdOn', 'createdBy']]);

			if ($this->locksTable->save($lock)) {
				return $lock;
			}

			return false;
		}

		// Update the lock if it is older than the timeout
		if ($lock && $this->isOwnLock($lock)) {
			$lock->set('createdOn', new DateTime());

			if (!$this->locksTable->save($lock)) {
				return false;
			}
		}

		return $lock ?? false;
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

		$lock = $this->findLock($id, true, $lockedUntil);

		if (!$lock) {
			return false;
		}

		if ($this->locksTable->delete($lock)) {
			return true;
		}

		return $lock;
	}


	/**
	 * Return the ID of the currently set identity
	 *
	 * @return ?int
	 */
	protected function getIdentityId(): ?int {
		return $this->getIdentity()?->getIdentifier();
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
		$controller = $this->getController();
		$session = $controller->getRequest()->getSession();

		$where = [
			'scope' => $this->getConfig('tableName'),
			'foreign_key' => $id,
		];

		if ($ownLock !== null) {
			$where['created_by' . ($ownLock ? '' : ' !=') ] = $this->getIdentityId();

			$sessionBased = Configure::read('Awyiss.System.Backend.lock.sessionBased', true);
			if ($sessionBased) {
				$where[ 'unique_id' . ($ownLock ? '' : ' !=') ] = $session->read('Backend.lockIdentifier');
			}
		}

		if ($createdOn) {
			$where['created_on <='] = $createdOn;
		}

		return $this->locksTable->find()->where($where)->contain(['CreatedByUser'])->first();
	}


	/**
	 * @return void
	 */
	protected function deleteTimedOutLocks(): void {
		$this->locksTable->deleteAll([
			'created_on <' => new DateTime()->modify('-' . $this->getConfig('timeout') . ' seconds'),
		]);
	}
}
