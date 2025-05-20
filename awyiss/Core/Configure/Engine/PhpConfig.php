<?php declare(strict_types=1);


namespace Awyiss\Core\Configure\Engine;


use Brick\VarExporter\VarExporter;
use Cake\Core\Configure\Engine\PhpConfig as BasePhpConfig;
use Cake\Core\Exception\CakeException;
use Cake\Utility\Hash;


/**
 * @inheritDoc
 */
class PhpConfig extends BasePhpConfig {
	protected array $paths = [
		'Awyiss' => CONFIG,
	];


	/**
	 * Constructor for PHP Config file reading.
	 *
	 * @param string|null $path The path to read config files from.
	 * @noinspection PhpMissingParentConstructorInspection
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function __construct(?string $path = null) {
		$this->_path = null ?? CONFIG;

		if (defined('CUSTOM_NAMESPACE')) {
			$this->paths[ CUSTOM_NAMESPACE ] = CUSTOM_CONFIG;
			$this->paths[ CUSTOM_NAMESPACE . '_' . CONFIG_ENV ] = ENV_CUSTOM_CONFIG;
		}
	}


	/**
	 * @param string $key
	 * @return array
	 */
	public function read(string $key): array {
		//$la_paths = $this->_path ? [$this->_path] : $this->paths;
		$la_paths = $this->paths;
		$la_return = [];

		foreach ($la_paths as $ls_path) {
			/*
			 * Set the internal path that's used by \Cake\Core\Configure\FileConfigTrait in _getFilePath()
			 * This way we don't have to overwrite the method
			 */
			$this->_path = $ls_path;
			try {
				$ls_filePath = $this->_getFilePath($key, true);
			}
			catch (CakeException $ex) {
				continue;
			}

			//Reset $config in case the file does something with $config internally
			//$config = null;

			$la_fileReturn = include $ls_filePath;
			if (is_array($la_fileReturn)) {
				//Merge the retuning values of the files
				$la_return = Hash::merge($la_return, $la_fileReturn);
			}
			else {
				throw new CakeException(sprintf('Config file "%s" did not return an array', $key . '.php'));
			}
		}


		/*if (count($la_paths) !== 1) {
			//Reset the internal path
			$this->_path = $la_paths[0];
		}*/


		return $la_return;
	}


	/**
	 * Converts the provided $data into a string of PHP code that can
	 * be used saved into a file and loaded later.
	 *
	 * @param string $key The identifier to write to.
	 * @param array $data Data to dump.
	 * @return bool Success
	 * @throws \Brick\VarExporter\ExportException
	 */
	public function dump(string $key, array $data): bool {
		$ls_contents = '<?php declare(strict_types=1);' . PHP_EOL . PHP_EOL . 'return ';

		/** @noinspection PhpVariableNamingConventionInspection */
		ksort($data, SORT_NATURAL | SORT_FLAG_CASE);

		$ls_contents .= VarExporter::export($data, VarExporter::TRAILING_COMMA_IN_ARRAY);
		$ls_contents .= ';';
		$ls_contents = str_replace('    ', "\t", $ls_contents);


		$ls_key = $key;
		$ls_folder = ENV_CUSTOM_CONFIG;

		if (str_contains($ls_key, '.')) {
			[$ls_folder, $ls_key] = explode('.', $ls_key);
			$ls_folder = $this->paths[ $ls_folder ] ?? ENV_CUSTOM_CONFIG;
		}

		$ls_filePath = $ls_folder . $ls_key . $this->_extension;

		if (!is_dir($ls_folder)) {
			mkdir($ls_folder, 0750, true);
		}

		if (file_put_contents($ls_filePath, $ls_contents)) {
			chmod($ls_filePath, 0660);


			return true;
		}


		return false;
	}
}
