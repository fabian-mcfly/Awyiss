<?php declare(strict_types=1);


namespace Awyiss\Authentication\Identifier;


use ArrayAccess;
use Authentication\Identifier\IdentifierCollection as BaseIdentifierCollection;


/**
 * @inheritDoc
 */
class IdentifierCollection extends BaseIdentifierCollection {
	/**
	 * Re-Identifies a user or service by the passed credentials
	 *
	 * @param array $credentials Authentication credentials
	 * @return ArrayAccess|array|null
	 * @noinspection PhpUnused
	 */
	public function reidentify(array $credentials): ArrayAccess|array|null {
		/** @var \Authentication\Identifier\IdentifierInterface $identifier */
		foreach ($this->_loaded as $identifierName => $identifier) {
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

			$this->_errors[ $identifierName ] = $identifier->getErrors();
		}

		$this->_successfulIdentifier = null;


		return null;
	}
}
