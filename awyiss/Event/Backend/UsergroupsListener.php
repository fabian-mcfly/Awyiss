<?php declare(strict_types=1);


namespace Awyiss\Event\Backend;


use Awyiss\Authentication\IdentityAwareTrait;
use Awyiss\Event\EventListenerTrait;
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
	use EventListenerTrait;
	use LocatorAwareTrait;
	use IdentityAwareTrait;

	/**
	 * @var string
	 */
	protected static string $scope;


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
	 * @param Event $ao_event
	 * @param \Awyiss\Model\Entity\Usergroup $ao_entity
	 * @return void
	 * @throws \Exception
	 */
	public function afterSave(Event $ao_event, Usergroup $ao_entity): void {
		$lo_usersTable = $this->fetchTable('Users');

		$lo_query = $lo_usersTable->find()->matching('Usergroups', function (SelectQuery $ao_query) use ($ao_entity) {
			return $ao_query->find('withDeleted')->where(['Usergroups.id' => $ao_entity->id]);
		});

		$lo_users = $lo_query->all();

		if (!$lo_users->count()) {
			//No records found? The item is alone in its scope.
			return;
		}

		/** @var \Awyiss\Model\Entity\User|\Awyiss\Model\Entity\UsersExternal $lo_currentUser */
		$lo_currentUser = $this->getIdentity();

		$lo_now = DateTime::now();
		//Decrease the system order of all records
		$lo_users->each(function (User $ao_user) use ($lo_now, $lo_currentUser): void {
			$ao_user->changedOn = $lo_now;

			if ($ao_user->id === $lo_currentUser->id) {
				$lo_currentUser->usergroups = null;
			}
		});

		try {
			//Save all found records, but skip the audit and the system order behavior on those to avoid recursion.
			$lo_usersTable->saveMany($lo_users, [
				'audit' => ['skip' => true],
				'atomic' => false,
				'checkRules' => false,
				'nest' => ['skip' => true],
				'systemOrder' => ['skip' => true],
				'transaction' => false,
			]);
		}
		catch (PersistenceFailedException $ex) {
			$ao_event->stopPropagation();
			$ao_event->setResult($ex->getEntity()->getErrors());
		}
	}
}
