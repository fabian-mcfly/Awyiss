<?php declare(strict_types=1);


namespace Awyiss\Command\Awyiss\Trait;


use Awyiss\Awyiss;
use Awyiss\Utility\Inflector;
use Brick\VarExporter\VarExporter;
use Cake\Database\Connection;
use Cake\Datasource\ConnectionManager;


/**
 * Trait ConfigTrait
 *
 * This trait provides methods for managing configuration in a CakePHP application.
 * It includes methods for creating an environment file, setting a base configuration file,
 * and setting an environment-specific configuration file.
 */
trait ConfigTrait {
	/**
	 * @var string The database username
	 */
	protected string $dbUsername;
	/**
	 * @var string The database password
	 */
	protected string $dbPassword;
	/**
	 * @var string The database name
	 */
	protected string $dbName;
	/**
	 * @var string The database host
	 */
	protected string $dbHost;
	/**
	 * @var string
	 */
	protected string $rtEditor;


	/**
	 * Creates an environment file by copying the .env.example file to .env and replacing placeholders with user inputs.
	 *
	 * @return void
	 */
	protected function createEnv(): void {
		if ($this->dryRun) {
			$this->io->success('.env file created.');

			return;
		}

		$envExampleFilePath = ROOT . DS . $this->customerName . DS . '.env.example';
		$envFilePath = ROOT . DS . '.env';

		// Load the contents of the .env.example file
		$envExampleContents = file_get_contents($envExampleFilePath);

		// Replace the placeholders with the user inputs
		$envContents = str_replace('CONFIG_ENV=\'development\'', 'CONFIG_ENV=\'' . $this->installEnvironment . '\'', $envExampleContents);
		$envContents = str_replace('CUSTOM_DIR=\'customer\'', 'CUSTOM_DIR=\'' . $this->customerName . '\'', $envContents);

		// Write the updated contents back to the .env file
		file_put_contents($envFilePath, $envContents);

		unlink($envExampleFilePath);

		$this->io->success('.env file created.');
	}


	/**
	 * Sets the base configuration file by loading it, replacing the salt and cookie name, and writing it back.
	 *
	 * @return void
	 * @throws \Brick\VarExporter\ExportException
	 * @throws \Random\RandomException
	 */
	protected function setBaseConfigFile(): void {
		if ($this->dryRun) {
			$this->io->success('Base config file set.');

			return;
		}

		// Define the path to the base configuration file
		$baseConfigFilePath = ROOT . DS . $this->customerName . DS . 'config/awyiss.php';

		// Load the base config file
		$baseConfig = include $baseConfigFilePath;

		// Generate a new salt
		$securitySalt = bin2hex(random_bytes(32));

		// Replace the salt in the base config file
		$baseConfig['Security']['salt'] = $securitySalt;

		// Set the cookie name based on the customer name
		$baseConfig['Session']['cookie'] = $this->customerName;

		// Convert the updated configuration array to a string of PHP code
		$contents = '<?php declare(strict_types=1);' . PHP_EOL . PHP_EOL . 'return ';
		$contents .= VarExporter::export($baseConfig, VarExporter::TRAILING_COMMA_IN_ARRAY);
		$contents .= ';';
		$contents = str_replace('    ', "\t", $contents);

		/**
		 * Replace
		 *
		 *- `'previewScssFiles' => null,`
		 * - `'scssFiles' => null,`
		 *
		 * with
		 *
		 * - 'previewScssFiles' => defined('CUSTOM_DIR') ? [ROOT . DS . CUSTOM_DIR . '/assets/scss/full.scss'] : null,
		 * - 'scssFiles' => defined('CUSTOM_DIR') ? [ROOT . DS . CUSTOM_DIR . '/assets/scss/helper/_variables.scss'] : null,
		 *
		 * since including the config file evaluates the statement
		 */
		$contents = str_replace([
			'\'previewScssFiles\' => null,',
			'\'scssFiles\' => null,',
		], [
			'\'previewScssFiles\' => defined(\'CUSTOM_DIR\') ? [ROOT . DS . CUSTOM_DIR . \'/assets/scss/full.scss\'] : null,',
			'\'scssFiles\' => defined(\'CUSTOM_DIR\') ? [ROOT . DS . CUSTOM_DIR . \'/assets/scss/helper/_variables.scss\'] : null,',
		], $contents);

		// Write the updated contents back to the base config file
		file_put_contents($baseConfigFilePath, $contents);

		$this->io->success('Base config file set.');
	}


