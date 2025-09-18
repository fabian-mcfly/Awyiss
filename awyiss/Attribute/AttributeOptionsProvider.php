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
	 * Returns all found AttributeOptionsCollection classes in both the Awyiss and the custom namespace
	 *
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
				/** @var class-string<\Awyiss\Attribute\AttributeOptionsCollectionInterface> $ls_attributeOptionsClass */
				foreach (static::$attributeOptions as $ls_attributeOptionsClass) {
					static::loadAttributeOptions($ls_attributeOptionsClass);
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
	 * @return AttributeOptionsCollectionInterface|null
	 * @throws \ReflectionException
	 * @noinspection PhpUnused
	 */
	public static function loadAttributeOptions(string $scope): ?AttributeOptionsCollectionInterface {
		$ls_scope = static::sanitizeScope($scope);

		if (array_key_exists($ls_scope, static::$loadedAttributeOptions)) {
			return static::$loadedAttributeOptions[ $ls_scope ];
		}

		if (class_exists($scope)) {
			$ls_scope = static::extractScopeFromClassName($scope);
			$ls_attributeOptionsClass = $scope;

			if (array_key_exists($ls_scope, static::$loadedAttributeOptions)) {
				return static::$loadedAttributeOptions[ $ls_scope ];
			}
		}
		else {
			/** @var class-string<AttributeOptionsCollectionInterface>|null $ls_attributeOptionsClass */
			$ls_attributeOptionsClass = static::getAttributeOptionsFile($ls_scope);
			if (!$ls_attributeOptionsClass) {
				static::$loadedAttributeOptions[ $ls_scope ] = null;


				return null;
			}
		}

		static::$loadedAttributeOptions[ $ls_scope ] = new $ls_attributeOptionsClass();


		return static::$loadedAttributeOptions[ $ls_scope ];
	}


	/**
	 * Returns the found AttributeOptionsCollection class for the provided scope or null
	 *
	 * @param string $scope
	 * @param bool $returnLoaded
	 * @return AttributeOptionsCollectionInterface|string|null
	 * @throws \ReflectionException
	 */
	public static function getAttributeOptionsFile(string $scope, bool $returnLoaded = false): string|AttributeOptionsCollectionInterface|null {
		$ls_scope = static::sanitizeScope($scope);

		if (empty(static::$attributeOptions[ $ls_scope ])) {
			static::$attributeOptions += static::findAttributeOptionsFiles($ls_scope, $returnLoaded);
		}

		if ($returnLoaded) {
			return static::$loadedAttributeOptions[ $ls_scope ] ?? null;
		}


		return static::$attributeOptions[ $ls_scope ] ?? null;
	}


	/**
	 * Sanitize the provided scope by removing all non-ascii characters
	 * Returns a camelBacked string
	 *
	 * @param string $scope
	 * @return string
	 */
	public static function sanitizeScope(string $scope): string {
		$ls_scope = Text::slug($scope, '_');
		$ls_scope = Inflector::singularize($ls_scope);
		$ls_scope = Inflector::pluralize($ls_scope);

		return Inflector::camelize($ls_scope);
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
		$la_classes = App::classes($scope, 'Attribute/AttributeOptionsCollection', 'AttributeOptionsCollection', AttributeOptionsCollectionInterface::class);

		$la_attributeOptionFiles = [];
		/** @var class-string<\Awyiss\Attribute\AttributeOptionsCollection> $ls_className */
		foreach ($la_classes as $ls_className) {
			$ls_scope = static::extractScopeFromClassName($ls_className);

			if ($load) {
				static::loadAttributeOptions($ls_className);
			}

			$la_attributeOptionFiles[ $ls_scope ] ??= $ls_className;
		}

		return $la_attributeOptionFiles;
	}


	/**
	 * @param string $scope
	 * @param int $suffixLength
	 * @return string
	 */
	public static function extractScopeFromClassName(string $scope, int $suffixLength = 26): string {
		$la_parts = explode('\\', trim($scope, '\\'));
		$ls_scope = array_pop($la_parts);
		$ls_scope = substr($ls_scope, 0, -$suffixLength);

		return static::sanitizeScope($ls_scope);
	}
}
