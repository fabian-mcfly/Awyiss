<?php declare(strict_types=1);


namespace Awyiss\Authentication;


use Authentication\AuthenticationService as BaseAuthenticationService;
use Authentication\Authenticator\ResultInterface;
use Authentication\Authenticator\StatelessInterface;
use Awyiss\Authentication\Identifier\IdentifierCollection;
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
		$lx_result = null;
		/** @var \Authentication\Authenticator\AuthenticatorInterface $lo_authenticator */
		foreach ($this->authenticators() as $lo_authenticator) {
			$lx_result = $lo_authenticator->authenticate($request);
			if ($lx_result->isValid()) {
				$this->_successfulAuthenticator = $lo_authenticator;

				$this->_result = $lx_result;

				$this->dispatchEvent('Authentication.afterAuthenticate', [
					'authenticator' => $lo_authenticator,
					'identity' => $this->getIdentity(),
				], $this);


				return $this->_result;
			}

			if ($lo_authenticator instanceof StatelessInterface) {
				$lo_authenticator->unauthorizedChallenge($request);
			}
		}

		if ($lx_result === null) {
			throw new RuntimeException('No authenticators loaded. You need to load at least one authenticator.');
		}

		$this->_successfulAuthenticator = null;


		return $this->_result = $lx_result;
	}


	/**
	 * @inheritDoc
	 * @param ServerRequestInterface $request The request
	 */
	public function getUnauthenticatedRedirectUrl(ServerRequestInterface $request): ?string {
		/*
		 * This one's hacky and needs a serious rework but works for now
		 * We write the current Uri to the session since we don't like having an uri-encoded
		 * paramter containing the old path. That looks amateurish.
		 */

		$lo_uri = $request->getUri();
		$ls_redirectUri = $lo_uri->getPath();

		/** @var \Cake\Http\Session $lo_session */
		$lo_session = $request->getAttribute('session');
		$lo_session->write('unauthenticatedRedirectUrl', $ls_redirectUri);


		return parent::getUnauthenticatedRedirectUrl($request);
	}


	/**
	 * @inheritDoc
	 * @param ServerRequestInterface $request The request
	 * @noinspection PhpMissingParentCallCommonInspection
	 */
	public function getLoginRedirect(ServerRequestInterface $request): ?string {
		/** @var \Cake\Http\Session $lo_session */
		$lo_session = $request->getAttribute('session');


		return $lo_session->read('unauthenticatedRedirectUrl');
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
