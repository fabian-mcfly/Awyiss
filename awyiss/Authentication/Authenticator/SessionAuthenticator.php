<?php declare(strict_types=1);


namespace Awyiss\Authentication\Authenticator;


use ArrayAccess;
use ArrayObject;
use Authentication\Authenticator\Result;
use Authentication\Authenticator\ResultInterface;
use Authentication\Authenticator\SessionAuthenticator as BaseSessionAuthenticator;
use Authentication\Identifier\IdentifierInterface;
use Psr\Http\Message\ServerRequestInterface;


/**
 * Session Authenticator
 */
class SessionAuthenticator extends BaseSessionAuthenticator {
	/**
	 * Identifier or identifiers collection.
	 *
	 * @var \Awyiss\Authentication\Identifier\IdentifierCollection
	 */
	protected IdentifierInterface $_identifier;


	/**
	 * {@inheritDoc}
	 *
	 * Extended to call the config setting `identify` if it's a callable
	 * and to use `reidentify`-method on `\Awyiss\Authentication\Identifier\IdentifierCollection::reidentify()`
	 *
	 * @see \Awyiss\Authentication\Identifier\IdentifierCollection::reidentify
	 * @noinspection PhpMissingParentCallCommonInspection
	 */
	public function authenticate(ServerRequestInterface $request): ResultInterface {
		$ls_sessionKey = $this->getConfig('sessionKey');
		/** @var \Cake\Http\Session $lo_session */
		$lo_session = $request->getAttribute('session');
		/** @var \Awyiss\Model\Entity\User $lo_user */
		$lo_user = $lo_session->read($ls_sessionKey);

		if (empty($lo_user)) {
			return new Result(null, ResultInterface::FAILURE_IDENTITY_NOT_FOUND);
		}

		$lx_identify = $this->getConfig('identify');
		if (is_callable($lx_identify)) {
			$lx_identify = $lx_identify($lo_user);
		}

		if ($lx_identify) {
			$la_credentials = $lx_identify;
			if ($lx_identify === true) {
				$la_credentials = [];
				foreach ($this->getConfig('fields') as $lx_key => $lx_field) {
					$la_credentials[ $lx_key ] = $lo_user[ $lx_field ];
				}
			}

			/** @var \Awyiss\Model\Entity\User $lo_reidentifiedUser */
			$lo_reidentifiedUser = $this->_identifier->reidentify($la_credentials);

			if (empty($lo_reidentifiedUser)) {
				// If the user is not found, redirect to the login
				$lo_session->delete($ls_sessionKey);

				return new Result(null, ResultInterface::FAILURE_IDENTITY_NOT_FOUND);
			}

			//If the db entry of the user changed,
			if (
				(
					!$lo_user->changedOn &&
					$lo_reidentifiedUser->changedOn
				) ||
				$lo_reidentifiedUser->changedOn?->notEquals($lo_user->changedOn)
			) {
				// unset the permissions and use the new changedOn value
				$lo_user->unsetPermissionCollection();
				$lo_user->changedOn = $lo_reidentifiedUser->changedOn;
			}
		}

		if (!($lo_user instanceof ArrayAccess)) {
			$lo_user = new ArrayObject($lo_user);
		}


		return new Result($lo_user, ResultInterface::SUCCESS);
	}


	/**
	 * When sleeping, don't allow serialization since the config can contain a callable
	 * Having a closure inside them means serialization of the collection fails.
	 *
	 * @return array
	 */
	public function __sleep(): array {
		return [];
	}
}
