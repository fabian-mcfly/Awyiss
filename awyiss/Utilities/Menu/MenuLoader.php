<?php declare(strict_types=1);


namespace Awyiss\Utilities\Menu;


use JsonSchema\Validator;
use RuntimeException;


class MenuLoader {
	public static function validateData (object $data, array $config): bool {
		$schema = $config['schema'] ?? NULL;
		$schemaPath = $config['schemaPath'] ?? NULL;
		if ($schemaPath) {
			$schema = static::loadJsonFile($schemaPath);
		}

		if (is_string($schema)) {
			$schema = static::fromJsonString($schema);
		}

		// Validate
		$validator = new Validator();
		$validator->validate($data, $schema);

		if ( ! $validator->isValid()) {
			return FALSE;
		}

		return TRUE;
	}


	public static function fromObject (object $data, array $config = []) {
		$lb_validateUniqueIdentifiers = $config['validate']['uniqueIdentifiers'] ?? FALSE;

		if (isset($config['validate'])) {
			$valid = static::validateData($data, $config['validate']);
			if ( ! $valid) {
				throw new RuntimeException('The data is not valid according to the specified scheme');
			}
		}

		//Validate-config is not needed inside the menu
		unset($config['validate']);

		$lo_menu = new Menu((array) $data, $config, 1);

		if ($lb_validateUniqueIdentifiers) {
			$la_knownIdentifiers = [];
			foreach ($lo_menu->items() AS $ls_identifier => $item) {
				if (in_array($ls_identifier, $la_knownIdentifiers)) {
					throw new RuntimeException(sprintf('Cannot use identifier `%s` twice in `%s`', $ls_identifier, self::class));
				}

				$la_knownIdentifiers[] = $ls_identifier;
			}
		}

		return $lo_menu;
	}


	public static function fromJsonFile ($filePath, array $config = []) {
		$data = static::loadJsonFile($filePath);

		return static::fromObject($data, $config);
	}


	public static function fromJsonString ($jsonString, array $config = []) {
		$data = static::loadJsonString($jsonString);

		return static::fromObject($data, $config);
	}


	public static function loadJsonFile ($filePath): object {
		$filePath = realpath($filePath);

		if ( ! file_exists($filePath)) {
			throw new RuntimeException(sprintf('File `%s` does not exist.', $filePath));
		}

		$jsonString = file_get_contents($filePath);

		return static::loadJsonString($jsonString);
	}


	public static function loadJsonString ($jsonString) {
		$data = json_decode($jsonString);
		if (json_last_error() !== JSON_ERROR_NONE) {
			throw new RuntimeException('Invalid JSON string.');
		}

		return $data;
	}
}
