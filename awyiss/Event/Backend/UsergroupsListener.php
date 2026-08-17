<?php declare(strict_types=1);


namespace Awyiss\Event\Backend;


use Awyiss\Authentication\IdentityAwareTrait;
use Awyiss\Model\Entity\User;
use Awyiss\Model\Entity\Usergroup;
use Cake\Event\Event;
use Cake\Event\EventListenerInterface;
use Cake\I18n\DateTime;
use Cake\ORM\Exception\PersistenceFailedException;
use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\ORM\Query\SelectQuery;


/**
 * Event listeners for the Usergroups scope of the backend
 */
class UsergroupsListener implements EventListenerInterface {
	use LocatorAwareTrait;
	use IdentityAwareTrait;


	/**
	 * @inheritDoc
	 */
	public function implementedEvents(): array {
		return [
			'Model.Usergroups.afterSave' => 'afterSave',
		];
	}


	/**
	 * Set the `changedOn`-field for all associated users.
	 * This will allow the SessionAuthenticator to reset the usergroups for every logged-in user on the next page request
	 *
	 * @param Event $event
	 * @param \Awyiss\Model\Entity\Usergroup $entity
	 * @return void
	 * @throws \Exception
	 */
	public function afterSave(Event $event, Usergroup $entity): void {
		$usersTable = $this->fetchTable('Users');

		$query = $usersTable
			->find()
			->matching(
				'Usergroups',
				/** @uses \Awyiss\Model\Behavior\SoftDeleteBehavior::findWithDeleted() */
				fn(SelectQuery $query) => $query->find('withDeleted')->where(['Usergroups.id' => $entity->id])
			)
		;

		$users = $query->all();

		/** @var \Awyiss\Model\Entity\User $currentUser */
		$currentUser = $this->getIdentity();

		/*
		 * No users found in this group means the group was saved without
		 * any users assigned to it.
		 * That also means that the current user is surely no longer in this group
		 * and we need to unset the usergroups and permission collection
		 * for the current user in case they were in this group before.
		 */
		if (!$users->count()) {
			$currentUser->unset('usergroups');
			$currentUser->unsetPermissionCollection();

			//No records found? The item is alone in its scope.
			return;
		}

		$now = DateTime::now();
		// Decrease the system order of all records
		$users = $users
			->compile()
			->each(function (User $user) use ($entity, $now, $currentUser): void {
				$user->changedOn = $now;

				if ($user->id === $currentUser->id) {
					$currentUser->unset('usergroups');
					$currentUser->unsetPermissionCollection();
				}
			})
		;

		try {
			// Save all found records, but skip the audit and the system order behavior on those to avoid recursion.
			$usersTable->saveMany($users, [
				'audit' => ['skip' => true],
				'atomic' => false,
				'checkRules' => false,
				'nest' => ['skip' => true],
				'systemOrder' => ['skip' => true],
				'transaction' => false,
			]);
		}
		catch (PersistenceFailedException $ex) {
			$event->stopPropagation();
			$event->setResult($ex->getEntity()->getErrors());
		}
	}
}
