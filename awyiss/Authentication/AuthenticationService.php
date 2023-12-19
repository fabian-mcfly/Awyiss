<?php


namespace Awyiss\Authentication;


use Awyiss\Authentication\Identifier\IdentifierCollection;


class AuthenticationService extends \Authentication\AuthenticationService {
	/**
	 * {@inheritDoc}
	 */
	public function identifiers (): IdentifierCollection {
		if ($this->_identifiers === NULL) {
			$this->_identifiers = new IdentifierCollection($this->getConfig('identifiers'));
		}

		return $this->_identifiers;
	}
}