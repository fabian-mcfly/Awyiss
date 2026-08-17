<?php declare(strict_types=1);


namespace Awyiss\Command\Bake;


use Awyiss\Command\Util\UtilTrait;
use Awyiss\Model\Table\AuditTable;
use Awyiss\Model\Table\UsergroupPermissionsTable;
use Awyiss\Model\Table\UsergroupsUsersTable;
use Awyiss\Utility\Inflector;
use Bake\Command\BakeCommand;
use Bake\Utility\TableScanner;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\Core\Configure;
use Cake\Datasource\ConnectionManager;


/**
 * Task class for creating policies
 */
class PolicyCommand extends BakeCommand {
	/*
	 * Use UtilTrait so that every call of `$this->getPath()` will use the one provided by this trait,
	 * honoring the `namespace`-option
	 */
	use UtilTrait;


	/**
	 * Path fragment for generated code.
	 *
	 * @var string
	 */
	public string $pathFragment = 'Authorization/Policy/';
	/**
	 * @var array<string> Table names that must never have permissions
	 */
	private array $blocklistedNames = [
		AuditTable::TABLE,
		'queue_processes',
		'queued_jobs',
		UsergroupPermissionsTable::TABLE,
		UsergroupsUsersTable::TABLE,
	];


	/**
	 * Execute the command.
	 *
	 * @param Arguments $args The command arguments.
	 * @param ConsoleIo $io The console io
	 * @return int|null The exit code or null for success
	 */
	public function execute(Arguments $args, ConsoleIo $io): ?int {
		$this->extractCommonProperties($args);
		$name = $args->getArgument('name') ?? '';
		$name = $this->_getName($name);

		if (empty($name)) {
			/** @var \Cake\Database\Connection $connection */
			$connection = ConnectionManager::get($this->connection);
			$scanner = new TableScanner($connection);
			$io->out('Possible policies based on your current database:');
			foreach ($scanner->listUnskipped() as $tableName) {
				if (str_starts_with($tableName, 'attributes_') || in_array($tableName, $this->blocklistedNames)) {
					continue;
				}

				$io->out('- ' . $this->_camelize($tableName));
			}


			return static::CODE_SUCCESS;
		}

		if (in_array($name, $this->blocklistedNames)) {
			$io->err('Error: Name not allowed');


			return static::CODE_ERROR;
		}

		$policyName = $this->_camelize($name);

		$this->bake($policyName, $args, $io);


		return static::CODE_SUCCESS;
	}


	/**
	 * Assembles and writes a Policy file
	 *
	 * @param string $policyName Policy name already pluralized and correctly cased.
	 * @param Arguments $args The console arguments
	 * @param ConsoleIo $io The console io
	 * @return void
	 */
	public function bake(string $policyName, Arguments $args, ConsoleIo $io): void {
		$io->quiet(sprintf('Baking policy class for %s...', $policyName));

		$prefix = $this->getPrefix($args);
		if ($prefix) {
			$prefix = '\\' . str_replace('/', '\\', $prefix);
		}

		//Controllers default to importing AppController from `App`
		$namespace = Inflector::camelize($args->getOption('namespace') ?: Configure::read('App.namespace'));

		$data = [
			'name' => $policyName,
			'namespace' => $namespace,
			'prefix' => $prefix,
		];

		$contents = $this
			->createTemplateRenderer()
			->set($data)
			->generate('Policy/policy')
		;

		$path = $this->getPath($args);
		$filePath = $path . $policyName . 'Policy.php';

		$io->createFile($filePath, $contents, $this->force);
	}


	/**
	 * Gets the option parser instance and configures it.
	 *
	 * @param ConsoleOptionParser $parser The option parser to update.
	 * @return ConsoleOptionParser
	 */
	public function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser {
		$parser = $this->_setCommonOptions($parser);

		$parser
			->setDescription('Bake a policy skeleton.')
			->addArgument('name', [
				'help' => 'Name of the policy to bake (without the `Policy` suffix).',
			])
			->addOption('namespace', [
				'choices' => [
					'Awyiss',
					CUSTOM_NAMESPACE,
				],
				'default' => 'Awyiss',
				'help' => 'The namespace for the policy.',
			])
			->addOption('prefix', [
				'help' => 'The routing prefix to use.',
			])
		;


		return $parser;
	}
}