	/**
	 * Sets the environment-specific configuration file by loading it, replacing placeholders with user inputs, and writing it back.
	 * If no database credentials are provided, this method will skip setting the environment config file.
	 *
	 * @return void
	 * @throws \Random\RandomException
	 * @throws \Brick\VarExporter\ExportException
	 */
	protected function setEnvironmentConfigFile(): void {
		if (!$this->dbUsername) {
			$this->io->out('No database credentials provided. Skipping environment config file.');

			return;
		}

		if ($this->dryRun) {
			$this->io->success('Environment config file set.');

			return;
		}

		$skeletonEnvironmentFilePath = ROOT . DS . $this->customerName . DS . 'config' . DS . 'environment_skeleton.php';
		$environmentConfigFilePath = ROOT . DS . $this->customerName . DS . 'config' . DS . $this->installEnvironment . DS . 'awyiss.php';

		$environmentFolderPath = dirname($environmentConfigFilePath);

		// Create the environment folder if it does not exist
		if (!file_exists($environmentFolderPath)) {
			mkdir($environmentFolderPath, 0755, true);
		}

		// Copy the environment skeleton file to the environment config file
		rename($skeletonEnvironmentFilePath, $environmentConfigFilePath);

		// Determine if the environment resembles a production environment
		$isProductionEnvironment = in_array($this->installEnvironment, ['production', 'prod', 'live']);

		// Set the log and debug flags and error level based on the environment
		$logFlag = !$isProductionEnvironment;
		$debugFlag = !$isProductionEnvironment;
		$errorLevel = $isProductionEnvironment ? 0 : 'E_ALL';

		// Load the environment config file
		$environmentConfig = include $environmentConfigFilePath;

		// Set the database configuration based on user inputs
		$environmentConfig['Datasources']['default']['database'] = $this->dbName;
		$environmentConfig['Datasources']['default']['host'] = $this->dbHost;
		$environmentConfig['Datasources']['default']['log'] = $logFlag;
		$environmentConfig['Datasources']['default']['password'] = $this->dbPassword;
		$environmentConfig['Datasources']['default']['username'] = $this->dbUsername;

		// Temporarily set the 'custom' connection as the default connection to apply the new database configuration immediately
		$config = ConnectionManager::get('default')->config();
		ConnectionManager::setConfig('custom', array_merge($config, $environmentConfig['Datasources']['default'], [
			'className' => Connection::class,
		]));
		ConnectionManager::alias('custom', 'default');


		// Set the debug flag and error level based on the environment
		$environmentConfig['debug'] = $debugFlag;
		$environmentConfig['Error']['errorLevel'] = $errorLevel;

		$contents = '<?php declare(strict_types=1);' . PHP_EOL . PHP_EOL . 'return ';

		$contents .= VarExporter::export($environmentConfig, VarExporter::TRAILING_COMMA_IN_ARRAY);
		$contents .= ';';
		$contents = str_replace('    ', "\t", $contents);

		// Write the updated contents back to the environment config file
		file_put_contents($environmentConfigFilePath, $contents);

		$this->io->success('Environment config file set.');
	}


	/**
	 * Sets up a demo attribute collection by updating the namespace in the ContentsAttributeOptions.php file.
	 *
	 * @return void
	 */
	protected function setupDemoAttributeCollection(): void {
		if ($this->dryRun) {
			$this->io->success('\Customer\Attribute\AttributeOptions\ContentsAttributeOptions file updated.');

			return;
		}

		// Define the path to the ContentsAttributeOptions.php file
		$filePath = ROOT . DS . $this->customerName . DS . 'Attribute' . DS . 'AttributeOptions' . DS . 'ContentsAttributeOptions.php';

		// Load the contents of the ContentsAttributeOptions.php file
		$fileContents = file_get_contents($filePath);

		$newNamespace = 'namespace ' . Inflector::camelize($this->customerName) . '\\Attribute\\AttributeOptions;';
		$fileContents = str_replace('namespace Customer\\Attribute\\AttributeOptions;', $newNamespace, $fileContents);

		// Write the updated contents back to the ContentsAttributeOptions.php file
		file_put_contents($filePath, $fileContents);

		$this->io->success('\Customer\Attribute\AttributeOptions\ContentsAttributeOptions file updated.');
	}


