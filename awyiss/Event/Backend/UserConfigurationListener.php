<?php declare(strict_types=1);


namespace Awyiss\Event\Backend;


use Awyiss\Authentication\IdentityAwareTrait;
use Awyiss\Awyiss;
use Awyiss\Configuration\ConfigOptionsProvider;
use Awyiss\Event\EventListenerTrait;
use Awyiss\Model\Entity\UserConfiguration;
use Cake\Event\Event;
use Cake\Event\EventListenerInterface;


/**
 * Event listeners for the Configuration scope of the backend
 */
class UserConfigurationListener implements EventListenerInterface {
	use EventListenerTrait;
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
			'Model.UserConfiguration.beforeSave' => 'beforeSave',
			'Model.UserConfiguration.afterSave' => 'resetConfiguration',
			'Model.UserConfiguration.afterDelete' => 'resetConfiguration',
		];
	}


	/**
	 * @param \Cake\Event\Event $ao_event
	 * @param \Awyiss\Model\Entity\UserConfiguration $ao_entity
	 * @return void
	 * @noinspection PhpUnusedParameterInspection
	 * @throws \ReflectionException
	 */
	public function beforeSave(Event $ao_event, UserConfiguration $ao_entity): void {
		$ao_entity->value = ConfigOptionsProvider::typecastConfigValue(
			$ao_entity->scope,
			Awyiss::REALM_BACKEND,
			$ao_entity->identifier,
			$ao_entity->value,
		);

		if (in_array(getType($ao_entity->value), ['array', 'object'])) {
			$ao_entity->value = json_encode($ao_entity->value);
		}

		$ao_entity->userId = $this->getIdentity()->getIdentifier();
	}


	/**
	 * @return void
	 */
	public function resetConfiguration(): void {
		/** @noinspection PhpPossiblePolymorphicInvocationInspection */
		$this->getIdentity()->resetConfiguration();
	}
}
