<?php declare(strict_types=1);


namespace Awyiss\Command\Awyiss\Trait;


use Brick\VarExporter\VarExporter;
use Cake\Database\Connection;
use Cake\Datasource\ConnectionManager;
use Cake\Utility\Inflector;


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
	 * Creates an environment file by copying the .env.example file to .env and replacing placeholders with user inputs.
	 *
	 * @return void
	 */
	protected function createEnv(): void {
		$ls_envExampleFilePath = ROOT . DS . $this->customerName . DS . '.env.example';
		$ls_envFilePath = ROOT . DS . '.env';

		// Load the contents of the .env.example file
		$ls_envExampleContents = file_get_contents($ls_envExampleFilePath);

		// Replace the placeholders with the user inputs
		$ls_envContents = str_replace('CONFIG_ENV=\'development\'', 'CONFIG_ENV=\'' . $this->installEnvironment . '\'', $ls_envExampleContents);
		$ls_envContents = str_replace('CUSTOM_DIR=\'customer\'', 'CUSTOM_DIR=\'' . $this->customerName . '\'', $ls_envContents);

		// Write the updated contents back to the .env file
		file_put_contents($ls_envFilePath, $ls_envContents);

		unlink($ls_envExampleFilePath);

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
		// Define the path to the base configuration file
		$ls_baseConfigFilePath = ROOT . DS . $this->customerName . DS . 'config/awyiss.php';

		// Load the base config file
		$ls_baseConfig = include $ls_baseConfigFilePath;

		// Generate a new salt
		$ls_securitySalt = bin2hex(random_bytes(32));

		// Replace the salt in the base config file
		$ls_baseConfig['Security']['salt'] = $ls_securitySalt;

		// Set the cookie name based on the customer name
		$ls_baseConfig['Session']['cookie'] = $this->customerName;

		// Convert the updated configuration array to a string of PHP code
		$ls_contents = '<?php declare(strict_types=1);' . PHP_EOL . PHP_EOL . 'return ';
		$ls_contents .= VarExporter::export($ls_baseConfig, VarExporter::TRAILING_COMMA_IN_ARRAY);
		$ls_contents .= ';';
		$ls_contents = str_replace('    ', "\t", $ls_contents);

		// Write the updated contents back to the base config file
		file_put_contents($ls_baseConfigFilePath, $ls_contents);

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

		$ls_skeletonEnvironmentFilePath = ROOT . DS . $this->customerName . DS . 'config' . DS . 'environment_skeleton.php';
		$ls_environmentConfigFilePath = ROOT . DS . $this->customerName . DS . 'config' . DS . $this->installEnvironment . DS . 'awyiss.php';

		$ls_environmentFolderPath = dirname($ls_environmentConfigFilePath);

		// Create the environment folder if it does not exist
		if (!file_exists($ls_environmentFolderPath)) {
			mkdir($ls_environmentFolderPath, 0750, true);
		}

		// Copy the environment skeleton file to the environment config file
		rename($ls_skeletonEnvironmentFilePath, $ls_environmentConfigFilePath);

		// Determine if the environment resembles a production environment
		$lb_isProductionEnvironment = in_array($this->installEnvironment, ['production', 'prod', 'live']);

		// Set the log, debug, forceEnable flags and error level based on the environment
		$ls_logFlag = !$lb_isProductionEnvironment;
		$ls_debugFlag = !$lb_isProductionEnvironment;
		$ls_forceEnableFlag = !$lb_isProductionEnvironment;
		$ls_errorLevel = $lb_isProductionEnvironment ? 0 : E_ALL;

		// Load the environment config file
		$la_environmentConfig = include $ls_environmentConfigFilePath;

		// Replace the placeholders with the correct values in the environment config file
		$la_environmentConfig['Datasources']['default']['database'] = $this->dbName;
		$la_environmentConfig['Datasources']['default']['host'] = $this->dbHost;
		$la_environmentConfig['Datasources']['default']['log'] = $ls_logFlag;
		$la_environmentConfig['Datasources']['default']['password'] = $this->dbPassword;
		$la_environmentConfig['Datasources']['default']['username'] = $this->dbUsername;


		// If it does, drop the existing configuration
		$la_config = ConnectionManager::get('default')->config();
		//ConnectionManager::drop('default');
		ConnectionManager::setConfig('custom', array_merge($la_config, $la_environmentConfig['Datasources']['default'], [
			'className' => Connection::class,
		]));
		ConnectionManager::alias('custom', 'default');


		// Set the debug, forceEnable flags and error level based on the environment
		$la_environmentConfig['debug'] = $ls_debugFlag;
		$la_environmentConfig['DebugKit']['forceEnable'] = $ls_forceEnableFlag;
		$la_environmentConfig['Error']['errorLevel'] = $ls_errorLevel;

		$ls_contents = '<?php declare(strict_types=1);' . PHP_EOL . PHP_EOL . 'return ';

		$ls_contents .= VarExporter::export($la_environmentConfig, VarExporter::TRAILING_COMMA_IN_ARRAY);
		$ls_contents .= ';';
		$ls_contents = str_replace('    ', "\t", $ls_contents);

		// Write the updated contents back to the environment config file
		file_put_contents($ls_environmentConfigFilePath, $ls_contents);

		$this->io->success('Environment config file set.');
	}


	/**
	 * Sets up a demo attribute collection by updating the namespace in the ContentsAttributeOptionsCollection.php file.
	 *
	 * @return void
	 */
	protected function setupDemoAttributeCollection(): void {
		// Define the path to the ContentsAttributeOptionsCollection.php file
		$ls_filePath = ROOT . DS . $this->customerName . DS . 'Attributes' . DS . 'AttributeOptionsCollection' . DS . 'ContentsAttributeOptionsCollection.php';

		// Load the contents of the ContentsAttributeOptionsCollection.php file
		$ls_fileContents = file_get_contents($ls_filePath);

		// Replace the namespace with the camelized version of the given customer name
		$ls_newNamespace = 'namespace ' . Inflector::camelize($this->customerName) . '\\Attributes\\AttributeOptionsCollection;';
		$ls_fileContents = str_replace('namespace Customer\\Attributes\\AttributeOptionsCollection;', $ls_newNamespace, $ls_fileContents);

		// Write the updated contents back to the ContentsAttributeOptionsCollection.php file
		file_put_contents($ls_filePath, $ls_fileContents);

		$this->io->success('\Customer\Attributes\AttributeOptionsCollection file updated.');
	}


	/**
	 * Updates the namespace in the ide-twig.json file.
	 *
	 * @return void
	 */
	protected function setTwigNamespace(): void {
		// Define the path to the ide-twig.json file
		$ls_filePath = ROOT . DS . $this->customerName . DS . 'templates' . DS . 'ide-twig.json';

		// Load the contents of the ide-twig.json file
		$ls_fileContents = file_get_contents($ls_filePath);

		// Decode the JSON content to a PHP array
		$la_content = json_decode($ls_fileContents, true);

		// Change the value of the namespace key to the camelized version of the given customer name
		$la_content['namespaces'][0]['namespace'] = Inflector::camelize($this->customerName);

		// Encode the updated PHP array back to a JSON string
		$ls_updatedContents = json_encode($la_content, JSON_PRETTY_PRINT);

		// Write the updated JSON string back to the ide-twig.json file
		file_put_contents($ls_filePath, $ls_updatedContents);

		$this->io->success('ide-twig.json file updated.');
	}


	/**
	 * Sets the Twig extension according to the customer's folder by updating the namespace and class name in the
	 *
	 * @return void
	 */
	protected function setTwigExtension(): void {
		// Define the path to the CustomerExtension.php file
		$ls_filePath = ROOT . DS . $this->customerName . DS . 'Twig' . DS . 'Extension' . DS . 'CustomerExtension.php';

		// Load the contents of the CustomerExtension.php file
		$ls_fileContents = file_get_contents($ls_filePath);

		// Replace the namespace and class name with the camelized version of the given customer name
		$ls_newNamespace = 'namespace ' . Inflector::camelize($this->customerName) . '\\Twig\\Extension;';
		$ls_newClassName = 'class ' . Inflector::camelize($this->customerName) . 'Extension extends AbstractExtension {';

		$ls_fileContents = str_replace('namespace Customer\\Twig\\Extension;', $ls_newNamespace, $ls_fileContents);
		$ls_fileContents = str_replace('class CustomerExtension extends AbstractExtension {', $ls_newClassName, $ls_fileContents);

		// Write the updated contents back to the CustomerExtension.php file
		file_put_contents($ls_filePath, $ls_fileContents);

		// Rename the CustomerExtension.php file to match the new class name
		$ls_newFilePath = ROOT . DS . $this->customerName . DS . 'Twig' . DS . 'Extension' . DS . Inflector::camelize($this->customerName) . 'Extension.php';
		rename($ls_filePath, $ls_newFilePath);

		$this->io->success('\Twig\Extension\CustomerExtension file updated and renamed.');
	}
}
