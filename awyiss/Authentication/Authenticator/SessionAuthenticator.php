<?php declare(strict_types=1);


namespace Awyiss\Authentication\Authenticator;


use Authentication\Authenticator\Result;
use Authentication\Authenticator\ResultInterface;
use Psr\Http\Message\ServerRequestInterface;


class SessionAuthenticator extends \Authentication\Authenticator\SessionAuthenticator {
	/**
	 * @inheritDoc
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function authenticate (ServerRequestInterface $ao_request): ResultInterface {
		$ls_sessionKey = $this->getConfig('sessionKey');
		/** @var \Cake\Http\Session $lo_session */
		$lo_session = $ao_request->getAttribute('session');
		$lo_user = $lo_session->read($ls_sessionKey);

		if (empty($lo_user)) {
			return new Result(NULL, ResultInterface::FAILURE_IDENTITY_NOT_FOUND);
		}

		$lx_identify = $this->getConfig('identify');
		if (is_callable($lx_identify)) {
			$lx_identify = $lx_identify($lo_user);
		}

		if ($lx_identify) {
			$la_credentials = $lx_identify;
			if ($lx_identify === TRUE) {
				$la_credentials = [];
				foreach ($this->getConfig('fields') as $key => $field) {
					$la_credentials[ $key ] = $lo_user[ $field ];
				}
			}

			/** @noinspection PhpPossiblePolymorphicInvocationInspection */
			$lo_user = $this->_identifier->reidentify($la_credentials);

			if (empty($lo_user)) {
				return new Result(NULL, ResultInterface::FAILURE_CREDENTIALS_INVALID);
			}
		}

		if ( ! ($lo_user instanceof \ArrayAccess)) {
			$lo_user = new \ArrayObject($lo_user);
		}

		//dd(debug_backtrace());

		return new Result($lo_user, ResultInterface::SUCCESS);
	}
}