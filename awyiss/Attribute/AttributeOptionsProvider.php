<?php declare(strict_types=1);


namespace Awyiss\Attribute;


use Awyiss\Core\App;
use Awyiss\Utility\Inflector;
use Cake\Utility\Text;
use RuntimeException;


/**
 * Provides access to all AttributeOptionsCollection classes in both the Awyiss and the custom namespace.
 */
class AttributeOptionsProvider {
	/**
	 * @var array<string, \Awyiss\Attribute\AttributeOptionsCollectionInterface>
	 */
	protected static array $attributeOptions = [];
	/**
	 * @var bool
	 */
	protected static bool $foundAll = false;
	/**
	 * @var array<string, \Awyiss\Attribute\AttributeOptionsCollectionInterface>
	 */
	protected static array $loadedAttributeOptions = [];


	/**
	 * Constructor. Throw an exception since this class only offers static methods
	 */
	private function __construct() {
		throw new RuntimeException(sprintf('The class `%s` cannot be instantiated', self::class));
	}


	/**
	 * Returns all found AttributeOptions classes in both the Awyiss and the custom namespace
	 *
	 * @param bool $returnLoaded Whether already loaded options should be returned.
	 * @return array<string, class-string<AttributeOptionsCollectionInterface>>
	 * @throws \ReflectionException
	 * @noinspection PhpUnused
	 */
	public static function getAttributeOptionsFiles(bool $returnLoaded = false): array {
		if (!static::$foundAll) {
			static::$attributeOptions = static::findAttributeOptionsFiles('*', $returnLoaded);
			static::$foundAll = true;
		}

		if ($returnLoaded) {
			if (empty(static::$loadedAttributeOptions)) {
				/** @var class-string<\Awyiss\Attribute\AttributeOptionsCollectionInterface> $attributeOptionsClass */
				foreach (static::$attributeOptions as $attributeOptionsClass) {
					static::loadAttributeOptions($attributeOptionsClass);
				}
			}

			return static::$loadedAttributeOptions;
		}


		return static::$attributeOptions;
	}


	/**
	 * Returns an instance of a AttributeOptionsCollection class with the provided scope or null
	 *
	 * @param class-string<\Awyiss\Attribute\AttributeOptionsCollectionInterface>|string $scope
	 * @return \Awyiss\Attribute\AttributeOptionsCollectionInterface|null
	 * @throws \ReflectionException
	 * @noinspection PhpUnused
	 */
	public static function loadAttributeOptions(string $scope): ?AttributeOptionsCollectionInterface {
		$sanitizedScope = static::sanitizeScope($scope);

		if (array_key_exists($sanitizedScope, static::$loadedAttributeOptions)) {
			return static::$loadedAttributeOptions[ $sanitizedScope ];
		}

		if (class_exists($scope)) {
			$sanitizedScope = static::extractScopeFromClassName($scope);
			$attributeOptionsClass = $scope;

			if (array_key_exists($sanitizedScope, static::$loadedAttributeOptions)) {
				return static::$loadedAttributeOptions[ $sanitizedScope ];
			}
		}
		else {
			/** @var class-string<AttributeOptionsCollectionInterface>|null $attributeOptionsClass */
			$attributeOptionsClass = static::getAttributeOptionsFile($sanitizedScope);
			if (!$attributeOptionsClass) {
				static::$loadedAttributeOptions[ $sanitizedScope ] = null;


				return null;
			}
		}

		static::$loadedAttributeOptions[ $sanitizedScope ] = new $attributeOptionsClass();


		return static::$loadedAttributeOptions[ $sanitizedScope ];
	}


	/**
	 * Returns the found AttributeOptionsCollection class for the provided scope or null
	 *
	 * @param string $scope
	 * @param bool $returnLoaded
	 * @return AttributeOptionsCollectionInterface|string|null
	 * @throws \ReflectionException
	 */
	public static function getAttributeOptionsFile(
		string $scope,
		bool $returnLoaded = false
	): string|AttributeOptionsCollectionInterface|null {
		$scope = static::sanitizeScope($scope);

		if (empty(static::$attributeOptions[ $scope ])) {
			static::$attributeOptions += static::findAttributeOptionsFiles($scope, $returnLoaded);
		}

		if ($returnLoaded) {
			return static::$loadedAttributeOptions[ $scope ] ?? null;
		}

		return static::$attributeOptions[ $scope ] ?? null;
	}


	/**
	 * Sanitize the provided scope by removing all non-ascii characters
	 * Returns a camelBacked string
	 *
	 * @param string $scope
	 * @return string
	 */
	public static function sanitizeScope(string $scope): string {
		$scope = Text::slug($scope, '_');
		$scope = Inflector::singularize($scope);
		$scope = Inflector::pluralize($scope);

		return Inflector::camelize($scope);
	}


	/**
	 * Sanitize the provided identifier by removing all non-ascii characters
	 * Returns a camelBacked string
	 *
	 * @param string $identifier
	 * @return string
	 */
	public static function sanitizeIdentifier(string $identifier): string {
		return Inflector::variable(Text::slug($identifier, '_'));
	}


	/**
	 * Finds all AttributeOptionsCollection classes in both the Awyiss and the custom namespace for a given name.
	 * `$scope` can be "*" to return all files.
	 * If a AttributeOptionsCollection class exists in both namespaces, the one from the custom namespace is returned,
	 * the Awyiss one is ignored.
	 *
	 * @param string $scope
	 * @param bool $load
	 * @return array<string, class-string<AttributeOptionsCollectionInterface>>
	 * @throws \ReflectionException
	 */
	protected static function findAttributeOptionsFiles(string $scope, bool $load = false): array {
		$classes = App::classes($scope, 'Attribute/AttributeOptions', 'AttributeOptions', AttributeOptionsCollectionInterface::class);

		$attributeOptionFiles = [];
		/** @var class-string<\Awyiss\Attribute\AttributeOptionsCollection> $className */
		foreach ($classes as $className) {
			$scope = static::extractScopeFromClassName($className);

			if ($load) {
				static::loadAttributeOptions($className);
			}

			$attributeOptionFiles[ $scope ] ??= $className;
		}

		return $attributeOptionFiles;
	}


	/**
	 * @param string $scope
	 * @param int $suffixLength
	 * @return string
	 */
	public static function extractScopeFromClassName(string $scope, int $suffixLength = 16): string {
		$parts = explode('\\', trim($scope, '\\'));
		$scope = array_pop($parts);
		$scope = substr($scope, 0, -$suffixLength);

		return static::sanitizeScope($scope);
	}
}
