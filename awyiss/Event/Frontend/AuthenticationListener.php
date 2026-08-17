<?php declare(strict_types=1);


namespace Awyiss\Event\Frontend;


use Authentication\Authenticator\AuthenticatorInterface;
use Authentication\IdentityInterface;
use Awyiss\Awyiss;
use Awyiss\Middleware\LocaleMiddleware;
use Awyiss\Routing\Router;
use Cake\Core\Exception\CakeException;
use Cake\Event\Event;
use Cake\Event\EventListenerInterface;


/**
 * Event listeners for the general events of the backend
 */
class AuthenticationListener implements EventListenerInterface {
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
	public function authenticationAfterAuthenticate(
		Event $event,
		AuthenticatorInterface $authenticator,
		IdentityInterface $identity
	): void {
		$this->identity = $identity;
	}


	/**
	 * @param \Cake\Event\Event $event
	 * @return void
	 * @noinspection PhpUnused
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function authenticationRequestIdentity(Event $event): void {
		try {
			/** @var \Awyiss\Model\Table $class */
			$class = $event->getSubject();
		}
		catch (CakeException) {
			$class = null;
		}

		if (isset($this->identity)) {
			$event->setResult($this->identity);
		}

		if (!$class || !method_exists($class, 'setIdentity')) {
			return;
		}

		if (isset($this->identity)) {
			$class->setIdentity($this->identity);
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
			'_name' => Awyiss::REALM_FRONTEND . 'CustomerCenterLogin' . ucfirst(LocaleMiddleware::getLanguage()->shortcode),
		]));
	}
}
