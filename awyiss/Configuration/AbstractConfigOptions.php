<?php declare(strict_types=1);


namespace Awyiss\Configuration;


use Awyiss\Awyiss;
use Cake\Utility\Hash;


/**
 * Default implementation of the method signatures of `ConfigOptionsInterface`
 *
 * @see ConfigOptionsInterface
 */
abstract class AbstractConfigOptions implements ConfigOptionsInterface {
	/** @var array<string, ConfigOptionCollection> */
	protected array $realms = [];


	public function __construct() {
		$ls_scope = static::getScope();
		$ls_testScope = ConfigOptionsProvider::sanitizeScope($ls_scope);
		if ($ls_testScope !== $ls_scope) {
			throw new \RuntimeException(
				sprintf(
					'The provided scope should be written CamelCased (`%s`). `%s` given.',
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
	 * @param array|ConfigOption $ax_configOption
	 *
	 * @return $this
	 */
	public function add(string $as_realm, array|ConfigOption $ax_configOption): static {
		if (!in_array($as_realm, Awyiss::getRealms())) {
			throw new \RuntimeException(
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
	public function getConfigOptions(string $as_realm = NULL): ConfigOptionCollection|array {
		if ($as_realm === NULL) {
			return $this->realms;
		}

		if (!isset($this->realms[ $as_realm ])) {
			throw new \RuntimeException(sprintf('The realm is not valid. `%s` given.', $as_realm));
		}


		return $this->realms[ $as_realm ];
	}


	/**
	 * @inheritDoc
	 */
	public function getConfigOption(string $as_realm, string|array $ax_path): ?ConfigOption {
		$la_configOptions = $this->realms[ $as_realm ] ?? [];

		if (empty($la_configOptions)) {
			return NULL;
		}


		return Hash::get($la_configOptions, $ax_path);
	}


	/**
	 * @inheritDoc
	 */
	public function validateConfigValue(
		string $as_realm,
		string $as_identifier,
		mixed $ax_value,
		?string $as_languageShortcode = NULL,
		bool $ab_fallbackValidity = TRUE
	): bool|string {
		$lo_configOption = $this->getConfigOption($as_realm, $as_identifier);

		if (!($lo_configOption instanceof ConfigOption)) {
			/*
			 * If there is no config option for the given identifier, we cannot define what's valid and what's not
			 * This means that we need to return the default validity that the call can specify (default: TRUE)
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
	public function typecastConfigValue(string $as_realm, string $as_identifier, mixed $ax_value): mixed {
		$lo_configOption = $this->getConfigOption($as_realm, $as_identifier);

		if (!($lo_configOption instanceof ConfigOption)) {
			return $ax_value;
		}


		return $lo_configOption->typecastConfigValue($ax_value);
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
