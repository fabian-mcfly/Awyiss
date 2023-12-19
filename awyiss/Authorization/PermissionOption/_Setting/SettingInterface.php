<?php declare(strict_types=1);


namespace Awyiss\Authorization\PermissionOption\Setting;


/**
 * Interface to be used by a Setting-class that offers
 * additional settings for a permission.
 */
interface SettingInterface {
	final public const TYPE_RADIO = 'radio';
	final public const TYPE_SELECT = 'select';

	/**
	 * @return string
	 */
	public function getType (): string;


	/**
	 * @param string $as_type
	 *
	 * @return $this
	 */
	public function setType (string $as_type): static;


	/**
	 * @return string
	 */
	public function getOptions (): string;


	/**
	 * @param array $aa_options
	 *
	 * @return $this
	 */
	public function setOptions (array $aa_options): static;
}