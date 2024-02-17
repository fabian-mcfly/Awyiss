<?php declare(strict_types=1);


namespace Awyiss\Command\Bake;


use Awyiss\Command\Util\UtilTrait;
use Bake\Command\BakeCommand;
use Bake\Utility\TableScanner;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\Core\Configure;
use Cake\Datasource\ConnectionManager;
use Cake\Utility\Inflector;


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
	 * @var array|array<string> Table names that must never have permissions
	 */
	private array $blocklistedNames = ['audit', 'queue_processes', 'queued_jobs', 'usergroup_permissions', 'usergroups_users'];


	/**
	 * Execute the command.
	 *
	 * @param Arguments $ao_args The command arguments.
	 * @param ConsoleIo $ao_io The console io
	 * @return int|null The exit code or null for success
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function execute(Arguments $ao_args, ConsoleIo $ao_io): ?int {
		$this->extractCommonProperties($ao_args);
		$ls_name = $ao_args->getArgument('name') ?? '';
		$ls_name = $this->_getName($ls_name);

		if (empty($ls_name)) {
			/** @var \Cake\Database\Connection $lo_connection */
			$lo_connection = ConnectionManager::get($this->connection);
			$lo_scanner = new TableScanner($lo_connection);
			$ao_io->out('Possible policies based on your current database:');
			foreach ($lo_scanner->listUnskipped() as $ls_table) {
				if (str_starts_with($ls_table, 'attributes_') || in_array($ls_table, $this->blocklistedNames)) {
					continue;
				}

				$ao_io->out('- ' . $this->_camelize($ls_table));
			}


			return static::CODE_SUCCESS;
		}

		if (in_array($ls_name, $this->blocklistedNames)) {
			$ao_io->err('Error: Name not allowed');


			return static::CODE_ERROR;
		}

		$ls_policy = $this->_camelize($ls_name);

		$this->bake($ls_policy, $ao_args, $ao_io);


		return static::CODE_SUCCESS;
	}


	/**
	 * Assembles and writes a Policy file
	 *
	 * @param string $as_policyName Policy name already pluralized and correctly cased.
	 * @param Arguments $ao_args The console arguments
	 * @param ConsoleIo $ao_io The console io
	 * @return void
	 */
	public function bake(string $as_policyName, Arguments $ao_args, ConsoleIo $ao_io): void {
		$ao_io->quiet(sprintf('Baking policy class for %s...', $as_policyName));

		$ls_prefix = $this->getPrefix($ao_args);
		if ($ls_prefix) {
			$ls_prefix = '\\' . str_replace('/', '\\', $ls_prefix);
		}

		//Controllers default to importing AppController from `App`
		$ls_namespace = Inflector::camelize($ao_args->getOption('namespace') ?: Configure::read('App.namespace'));

		$la_data = [
			'name' => $as_policyName,
			'namespace' => $ls_namespace,
			'prefix' => $ls_prefix,
		];

		$ls_contents = $this->createTemplateRenderer()->set($la_data)->generate('Policy/policy');

		$ls_path = $this->getPath($ao_args);
		$ls_filePath = $ls_path . $as_policyName . 'Policy.php';

		$ao_io->createFile($ls_filePath, $ls_contents, $this->force);
	}


	/**
	 * Gets the option parser instance and configures it.
	 *
	 * @param ConsoleOptionParser $ao_parser The option parser to update.
	 * @return ConsoleOptionParser
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function buildOptionParser(ConsoleOptionParser $ao_parser): ConsoleOptionParser {
		$lo_parser = $this->_setCommonOptions($ao_parser);

		$lo_parser->setDescription(
			'Bake a policy skeleton.'
		)->addArgument('name', [
			'help' => 'Name of the policy to bake (without the `Policy` suffix).',
		])->addOption('namespace', [
			'choices' => [
				'Awyiss',
				CUSTOM_NAMESPACE,
			],
			'default' => 'Awyiss',
			'help' => 'The namespace for the policy.',
		])->addOption('prefix', [
			'help' => 'The routing prefix to use.',
		]);


		return $lo_parser;
	}
}
