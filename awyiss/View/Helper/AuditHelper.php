<?php declare(strict_types=1);


namespace Awyiss\View\Helper;


use Awyiss\Model\Entity\User;
use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\View\Helper;


/**
 * AuditHelper
 */
class AuditHelper extends Helper {
	use LocatorAwareTrait;


	/**
	 * User cache
	 *
	 * @var array<int, \Awyiss\Model\Entity\User>|null
	 */
	protected static ?array $userCache = null;


	/**
	 * @param int $userId
	 * @return \Awyiss\Model\Entity\User|null
	 */
	public function getUser(int $userId): ?User {
		if (!isset(static::$userCache)) {
			$this->loadUsers();
		}

		return static::$userCache[ $userId ] ?? null;
	}


	/**
	 * @param int $userId
	 * @return string
	 */
	public function getUsername(int $userId): string {
		return $this->getUser($userId)?->username ?? __('user_unknown');
	}


	/**
	 * @return void
	 */
	protected function loadUsers(): void {
		$usersTable = $this->fetchTable('Users');
		$users = $usersTable->find()->all();

		static::$userCache = $users->indexBy('id')->toArray();
	}
}
