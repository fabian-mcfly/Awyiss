<?php declare(strict_types=1);


namespace Awyiss\Authorization\Permission;


use Awyiss\Authorization\AccessCollection;


/**
 * A simple permission that offers access, depending on three options:
 * - granted
 * - denied
 * - indifferent
 */
class SimplePermission extends AbstractPermission {
	final public const OPTION_GRANTED = 1;
	final public const OPTION_DENIED = 0;
	final public const OPTION_INDIFFERENT = NULL;
	protected string $type = 'simple';


	/**
	 * @inheritDoc
	 */
	public function __construct (array $aa_config, PermissionCollection $ao_permissionCollection) {
		parent::__construct($aa_config, $ao_permissionCollection);

		$this->options = [
			static::OPTION_GRANTED,
			static::OPTION_DENIED,
			static::OPTION_INDIFFERENT,
		];
	}


	/**
	 * @inheritDoc
	 */
	public function harmonizeOptionValue (mixed $ax_value): ?int {
		$lx_value = ($ax_value !== '' && $ax_value !== NULL) ? (int)$ax_value : NULL;

		if ($lx_value === static::OPTION_GRANTED) {
			return static::OPTION_GRANTED;
		}
		elseif ($lx_value === static::OPTION_DENIED) {
			return static::OPTION_DENIED;
		}

		return static::OPTION_INDIFFERENT;
	}


	/**
	 * @inheritDoc
	 */
	public function isAccessible (array $aa_access, array $aa_additionalData, AccessCollection $ao_accessCollection): ?bool {
		$ls_identifier = $this->getConfig('identifier');

		$la_accesses = [];
		foreach (($aa_access[ $ls_identifier ] ?? []) AS $la_access) {
			$la_accesses[] = $this->harmonizeOptionValue($la_access['access']);
		}

		if (in_array(static::OPTION_DENIED, $la_accesses, TRUE)) {
			return FALSE;
		}
		elseif (in_array(static::OPTION_GRANTED, $la_accesses, TRUE)) {
			return TRUE;
		}

		return NULL;
	}
}