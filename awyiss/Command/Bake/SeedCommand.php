<?php declare(strict_types=1);


namespace Awyiss\Command\Bake;


use Awyiss\Command\Util\UtilTrait;
use Cake\Console\Arguments;
use Cake\Console\ConsoleOptionParser;
use Cake\Core\Configure;
use Cake\Datasource\ConnectionManager;
use Cake\Utility\Inflector;
use Migrations\Command\BakeSeedCommand as BaseBakeSeedCommand;


/**
 * Task class for creating and updating controller files.
 */
class SeedCommand extends BaseBakeSeedCommand {
	use UtilTrait;


	/**
	 * {@inheritDoc}
	 *
	 * Adds the `folder`-option
	 *
	 * @param ConsoleOptionParser $ao_parser
	 * @return ConsoleOptionParser
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function buildOptionParser(ConsoleOptionParser $ao_parser): ConsoleOptionParser {
		$lo_parser = parent::buildOptionParser($ao_parser);

		$lo_parser->addOption('folder', [
			'help' => 'The folder to save the migration in.',
		]);

		$lo_parser->addOption('truncate', [
			'boolean' => true,
			'help' => 'Add the truncate command in the seed.',
			'short' => 't',
		]);


		return $lo_parser;
	}


	/**
	 * @inheritDoc
	 */
	public function template(): string {
		return 'Migration/Seed/seed';
	}


	/**
	 * Get template data.
	 *
	 * @param \Cake\Console\Arguments $arguments The arguments for the command
	 * @return array
	 * @phpstan-return array<string, mixed>
	 */
	public function templateData(Arguments $arguments): array {
		$ls_namespace = Configure::read('App.namespace');
		if ($this->plugin) {
			$ls_namespace = $this->_pluginNamespace($this->plugin);
		}

		$ls_table = Inflector::tableize((string)$arguments->getArgumentAt(0));
		if ($arguments->hasOption('table')) {
			/** @var string $ls_table */
			$ls_table = $arguments->getOption('table');
		}

		$la_records = false;
		if ($arguments->getOption('data')) {
			$li_limit = (int)$arguments->getOption('limit');

			/** @var string $lx_fields */
			$lx_fields = $arguments->getOption('fields') ?: '*';
			if ($lx_fields !== '*') {
				$lx_fields = explode(',', $lx_fields);
			}

			$lo_model = $this->getTableLocator()->get('BakeSeed', [
				'table' => $ls_table,
				'connection' => ConnectionManager::get($this->connection),
			]);

			$lo_query = $lo_model->find('all')->enableHydration(false);

			if ($li_limit) {
				$lo_query->limit($li_limit);
			}
			if ($lx_fields !== '*') {
				$lo_query->select($lx_fields);
			}

			$la_records = $lo_query->disableResultsCasting()->toArray();

			$la_records = $this->prettifyArray($la_records);
		}


		return [
			'className' => $this->_name,
			'namespace' => $ls_namespace,
			'records' => $la_records,
			'table' => $ls_table,
			'truncate' => $arguments->getOption('truncate'),
		];
	}
}
