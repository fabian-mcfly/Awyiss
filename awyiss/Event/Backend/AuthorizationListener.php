<?php declare(strict_types=1);


namespace Awyiss\Event\Backend;


use Awyiss\Authorization\AuthorizationServiceInterface;
use Awyiss\Event\EventListenerTrait;
use Cake\Event\Event;
use Cake\Event\EventListenerInterface;


/**
 * Event listeners for the general events of the backend
 */
class AuthorizationListener implements EventListenerInterface {
	use EventListenerTrait;


	/**
	 * @var AuthorizationServiceInterface
	 */
	protected AuthorizationServiceInterface $authorizationService;
	/**
	 * @var string
	 */
	protected static string $scope;


	/**
	 * @inheritDoc
	 */
	public function implementedEvents(): array {
		return [
			'Authorization.requestAuthorizationService' => 'requestAuthorizationService',
			'Authorization.afterMiddlewareProcess' => 'authorizationMiddlewareAfterProcess',
		];
	}


	/**
	 * After the authorization middleware was processed, set the AuthorizationService in every model's AuthorizationBehavior
	 *
	 * @param Event $event
	 * @param AuthorizationServiceInterface $authorizationService
	 * @noinspection PhpUnused
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function authorizationMiddlewareAfterProcess(Event $event, AuthorizationServiceInterface $authorizationService): void {
		$this->authorizationService = $authorizationService;
	}


	/**
	 * The event `Authorization.requestAuthorizationService` asks for an AuthorizationService instance.
	 * Return it, in case it is set.
	 *
	 * @param Event $event
	 * @noinspection PhpUnused
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function requestAuthorizationService(Event $event): void {
		if (isset($this->authorizationService)) {
			$event->setResult($this->authorizationService);
		}
	}
}
