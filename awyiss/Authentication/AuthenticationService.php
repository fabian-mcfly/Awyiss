<?php declare(strict_types=1);


namespace Awyiss\Authentication;


use Authentication\AuthenticationService as BaseAuthenticationService;
use Authentication\Authenticator\ResultInterface;
use Authentication\Authenticator\StatelessInterface;
use Awyiss\Authentication\Authenticator\AuthenticatorCollection;
use Awyiss\Authentication\Identifier\IdentifierCollection;
use Awyiss\Awyiss;
use Awyiss\Event\EventDispatcherTrait;
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;


/**
 * @inheritDoc
 */
class AuthenticationService extends BaseAuthenticationService {
	use EventDispatcherTrait;


	/**
	 * @inheritDoc
	 * @uses IdentifierCollection
	 * @noinspection PhpMissingParentCallCommonInspection
	 */
	public function identifiers(): IdentifierCollection {
		if ($this->_identifiers === null) {
			$this->_identifiers = new IdentifierCollection($this->getConfig('identifiers'));
		}


		return $this->_identifiers;
	}


	/**
	 * @inheritDoc
	 * @param ServerRequestInterface $request The request.
	 * @noinspection PhpMissingParentCallCommonInspection
	 */
	public function authenticate(ServerRequestInterface $request): ResultInterface {
		$result = null;
		/** @var \Authentication\Authenticator\AuthenticatorInterface $authenticator */
		foreach ($this->authenticators() as $authenticator) {
			$result = $authenticator->authenticate($request);
			if ($result->isValid()) {
				$this->_successfulAuthenticator = $authenticator;

				$this->_result = $result;

				$this->dispatchEvent('Authentication.afterAuthenticate', [
					'authenticator' => $authenticator,
					'identity' => $this->getIdentity(),
				], $this);


				return $this->_result;
			}

			if ($authenticator instanceof StatelessInterface) {
				$authenticator->unauthorizedChallenge($request);
			}
		}

		if ($result === null) {
			throw new RuntimeException('No authenticators loaded. You need to load at least one authenticator.');
		}

		$this->_successfulAuthenticator = null;


		return $this->_result = $result;
	}


	/**
	 * @inheritDoc
	 * @param ServerRequestInterface $request The request
	 */
	public function getUnauthenticatedRedirectUrl(ServerRequestInterface $request): ?string {
		/*
		 * This one's hacky and needs a serious rework but works for now
		 * We write the current Uri to the session since we don't like having an uri-encoded
		 * parameter containing the old path. That looks amateurish.
		 */

		$uri = $request->getUri();
		$redirectUri = $uri->getPath();

		if (
			!str_ends_with($redirectUri, '/request-lock/') &&
			!str_ends_with($redirectUri, '/release-lock/') &&
			!str_contains($redirectUri, '/mode:frontend-editor/')
		) {
			/** @var \Cake\Http\Session $session */
			$session = $request->getAttribute('session');
			$session->write(Awyiss::getRealm() . '.unauthenticatedRedirectUrl', $redirectUri);
		}

		return parent::getUnauthenticatedRedirectUrl($request);
	}


	/**
	 * @inheritDoc
	 * @param ServerRequestInterface $request The request
	 * @noinspection PhpMissingParentCallCommonInspection
	 */
	public function getLoginRedirect(ServerRequestInterface $request): ?string {
		/** @var \Cake\Http\Session $session */
		$session = $request->getAttribute('session');


		return $session->read(Awyiss::getRealm() . '.unauthenticatedRedirectUrl');
	}


	/**
	 * Reimplemented 1:1 to use \Awyiss\Authentication\Authenticator\AuthenticatorCollection
	 *
	 * @inheritDoc
	 */
	public function authenticators(): AuthenticatorCollection {
		if ($this->_authenticators === null) {
			$identifiers = $this->identifiers();
			$authenticators = $this->getConfig('authenticators');
			$this->_authenticators = new AuthenticatorCollection($identifiers, $authenticators);
		}

		return $this->_authenticators;
	}


	/**
	 * When sleeping, don't allow serialization since $_result can contain a resultset
	 * Having a resultset means serialization of the object fails.
	 *
	 * @return array
	 */
	public function __sleep(): array {
		return [];
	}
}
