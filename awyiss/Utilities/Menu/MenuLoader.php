<?php declare(strict_types=1);


namespace Awyiss\Utilities\Menu;


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
	 * @param object $ao_data
	 * @param array $aa_config
	 * @return bool
	 * @throws \ReflectionException
	 */
	public static function validateData(object $ao_data, array $aa_config): bool {
		$lo_factory = null;
		$lx_schema = $aa_config['schema'] ?? null;
		$ls_schemaPath = $aa_config['schemaPath'] ?? null;
		if ($ls_schemaPath) {
			$lx_schema = static::loadJsonFile($ls_schemaPath);

			$lo_schemaStorage = new SchemaStorage();
			$lo_schemaStorage->addSchema('file://' . $ls_schemaPath, $lx_schema);
			$lo_factory = new Factory($lo_schemaStorage);
		}

		if (is_string($lx_schema)) {
			$lx_schema = static::fromJsonString($lx_schema);
		}

		// Validate
		$lo_validator = new Validator($lo_factory);
		$lo_validator->validate($ao_data, $lx_schema);

		if (!$lo_validator->isValid()) {
			return false;
		}


		return true;
	}


	/**
	 * @param object $ao_data
	 * @param array $aa_config
	 * @return \Awyiss\Utilities\Menu\Menu
	 * @throws \ReflectionException
	 */
	public static function fromObject(object $ao_data, array $aa_config = []): Menu {
		$lb_validateUniqueIdentifiers = $aa_config['validate']['uniqueIdentifiers'] ?? false;

		if (isset($aa_config['validate'])) {
			$lb_valid = static::validateData($ao_data, $aa_config['validate']);
			if (!$lb_valid) {
				throw new RuntimeException('The data is not valid according to the specified schema');
			}
		}

		//Validate-config is not needed inside the menu
		unset($aa_config['validate']);

		$lo_menu = new Menu((array)$ao_data, $aa_config, 1);

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
	 * @param string $as_filePath
	 * @param array $aa_config
	 * @return \Awyiss\Utilities\Menu\Menu
	 * @throws \ReflectionException
	 */
	public static function fromJsonFile(string $as_filePath, array $aa_config = []): Menu {
		$lo_data = static::loadJsonFile($as_filePath);


		return static::fromObject($lo_data, $aa_config);
	}


	/**
	 * @param string $as_jsonString
	 * @param array $aa_config
	 * @return \Awyiss\Utilities\Menu\Menu
	 * @throws \ReflectionException
	 */
	public static function fromJsonString(string $as_jsonString, array $aa_config = []): Menu {
		$lo_data = static::loadJsonString($as_jsonString);


		return static::fromObject($lo_data, $aa_config);
	}


	/**
	 * @param string $as_filePath
	 * @return object
	 */
	public static function loadJsonFile(string $as_filePath): object {
		$as_filePath = realpath($as_filePath);

		if (!file_exists($as_filePath)) {
			throw new RuntimeException(sprintf('File `%s` does not exist.', $as_filePath));
		}

		$ls_jsonString = file_get_contents($as_filePath);


		return static::loadJsonString($ls_jsonString);
	}


	/**
	 * @param string $as_jsonString
	 * @return object
	 */
	public static function loadJsonString(string $as_jsonString): object {
		$lo_data = json_decode($as_jsonString);
		if (json_last_error() !== JSON_ERROR_NONE) {
			throw new RuntimeException('Invalid JSON string.');
		}


		return $lo_data;
	}
}
