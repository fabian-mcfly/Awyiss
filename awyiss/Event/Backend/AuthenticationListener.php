<?php declare(strict_types=1);


namespace Awyiss\Event\Backend;


use Authentication\Authenticator\AuthenticatorInterface;
use Authentication\IdentityInterface;
use Awyiss\Awyiss;
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
	/**
	 * @var array<array>
	 */
	protected static array $initializedClasses = [];
	/**
	 * @var array<array>
	 */
	protected static array $initializedModels = [];


	/**
	 * @var IdentityInterface
	 */
	protected IdentityInterface $identity;


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
	 * @param Event $event
	 * @param AuthenticatorInterface $authenticator
	 * @param IdentityInterface $identity
	 * @noinspection PhpUnused
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function authenticationAfterAuthenticate(Event $event, AuthenticatorInterface $authenticator, IdentityInterface $identity): void {
		$this->identity = $identity;

		/** @var \Awyiss\Authentication\IdentityAwareTrait $lo_class */
		foreach (static::$initializedClasses as $li_key => $lo_class) {
			$lo_class->setIdentity($this->identity);
			unset(static::$initializedClasses[ $li_key ]);
		}

		/** @var Table $lo_model */
		foreach (static::$initializedModels as $li_key => $lo_model) {
			if ($lo_model->hasBehavior('Audit')) {
				$lo_model->getBehavior('Audit')->setIdentity($this->identity);
			}
			unset(static::$initializedModels[ $li_key ]);
		}
	}


	/**
	 * @param \Cake\Event\Event $event
	 * @return void
	 */
	public function authenticationRequestIdentity(Event $event): void {
		try {
			/** @var Table $lo_model */
			$lo_class = $event->getSubject();
		}
		catch (CakeException) {
			$lo_class = null;
		}

		if (isset($this->identity)) {
			$event->setResult($this->identity);
		}

		if (!$lo_class || !method_exists($lo_class, 'setIdentity')) {
			return;
		}

		if (isset($this->identity)) {
			$lo_class->setIdentity($this->identity);

			return;
		}

		if (!in_array($lo_class, static::$initializedClasses, true)) {
			static::$initializedClasses[] = $lo_class;
		}
	}


	/**
	 * The authentication process might require a URL.
	 * For example, the `FormAuthenticator::class` requires one to work.
	 *
	 * @param Event $event
	 * @return void
	 * @throws \Exception
	 * @noinspection PhpUnused
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function authenticationRequestLoginUrl(Event $event): void {
		$event->setResult(Router::url([
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
	 * @param Event $event
	 * @return void
	 * @noinspection PhpUnused
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function modelInitialize(Event $event): void {
		/** @var Table $lo_model */
		$lo_model = $event->getSubject();
		if (!$lo_model instanceof Table) {
			return;
		}

		if (isset($this->identity)) {
			if ($lo_model->hasBehavior('Audit')) {
				$lo_model->getBehavior('Audit')->setIdentity($this->identity);
			}

			return;
		}

		if (!in_array($lo_model, static::$initializedModels, true)) {
			static::$initializedModels[] = $lo_model;
		}
	}
}
