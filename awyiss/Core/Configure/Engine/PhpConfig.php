<?php declare(strict_types=1);


namespace Awyiss\Core\Configure\Engine;


use Brick\VarExporter\ExportException;
use Brick\VarExporter\VarExporter;
use Cake\Core\Configure\Engine\PhpConfig as BasePhpConfig;
use Cake\Core\Exception\CakeException;
use Cake\Utility\Hash;


/**
 * @inheritDoc
 */
class PhpConfig extends BasePhpConfig {
	protected array $paths = [
		CONFIG,
		CUSTOM_CONFIG,
		ENV_CUSTOM_CONFIG,
	];


	/**
	 * Constructor for PHP Config file reading.
	 *
	 * @param string|NULL $as_path The path to read config files from.
	 *
	 * @noinspection PhpMissingParentConstructorInspection
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function __construct(?string $as_path = NULL) {
		$this->_path = NULL ?? CONFIG;
	}


	/**
	 * @param string $as_key
	 *
	 * @return array
	 *
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
				$ls_filePath = $this->_getFilePath($as_key, TRUE);
			}
			catch (CakeException $ex) {
				continue;
			}

			//Reset $config in case the file does something with $config internally
			//$config = NULL;

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
	 *
	 * @return bool Success
	 *
	 * @throws ExportException
	 *
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function dump(string $as_key, array $aa_data): bool {
		$ls_contents = '<?php declare(strict_types=1);' . PHP_EOL . PHP_EOL . 'return ';
		$ls_contents .= VarExporter::export($aa_data, VarExporter::TRAILING_COMMA_IN_ARRAY);
		$ls_contents .= ';';
		$ls_contents = str_replace('    ', "\t", $ls_contents);

		$ls_filename = ENV_CUSTOM_CONFIG . $as_key . $this->_extension;

		if (file_put_contents($ls_filename, $ls_contents) > 0) {
			chmod($ls_filename, fileperms($ls_filename) | 128 + 16 + 2);


			return TRUE;
		}


		return FALSE;
	}
}
