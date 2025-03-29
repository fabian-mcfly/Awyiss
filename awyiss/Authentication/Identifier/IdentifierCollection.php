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
		/** @var \Authentication\Identifier\IdentifierInterface $lo_identifier */
		foreach ($this->_loaded as $ls_identifier => $lo_identifier) {
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

			$this->_errors[ $ls_identifier ] = $lo_identifier->getErrors();
		}

		$this->_successfulIdentifier = null;


		return null;
	}
}
