<?php declare(strict_types=1);


namespace Awyiss\Event\Backend;


use Authentication\Authenticator\AuthenticatorInterface;
use Authentication\IdentityInterface;
use Awyiss\Awyiss;
use Awyiss\Event\EventListenerTrait;
use Awyiss\Middleware\LocaleMiddleware;
use Awyiss\Model\Table;
use Awyiss\Routing\Router;
use Cake\Core\Exception\CakeException;
use Cake\Event\Event;
use Cake\Event\EventListenerInterface;


/**
 * Event listeners for the general events of the backend
 */
class AuthenticationListener implements EventListenerInterface {
	use EventListenerTrait;


	/**
	 * @var array|array<array>
	 */
	protected array $initializedClasses = [
		'identity' => [],
	];
	/**
	 * @var array|array<array>
	 */
	protected array $initializedModels = [
		'identity' => [],
	];
	/**
	 * @var IdentityInterface
	 */
	protected IdentityInterface $identity;
	/**
	 * @var string
	 */
	protected static string $scope;


	/**
	 * @inheritDoc
	 */
	public function implementedEvents(): array {
		return [
			'Authentication.afterAuthenticate' => 'authenticationAfterAuthenticate',
			'Authentication.requestIdentity' => 'authenticationRequestIdentity',
			'Authentication.requestLoginUrl' => 'authenticationRequestLoginUrl',
			'Model.initialize' => 'modelInitialize',
		];
	}


	/**
	 * After authentication, set the Identity in every model's AuditBehavior
	 *
	 * @param Event $ao_event
	 * @param AuthenticatorInterface $ao_authenticator
	 * @param IdentityInterface $ao_identity
	 * @noinspection PhpUnused
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function authenticationAfterAuthenticate(Event $ao_event, AuthenticatorInterface $ao_authenticator, IdentityInterface $ao_identity): void {
		$this->identity = $ao_identity;

		/** @var \Awyiss\Authentication\IdentityAwareTrait $lo_class */
		foreach ($this->initializedClasses['identity'] as $lo_class) {
			$lo_class->setIdentity($this->identity);
		}

		/** @var Table $lo_model */
		foreach ($this->initializedModels['identity'] as $lo_model) {
			if ($lo_model->hasBehavior('Audit')) {
				/** @noinspection PhpPossiblePolymorphicInvocationInspection */
				$lo_model->getBehavior('Audit')->setIdentity($this->identity);
			}
		}

		// Add user configuration to the configuration now that the user is authenticated
		Awyiss::addUserConfiguration();
	}


	/**
	 * @param \Cake\Event\Event $ao_event
	 * @return void
	 */
	public function authenticationRequestIdentity(Event $ao_event): void {
		try {
			/** @var Table $lo_model */
			$lo_class = $ao_event->getSubject();
		}
		catch (CakeException) {
			$lo_class = null;
		}

		if ($lo_class && method_exists($lo_class, 'setIdentity')) {
			if (!isset($this->identity)) {
				$this->initializedClasses['identity'][] = $lo_class;
			}
			else {
				$lo_class->setIdentity($this->identity);
				$ao_event->setResult($this->identity);
			}
		}
		elseif (isset($this->identity)) {
			$ao_event->setResult($this->identity);
		}
	}


	/**
	 * The authentication process might require a URL.
	 * For example, the `FormAuthenticator::class` requires one to work.
	 *
	 * @param Event $ao_event
	 * @return void
	 * @throws \Exception
	 * @noinspection PhpUnused
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function authenticationRequestLoginUrl(Event $ao_event): void {
		$ao_event->setResult(Router::url([
			'_name' => Awyiss::REALM_BACKEND,
			'lang' => LocaleMiddleware::getLanguage()->shortcode,
			'controller' => 'Users',
			'action' => 'login',
		]));
	}


	/**
	 * For every model that is loaded, set
	 * - the AuthorizationService in every model's AuthorizationBehavior, if the Identity is AuthorizationService.
	 * If not, save the model to be handled in `authorizationMiddlewareAfterProcess`.
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
			if (!isset($this->identity)) {
				$this->initializedModels['identity'][] = $lo_model;
			}
			elseif ($lo_model->hasBehavior('Audit')) {
				/** @noinspection PhpPossiblePolymorphicInvocationInspection */
				$lo_model->getBehavior('Audit')->setIdentity($this->identity);
			}
		}
	}
}
