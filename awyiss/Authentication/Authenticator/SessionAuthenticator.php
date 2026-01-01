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
	protected IdentifierInterface $_identifier; // phpcs:ignore


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
		$sessionKey = $this->getConfig('sessionKey');
		/** @var \Cake\Http\Session $session */
		$session = $request->getAttribute('session');
		/** @var \Awyiss\Model\Entity\User $user */
		$user = $session->read($sessionKey);

		if (!$user) {
			return new Result(null, ResultInterface::FAILURE_IDENTITY_NOT_FOUND);
		}

		$identify = $this->getConfig('identify');
		if (is_callable($identify)) {
			$identify = $identify($user);
		}

		if ($identify) {
			$credentials = $identify;
			if ($identify === true) {
				$credentials = array_map(function ($field) use ($user) {
					return $user[ $field ];
				}, $this->getConfig('fields'));
			}

			/** @var \Awyiss\Model\Entity\User $reidentifiedUser */
			$reidentifiedUser = $this->_identifier->reidentify($credentials);

			if (!$reidentifiedUser) {
				// If the user is not found, redirect to the login
				$session->delete($sessionKey);

				return new Result(null, ResultInterface::FAILURE_IDENTITY_NOT_FOUND);
			}

			//If the db entry of the user changed,
			if (
				(
					!$user->changedOn &&
					$reidentifiedUser->changedOn
				) ||
				$reidentifiedUser->changedOn?->notEquals($user->changedOn)
			) {
				if (method_exists($user, 'unsetPermissionCollection')) {
					// unset the permissions and use the new changedOn value
					$user->unsetPermissionCollection();
				}
				$user->changedOn = $reidentifiedUser->changedOn;
			}
		}

		if (!($user instanceof ArrayAccess)) {
			$user = new ArrayObject($user);
		}


		return new Result($user, ResultInterface::SUCCESS);
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
