<?php declare(strict_types=1);


namespace Awyiss\Event\Backend;


use Authentication\Authenticator\AuthenticatorInterface;
use Authentication\IdentityInterface;
use Awyiss\Authorization\AuthorizationServiceInterface;
use Awyiss\Authorization\Policy\AnonymousPolicy;
use Awyiss\Controller\Component\AccessComponent;
use Awyiss\Event\EventListenerTrait;
use Awyiss\Model\Table;
use Cake\Datasource\EntityInterface;
use Cake\Event\Event;
use Cake\Event\EventListenerInterface;


class GeneralEventsListener implements EventListenerInterface {
	use EventListenerTrait;


	protected array $initializedModels = [
		'authorizationService' => [],
		'identity' => [],
	];
	protected AuthorizationServiceInterface $authorizationService;
	protected IdentityInterface $identity;


	/**
	 * @noinspection PhpArrayShapeAttributeCanBeAddedInspection
	 */
	public function implementedEvents (): array {
		return [
			'Authentication.afterAuthenticate' => 'authenticationAfterAuthenticate',
			'AuthorizationMiddleware.afterProcess' => 'authorizationMiddlewareAfterProcess',
			'Component.requestPolicyClass' => 'requestPolicyClass',
			'Model.initialize' => 'modelInitialize',
			'Model.requestPolicyClass' => 'requestPolicyClass',
			'Model.requestAuthorizationService' => 'modelRequestAuthorizationService',
		];
	}


	/**
	 * @noinspection PhpUnused
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function authorizationMiddlewareAfterProcess (Event $ao_event, AuthorizationServiceInterface $ao_authorizationService): void {
		$this->authorizationService = $ao_authorizationService;

		/** @var Table $lo_model */
		foreach ($this->initializedModels['authorizationService'] as $lo_model) {
			if ($lo_model->hasBehavior('Access')) {
				$lo_model->setAuthorizationService($this->authorizationService);
			}
		}
	}


	/**
	 * @noinspection PhpUnused
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function authenticationAfterAuthenticate (Event $ao_event, AuthenticatorInterface $ao_authenticator, IdentityInterface $ao_identity): void {
		$this->identity = $ao_identity;

		/** @var Table $lo_model */
		foreach ($this->initializedModels['identity'] as $lo_model) {
			if ($lo_model->hasBehavior('Audit')) {
				$lo_model->behaviors()->get('Audit')->setIdentity($this->identity);
			}
		}
	}


	/**
	 * @noinspection PhpUnused
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function modelInitialize (Event $ao_event): void {
		/** @var Table $lo_model */
		$lo_model = $ao_event->getSubject();

		if ($lo_model instanceof Table) {
			if ( ! isset($this->authorizationService)) {
				$this->initializedModels['authorizationService'][] = $lo_model;
			}
			else {
				if ($lo_model->hasBehavior('Access')) {
					$lo_model->setAuthorizationService($this->authorizationService);
				}
			}

			if ( ! isset($this->identity)) {
				$this->initializedModels['identity'][] = $lo_model;
			}
			else {
				if ($lo_model->hasBehavior('Audit')) {
					$lo_model->behaviors()->get('Audit')->setIdentity($this->identity);
				}
			}
		}
	}


	/**
	 * @noinspection PhpUnused
	 */
	public function modelRequestAuthorizationService (Event $ao_event): void {
		/** @var Table $lo_model */
		$lo_model = $ao_event->getSubject();
		if (isset($this->authorizationService)) {
			$lo_model->setAuthorizationService($this->authorizationService);
		}
	}



	/**
	 * @noinspection PhpUnused
	 */
	public function requestPolicyClass (Event $ao_event): void {
		/** @var AccessComponent|Table $lo_model */
		$lo_subject = $ao_event->getSubject();

		$ls_singular = \Cake\Utility\Inflector::singularize($ao_event->getData('scope'));
		$ls_constant = 'PAGEROLE_' . strtoupper($ls_singular);
		if (defined($ls_constant)) {
			$lo_policyClass = new AnonymousPolicy($ao_event->getData('scope'));

			$lo_subject->setPolicyClass($lo_policyClass);
		}
	}
}