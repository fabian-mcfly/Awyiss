<?php declare(strict_types=1);


namespace Awyiss\Command\Awyiss\Trait;


use Awyiss\Awyiss;
use Awyiss\Utility\Inflector;
use Brick\VarExporter\VarExporter;
use Cake\Database\Connection;
use Cake\Database\Driver\Postgres;
use Cake\Database\Driver\Sqlite;
use Cake\Datasource\ConnectionManager;
use Cake\Utility\Security;


/**
 * Trait ConfigTrait
 *
 * This trait provides methods for managing configuration in a CakePHP application.
 * It includes methods for creating an environment file, setting a base configuration file,
 * and setting an environment-specific configuration file.
 */
trait ConfigTrait {
	/**
	 * @var string The database host
	 */
	protected string $dbHost = 'localhost';
	/**
	 * @var string The database name
	 */
	protected string $dbName;
	/**
	 * @var string The database password
	 */
	protected string $dbPassword;
	/**
	 * @var string The database port
	 */
	protected string $dbPort;
	/**
	 * @var string The database type
	 */
	protected string $dbType = 'mysql';
	/**
	 * @var string The database username
	 */
	protected string $dbUsername;
	/**
	 * @var string
	 */
	protected string $rtEditor;


	/**
	 * Creates an environment file by copying the .env.example file to .env and replacing placeholders with user inputs.
	 *
	 * @return void
	 * @throws \Random\RandomException
	 */
	protected function createEnv(): void {
		$envExampleFilePath = ROOT . DS . $this->customerName . DS . '.env.example';
		$envFilePath = ROOT . DS . '.env';

		if ($this->dryRun) {
			// Check if the .env file can be created
			if (
				(
					file_exists($envFilePath)
					&& !is_writable($envFilePath)
				)
				|| (
					!file_exists($envFilePath)
					&& !is_writable(dirname($envFilePath))
				)
			) {
				$this->io->error('.env file cannot be created due to permission issues.');

				return;
			}

			$this->io->success('.env file can be created.');

			return;
		}

		$securitySalt = bin2hex(random_bytes(32));
		Security::setSalt($securitySalt);

		if (!file_exists($envExampleFilePath)) {
			$envContents = 'export CONFIG_ENV=\'' . $this->installEnvironment . '\'' . PHP_EOL;
			$envContents .= 'export CUSTOM_DIR=\'' . $this->customerName . '\'' . PHP_EOL;
			$envContents .= 'export SECURITY_SALT=\'' . $securitySalt . '\'' . PHP_EOL;
			$envContents .= 'export SESSION_COOKIE_NAME=\'' . Inflector::underscore($this->customerName) . '_session\'' . PHP_EOL;
		}
		else {
			// Load the contents of the .env.example file
			$envExampleContents = file_get_contents($envExampleFilePath);
			unlink($envExampleFilePath);

			// Replace the placeholders with the user inputs
			$envContents = str_replace(
				'CONFIG_ENV=\'development\'',
				'CONFIG_ENV=\'' . $this->installEnvironment . '\'',
				$envExampleContents
			);
			$envContents = str_replace('CUSTOM_DIR=\'customer\'', 'CUSTOM_DIR=\'' . $this->customerName . '\'', $envContents);
			$envContents = str_replace('SECURITY_SALT=\'random_salt\'', 'SECURITY_SALT=\'' . $securitySalt . '\'', $envContents);
			$envContents = str_replace(
				'SESSION_COOKIE_NAME=\'awyiss_session\'',
				'SESSION_COOKIE_NAME=\'' . Inflector::underscore($this->customerName) . '_session\'',
				$envContents
			);
		}

		// Write the updated contents back to the .env file
		if (file_put_contents($envFilePath, $envContents)) {
			$this->io->success('.env file created.');

			return;
		}

		$this->io->warning('Could not create .env file. You can manually create the .env file with the following contents:');
		$this->io->info($envContents);
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
		$skeletonEnvironmentFilePath = ROOT . DS . $this->customerName . DS . 'config' . DS . 'environment_skeleton.php';
		if (!file_exists($skeletonEnvironmentFilePath)) {
			$this->io->error('Skeleton environment config file does not exist.');

			return;
		}

		$environmentConfigFilePath = ROOT . DS . $this->customerName . DS . 'config' . DS . $this->installEnvironment . DS . 'awyiss.php';
		$environmentFolderPath = dirname($environmentConfigFilePath);

		// Determine if the environment resembles a production environment
		$isProductionEnvironment = in_array($this->installEnvironment, ['production', 'prod', 'live']);

		// Set the log and debug flags and error level based on the environment
		$logFlag = !$isProductionEnvironment;
		$debugFlag = !$isProductionEnvironment;
		$errorLevel = $isProductionEnvironment ? 0 : E_ALL;

		// Load the environment config file
		$environmentConfig = include $environmentConfigFilePath;

		// Set the database configuration based on user inputs
		$environmentConfig['Datasources']['default']['database'] = $this->dbName;

		if ($this->dbType === 'postgresql') {
			$environmentConfig['Datasources']['default']['driver'] = Postgres::class;
		}
		elseif ($this->dbType === 'sqlite') {
			$environmentConfig['Datasources']['default']['driver'] = Sqlite::class;
		}

		if (!empty($this->dbHost)) {
			$environmentConfig['Datasources']['default']['host'] = $this->dbHost;
		}
		else {
			unset($environmentConfig['Datasources']['default']['host']);
		}

		if (!empty($this->dbPort)) {
			$environmentConfig['Datasources']['default']['port'] = $this->dbPort;
		}
		else {
			unset($environmentConfig['Datasources']['default']['port']);
		}

		if (!empty($this->dbPassword)) {
			$environmentConfig['Datasources']['default']['password'] = $this->dbPassword;
		}
		else {
			unset($environmentConfig['Datasources']['default']['password']);
		}

		if (!empty($this->dbUsername)) {
			$environmentConfig['Datasources']['default']['username'] = $this->dbUsername;
		}
		else {
			unset($environmentConfig['Datasources']['default']['username']);
		}

		$environmentConfig['Datasources']['default']['log'] = $logFlag;

		// Temporarily set the 'custom' connection as the default connection to apply the new database configuration immediately
		$config = ConnectionManager::get('default')
			->config()
		;
		ConnectionManager::setConfig('custom', array_merge($config, $environmentConfig['Datasources']['default'], [
			'className' => Connection::class,
		]));
		ConnectionManager::alias('custom', 'default');

		if ($this->dryRun) {
			// Check if the folder can be created
			if (
				(
					file_exists($environmentConfigFilePath)
					&& !is_writable($environmentConfigFilePath)
				)
				|| (
					!file_exists($environmentConfigFilePath)
					&& !file_exists($environmentFolderPath)
					&& !is_writable(dirname($environmentFolderPath))
				)
			) {
				$this->io->error('Environment config file cannot be created due to permission issues.');

				return;
			}

			$this->io->success('Environment config file can be created.');

			return;
		}

		// Create the environment folder if it does not exist
		if (!file_exists($environmentFolderPath)) {
			mkdir($environmentFolderPath, 0755, true);
		}

		// Move the environment skeleton file to the environment config file
		if (!rename($skeletonEnvironmentFilePath, $environmentConfigFilePath)) {
			$this->io->error('Could not set environment config file. Please check the file permissions and try again.');

			return;
		}

		// Set the debug flag and error level based on the environment
		$environmentConfig['debug'] = $debugFlag;
		$environmentConfig['Error']['errorLevel'] = $errorLevel;

		$contents = '<?php declare(strict_types=1);' . PHP_EOL . PHP_EOL . 'return ';

		$contents .= VarExporter::export($environmentConfig, VarExporter::TRAILING_COMMA_IN_ARRAY);
		$contents .= ';';
		$contents = str_replace('    ', "\t", $contents);

		// Write the updated contents back to the environment config file
		if (file_put_contents($environmentConfigFilePath, $contents)) {
			$this->io->success('Environment config file set.');

			return;
		}

		$this->io->error('Could not set environment config file. Please check the file permissions and try again.');
	}


