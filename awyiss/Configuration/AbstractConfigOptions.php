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
	 * @var array<string, ConfigOptionsCollection>
	 */
	protected array $realms = [];


	/**
	 * Set the scope and initialize the config options
	 */
	public function __construct() {
		foreach (Awyiss::getRealms() as $realm) {
			$this->realms[ $realm ] = new ConfigOptionsCollection();
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
	public function getConfigOptions(?string $realm = null): ConfigOptionsCollection|array {
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
		$configOptions = $this->getConfigOptions($realm);

		if (empty($configOptions)) {
			return null;
		}

		$path = $this->sanitizePath($path);

		$configOption = Hash::get($configOptions, $path);

		if ($configOption instanceof ConfigOptionsCollection) {
			throw new InvalidArgumentException(sprintf('Expected a path to a config option. Found `%s` instead.`', ConfigOptionsCollection::class));
		}


		return $configOption;
	}


	/**
	 * @param array|string $path
	 * @return array
	 */
	public function sanitizePath(array|string $path): array {
		$identifierPath = $path;
		if (!is_array($path)) {
			$identifierPath = explode('.', $path);
		}


		return array_map(function ($pathFragment) {
			return ConfigOptionsProvider::sanitizeIdentifier($pathFragment);
		}, $identifierPath);
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
			$configOption = $this->getConfigOption($realm, $path);
		}
		catch (InvalidArgumentException) {
			return false;
		}

		if (!$configOption instanceof ConfigOption) {
			/*
			 * If there is no config option for the given identifier, we cannot define what's valid and what's not
			 * This means that we need to return the default validity that the call can specify (default: true)
			 *
			 * This is also the case if the given identifier points to a ConfigOptionsCollection instead of a ConfigOption
			 */
			return $fallbackValidity;
		}


		return $configOption->validateConfigValue($value, $languageShortcode);
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
			$configOption = $this->getConfigOption($realm, $path);
		}
		catch (InvalidArgumentException) {
			return null;
		}

		if (!($configOption instanceof ConfigOption)) {
			return $value;
		}


		return $configOption->typecastConfigValue($value, $languageShortcode);
	}


	/**
	 * @inheritDoc
	 */
	public static function getScope(): string {
		$parts = explode('\\', trim(static::class, '\\'));
		$scope = array_pop($parts);
		$scope = substr($scope, 0, -13);

		return ConfigOptionsProvider::sanitizeScope($scope);
	}
}
