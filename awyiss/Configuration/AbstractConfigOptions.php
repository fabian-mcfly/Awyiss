<?php declare(strict_types=1);


namespace Awyiss\Configuration;


use Cake\Utility\Hash;
use Cake\Utility\Inflector;


/**
 * Default implementation of the method signatures of `ConfigOptionsInterface`
 *
 * @see \Awyiss\Configuration\ConfigOptionsInterface
 */
abstract class AbstractConfigOptions extends ConfigOptionsCollection implements ConfigOptionsInterface {
	/** @noinspection PhpMissingParentConstructorInspection */
	public function __construct () {
		$this->name = static::getScope();

		$this->initializeConfigOptions();
	}


	/**
	 * @inheritDoc
	 */
	public static function getScope (): string {
		if (!isset(static::$scope)) {
			$la_parts = explode('\\', static::class);
			static::$scope = array_pop($la_parts);
			static::$scope = substr(static::$scope, 0, -13);
			static::$scope = Inflector::underscore(static::$scope);
		}

		return static::$scope;
	}


	/**
	 * @inheritDoc
	 */
	public function getConfigOption (string|array $ax_path): ?ConfigOption {
		return Hash::get($this, $ax_path);
	}


	/**
	 * @inheritDoc
	 */
	public function validateConfigValue (string $as_configOptionName, mixed $ax_value, ?string $as_languageShortcode = NULL, bool $ab_fallbackValidity = TRUE): bool|string {
		$lo_configOption = $this->getConfigOption($as_configOptionName);

		if ( ! ($lo_configOption instanceof ConfigOption)) {
			/*
			 * If there is no config option for the given name, we cannot define what's valid and what's not
			 * This means that we need to return the default validity that the call can specify (default: TRUE)
			 *
			 * This is also the case if the given name points to a ConfigOptionsCollection instead of a ConfigOption
			 */
			return $ab_fallbackValidity;
		}

		return $lo_configOption->validateConfigValue($ax_value, $as_languageShortcode);
	}


	/**
	 * @inheritDoc
	 */
	public function typecastConfigValue (string $as_configOptionName, mixed $ax_value): mixed {
		$lo_configOption = $this->getConfigOption($as_configOptionName);

		if (!($lo_configOption instanceof ConfigOption)) {
			return $ax_value;
		}


		return $lo_configOption->typecastConfigValue($ax_value);
	}
}