	/**
	 * Sets up a demo attribute collection by updating the namespace in the ContentsAttributeOptions.php file.
	 *
	 * @return void
	 */
	protected function setupDemoAttributeCollection(): void {
		// Define the path to the ContentsAttributeOptions.php file
		$filePath = ROOT . DS . $this->customerName . DS . 'Attribute' . DS . 'AttributeOptions' . DS . 'ContentsAttributeOptions.php';

		if ($this->dryRun) {
			// If the file does not exist or is not writable, skip the update
			if (
				(
					!file_exists($filePath)
					|| !is_writable($filePath)
				)
			) {
				$this->io->warning(
					'\Customer\Attribute\AttributeOptions\ContentsAttributeOptions file cannot be updated due to permission issues.'
				);

				return;
			}

			$this->io->success('\Customer\Attribute\AttributeOptions\ContentsAttributeOptions can be updated.');

			return;
		}


		// Load the contents of the ContentsAttributeOptions.php file
		$fileContents = file_get_contents($filePath);

		$newNamespace = 'namespace ' . Inflector::ucparts($this->customerName, false) . '\\Attribute\\AttributeOptions;';
		$fileContents = str_replace('namespace Customer\\Attribute\\AttributeOptions;', $newNamespace, $fileContents);

		// Write the updated contents back to the ContentsAttributeOptions.php file
		if (file_put_contents($filePath, $fileContents)) {
			$this->io->success('\Customer\Attribute\AttributeOptions\ContentsAttributeOptions file updated.');

			return;
		}

		$this->io->error('Could not update \Customer\Attribute\AttributeOptions\ContentsAttributeOptions file.');
	}


