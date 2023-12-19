<?php declare(strict_types=1);


namespace Awyiss\ConfigOptions;


abstract class AbstractConfigOptions extends ConfigOptionsCollection {
	protected static ?string $scope = NULL;



	/** @noinspection PhpMissingParentConstructorInspection */
	public function __construct () {
		$this->name = static::getScope();

		$this->initializeConfigOptions();
	}


	public static function getScope (): string {
		if (static::$scope === NULL) {
			$la_parts = explode('\\', static::class);
			static::$scope = array_pop($la_parts);
			static::$scope = substr(static::$scope, 0, -13);
			static::$scope = \Cake\Utility\Inflector::underscore(static::$scope);
		}

		return static::$scope;
	}


	/**
	 * @noinspection PhpUnused
	 */
	public function getConfigOption (string $as_path) {
		return \Cake\Utility\Hash::get($this, $as_path);
	}


	abstract public function initializeConfigOptions (): void;

	//abstract public function getConfigOptions ();
	//abstract public function getDefaults (): array;
}