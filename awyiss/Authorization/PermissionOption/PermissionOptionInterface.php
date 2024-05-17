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
	 * @param array $config
	 * @param PermissionOptionCollection $permissionOptionCollection
	 */
	public function __construct(array $config, PermissionOptionCollection $permissionOptionCollection);


	/**
	 * Return the `PermissionOptionCollection` the Permission is part of.
	 *
	 * This is useful to retreive the scope of the collection since it coult change and this permission would not know
	 * about it
	 *
	 * @return PermissionOptionCollection
	 */
	public function getPermissionOptionCollection(): PermissionOptionCollection;


	/**
	 * @param string|null $key
	 * @param mixed $default
	 * @return mixed
	 * @see \Cake\Core\InstanceConfigTrait::getConfig()
	 *
	 * Since InstanceConfigTrait does not define it:
	 * @noinspection PhpMissingReturnTypeInspection
	 */
	public function getConfig(?string $key = null, mixed $default = null): mixed;


	/**
	 * @param array<string, mixed>|string $key The key to set, or a complete array of configs.
	 * @param mixed|null $value The value to set.
	 * @param bool $merge Whether to recursively merge or overwrite existing config, defaults
	 *                                              to true.
	 * @return $this
	 * @see \Cake\Core\InstanceConfigTrait::setConfig()
	 *
	 * Since InstanceConfigTrait does not define it:
	 * @noinspection PhpMissingReturnTypeInspection
	 */
	public function setConfig(array|string $key, mixed $value = null, bool $merge = true);


	/**
	 * Return the type this permission has
	 *
	 * @return string
	 */
	public function getType(): string;


	/**
	 * Return all available options for this permission
	 *
	 * @return array
	 */
	public function getOptions(): array;


	/**
	 * Set the available options for this permission
	 *
	 * @param array $options
	 * @return $this
	 */
	public function setOptions(array $options): static;


	/**
	 * Returns true or false whether this permission offers additional settings
	 *
	 * @return bool
	 */
	public function hasSettings(): bool;


	/**
	 * Transform the given value into one that matches the permission's options
	 *
	 * @param mixed $value
	 * @return PermissionAccess|null
	 */
	public function harmonizeOptionValue(mixed $value): ?PermissionAccess;


	/**
	 * Returns PermissionType depending on whether the permission is granted, denied or not defined (indifferent)
	 *
	 * @param mixed $access
	 * @param mixed $settings
	 * @param array $additionalData
	 * @param PermissionCollection $permissionCollection
	 * @return ?bool
	 */
	public function isAccessible(mixed $access, mixed $settings, array $additionalData, PermissionCollection $permissionCollection): ?bool;
}
