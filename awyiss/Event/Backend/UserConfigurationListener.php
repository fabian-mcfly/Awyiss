<?php declare(strict_types=1);


namespace Awyiss\Event\Backend;


use Awyiss\Authentication\IdentityAwareTrait;
use Awyiss\Awyiss;
use Awyiss\Configuration\ConfigOptionsProvider;
use Awyiss\Model\Entity\UserConfiguration;
use Cake\Event\Event;
use Cake\Event\EventListenerInterface;


/**
 * Event listeners for the Configuration scope of the backend
 */
class UserConfigurationListener implements EventListenerInterface {
	use IdentityAwareTrait;


	/**
	 * @inheritDoc
	 */
	public function implementedEvents(): array {
		return [
			'Model.UserConfiguration.beforeSave' => 'beforeSave',
			'Model.UserConfiguration.afterSave' => 'afterSave',
			'Model.UserConfiguration.afterDelete' => 'afterDelete',
		];
	}


	/**
	 * @param \Cake\Event\Event $event
	 * @param \Awyiss\Model\Entity\UserConfiguration $entity
	 * @return void
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function beforeSave(Event $event, UserConfiguration $entity): void {
		$entity->value = ConfigOptionsProvider::typecastConfigValue(
			$entity->scope,
			Awyiss::REALM_BACKEND,
			$entity->identifier,
			$entity->value,
		);

		if (in_array(getType($entity->value), ['array', 'object'])) {
			$entity->value = json_encode($entity->value);
		}

		$entity->userId = $this->getIdentity()->getIdentifier();
	}


	/**
	 * @return void
	 */
	public function afterSave(): void {
		$this->resetConfiguration();
	}


	/**
	 * @return void
	 */
	public function afterDelete(): void {
		$this->resetConfiguration();
	}


	/**
	 * @return void
	 */
	public function resetConfiguration(): void {
		/** @noinspection PhpPossiblePolymorphicInvocationInspection */
		$this->getIdentity()->resetConfiguration();
	}
}
