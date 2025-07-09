<?php declare(strict_types=1);


namespace Awyiss\Utility\Menu;


use Awyiss\Core\App;
use JsonSchema\Constraints\Factory;
use JsonSchema\SchemaStorage;
use JsonSchema\Validator;
use RuntimeException;


/**
 * A loader for objects, json encoded objects or json encoded files, holding menu data.
 * Validates the json data and returns a menu instance with the data as menuitems
 */
class MenuLoader {
	/**
	 * @param object $data
	 * @param array $config
	 * @return bool
	 * @throws \ReflectionException
	 */
	public static function validateData(object $data, array $config): bool {
		$lo_factory = null;
		$lx_schema = $config['schema'] ?? null;
		$ls_schemaPath = $config['schemaPath'] ?? null;
		if ($ls_schemaPath) {
			$lx_schema = static::loadJsonFile($ls_schemaPath);

			$lo_schemaStorage = new SchemaStorage();
			$lo_schemaStorage->addSchema('file://' . str_replace(DS, '/', $ls_schemaPath), $lx_schema);
			$lo_factory = new Factory($lo_schemaStorage);
		}

		if (is_string($lx_schema)) {
			$lx_schema = static::fromJsonString($lx_schema);
		}

		// Validate
		$lo_validator = new Validator($lo_factory);
		$lo_validator->validate($data, $lx_schema);

		if (!$lo_validator->isValid()) {
			return false;
		}


		return true;
	}


	/**
	 * @param object $data
	 * @param array $config
	 * @return \Awyiss\Utility\Menu\Menu
	 * @throws \ReflectionException
	 */
	public static function fromObject(object $data, array $config = []): Menu {
		$lb_validateUniqueIdentifiers = $config['validate']['uniqueIdentifiers'] ?? false;

		$la_config = $config;

		if (isset($la_config['validate'])) {
			$lb_valid = static::validateData($data, $la_config['validate']);
			if (!$lb_valid) {
				throw new RuntimeException('The data is not valid according to the specified schema');
			}
		}

		//Validate-config is not needed inside the menu
		unset($la_config['validate']);

		/** @var class-string<\Awyiss\Utility\Menu\Menu> $ls_className */
		$ls_className = App::className('Menu', 'Utility/Menu');
		/** @see \Awyiss\Utility\Menu\Menu::__construct() */
		$lo_menu = new $ls_className((array)$data, $la_config, 1);

		if ($lb_validateUniqueIdentifiers) {
			$la_knownIdentifiers = [];
			foreach ($lo_menu->items() as $ls_identifier => $lo_item) {
				if (in_array($ls_identifier, $la_knownIdentifiers)) {
					throw new RuntimeException(sprintf('Cannot use identifier `%s` twice in `%s`', $ls_identifier, self::class));
				}

				$la_knownIdentifiers[] = $ls_identifier;
			}
		}


		return $lo_menu;
	}


	/**
	 * @param string $filePath
	 * @param array $config
	 * @return \Awyiss\Utility\Menu\Menu
	 * @throws \ReflectionException
	 */
	public static function fromJsonFile(string $filePath, array $config = []): Menu {
		$lo_data = static::loadJsonFile($filePath);


		return static::fromObject($lo_data, $config);
	}


	/**
	 * @param string $jsonString
	 * @param array $config
	 * @return \Awyiss\Utility\Menu\Menu
	 * @throws \ReflectionException
	 */
	public static function fromJsonString(string $jsonString, array $config = []): Menu {
		$lo_data = static::loadJsonString($jsonString);


		return static::fromObject($lo_data, $config);
	}


	/**
	 * @param string $filePath
	 * @return object
	 */
	public static function loadJsonFile(string $filePath): object {
		$ls_filePath = realpath($filePath);

		if (!file_exists($ls_filePath)) {
			throw new RuntimeException(sprintf('File `%s` does not exist.', $ls_filePath));
		}

		$ls_jsonString = file_get_contents($ls_filePath);


		return static::loadJsonString($ls_jsonString);
	}


	/**
	 * @param string $jsonString
	 * @return object
	 */
	public static function loadJsonString(string $jsonString): object {
		$lo_data = json_decode($jsonString);
		if (json_last_error() !== JSON_ERROR_NONE) {
			throw new RuntimeException('Invalid JSON string.');
		}


		return $lo_data;
	}
}
