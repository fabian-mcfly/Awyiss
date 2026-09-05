<?php declare(strict_types=1);


namespace Awyiss\Authentication\Authenticator;


use ArrayAccess;
use ArrayObject;
use Authentication\Authenticator\Result;
use Authentication\Authenticator\ResultInterface;
use Authentication\Authenticator\SessionAuthenticator as BaseSessionAuthenticator;
use Authentication\Identifier\IdentifierInterface;
use Authentication\Identifier\PasswordIdentifier;
use Awyiss\Authorization\IdentityGroupPermissionInterface;
use Awyiss\Authorization\IdentityPermissionsInterface;
use Psr\Http\Message\ServerRequestInterface;


/**
 * Session Authenticator
 */
class SessionAuthenticator extends BaseSessionAuthenticator {
	/**
	 * Identifier or identifiers collection.
	 *
	 * @var \Authentication\Identifier\IdentifierInterface|null
	 */
	protected ?IdentifierInterface $_identifier; // phpcs:ignore


	/**
	 * {@inheritDoc}
	 *
	 * Extended to call the config setting `identify` if it's a callable
	 * and to use `reidentify`-method on `\Awyiss\Authentication\Identifier\IdentifierCollection::reidentify()`
	 *
	 * @param \Psr\Http\Message\ServerRequestInterface $request The request.
	 * @return \Authentication\Authenticator\ResultInterface The result of the authentication.
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

			$identifier = $this->getIdentifier();
			$reidentifiedUser = null;
			if ($identifier instanceof PasswordIdentifier) {
				/** @var \Authentication\IdentityInterface $reidentifiedUser */
				$reidentifiedUser = $identifier->identify($credentials);
			}

			if (!$reidentifiedUser) {
				// If the user is not found, redirect to the login
				$session->delete($sessionKey);

				return new Result(null, ResultInterface::FAILURE_IDENTITY_NOT_FOUND);
			}

			//If the db entry of the user changed,
			if (
				(
					!$user->changedOn
					&& $reidentifiedUser->changedOn
				)
				|| $reidentifiedUser->changedOn?->notEquals($user->changedOn)
			) {
				// If the class implements `IdentityPermissionsInterface`, we need to reset the permissions
				if ($user instanceof IdentityPermissionsInterface) {
					$user->unsetPermissionCollection();
				}

				// If the class implements `IdentityGroupPermissionInterface`, we need to reset the customer groups
				if ($user instanceof IdentityGroupPermissionInterface) {
					$user->unsetGroups();
				}

				$user->changedOn = $reidentifiedUser->changedOn;
				$user->twoFactorEnabled = $reidentifiedUser->twoFactorEnabled;
				$user->twoFactorSecret = $reidentifiedUser->twoFactorSecret;
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
