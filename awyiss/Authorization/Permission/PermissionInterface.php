<?php declare(strict_types=1);


namespace Awyiss\Authorization\Permission;


interface PermissionInterface {
	public function __construct (array $aa_config, PermissionCollection $ao_permissionCollection);


	public function getPermissionCollection (): PermissionCollection;


	public function getConfig(?string $as_key = NULL, $ax_default = NULL);


	public function setConfig($as_key, $ax_value = NULL, $ab_merge = TRUE);


	public function getType(): string;


	/**
	 * @return array
	 */
	public function getOptions (): array;


	/**
	 * @param array $aa_options
	 *
	 * @return $this
	 */
	public function setOptions (array $aa_options): self;


	/**
	 * @return bool
	 */
	public function hasSettings (): bool;


	public function harmonizeOptionValue (mixed $ax_value): mixed;


	/**
	 * @param null|array $aa_access
	 *
	 * @return null|bool
	 *
	 * TODO: additional parameters
	 */
	public function isAccessible (?array $aa_access): ?bool;
}