<?php declare(strict_types=1);


namespace Awyiss\Authorization\PermissionOption\Setting;


/**
 * Interface to be used by a Setting-class that offers
 * additional settings for a permission.
 */
interface SettingInterface {
	/**
	 * WIP
	 */
	final public const string TYPE_RADIO = 'radio';
	/**
	 * WIP
	 */
	final public const string TYPE_SELECT = 'select';


	/**
	 * @return string
	 */
	public function getType(): string;


	/**
	 * @param string $type
	 * @return $this
	 */
	public function setType(string $type): static;


	/**
	 * @return string
	 */
	public function getOptions(): string;


	/**
	 * @param array $options
	 * @return $this
	 */
	public function setOptions(array $options): static;
}
