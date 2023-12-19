<?php


declare(strict_types=1);


namespace Awyiss\Authentication\Authenticator;


use Authentication\Authenticator\Result;
use Authentication\Authenticator\ResultInterface;
use Psr\Http\Message\ServerRequestInterface;


class SessionAuthenticator extends \Authentication\Authenticator\SessionAuthenticator {
	/**
	 * @inheritDoc
	 */
	public function authenticate (ServerRequestInterface $request): ResultInterface {
		$sessionKey = $this->getConfig('sessionKey');
		/** @var \Cake\Http\Session $session */
		$session = $request->getAttribute('session');
		$user = $session->read($sessionKey);

		if (empty($user)) {
			return new Result(NULL, Result::FAILURE_IDENTITY_NOT_FOUND);
		}

		$lx_identify = $this->getConfig('identify');
		if (is_callable($lx_identify)) {
			$lx_identify = $lx_identify($user);
		}

		if ($lx_identify) {
			$credentials = $lx_identify;
			if ($lx_identify === TRUE) {
				$credentials = [];
				foreach ($this->getConfig('fields') as $key => $field) {
					$credentials[ $key ] = $user[ $field ];
				}
			}

			$user = $this->_identifier->reidentify($credentials);

			if (empty($user)) {
				return new Result(NULL, Result::FAILURE_CREDENTIALS_INVALID);
			}
		}

		if ( ! ($user instanceof \ArrayAccess)) {
			$user = new \ArrayObject($user);
		}

		return new Result($user, Result::SUCCESS);
	}
}