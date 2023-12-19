<?php declare(strict_types=1);


namespace Awyiss\Event\Backend;


use Authentication\Authenticator\AuthenticatorInterface;
use Authentication\IdentityInterface;
use Awyiss\Authorization\AuthorizationServiceInterface;
use Awyiss\Authorization\Policy\AnonymousPolicy;
//use Awyiss\Controller\Component\AccessComponent;
use Awyiss\Event\EventListenerTrait;
use Awyiss\Model\Table;
//use Cake\Datasource\EntityInterface;
use Cake\Event\Event;
use Cake\Event\EventListenerInterface;
use Cake\Routing\Router;
use Cake\Utility\Inflector;


/**
 * Event listeners for the general events of the backend
 */
class GeneralEventsListener implements EventListenerInterface {
	use EventListenerTrait;


	protected static string $scope;


	protected array $initializedModels = [
		'authorizationService' => [],
		'identity' => [],
	];
	protected AuthorizationServiceInterface $authorizationService;
	protected IdentityInterface $identity;


	/**
	 * @inheritDoc
	 */
	public function implementedEvents (): array {
		return [
			'Access.requestPolicyClass' => 'requestPolicyClass',
			'Access.requestAuthorizationService' => 'requestAuthorizationService',
			'Authentication.afterAuthenticate' => 'authenticationAfterAuthenticate',
			'Authentication.requestLoginUrl' => 'authenticationRequestLoginUrl',
			'AuthorizationMiddleware.afterProcess' => 'authorizationMiddlewareAfterProcess',
			'Model.initialize' => 'modelInitialize',
			'Model.requestPolicyClass' => 'requestPolicyClass',
			#'View.beforeRender' => 'test',
		];
	}


	/*public function test ($ao_event, $as_filename) {
		if ($as_filename === '/var/www/cms/awyiss/templates/Backend/Configuration/overview.twig') {
			return;
		}

		dd(debug_backtrace(2), $as_filename);
	}*/


	/**
	 * After the authorization middleware was processed, set the AuthorizationService in every model's AccessBehavior
	 *
	 * @param \Cake\Event\Event $ao_event
	 * @param \Awyiss\Authorization\AuthorizationServiceInterface $ao_authorizationService
	 *
	 * @noinspection PhpUnused
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function authorizationMiddlewareAfterProcess (Event $ao_event, AuthorizationServiceInterface $ao_authorizationService): void {
		$this->authorizationService = $ao_authorizationService;

		/** @var Table $lo_model */
		foreach ($this->initializedModels['authorizationService'] as $lo_model) {
			if ($lo_model->hasBehavior('Access')) {
				/** @noinspection PhpPossiblePolymorphicInvocationInspection */
				$lo_model->getBehavior('Access')->setAuthorizationService($this->authorizationService);
			}
		}
	}


	/**
	 * After authentication, set the Identity in every model's AuditBehavior
	 *
	 * @param \Cake\Event\Event $ao_event
	 * @param \Authentication\Authenticator\AuthenticatorInterface $ao_authenticator
	 * @param \Authentication\IdentityInterface $ao_identity
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
	 * @param \Cake\Event\Event $ao_event
	 * @param string $as_languageShortcode
	 *
	 * @return string
	 *
	 * @noinspection PhpUnused
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function authenticationRequestLoginUrl (Event $ao_event, string $as_languageShortcode = ''): string {
		return Router::url([
			'_name' => 'backend',
			'lang' => $as_languageShortcode,
			'controller' => 'Users',
			'action' => 'login',
		]);
	}


	/**
	 * For every model that is loaded, set
	 *
	 * - the AuthorizationService in every model's AccessBehavior, if the Identity is AuthorizationService.
	 * If not, save the model to be handled in authorizationMiddlewareAfterProcess
	 *
	 * - the Identity in the AuditBehavior, if the Identity is known
	 * If not, save the model to be handled in authenticationAfterAuthenticate.
	 *
	 * @param \Cake\Event\Event $ao_event
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
			elseif ($lo_model->hasBehavior('Access')) {
				/** @noinspection PhpPossiblePolymorphicInvocationInspection */
				$lo_model->getBehavior('Access')->setAuthorizationService($this->authorizationService);
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
	 * The event `Access.requestAuthorizationService` asks for an AuthorizationService instance.
	 * Return it, in case it is set.
	 *
	 * @param \Cake\Event\Event $ao_event
	 *
	 * @return NULL|\Awyiss\Authorization\AuthorizationServiceInterface
	 *
	 * @noinspection PhpUnused
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function requestAuthorizationService (Event $ao_event): ?AuthorizationServiceInterface {
		if (isset($this->authorizationService)) {
			return $this->authorizationService;
		}

		return NULL;
	}



	/**
	 * The events `Access.requestPolicyClass` and `Model.requestPolicyClass` ask for a Policy.
	 * Return an instance of AnonymousPolicy, in case the event has a data-field 'scope' and it holds
	 * the name of a page role.
	 *
	 * @param \Cake\Event\Event $ao_event
	 *
	 * @return NULL|\Awyiss\Authorization\Policy\AnonymousPolicy
	 *
	 * @noinspection PhpUnused
	 */
	public function requestPolicyClass (Event $ao_event): ?AnonymousPolicy {
		$ls_singular = Inflector::singularize($ao_event->getData('scope'));
		$ls_constant = 'PAGEROLE_' . strtoupper($ls_singular);
		if (defined($ls_constant)) {
			return new AnonymousPolicy($ao_event->getData('scope'));
		}

		return NULL;
	}
}