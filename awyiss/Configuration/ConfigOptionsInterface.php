<?php declare(strict_types=1);


namespace Awyiss\Configuration;


/**
 * Signature of all neccessary methods to connect `ConfigOption` with `ConfigOptionsProvider`
 *
 * @see ConfigOption
 * @see ConfigOptionsProvider
 */
interface ConfigOptionsInterface {
	/**
	 * Initializes the configuration options and adds them to the current object (`ConfigOptionsCollection`)
	 *
	 * @return void
	 */
	public function initializeConfigOptions(): void;


	/**
	 * Return all config options as flattened array
	 *
	 * @param string|null $realm
	 * @return ConfigOptionCollection|array
	 * @see ConfigOption
	 */
	public function getConfigOptions(?string $realm = null): ConfigOptionCollection|array;


	/**
	 * Return the config option found under the path provided.
	 *
	 * @param string $realm
	 * @param array<string>|string $path
	 * @return ConfigOption|null
	 * @see ConfigOption
	 * @see \Cake\Utility\Hash::get()
	 */
	public function getConfigOption(string $realm, string|array $path): ?ConfigOption;


	/**
	 * Retreives a configuration class and validates the provided value for the given configOptionIdentifier
	 * Returns a string with an error message if the value is not valid.
	 *
	 * @param string $realm
	 * @param array|string $path
	 * @param mixed $value
	 * @param string|null $languageShortcode
	 * @param bool $fallbackValidity
	 * @return string|bool
	 */
	public function validateConfigValue(
		string $realm,
		array|string $path,
		mixed $value,
		?string $languageShortcode = null,
		bool $fallbackValidity = true
	): bool|string;


	/**
	 * Retreives a configuration class and cast the provided value to it's correct type for the given configOptionIdentifier
	 *
	 * @param string $realm
	 * @param array|string $path
	 * @param mixed $value
	 * @param string|null $languageShortcode
	 * @return mixed
	 * @throws \ReflectionException
	 */
	public function typecastConfigValue(
		string $realm,
		array|string $path,
		mixed $value,
		?string $languageShortcode = null,
	): mixed;


	/**
	 * Return the scope of the options-collection.
	 * If none is set, use the identifier of the class that extends this one
	 *
	 * @return string
	 */
	public static function getScope(): string;
}
