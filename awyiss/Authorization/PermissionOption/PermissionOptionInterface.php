<?php declare(strict_types=1);


namespace Awyiss\Authorization\PermissionOption;


use Awyiss\Authorization\Permission\PermissionAccess;
use Awyiss\Authorization\Permission\PermissionCollection;


/**
 * Interface with method signatures for a Permission
 */
interface PermissionOptionInterface {
	/**
	 * Set the config and remember the PermissionOptionCollection
	 *
	 * @param array                      $aa_config
	 * @param PermissionOptionCollection $ao_permissionOptionCollection
	 */
	public function __construct (array $aa_config, PermissionOptionCollection $ao_permissionOptionCollection);


	/**
	 * Return the `PermissionOptionCollection` the Permission is part of.
	 *
	 * This is useful to retreive the scope of the collection since it coult change and this permission would not know
	 * about it
	 *
	 * @return PermissionOptionCollection
	 */
	public function getPermissionOptionCollection (): PermissionOptionCollection;


	/**
	 * @param null|string $as_key
	 * @param mixed       $ax_default
	 *
	 * @return mixed
	 *
	 * @see          \Cake\Core\InstanceConfigTrait::getConfig()
	 *
	 * Since InstanceConfigTrait does not define it:
	 * @noinspection PhpMissingReturnTypeInspection
	 * @noinspection PhpMissingParamTypeInspection
	 */
	public function getConfig (?string $as_key = NULL, $ax_default = NULL);


	/**
	 * @param array<string, mixed>|string $ax_key   The key to set, or a complete array of configs.
	 * @param mixed|null                  $ax_value The value to set.
	 * @param bool                        $ab_merge Whether to recursively merge or overwrite existing config, defaults
	 *                                              to true.
	 *
	 * @return $this
	 *
	 * @see          \Cake\Core\InstanceConfigTrait::setConfig()
	 *
	 * Since InstanceConfigTrait does not define it:
	 * @noinspection PhpMissingReturnTypeInspection
	 * @noinspection PhpMissingParamTypeInspection
	 */
	public function setConfig (array|string $key, mixed $value = NULL, bool $merge = TRUE);


	/**
	 * Return the type this permission has
	 *
	 * @return string
	 */
	public function getType (): string;


	/**
	 * Return all available options for this permission
	 *
	 * @return array
	 */
	public function getOptions (): array;


	/**
	 * Set the available options for this permission
	 *
	 * @param array $aa_options
	 *
	 * @return $this
	 */
	public function setOptions (array $aa_options): static;


	/**
	 * Returns TRUE or FALSE whether this permission offers additional settings
	 *
	 * @return bool
	 */
	public function hasSettings (): bool;


	/**
	 * Transform the given value into one that matches the permission's options
	 *
	 * @param mixed $ax_value
	 *
	 * @return PermissionAccess|NULL
	 */
	public function harmonizeOptionValue (mixed $ax_value): ?PermissionAccess;


	/**
	 * Returns PermissionType depending on whether the permission is granted, denied or not defined (indifferent)
	 *
	 * @param mixed                $ax_access
	 * @param mixed                $ax_settings
	 * @param array                $aa_additionalData
	 * @param PermissionCollection $ao_permissionCollection
	 *
	 * @return ?bool
	 */
	public function isAccessible (mixed $ax_access, mixed $ax_settings, array $aa_additionalData, PermissionCollection $ao_permissionCollection): ?bool;
}
