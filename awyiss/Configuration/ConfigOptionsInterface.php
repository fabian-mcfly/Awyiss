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
	 * @param string|null $as_realm
	 * @return ConfigOptionCollection|array
	 * @see ConfigOption
	 */
	public function getConfigOptions(?string $as_realm = null): ConfigOptionCollection|array;


	/**
	 * Return the config option found under the path provided.
	 *
	 * @param string $as_realm
	 * @param array<string>|string $ax_path
	 * @return ConfigOption|null
	 * @see ConfigOption
	 * @see \Cake\Utility\Hash::get()
	 */
	public function getConfigOption(string $as_realm, string|array $ax_path): ?ConfigOption;


	/**
	 * Retreives a configuration class and validates the provided value for the given configOptionIdentifier
	 * Returns a string with an error message if the value is not valid.
	 *
	 * @param string $as_realm
	 * @param array|string $ax_path
	 * @param mixed $ax_value
	 * @param string|null $as_languageShortcode
	 * @param bool $ab_fallbackValidity
	 * @return string|bool
	 */
	public function validateConfigValue(
		string $as_realm,
		array|string $ax_path,
		mixed $ax_value,
		?string $as_languageShortcode = null,
		bool $ab_fallbackValidity = true
	): bool|string;


	/**
	 * Retreives a configuration class and cast the provided value to it's correct type for the given configOptionIdentifier
	 *
	 * @param string $as_realm
	 * @param array|string $ax_path
	 * @param mixed $ax_value
	 * @param string|null $as_languageShortcode
	 * @return mixed
	 * @throws \ReflectionException
	 */
	public function typecastConfigValue(string $as_realm, array|string $ax_path, mixed $ax_value, ?string $as_languageShortcode = null): mixed;


	/**
	 * Return the scope of the options-collection.
	 * If none is set, use the identifier of the class that extends this one
	 *
	 * @return string
	 */
	public static function getScope(): string;
}
