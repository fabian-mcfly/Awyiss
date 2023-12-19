<?php declare(strict_types=1);


namespace Awyiss\Event\Backend;


use Authentication\Authenticator\AuthenticatorInterface;
use Authentication\IdentityInterface;
use Awyiss\Authorization\AuthorizationServiceInterface;
use Awyiss\Authorization\Policy\GenericPagePolicy;
use Awyiss\Awyiss;
use Awyiss\Event\EventListenerTrait;
use Awyiss\Middleware\LocaleMiddleware;
use Awyiss\Model\Table;
use Cake\Event\Event;
use Cake\Event\EventListenerInterface;
use Awyiss\Routing\Router;
use Cake\Utility\Inflector;


/**
 * Event listeners for the general events of the backend
 */
class GeneralEventsListener implements EventListenerInterface {
	use EventListenerTrait;


	/**
	 * @var string
	 */
	protected static string $scope;


	/**
	 * @var array|array[]
	 */
	protected array $initializedModels = [
		'authorizationService' => [],
		'identity' => [],
	];
	/**
	 * @var AuthorizationServiceInterface
	 */
	protected AuthorizationServiceInterface $authorizationService;
	/**
	 * @var IdentityInterface
	 */
	protected IdentityInterface $identity;


	/**
	 * @inheritDoc
	 */
	public function implementedEvents (): array {
		return [
			'Authentication.afterAuthenticate' => 'authenticationAfterAuthenticate',
			'Authentication.requestLoginUrl' => 'authenticationRequestLoginUrl',
			'Authorization.requestPolicyClass' => 'requestPolicyClass',
			'Authorization.requestAuthorizationService' => 'requestAuthorizationService',
			'AuthorizationMiddleware.afterProcess' => 'authorizationMiddlewareAfterProcess',
			'Model.initialize' => 'modelInitialize',
			'Model.requestPolicyClass' => 'requestPolicyClass',
		];
	}


	/**
	 * After the authorization middleware was processed, set the AuthorizationService in every model's AuthorizationBehavior
	 *
	 * @param Event                         $ao_event
	 * @param AuthorizationServiceInterface $ao_authorizationService
	 *
	 * @noinspection PhpUnused
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function authorizationMiddlewareAfterProcess (Event $ao_event, AuthorizationServiceInterface $ao_authorizationService): void {
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
	 * After authentication, set the Identity in every model's AuditBehavior
	 *
	 * @param Event                  $ao_event
	 * @param AuthenticatorInterface $ao_authenticator
	 * @param IdentityInterface      $ao_identity
	 *
	 * @noinspection PhpUnused
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function authenticationAfterAuthenticate (Event $ao_event, AuthenticatorInterface $ao_authenticator, IdentityInterface $ao_identity): void {
		$this->identity = $ao_identity;

		/** @var Table $lo_model */
		foreach ($this->initializedModels['identity'] as $lo_model) {
			if ($lo_model->hasBehavior('Audit')) {
				/** @noinspection PhpPossiblePolymorphicInvocationInspection */
				$lo_model->getBehavior('Audit')->setIdentity($this->identity);
			}
		}
	}


	/**
	 * The authentication process might require a URL.
	 *
	 * For example, the `FormAuthenticator::class` requires one to work.
	 *
	 * @param Event $ao_event
	 *
	 * @return void
	 *
	 * @throws \Exception
	 * @noinspection PhpUnused
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function authenticationRequestLoginUrl (Event $ao_event): void {
		/** @noinspection PhpUnhandledExceptionInspection */
		$ao_event->setResult(Router::url([
			'_name' => Awyiss::REALM_BACKEND,
			'lang' => LocaleMiddleware::getLanguage()->shortcode,
			'controller' => 'Users',
			'action' => 'login',
		]));
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
	 *
	 * @return void
	 *
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
			elseif ($lo_model->hasBehavior('Authorize')) {
				/** @noinspection PhpPossiblePolymorphicInvocationInspection */
				$lo_model->getBehavior('Authorize')->setAuthorizationService($this->authorizationService);
			}

			if ( ! isset($this->identity)) {
				$this->initializedModels['identity'][] = $lo_model;
			}
			elseif ($lo_model->hasBehavior('Audit')) {
				/** @noinspection PhpPossiblePolymorphicInvocationInspection */
				$lo_model->getBehavior('Audit')->setIdentity($this->identity);
			}
		}
	}


	/**
	 * The event `Authorization.requestAuthorizationService` asks for an AuthorizationService instance.
	 * Return it, in case it is set.
	 *
	 * @param Event $ao_event
	 *
	 * @noinspection PhpUnused
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function requestAuthorizationService (Event $ao_event): void {
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
	 *
	 * @noinspection PhpUnused
	 */
	public function requestPolicyClass (Event $ao_event): void {
		$ls_singular = Inflector::singularize(Inflector::underscore($ao_event->getData('scope')));
		$ls_constant = 'PAGEROLE_' . strtoupper($ls_singular);
		if (defined($ls_constant)) {
			$lo_policy = new GenericPagePolicy($ao_event->getData('scope'));
			$ao_event->setResult($lo_policy);
		}
	}
}