	/**
	 * Sets up a demo menu cell
	 *
	 * @return void
	 */
	protected function setupDemoMenuCell(): void {
		// Define the path to the MenuCell.php file
		$filePath = ROOT . DS . $this->customerName . DS . 'View' . DS . 'Cell' . DS . 'Frontend' . DS . 'MenuCell.php';

		if ($this->dryRun) {
			// If the file does not exist or is not writable, skip the update
			if (
				(
					!file_exists($filePath)
					|| !is_writable($filePath)
				)
			) {
				$this->io->warning('\Customer\View\Cell\Frontend\MenuCell file cannot be updated due to permission issues.');

				return;
			}

			$this->io->success('\Customer\View\Cell\Frontend\MenuCell can be updated.');

			return;
		}

		// Load the contents of the MenuCell.php file
		$fileContents = file_get_contents($filePath);

		// Replace the namespace with the CamelCased version of the given customer name
		$newNamespace = 'namespace ' . Inflector::ucparts($this->customerName, false) . '\\View\\Cell\\Frontend;';
		$fileContents = str_replace('namespace Customer\\View\\Cell\\Frontend;', $newNamespace, $fileContents);

		// Write the updated contents back to the MenuCell.php file
		if (file_put_contents($filePath, $fileContents)) {
			$this->io->success('\Customer\View\Cell\Frontend\MenuCell file updated.');

			return;
		}

		$this->io->error('Could not update \Customer\View\Cell\Frontend\MenuCell file.');
	}


