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
	 * @param string|null $as_path The path to read config files from.
	 * @noinspection PhpMissingParentConstructorInspection
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function __construct(?string $as_path = null) {
		$this->_path = null ?? CONFIG;

		if (defined('CUSTOM_NAMESPACE')) {
			$this->paths[ CUSTOM_NAMESPACE ] = CUSTOM_CONFIG;
			$this->paths[ CUSTOM_NAMESPACE . '_' . CONFIG_ENV ] = ENV_CUSTOM_CONFIG;
		}
	}


	/**
	 * @param string $as_key
	 * @return array
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function read(string $as_key): array {
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
				$ls_filePath = $this->_getFilePath($as_key, true);
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
				throw new CakeException(sprintf('Config file "%s" did not return an array', $as_key . '.php'));
			}
		}


		/*if (count($la_paths) !== 1) {
			//Reset the internal path
			$this->_path = $la_paths[0];
		}*/


		return $la_return;
	}


	/**
	 * Converts the provided $aa_data into a string of PHP code that can
	 * be used saved into a file and loaded later.
	 *
	 * @param string $as_key The identifier to write to.
	 * @param array $aa_data Data to dump.
	 * @return bool Success
	 * @throws \Brick\VarExporter\ExportException
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function dump(string $as_key, array $aa_data): bool {
		$ls_contents = '<?php declare(strict_types=1);' . PHP_EOL . PHP_EOL . 'return ';

		ksort($aa_data, SORT_NATURAL | SORT_FLAG_CASE);

		$ls_contents .= VarExporter::export($aa_data, VarExporter::TRAILING_COMMA_IN_ARRAY);
		$ls_contents .= ';';
		$ls_contents = str_replace('    ', "\t", $ls_contents);


		$ls_key = $as_key;
		$ls_folder = ENV_CUSTOM_CONFIG;

		if (str_contains($ls_key, '.')) {
			[$ls_folder, $ls_key] = explode('.', $ls_key);
			$ls_folder = $this->paths[ $ls_folder ] ?? ENV_CUSTOM_CONFIG;
		}

		$ls_filePath = $ls_folder . $ls_key . $this->_extension;

		if (file_put_contents($ls_filePath, $ls_contents)) {
			chmod($ls_filePath, 0660);


			return true;
		}


		return false;
	}
}
