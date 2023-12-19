<?php


namespace Awyiss\Authentication;


use Awyiss\Authentication\Identifier\IdentifierCollection;
use Psr\Http\Message\ServerRequestInterface;


class AuthenticationService extends \Authentication\AuthenticationService {
	/**
	 * {@inheritDoc}
	 *
	 * @uses \Awyiss\Authentication\Identifier\IdentifierCollection
	 */
	public function identifiers (): IdentifierCollection {
		if ($this->_identifiers === NULL) {
			$this->_identifiers = new IdentifierCollection($this->getConfig('identifiers'));
		}

		return $this->_identifiers;
	}


	/**
	 * {@inheritDoc}
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function getUnauthenticatedRedirectUrl (ServerRequestInterface $ao_request): ?string {
		/**
		 * This one's hacky and needs a serious rework but works for now
		 * We write the current Uri to the session since we don't like having an uri-encoded
		 * paramter containing the old path. That looks amateurish
		 */

		$lo_uri = $ao_request->getUri();
		$ls_redirectUri = $lo_uri->getPath();

		/** @var \Cake\Http\Session $lo_session */
		$lo_session = $ao_request->getAttribute('session');
		$lo_session->write('unauthenticatedRedirectUrl', $ls_redirectUri);

		return parent::getUnauthenticatedRedirectUrl($ao_request);
	}


	/**
	 * {@inheritDoc}
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function getLoginRedirect (ServerRequestInterface $ao_request): ?string {
		/** @var \Cake\Http\Session $lo_session */
		$lo_session = $ao_request->getAttribute('session');

		return $lo_session->read('unauthenticatedRedirectUrl');
	}
}