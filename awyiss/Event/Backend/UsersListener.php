<?php declare(strict_types=1);


namespace Awyiss\Event\Backend;


use Awyiss\Model\Entity\User;
use Cake\Event\Event;
use Cake\Event\EventListenerInterface;


/**
 * Event listeners for the Users scope of the backend
 */
class UsersListener implements EventListenerInterface {
	/**
	 * @inheritDoc
	 */
	public function implementedEvents(): array {
		return [
			'Model.Users.beforeSave' => 'beforeSave',
		];
	}


	/**
	 * Clear the two-factor secret when two-factor authentication is disabled.
	 *
	 * @param \Cake\Event\Event $event
	 * @param \Awyiss\Model\Entity\User $entity
	 * @return void
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function beforeSave(Event $event, User $entity): void {
		if (!$entity->twoFactorEnabled) {
			$entity->twoFactorSecret = null;
		}
	}
}
