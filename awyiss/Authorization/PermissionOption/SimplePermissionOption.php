<?php declare(strict_types=1);


namespace Awyiss\Authorization\PermissionOption;


use Awyiss\Authorization\Permission\PermissionAccess;
use Awyiss\Authorization\Permission\PermissionCollection;


/**
 * A simple permission that offers access, depending on three options:
 * - granted
 * - denied
 * - indifferent
 */
class SimplePermissionOption extends AbstractPermissionOption {
	/**
	 * @var string
	 */
	protected string $type = 'simple';


	/**
	 * @inheritDoc
	 */
	public function __construct(array $aa_config, PermissionOptionCollection $ao_permissionOptionCollection) {
		parent::__construct($aa_config, $ao_permissionOptionCollection);

		$this->options = [
			'granted' => PermissionAccess::OPTION_GRANTED,
			'denied' => PermissionAccess::OPTION_DENIED,
			'indifferent' => null,
		];
	}


	/**
	 * @inheritDoc
	 */
	public function harmonizeOptionValue(mixed $ax_value): ?PermissionAccess {
		$lx_value = $ax_value !== '' && $ax_value !== null ? (int)$ax_value : null;

		if ($lx_value === null) {
			return null;
		}


		return PermissionAccess::tryFrom($lx_value);
	}


	/**
	 * @inheritDoc
	 */
	public function isAccessible(mixed $ax_access, mixed $ax_settings, array $aa_additionalData, PermissionCollection $ao_permissionCollection): ?bool {
		$lx_access = $ax_access;

		if (!$lx_access instanceof PermissionAccess) {
			$lx_access = $this->harmonizeOptionValue($ax_access);
		}

		if ($lx_access === PermissionAccess::OPTION_GRANTED) {
			return true;
		}
		elseif ($lx_access === PermissionAccess::OPTION_DENIED) {
			return false;
		}


		return null;
	}
}
