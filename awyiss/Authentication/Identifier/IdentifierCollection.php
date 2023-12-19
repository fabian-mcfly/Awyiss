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
	public function reidentify (array $credentials) {
		/** @var \Authentication\Identifier\IdentifierInterface $identifier */
		foreach ($this->_loaded as $name => $identifier) {
			if (is_callable([$identifier, 'reidentify'])) {
				$result = $identifier->reidentify($credentials);
			}
			else {
				$result = $identifier->identify($credentials);
			}

			if ($result) {
				$this->_successfulIdentifier = $identifier;

				return $result;
			}
			$this->_errors[ $name ] = $identifier->getErrors();
		}

		$this->_successfulIdentifier = NULL;

		return NULL;
	}
}