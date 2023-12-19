<?php declare(strict_types=1);


namespace Awyiss\Configuration;


/**
 * Signature of all neccessary methods to connect `ConfigOption` with `ConfigOptionsProvider`
 *
 * @see \Awyiss\Configuration\ConfigOption
 * @see \Awyiss\Configuration\ConfigOptionsProvider
 */
interface ConfigOptionsInterface {
	/**
	 * Initializes the configuration options and adds them to the current object (`ConfigOptionsCollection`)
	 *
	 * @return void
	 */
	public function initializeConfigOptions (): void;

	/**
	 * Return the scope of the options-collection.
	 * If none is set, use the name of the class that extends this one
	 *
	 * @return string
	 */
	public static function getScope (): string;


	/**
	 * Return the config option found under the path provided.
	 *
	 * @param string|string[] $ax_path
	 *
	 * @see \Awyiss\Configuration\ConfigOption
	 * @see \Cake\Utility\Hash::get()
	 */
	public function getConfigOption (string|array $ax_path): ?ConfigOption;


	/**
	 * Retreives a configuration class and validates the provided value for the given configOptionName
	 *
	 * Returns a string with an error message if the value is not valid.
	 *
	 * @param string $as_configOptionName
	 * @param mixed $ax_value
	 * @param null|string $as_languageShortcode
	 * @param bool $ab_fallbackValidity
	 *
	 * @return bool|string
	 */
	public function validateConfigValue (string $as_configOptionName, mixed $ax_value, ?string $as_languageShortcode = NULL, bool $ab_fallbackValidity = TRUE): bool|string;


	/**
	 * Retreives a configuration class and cast the provided value to it's correct type for the given configOptionName
	 *
	 * @param string $as_configOptionName
	 * @param mixed $ax_value
	 *
	 * @return mixed
	 * @throws \ReflectionException
	 */
	public function typecastConfigValue (string $as_configOptionName, mixed $ax_value): mixed;
}