	/**
	 * Sets up a demo menu cell
	 *
	 * @return void
	 */
	protected function setupDemoMenuCell(): void {
		if ($this->dryRun) {
			$this->io->success('\Customer\View\Cell\Frontend\MenuCell file updated.');

			return;
		}

		// Define the path to the MenuCell.php file
		$filePath = ROOT . DS . $this->customerName . DS . 'View' . DS . 'Cell' . DS . 'Frontend' . DS . 'MenuCell.php';

		// Load the contents of the MenuCell.php file
		$fileContents = file_get_contents($filePath);

		// Replace the namespace with the camelized version of the given customer name
		$newNamespace = 'namespace ' . Inflector::camelize($this->customerName) . '\\View\\Cell\\Frontend;';
		$fileContents = str_replace('namespace Customer\\View\\Cell\\Frontend;', $newNamespace, $fileContents);

		// Write the updated contents back to the MenuCell.php file
		file_put_contents($filePath, $fileContents);

		$this->io->success('\Customer\View\Cell\Frontend\MenuCell file updated.');
	}


	/**
	 * Updates the namespace in the ide-twig.json file.
	 *
	 * @return void
	 */
	protected function setTwigNamespace(): void {
		if ($this->dryRun) {
			$this->io->success('ide-twig.json file updated.');

			return;
		}

		// Define the path to the ide-twig.json file
		$filePath = ROOT . DS . $this->customerName . DS . 'templates' . DS . 'ide-twig.json';

		// Load the contents of the ide-twig.json file
		$fileContents = file_get_contents($filePath);

		// Decode the JSON content to a PHP array
		$content = json_decode($fileContents, true);

		// Change the value of the namespace key to the camelized version of the given customer name
		$content['namespaces'][0]['namespace'] = Inflector::camelize($this->customerName);

		// Encode the updated PHP array back to a JSON string
		$updatedContents = json_encode($content, JSON_PRETTY_PRINT);

		// Write the updated JSON string back to the ide-twig.json file
		file_put_contents($filePath, $updatedContents);

		$this->io->success('ide-twig.json file updated.');
	}


	/**
	 * Sets the Twig extension according to the customer's folder by updating the namespace and class name in the
	 *
	 * @return void
	 */
	protected function setTwigExtension(): void {
		if ($this->dryRun) {
			$this->io->success('\Twig\Extension\CustomerExtension file updated and renamed.');

			return;
		}

		// Define the path to the CustomerExtension.php file
		$filePath = ROOT . DS . $this->customerName . DS . 'Twig' . DS . 'Extension' . DS . 'CustomerExtension.php';

		// Load the contents of the CustomerExtension.php file
		$fileContents = file_get_contents($filePath);

		// Replace the namespace and class name with the camelized version of the given customer name
		$newNamespace = 'namespace ' . Inflector::camelize($this->customerName) . '\\Twig\\Extension;';
		$newClassName = 'class ' . Inflector::camelize($this->customerName) . 'Extension extends AbstractExtension {';

		$fileContents = str_replace('namespace Customer\\Twig\\Extension;', $newNamespace, $fileContents);
		$fileContents = str_replace('class CustomerExtension extends AbstractExtension {', $newClassName, $fileContents);

		// Write the updated contents back to the CustomerExtension.php file
		file_put_contents($filePath, $fileContents);

		// Rename the CustomerExtension.php file to match the new class name
		$newFilePath = ROOT . DS . $this->customerName . DS . 'Twig' . DS . 'Extension' . DS . Inflector::camelize($this->customerName) . 'Extension.php';
		rename($filePath, $newFilePath);

		$this->io->success('\Twig\Extension\CustomerExtension file updated and renamed.');
	}


	/**
	 * Sets the rich text editor according to the user's choice.
	 *
	 * @return void
	 */
	protected function setRichTextEditor(): void {
		if ($this->rtEditor === 'none') {
			return;
		}

		if ($this->dryRun) {
			$this->io->success('Rich text editor set.');

			return;
		}

		/** @var \Awyiss\Model\Table\ConfigurationTable $configTable */
		$configTable = $this->fetchTable('Configuration');
		$configTable->getBehavior('Categories')->setConfig('buildRules', false);
		$config = $configTable->newDefaultEntity();

		$configTable->patchEntity($config, [
			'realm' => Awyiss::REALM_BACKEND,
			'scope' => 'system',
			'identifier' => 'interface.editor',
			'value' => strtolower($this->rtEditor),
		]);

		if ($configTable->save($config)) {
			$this->io->success('Rich text editor set.');
		}
		else {
			$this->io->error('Could not set rich text editor. Please check the database connection and try again.');
		}
	}
}
