<?php declare(strict_types=1);


namespace Awyiss\Configuration;


use Awyiss\Awyiss;
use Cake\Utility\Hash;
use InvalidArgumentException;
use RuntimeException;


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
			throw new RuntimeException(
				sprintf(
					'The provided scope must be written `%s`, `%s` given.',
					$ls_testScope,
					$ls_scope
				)
			);
		}

		foreach (Awyiss::getRealms() as $ls_realm) {
			$this->realms[ $ls_realm ] = new ConfigOptionCollection();
		}

		$this->initializeConfigOptions();
	}


	/**
	 * @param string $as_realm
	 * @param ConfigOption|array $ax_configOption
	 * @return $this
	 */
	public function add(string $as_realm, array|ConfigOption $ax_configOption): static {
		if (!in_array($as_realm, Awyiss::getRealms())) {
			throw new InvalidArgumentException(
				sprintf(
					'The realm is not valid. `%s` given.',
					$as_realm
				)
			);
		}

		$this->realms[ $as_realm ]->add($ax_configOption);


		return $this;
	}


	/**
	 * @inheritDoc
	 */
	public function getConfigOptions(?string $as_realm = null): ConfigOptionCollection|array {
		if ($as_realm === null) {
			return $this->realms;
		}

		if (!isset($this->realms[ $as_realm ])) {
			throw new InvalidArgumentException(sprintf('The realm is not valid. `%s` given.', $as_realm));
		}


		return $this->realms[ $as_realm ];
	}


	/**
	 * @inheritDoc
	 */
	public function getConfigOption(string $as_realm, string|array $ax_path): ?ConfigOption {
		$la_configOptions = $this->realms[ $as_realm ] ?? [];

		if (empty($la_configOptions)) {
			return null;
		}

		$la_path = $this->sanitizePath($ax_path);

		$lo_configOption = Hash::get($la_configOptions, $la_path);

		if ($lo_configOption instanceof ConfigOptionCollection) {
			throw new InvalidArgumentException(sprintf('Expected a path to a config option. Found `%s` instead.`', ConfigOptionCollection::class));
		}


		return $lo_configOption;
	}


	/**
	 * @param array|string $ax_path
	 * @return array
	 */
	public function sanitizePath(array|string $ax_path): array {
		$la_identifierPath = $ax_path;
		if (!is_array($ax_path)) {
			$la_identifierPath = explode('.', $ax_path);
		}


		return array_map(function ($as_pathFragment) {
			return ConfigOptionsProvider::sanitizeIdentifier($as_pathFragment);
		}, $la_identifierPath);
	}


	/**
	 * @inheritDoc
	 */
	public function validateConfigValue(
		string $as_realm,
		array|string $ax_path,
		mixed $ax_value,
		?string $as_languageShortcode = null,
		bool $ab_fallbackValidity = true
	): bool|string {
		try {
			$lo_configOption = $this->getConfigOption($as_realm, $ax_path);
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
			return $ab_fallbackValidity;
		}


		return $lo_configOption->validateConfigValue($ax_value, $as_languageShortcode);
	}


	/**
	 * @inheritDoc
	 */
	public function typecastConfigValue(
		string $as_realm,
		array|string $ax_path,
		mixed $ax_value,
		?string $as_languageShortcode = null,
	): mixed {
		try {
			$lo_configOption = $this->getConfigOption($as_realm, $ax_path);
		}
		catch (InvalidArgumentException) {
			return null;
		}

		if (!($lo_configOption instanceof ConfigOption)) {
			return $ax_value;
		}


		return $lo_configOption->typecastConfigValue($ax_value, $as_languageShortcode);
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
