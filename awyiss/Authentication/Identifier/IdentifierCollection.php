<?php declare(strict_types=1);


namespace Awyiss\Authentication\Identifier;


use ArrayAccess;
use Authentication\Identifier\IdentifierInterface;


/**
 * @inheritDoc
 */
class IdentifierCollection extends \Authentication\Identifier\IdentifierCollection {
	/**
	 * Re-Identifies a user or service by the passed credentials
	 *
	 * @param array $aa_credentials Authentication credentials
	 *
	 * @return ArrayAccess|array|NULL
	 * @noinspection PhpUnused
	 */
	public function reidentify(array $aa_credentials): ArrayAccess|array|null {
		/** @var IdentifierInterface|ProxyIdentifier $lo_identifier */
		foreach ($this->_loaded as $ls_identifier => $lo_identifier) {
			if (is_callable([$lo_identifier, 'reidentify'])) {
				$lx_result = $lo_identifier->reidentify($aa_credentials);
			}
			else {
				$lx_result = $lo_identifier->identify($aa_credentials);
			}

			if ($lx_result) {
				$this->_successfulIdentifier = $lo_identifier;


				return $lx_result;
			}
			$this->_errors[ $ls_identifier ] = $lo_identifier->getErrors();
		}

		$this->_successfulIdentifier = NULL;


		return NULL;
	}
}
