<?php declare(strict_types=1);


namespace Awyiss\Attributes;


use Cake\Utility\Inflector;
use Cake\Utility\Text;


/**
 * Provides access to all AttributeOptionsCollection classes in both the Awyiss and the custom namespace.
 */
class AttributeOptionsProvider {
	/**
	 * @var array<string, AttributeOptionsInterface>
	 */
	protected static array $attributeOptions = [];
	/**
	 * @var bool
	 */
	protected static bool $foundAll = FALSE;
	/**
	 * @var array<string, AttributeOptionsInterface>
	 */
	protected static array $loadedAttributeOptions = [];


	private function __construct () {
		throw new \RuntimeException(sprintf('The class `%s` cannot be instantiated', self::class));
	}


	/**
	 * Returns all found AttributeOptionsCollection classes in both the Awyiss and the custom namespace
	 *
	 * @return array<string, class-string<AttributeOptionsInterface>>
	 * @throws \ReflectionException
	 *
	 * @noinspection PhpUnused
	 */
	public static function getAttributeOptionsFiles (bool $ab_returnLoaded = FALSE): array {
		if ( ! static::$foundAll) {
			static::$attributeOptions = static::findAttributeOptionsFiles('*', $ab_returnLoaded);
			static::$foundAll = TRUE;
		}

		if ($ab_returnLoaded) {
			return static::$loadedAttributeOptions;
		}

		return static::$attributeOptions;
	}


	/**
	 * Finds all AttributeOptionsCollection classes in both the Awyiss and the custom namespace for a given name.
	 *
	 * `$as_scope` can be "*" to return all files.
	 *
	 * If a AttributeOptionsCollection class exists in both namespaces, the one from the custom namespace is returned,
	 * the Awyiss one is ignored.
	 *
	 * @param string $as_scope
	 * @param bool   $ab_load
	 *
	 * @return array<string, class-string<AttributeOptionsInterface>>
	 * @throws \ReflectionException
	 */
	protected static function findAttributeOptionsFiles (string $as_scope, bool $ab_load = FALSE): array {
		$la_attributeOptionFiles = [];

		$la_paths = [
			'\\' . CUSTOM_NAMESPACE . '\Attributes\AttributeOptionsCollection\\' => implode(DS,
				[
					ROOT,
					CUSTOM_DIR,
					'Attributes',
					'AttributeOptionsCollection',
					$as_scope . 'AttributeOptionsCollection.php',
				]),
			'\Awyiss\Attributes\AttributeOptionsCollection\\' => implode(DS,
				[
					ROOT,
					APP_DIR,
					'Attributes',
					'AttributeOptionsCollection',
					$as_scope . 'AttributeOptionsCollection.php',
				]),
		];

		foreach ($la_paths as $ls_namespace => $ls_path) {
			foreach (glob($ls_path) as $ls_filePath) {
				$ls_attributeOptionsName = substr($ls_filePath, strrpos($ls_filePath, DS) + 1, -4);
				if (str_starts_with($ls_attributeOptionsName, '_')) {
					continue;
				}

				$ls_attributeOptionsClass = $ls_namespace . $ls_attributeOptionsName;

				$lo_reflection = new \ReflectionClass($ls_attributeOptionsClass);

				if ( ! $lo_reflection->implementsInterface(AttributeOptionsInterface::class)) {
					throw new \RuntimeException(sprintf('The provided Attributes class `%s` does not extend the `%s` class.',
						$ls_attributeOptionsClass,
						AttributeOptionsInterface::class));
				}

				/**
				 * @var AttributeOptionsInterface $ls_attributeOptionsClass
				 */
				$ls_scope = $ls_attributeOptionsClass::getScope();

				if (isset($la_attributeOptionFiles[ $ls_scope ])) {
					continue;
				}

				if ($ab_load) {
					static::loadAttributeOptions($ls_attributeOptionsClass);
				}

				$la_attributeOptionFiles[ $ls_scope ] = $ls_attributeOptionsClass;
			}
		}

		return $la_attributeOptionFiles;
	}


	/**
	 * Returns an instance of a AttributeOptionsCollection class with the provided scope or NULL
	 *
	 * @param string|class-string<AttributeOptionsInterface> $as_scope
	 *
	 * @return NULL|AttributeOptionsInterface
	 * @throws \ReflectionException
	 * @noinspection PhpUnused
	 */
	public static function loadAttributeOptions (string $as_scope): ?AttributeOptionsInterface {
		$ls_scope = static::sanitizeScope($as_scope);

		if (array_key_exists($ls_scope, static::$loadedAttributeOptions)) {
			return static::$loadedAttributeOptions[ $ls_scope ];
		}

		if (class_exists($as_scope)) {
			$ls_scope = $as_scope::getScope();
			$ls_attributeOptionsClass = $as_scope;

			if (array_key_exists($ls_scope, static::$loadedAttributeOptions)) {
				return static::$loadedAttributeOptions[ $ls_scope ];
			}
		}
		else {
			/** @var NULL|class-string<AttributeOptionsInterface> $ls_attributeOptionsClass */
			$ls_attributeOptionsClass = static::getAttributeOptionsFile($ls_scope);
			if ( ! $ls_attributeOptionsClass) {
				static::$loadedAttributeOptions[ $ls_scope ] = NULL;

				return NULL;
			}
		}

		static::$loadedAttributeOptions[ $ls_scope ] = new $ls_attributeOptionsClass();

		return static::$loadedAttributeOptions[ $ls_scope ];
	}


	/**
	 * Returns the found AttributeOptionsCollection class for the provided scope or NULL
	 *
	 * @param string $as_scope
	 * @param bool   $ab_returnLoaded
	 *
	 * @return NULL|string|AttributeOptionsInterface
	 * @throws \ReflectionException
	 */
	public static function getAttributeOptionsFile (string $as_scope, bool $ab_returnLoaded = FALSE): string|AttributeOptionsInterface|null {
		$ls_scope = static::sanitizeScope($as_scope);

		if (empty(static::$attributeOptions[ $ls_scope ])) {
			static::$attributeOptions += static::findAttributeOptionsFiles($ls_scope, $ab_returnLoaded);
		}

		if ($ab_returnLoaded) {
			return static::$loadedAttributeOptions[ $ls_scope ] ?? NULL;
		}

		return static::$attributeOptions[ $ls_scope ] ?? NULL;
	}


	/**
	 * Sanitize the provided scope by removing all non-ascii characters
	 * Returns a camelBacked string
	 *
	 * @param string $as_scope
	 *
	 * @return string
	 */
	public static function sanitizeScope (string $as_scope): string {
		return Inflector::camelize(Inflector::pluralize(Text::slug($as_scope, '_')));
	}


	/**
	 * Sanitize the provided identifier by removing all non-ascii characters
	 * Returns a camelBacked string
	 *
	 * @param string $as_identifier
	 *
	 * @return string
	 */
	public static function sanitizeIdentifier (string $as_identifier): string {
		return Inflector::variable(Text::slug($as_identifier, '_'));
	}
}