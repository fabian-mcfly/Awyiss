<?php declare(strict_types=1);


namespace Awyiss\Authorization\PermissionOption;


use Awyiss\Authorization\Permission\PermissionCollection;
use Awyiss\Authorization\Permission\PermissionTypes;


/**
 * A simple permission that offers access, depending on three options:
 * - granted
 * - denied
 * - indifferent
 */
class SimplePermissionOption extends AbstractPermissionOption {
	//final public const OPTION_GRANTED = PermissionTypes::OPTION_GRANTED;
	//final public const OPTION_DENIED = PermissionTypes::OPTION_DENIED;
	//final public const OPTION_INDIFFERENT = PermissionTypes::OPTION_INDIFFERENT;
	protected string $type = 'simple';


	/**
	 * @inheritDoc
	 */
	public function __construct (array $aa_config, PermissionOptionCollection $ao_permissionOptionCollection) {
		parent::__construct($aa_config, $ao_permissionOptionCollection);

		$this->options = [
			'granted' => PermissionTypes::OPTION_GRANTED->databaseValue(),
			'denied' => PermissionTypes::OPTION_DENIED->databaseValue(),
			'indifferent' => PermissionTypes::OPTION_INDIFFERENT->databaseValue(),
		];
	}


	/**
	 * @inheritDoc
	 */
	public function harmonizeOptionValue (mixed $ax_value): PermissionTypes {
		$lx_value = ($ax_value !== '' && $ax_value !== NULL) ? (int)$ax_value : NULL;

		return PermissionTypes::from($lx_value);
	}


	/**
	 * @inheritDoc
	 */
	public function isAccessible (mixed $ax_access, mixed $ax_settings, array $aa_additionalData, PermissionCollection $ao_permissionCollection): ?bool {
		$lx_access = $this->harmonizeOptionValue($ax_access);

		if ($lx_access === PermissionTypes::OPTION_GRANTED) {
			return TRUE;
		}
		elseif ($lx_access === PermissionTypes::OPTION_DENIED) {
			return FALSE;
		}

		return NULL;
	}
}