<?php declare(strict_types=1);


namespace Awyiss\Authorization\Permission;


class SimplePermission extends AbstractPermission {
	public const OPTION_GRANTED = 1;
	public const OPTION_DENIED = 0;
	public const OPTION_INDIFFERENT = NULL;

	protected ?array $options;
	protected ?string $type = 'simple';


	public function __construct (array $aa_config, ?PermissionCollection $ao_permissionCollection = NULL) {
		$this->options = [
			static::OPTION_GRANTED => __('permissions::simple_permission_option_granted'),
			static::OPTION_DENIED => __('permissions::simple_permission_option_denied'),
			static::OPTION_INDIFFERENT => __('permissions::simple_permission_option_indifferent'),
		];

		parent::__construct($aa_config, $ao_permissionCollection);
	}


	public function harmonizeOptionValue (mixed $ax_value): ?int {
		$lx_value = ($ax_value !== '' && $ax_value !== NULL) ? (int)$ax_value : NULL;

		if ($lx_value === static::OPTION_GRANTED) {
			return static::OPTION_GRANTED;
		}
		elseif ($lx_value === static::OPTION_DENIED) {
			return static::OPTION_DENIED;
		}

		return NULL;
	}


	public function isAccessible (?array $aa_access): ?bool {
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