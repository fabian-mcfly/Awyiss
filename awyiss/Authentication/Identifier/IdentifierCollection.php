<?php


namespace Awyiss\Authentication\Identifier;


class IdentifierCollection extends \Authentication\Identifier\IdentifierCollection {
	/**
	 * Re-Identifies an user or service by the passed credentials
	 *
	 * @param array $credentials Authentication credentials
	 *
	 * @return \ArrayAccess|array|null
	 */
	public function reidentify (array $credentials): \ArrayAccess|array|null {
		/** @var \Authentication\Identifier\IdentifierInterface $lo_identifier */
		foreach ($this->_loaded as $ls_name => $lo_identifier) {
			if (is_callable([$lo_identifier, 'reidentify'])) {
				$lx_result = $lo_identifier->reidentify($credentials);
			}
			else {
				$lx_result = $lo_identifier->identify($credentials);
			}

			if ($lx_result) {
				$this->_successfulIdentifier = $lo_identifier;

				return $lx_result;
			}
			$this->_errors[ $ls_name ] = $lo_identifier->getErrors();
		}

		$this->_successfulIdentifier = NULL;

		return NULL;
	}
}