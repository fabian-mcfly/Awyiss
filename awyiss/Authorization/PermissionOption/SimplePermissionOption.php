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
	public function __construct(array $config, PermissionOptionCollection $permissionOptionCollection) {
		parent::__construct($config, $permissionOptionCollection);

		$this->options = [
			'granted' => PermissionAccess::Granted,
			'denied' => PermissionAccess::Denied,
			'indifferent' => null,
		];
	}


	/**
	 * @inheritDoc
	 */
	public function harmonizeOptionValue(mixed $value): ?PermissionAccess {
		$value = $value !== '' && $value !== null ? (int)$value : null;

		if ($value === null) {
			return null;
		}

		return PermissionAccess::tryFrom($value);
	}


	/**
	 * @inheritDoc
	 */
	public function isAccessible(mixed $access, mixed $settings, array $additionalData, PermissionCollection $permissionCollection): ?bool {
		if (!$access instanceof PermissionAccess) {
			$access = $this->harmonizeOptionValue($access);
		}

		if ($access === PermissionAccess::Granted) {
			return true;
		}
		elseif ($access === PermissionAccess::Denied) {
			return false;
		}


		return null;
	}
}
