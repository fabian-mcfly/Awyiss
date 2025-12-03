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
		$paths = $this->paths;
		$return = [];

		foreach ($paths as $path) {
			/**
			 * Set the internal path that's used by \Cake\Core\Configure\FileConfigTrait in _getFilePath()
			 * This way we don't have to overwrite the method
			 */
			$this->_path = $path;
			try {
				$filePath = $this->_getFilePath($key, true);
			}
			catch (CakeException $ex) {
				continue;
			}

			$fileReturn = include $filePath;
			if (!is_array($fileReturn)) {
				throw new CakeException(sprintf('Config file "%s" did not return an array', $key . '.php'));
			}

			//Merge the retuning values of the files
			$return = Hash::merge($return, $fileReturn);
		}

		return $return;
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
		$contents = '<?php declare(strict_types=1);' . PHP_EOL . PHP_EOL . 'return ';

		ksort($data, SORT_NATURAL | SORT_FLAG_CASE);

		$contents .= VarExporter::export($data, VarExporter::TRAILING_COMMA_IN_ARRAY);
		$contents .= ';';
		$contents = str_replace('    ', "\t", $contents);

		$folder = ENV_CUSTOM_CONFIG;

		if (str_contains($key, '.')) {
			[$folder, $key] = explode('.', $key);
			$folder = $this->paths[ $folder ] ?? ENV_CUSTOM_CONFIG;
		}

		$filePath = $folder . $key . $this->_extension;

		if (!is_dir($folder)) {
			mkdir($folder, 0755, true);
		}

		if (file_put_contents($filePath, $contents)) {
			chmod($filePath, 0660);


			return true;
		}


		return false;
	}
}
