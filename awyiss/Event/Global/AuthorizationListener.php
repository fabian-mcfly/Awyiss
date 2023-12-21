<?php declare(strict_types=1);


namespace Awyiss\Event\Global;


use Awyiss\Authorization\AuthorizationServiceInterface;
use Awyiss\Authorization\Policy\GenericPagePolicy;
use Awyiss\Event\EventListenerTrait;
use Awyiss\Model\Table;
use Cake\Event\Event;
use Cake\Event\EventListenerInterface;
use Cake\Utility\Inflector;


/**
 * Event listeners for the general events of the backend
 */
class AuthorizationListener implements EventListenerInterface {
	use EventListenerTrait;


	/**
	 * @var array|array<array>
	 */
	protected array $initializedModels = [
		'authorizationService' => [],
	];
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
			'Authorization.requestPolicyClass' => 'requestPolicyClass',
			'Authorization.requestAuthorizationService' => 'requestAuthorizationService',
			'Authorization.afterMiddlewareProcess' => 'authorizationMiddlewareAfterProcess',
			'Model.initialize' => 'modelInitialize',
		];
	}


	/**
	 * After the authorization middleware was processed, set the AuthorizationService in every model's AuthorizationBehavior
	 *
	 * @param Event $ao_event
	 * @param AuthorizationServiceInterface $ao_authorizationService
	 * @noinspection PhpUnused
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function authorizationMiddlewareAfterProcess(Event $ao_event, AuthorizationServiceInterface $ao_authorizationService): void {
		$this->authorizationService = $ao_authorizationService;

		/** @var Table $lo_model */
		foreach ($this->initializedModels['authorizationService'] as $lo_model) {
			if ($lo_model->hasBehavior('Authorize')) {
				/** @noinspection PhpPossiblePolymorphicInvocationInspection */
				$lo_model->getBehavior('Authorize')->setAuthorizationService($this->authorizationService);
			}
		}
	}


	/**
	 * For every model that is loaded, set
	 *
	 * - the AuthorizationService in every model's AuthorizationBehavior, if the Identity is AuthorizationService.
	 * If not, save the model to be handled in `authorizationMiddlewareAfterProcess`.
	 *
	 * - the Identity in the AuditBehavior, if the Identity is known
	 * If not, save the model to be handled in `authenticationAfterAuthenticate`.
	 *
	 * @param Event $ao_event
	 * @return void
	 * @noinspection PhpUnused
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function modelInitialize(Event $ao_event): void {
		/** @var Table $lo_model */
		$lo_model = $ao_event->getSubject();

		if ($lo_model instanceof Table) {
			if (!isset($this->authorizationService)) {
				$this->initializedModels['authorizationService'][] = $lo_model;
			}
			elseif ($lo_model->hasBehavior('Authorize')) {
				/** @noinspection PhpPossiblePolymorphicInvocationInspection */
				$lo_model->getBehavior('Authorize')->setAuthorizationService($this->authorizationService);
			}
		}
	}


	/**
	 * The event `Authorization.requestAuthorizationService` asks for an AuthorizationService instance.
	 * Return it, in case it is set.
	 *
	 * @param Event $ao_event
	 * @noinspection PhpUnused
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function requestAuthorizationService(Event $ao_event): void {
		if (isset($this->authorizationService)) {
			$ao_event->setResult($this->authorizationService);
		}
	}


	/**
	 * The events `Authorization.requestPolicyClass` and `Model.requestPolicyClass` ask for a Policy.
	 * Return an instance of GenericPagePolicy, in case the event has a data-field 'scope' and it holds
	 * the name of a page role.
	 *
	 * @param Event $ao_event
	 * @noinspection PhpUnused
	 */
	public function requestPolicyClass(Event $ao_event): void {
		$ls_singular = Inflector::singularize(Inflector::underscore($ao_event->getData('scope')));
		$ls_constant = 'PAGEROLE_' . strtoupper($ls_singular);
		if (defined($ls_constant)) {
			$lo_policy = new GenericPagePolicy($ao_event->getData('scope'));
			$ao_event->setResult($lo_policy);
		}
	}
}
