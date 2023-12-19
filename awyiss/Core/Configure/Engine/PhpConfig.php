<?php declare(strict_types=1);


namespace Awyiss\Core\Configure\Engine;


use Cake\Core\Exception\CakeException;
use Cake\Utility\Hash;


class PhpConfig extends \Cake\Core\Configure\Engine\PhpConfig {
	protected array $paths = [
		CONFIG,
		CUSTOM_CONFIG,
		ENV_CUSTOM_CONFIG,
	];


	/**
	 * Constructor for PHP Config file reading.
	 *
	 * @param string|null $as_path The path to read config files from.
	 *
	 * @noinspection PhpMissingParentConstructorInspection
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function __construct (?string $as_path = NULL) {
		$this->_path = NULL;
	}


	public function read (string $key): array {
		$la_paths = $this->_path ? [$this->_path] : $this->paths;
		$la_return = [];

		foreach ($la_paths as $ls_path) {
			/*
			 * Set the internal path that's used by \Cake\Core\Configure\FileConfigTrait in _getFilePath()
			 * This way we don't have to overwrite the method
			 */
			$this->_path = $ls_path;
			try {
				$ls_filePath = $this->_getFilePath($key, TRUE);
			}
			catch (\Cake\Core\Exception\CakeException $ex) {
				continue;
			}

			//Reset $config in case the file does something with $config internally
			$config = NULL;

			$la_fileReturn = include $ls_filePath;
			if (is_array($la_fileReturn)) {
				//Merge the retuning values of the files
				$la_return = Hash::merge($la_return, $la_fileReturn);
			}
			else {
				throw new CakeException(sprintf('Config file "%s" did not return an array', $key . '.php'));
			}
		}

		if (count($la_paths) !== 1) {
			//Reset the internal path
			$this->_path = $la_paths[0];
		}

		return $la_return;
	}
}