	/**
	 * Updates the namespace in the ide-twig.json file.
	 *
	 * @return void
	 */
	protected function setTwigNamespace(): void {
		// Define the path to the ide-twig.json file
		$filePath = ROOT . DS . $this->customerName . DS . 'templates' . DS . 'ide-twig.json';

		if ($this->dryRun) {
			// If the file does not exist or is not writable, skip the update
			if (
				(
					!file_exists($filePath)
					|| !is_writable($filePath)
				)
			) {
				$this->io->warning('ide-twig.json file cannot be updated due to permission issues.');

				return;
			}

			$this->io->success('ide-twig.json file can be updated.');

			return;
		}

		// Rename `_ide-twig.json` to `ide-twig.json` if it exists in Frontend and Backend template folders
		$backendFilePath = ROOT . DS . $this->customerName . DS . 'templates' . DS . 'Backend' . DS;
		if (file_exists($backendFilePath . '_ide-twig.json')) {
			rename($backendFilePath . '_ide-twig.json', $backendFilePath . 'ide-twig.json');
		}

		$frontendFilePath = ROOT . DS . $this->customerName . DS . 'templates' . DS . 'Frontend' . DS;
		if (file_exists($frontendFilePath . '_ide-twig.json')) {
			rename($frontendFilePath . '_ide-twig.json', $frontendFilePath . 'ide-twig.json');
		}

		// Load the contents of the ide-twig.json file
		$fileContents = file_get_contents($filePath);

		// Decode the JSON content to a PHP array
		$content = json_decode($fileContents, true);

		// Change the value of the namespace key to the CamelCased version of the given customer name
		$content['namespaces'][0]['namespace'] = Inflector::ucparts($this->customerName, false);

		// Encode the updated PHP array back to a JSON string
		$updatedContents = json_encode($content, JSON_PRETTY_PRINT);

		// Write the updated JSON string back to the ide-twig.json file
		if (file_put_contents($filePath, $updatedContents)) {
			$this->io->success('ide-twig.json file updated.');

			return;
		}

		$this->io->error('Could not update ide-twig.json file.');
	}


	/**
	 * Sets the Twig extension according to the customer's folder by updating the namespace and class name in the
	 *
	 * @return void
	 */
	protected function setTwigExtension(): void {
		// Define the path to the CustomerExtension.php file
		$filePath = ROOT . DS . $this->customerName . DS . 'Twig' . DS . 'Extension' . DS . 'CustomerExtension.php';

		if ($this->dryRun) {
			// If the file does not exist or is not writable, skip the update
			if (
				(
					!file_exists($filePath)
					|| !is_writable($filePath)
				)
			) {
				$this->io->warning('\Twig\Extension\CustomerExtension file cannot be updated due to permission issues.');

				return;
			}

			$this->io->success('\Twig\Extension\CustomerExtension can be updated.');

			return;
		}

		// Load the contents of the CustomerExtension.php file
		$fileContents = file_get_contents($filePath);

		// Replace the namespace and class name with the CamelCased version of the given customer name
		$newNamespace = 'namespace ' . Inflector::ucparts($this->customerName, false) . '\\Twig\\Extension;';
		$newClassName = 'class ' . Inflector::ucparts($this->customerName, false) . 'Extension extends AbstractExtension {';

		$fileContents = str_replace('namespace Customer\\Twig\\Extension;', $newNamespace, $fileContents);
		$fileContents = str_replace('class CustomerExtension extends AbstractExtension {', $newClassName, $fileContents);

		// Write the updated contents back to the CustomerExtension.php file
		if (!file_put_contents($filePath, $fileContents)) {
			$this->io->error('Could not update \Twig\Extension\CustomerExtension file.');

			return;
		}

		// Rename the CustomerExtension.php file to match the new class name
		$newFilePath = ROOT . DS . $this->customerName . DS . 'Twig' . DS . 'Extension' . DS . Inflector::ucparts(
			$this->customerName,
			false
		) . 'Extension.php';
		if (rename($filePath, $newFilePath)) {
			$this->io->success('\Twig\Extension\CustomerExtension file updated and renamed.');

			return;
		}

		$this->io->error('Could not update and rename \Twig\Extension\CustomerExtension file.');
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
			$this->io->info('Rich text editor would be set to ' . strtolower($this->rtEditor) . '.');

			return;
		}

		/** @var \Awyiss\Model\Table\ConfigurationTable $configTable */
		$configTable = $this->fetchTable('Configuration');
		$configTable
			->getBehavior('Categories')
			->setConfig('buildRules', false)
		;
		$config = $configTable->newDefaultEntity();

		$configTable->patchEntity($config, [
			'realm' => Awyiss::REALM_BACKEND,
			'scope' => 'System',
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
