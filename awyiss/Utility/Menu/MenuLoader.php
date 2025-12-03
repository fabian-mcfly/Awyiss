<?php declare(strict_types=1);


namespace Awyiss\Utility\Menu;


use Awyiss\Core\App;
use Awyiss\Utility\Menu\Exception\MenuDuplicateIdentifierException;
use Awyiss\Utility\Menu\Exception\MenuFileException;
use Awyiss\Utility\Menu\Exception\MenuValidationException;
use JsonSchema\Constraints\Factory;
use JsonSchema\SchemaStorage;
use JsonSchema\Validator;


/**
 * A loader for objects, json encoded objects or json encoded files, holding menu data.
 * Validates the json data and returns a menu instance with the data as menu items
 */
class MenuLoader {
	/**
	 * @param object $data
	 * @param array $config
	 * @return bool
	 */
	public static function validateData(object $data, array $config): bool {
		$factory = null;
		$schema = $config['schema'] ?? null;
		$schemaPath = $config['schemaPath'] ?? null;
		if ($schemaPath) {
			$schema = static::loadJsonFile($schemaPath);

			$schemaStorage = new SchemaStorage();
			$schemaStorage->addSchema('file://' . str_replace(DS, '/', $schemaPath), $schema);
			$factory = new Factory($schemaStorage);
		}

		if (is_string($schema)) {
			$schema = static::fromJsonString($schema);
		}

		// Validate
		$validator = new Validator($factory);
		$validator->validate($data, $schema);

		if (!$validator->isValid()) {
			return false;
		}


		return true;
	}


	/**
	 * @param object $data
	 * @param array $config
	 * @return \Awyiss\Utility\Menu\Menu
	 */
	public static function fromObject(object $data, array $config = []): Menu {
		$validateUniqueIdentifiers = $config['validate']['uniqueIdentifiers'] ?? false;

		if (isset($config['validate'])) {
			$valid = static::validateData($data, $config['validate']);
			if (!$valid) {
				throw new MenuValidationException('The data is not valid according to the specified schema');
			}
		}

		//Validate-config is not needed inside the menu
		unset($config['validate']);

		/** @var class-string<\Awyiss\Utility\Menu\Menu> $menuClass */
		$menuClass = $config['menuClass'] ?? App::className('Menu', 'Utility/Menu');
		/** @see \Awyiss\Utility\Menu\Menu::__construct() */
		$menu = new $menuClass((array)$data, $config, 1);

		if ($validateUniqueIdentifiers) {
			static::validateUniqueIdentifiers($menu);
		}

		return $menu;
	}


	/**
	 * @param string $filePath
	 * @param array $config
	 * @return \Awyiss\Utility\Menu\Menu
	 */
	public static function fromJsonFile(string $filePath, array $config = []): Menu {
		$data = static::loadJsonFile($filePath);


		return static::fromObject($data, $config);
	}


	/**
	 * @param string $jsonString
	 * @param array $config
	 * @return \Awyiss\Utility\Menu\Menu
	 */
	public static function fromJsonString(string $jsonString, array $config = []): Menu {
		$data = static::loadJsonString($jsonString);


		return static::fromObject($data, $config);
	}


	/**
	 * @param string $filePath
	 * @return object
	 */
	public static function loadJsonFile(string $filePath): object {
		$filePath = realpath($filePath);

		if (!$filePath || !file_exists($filePath)) {
			throw new MenuFileException(sprintf('File `%s` does not exist.', $filePath));
		}

		$jsonString = file_get_contents($filePath);


		return static::loadJsonString($jsonString);
	}


	/**
	 * @param string $jsonString
	 * @return object
	 */
	public static function loadJsonString(string $jsonString): object {
		$data = json_decode($jsonString);
		if (json_last_error() !== JSON_ERROR_NONE) {
			throw new MenuValidationException('Invalid JSON string.');
		}


		return $data;
	}


	/**
	 * @param \Awyiss\Utility\Menu\Menu $menu
	 * @return void
	 */
	protected static function validateUniqueIdentifiers(Menu $menu): void {
		$knownIdentifiers = [];
		foreach ($menu->items() as $identifier => $item) {
			if (in_array($identifier, $knownIdentifiers)) {
				throw new MenuDuplicateIdentifierException(sprintf('Cannot use identifier `%s` twice in `%s`', $identifier, self::class));
			}

			$knownIdentifiers[] = $identifier;
		}
	}
}
