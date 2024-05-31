<?php declare(strict_types=1);


namespace Awyiss\Command\Awyiss;


use Awyiss\Command\Awyiss\Trait\AdminTrait;
use Awyiss\Command\Awyiss\Trait\ConfigTrait;
use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Database\Exception\MissingConnectionException;
use Cake\Datasource\ConnectionManager;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;


/**
 * Class InstallCommand
 * This class handles the installation process.
 */
class InstallCommand extends Command {
	use AdminTrait;
	use ConfigTrait;


	/**
	 * @var Filesystem The filesystem
	 */
	protected Filesystem $filesystem;
	/**
	 * @var ConsoleIo The console I/O
	 * @noinspection PhpPropertyNamingConventionInspection
	 */
	protected ConsoleIo $io;
	/**
	 * @var bool Whether the database connection is valid
	 */
	protected bool $connectionValid = false;
	/**
	 * @var string The customer's name
	 */
	protected string $customerName;
	/**
	 * @var string The environment of the installation
	 */
	protected string $installEnvironment;


	/**
	 * Main execution method
	 *
	 * @param Arguments $args
	 * @param ConsoleIo $io
	 * @return int
	 * @throws \Brick\VarExporter\ExportException
	 * @throws \Random\RandomException
	 * @throws \ReflectionException
	 */
	public function execute(Arguments $args, ConsoleIo $io): int {
		$this->filesystem = new Filesystem();
		$this->io = $io;

		// Check if the dummy folder exists
		$this->checkDummyFolder();

		// Get user inputs
		$this->getInputs();

		// Move the dummy folder to the customer's folder
		$this->copyDummyFolder();

		// Create the .env file
		$this->createEnv();

		// Update the base configuration file with the user inputs
		$this->setBaseConfigFile();

		// Update the environment configuration file with the user inputs
		$this->setEnvironmentConfigFile();

		// Check the database connection
		$this->checkConnection();

		// Create the database if the connection is valid
		if ($this->connectionValid) {
			// Migrate the database
			$this->migrateDatabase();

			// Create the admin user with the provided username and password
			$this->createAdminUser();
		}

		// Set up the demo attribute collection
		$this->setupDemoAttributeCollection();

		// Set the Twig namespace in the ide-twig.json
		$this->setTwigNamespace();

		// Set the Twig extension according to the customer's folder
		$this->setTwigExtension();

		// Create directories and symlinks for the assets
		$this->createDirectoriesAndSymlinks();

		// Remove all .gitkeep files from the customer's folder
		$this->removeGitkeepFiles();

		// Done
		$this->io->success('Installation completed.');


		return static::CODE_SUCCESS;
	}


	/**
	 * Check if the dummy folder exists
	 *
	 * @return bool
	 */
	protected function checkDummyFolder(): void {
		if (!$this->filesystem->exists(ROOT . DS . '_customer_skeleton')) {
			$this->io->abort('Skeleton folder does not exist. Installation aborted.');
		}
	}


	/**
	 * Gathers user inputs for the installation process.
	 * This method prompts the user for necessary information to proceed with the installation.
	 * It asks for database credentials, customer name, admin username and password, and the environment of installation.
	 * The gathered inputs are stored in the respective class properties.
	 *
	 * @return void
	 */
	protected function getInputs(): void {
		// Ask for the database username. If provided, also ask for the database password, name, and host.
		$this->dbUsername = $this->io->ask('Database username?');
		if ($this->dbUsername) {
			$this->dbPassword = $this->io->ask('Database password?');
			$this->dbName = $this->io->ask('Database name?');
			$this->dbHost = $this->io->ask('Database host?', 'localhost');
		}

		// Ask for the customer name and validate it.
		$this->customerName = $this->io->ask('Customer name?');
		$this->validateCustomerName();

		// Ask for the admin username. If provided, also ask for the admin password.
		$this->adminUsername = $this->io->ask('Admin username?');
		if ($this->adminUsername) {
			$this->adminPassword = $this->io->ask('Admin password?');
		}

		// Ask for the environment of installation.
		$this->installEnvironment = $this->io->ask('Environment of install?', 'development');
	}


	/**
	 * Move the dummy folder to the customer's folder
	 */
	protected function copyDummyFolder(): void {
		try {
			$this->filesystem->mirror(ROOT . DS . '_customer_skeleton', ROOT . DS . $this->customerName);
		}
		catch (IOExceptionInterface) {
			$this->io->abort('Failed to move skeleton folder.');
		}
	}


