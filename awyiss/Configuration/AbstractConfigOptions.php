<?php declare(strict_types=1);


namespace Awyiss\Configuration;


use Awyiss\Awyiss;
use Cake\Utility\Hash;
use InvalidArgumentException;


/**
 * Default implementation of the method signatures of `ConfigOptionsInterface`
 *
 * @see ConfigOptionsInterface
 */
abstract class AbstractConfigOptions implements ConfigOptionsInterface {
	/**
	 * @var array<string, ConfigOptionCollection>
	 */
	protected array $realms = [];


	/**
	 * Set the scope and initialize the config options
	 */
	public function __construct() {
		$ls_scope = static::getScope();
		$ls_testScope = ConfigOptionsProvider::sanitizeScope($ls_scope);

		if ($ls_testScope !== $ls_scope) {
			static::$scope = $ls_testScope;
		}

		foreach (Awyiss::getRealms() as $ls_realm) {
			$this->realms[ $ls_realm ] = new ConfigOptionCollection();
		}

		$this->initializeConfigOptions();
	}


	/**
	 * @param string $realm
	 * @param ConfigOption|array $configOption
	 * @return $this
	 */
	public function add(string $realm, array|ConfigOption $configOption): static {
		if (!in_array($realm, Awyiss::getRealms())) {
			throw new InvalidArgumentException(
				sprintf(
					'The realm is not valid. `%s` given.',
					$realm
				)
			);
		}

		$this->realms[ $realm ]->add($configOption);


		return $this;
	}


	/**
	 * @inheritDoc
	 */
	public function getConfigOptions(?string $realm = null): ConfigOptionCollection|array {
		if ($realm === null) {
			return $this->realms;
		}

		if (!isset($this->realms[ $realm ])) {
			throw new InvalidArgumentException(sprintf('The realm is not valid. `%s` given.', $realm));
		}


		return $this->realms[ $realm ];
	}


	/**
	 * @inheritDoc
	 */
	public function getConfigOption(string $realm, string|array $path): ?ConfigOption {
		$la_configOptions = $this->getConfigOptions($realm);

		if (empty($la_configOptions)) {
			return null;
		}

		$la_path = $this->sanitizePath($path);

		$lo_configOption = Hash::get($la_configOptions, $la_path);

		if ($lo_configOption instanceof ConfigOptionCollection) {
			throw new InvalidArgumentException(sprintf('Expected a path to a config option. Found `%s` instead.`', ConfigOptionCollection::class));
		}


		return $lo_configOption;
	}


	/**
	 * @param array|string $path
	 * @return array
	 */
	public function sanitizePath(array|string $path): array {
		$la_identifierPath = $path;
		if (!is_array($path)) {
			$la_identifierPath = explode('.', $path);
		}


		return array_map(function ($pathFragment) {
			return ConfigOptionsProvider::sanitizeIdentifier($pathFragment);
		}, $la_identifierPath);
	}


	/**
	 * @inheritDoc
	 */
	public function validateConfigValue(
		string $realm,
		array|string $path,
		mixed $value,
		?string $languageShortcode = null,
		bool $fallbackValidity = true
	): bool|string {
		try {
			$lo_configOption = $this->getConfigOption($realm, $path);
		}
		catch (InvalidArgumentException) {
			return false;
		}

		if (!($lo_configOption instanceof ConfigOption)) {
			/*
			 * If there is no config option for the given identifier, we cannot define what's valid and what's not
			 * This means that we need to return the default validity that the call can specify (default: true)
			 *
			 * This is also the case if the given identifier points to a ConfigOptionsCollection instead of a ConfigOption
			 */
			return $fallbackValidity;
		}


		return $lo_configOption->validateConfigValue($value, $languageShortcode);
	}


	/**
	 * @inheritDoc
	 */
	public function typecastConfigValue(
		string $realm,
		array|string $path,
		mixed $value,
		?string $languageShortcode = null,
	): mixed {
		try {
			$lo_configOption = $this->getConfigOption($realm, $path);
		}
		catch (InvalidArgumentException) {
			return null;
		}

		if (!($lo_configOption instanceof ConfigOption)) {
			return $value;
		}


		return $lo_configOption->typecastConfigValue($value, $languageShortcode);
	}


	/**
	 * @inheritDoc
	 */
	public static function getScope(): string {
		if (!isset(static::$scope)) {
			$la_parts = explode('\\', static::class);
			static::$scope = array_pop($la_parts);
			static::$scope = substr(static::$scope, 0, -13);
			static::$scope = ConfigOptionsProvider::sanitizeScope(static::$scope);
		}


		return static::$scope;
	}
}