	/**
	 * Checks the database connection.
	 * This method attempts to establish a connection to the database using the "default" connection configuration.
	 * It then executes a simple SQL statement to verify that the connection is working.
	 * If the connection is successful, it sets the $connectionValid property to true and outputs a success message.
	 * If the connection fails, it sets the $connectionValid property to false, outputs a warning message, and returns early.
	 *
	 * @return void
	 * @throws \Cake\Database\Exception\MissingConnectionException if the connection fails.
	 */
	protected function checkConnection(): void {
		try {
			// Get the "default" connection
			/** @var \Cake\Database\Connection $lo_connection */
			$lo_connection = ConnectionManager::get('default');

			// Execute a simple SQL statement to check the connection
			$lo_connection->execute('SELECT 1');
			$this->connectionValid = true;

			$this->io->success('Connected to the database successfully.');
		}
		catch (MissingConnectionException) {
			$this->connectionValid = false;
			$this->io->warning('Failed to connect to the database. Please check your database credentials.');
		}
	}


	/**
	 * Migrates the database.
	 * This method runs the necessary commands to migrate the database.
	 * It uses the Symfony Process component to execute the commands.
	 *
	 * @return void
	 */
	protected function migrateDatabase(): void {
		// Run the migrations
		$this->runCommand(['bin/cake', 'migrations', 'migrate', '-q', '--no-lock']);
		$this->io->success('Migrations completed.');

		$this->runCommand(['bin/cake', 'migrations', 'migrate', '-q', '-p', 'Queue']);
		$this->io->success('Queue migrations completed.');

		$this->runCommand(['bin/cake', 'migrations', 'seed', '-q']);
		$this->io->success('Seeding completed.');
	}


	/**
	 * Create directories and symlinks for the assets
	 */
	protected function createDirectoriesAndSymlinks(): void {
		$this->filesystem->symlink(ROOT . DS . 'awyiss' . DS . 'assets', WWW_ROOT . 'awyiss' . DS . 'assets');
		$this->filesystem->symlink(ROOT . DS . 'vendor' . DS . 'tinymce' . DS . 'tinymce', WWW_ROOT . 'awyiss' . DS . 'assets' . DS . 'js' . DS . 'TinyMCE' . DS . 'tinymce');
		$this->filesystem->symlink(ROOT . DS . $this->customerName . DS . 'assets', WWW_ROOT . 'assets');
	}


	/**
	 * Make sure the customer name is a valid folder name and not one of the reserved names
	 *
	 * @return void
	 */
	protected function validateCustomerName(): void {
		$la_reservedNames = ['_customer_skeleton', 'awyiss', 'bin', 'logs', 'tests', 'tmp', 'vendor', 'webroot'];

		if (empty($this->customerName)) {
			$this->io->abort('Invalid customer name.');
		}

		if (in_array($this->customerName, $la_reservedNames)) {
			$this->io->abort('Invalid customer name.');
		}

		if (preg_match('/[^a-z0-9_-]/', $this->customerName)) {
			$this->io->abort('Invalid customer name.');
		}
	}


	/**
	 * Executes a command using the Symfony Process component.
	 * This method takes an array of strings representing the command to be executed.
	 * It creates a new Process instance with the command, and then calls the mustRun method on the Process instance to execute the command.
	 * If the command is successful, the output of the command is printed to the console.
	 * If there is an error, an exception is thrown and the error message is printed to the console.
	 *
	 * @param array $command An array of strings representing the command to be executed.
	 * @return void
	 * @throws ProcessFailedException if the command fails.
	 */
	protected function runCommand(array $command): void {
		$lo_process = new Process($command);

		try {
			$lo_process->mustRun();
		}
		catch (ProcessFailedException $ex) {
			$this->io->abort('The command failed. Error: ' . $ex->getMessage());
		}
	}


	/**
	 * Find and remove all .gitkeep files from the customer's folder
	 */
	protected function removeGitkeepFiles(): void {
		$lo_finder = new Finder();
		$lo_finder->files()->in(ROOT . DS . $this->customerName)->name('.gitkeep')->ignoreDotFiles(false);

		foreach ($lo_finder as $lo_file) {
			$this->filesystem->remove($lo_file->getRealPath());
		}

		$this->io->success('.gitkeep files removed successfully.');
	}
}
