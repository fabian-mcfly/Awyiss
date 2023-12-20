<?php declare(strict_types=1);


namespace AwyissBake\Command\Bake;


use Cake\Console\Arguments;
use Cake\Console\ConsoleOptionParser;
use Cake\Core\Configure;
use Cake\Datasource\ConnectionManager;
use Cake\Utility\Inflector;
use Migrations\Command\BakeSeedCommand;


/**
 * Task class for creating and updating controller files.
 */
class SeedCommand extends BakeSeedCommand {
	/**
	 * {@inheritDoc}
	 *
	 * Adds the `folder`-option
	 *
	 * @param ConsoleOptionParser $ao_parser
	 *
	 * @return ConsoleOptionParser
	 *
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function buildOptionParser(ConsoleOptionParser $ao_parser): ConsoleOptionParser {
		$lo_parser = parent::buildOptionParser($ao_parser);

		$lo_parser->addOption('folder', [
			'help' => 'The folder to save the migration in.',
		]);

		$lo_parser->addOption('truncate', [
			'boolean' => TRUE,
			'help' => 'Add the truncate command in the seed.',
			'short' => 't',
		]);


		return $lo_parser;
	}


	/**
	 * {@inheritDoc}
	 *
	 * Honors the `folder`-option
	 *
	 * @param Arguments $ao_args
	 *
	 * @return string
	 *
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function getPath(Arguments $ao_args): string {
		$ls_path = \APP . $this->pathFragment;
		if ($this->plugin) {
			$ls_path = $this->_pluginPath($this->plugin) . $this->pathFragment;
		}
		elseif ($ao_args->getOption('folder')) {
			$ls_path = $ao_args->getOption('folder');
			if (!in_array(substr($ls_path, 0, 1), ['/', DS])) {
				$ls_path = \ROOT . \DS . $ls_path;
			}
		}

		$ls_path = rtrim($ls_path, DS . '/') . \DS;


		return str_replace('/', DS, $ls_path);
	}


	/**
	 * @inheritDoc
	 */
	public function template(): string {
		return 'AwyissBake.Migrations/Seed/seed';
	}


	/**
	 * Get template data.
	 *
	 * @param \Cake\Console\Arguments $arguments The arguments for the command
	 *
	 * @return array
	 * @phpstan-return array<string, mixed>
	 */
	public function templateData(Arguments $arguments): array {
		$ls_namespace = Configure::read('App.namespace');
		if ($this->plugin) {
			$ls_namespace = $this->_pluginNamespace($this->plugin);
		}

		$ls_table = Inflector::tableize((string) $arguments->getArgumentAt(0));
		if ($arguments->hasOption('table')) {
			/** @var string $ls_table */
			$ls_table = $arguments->getOption('table');
		}

		$la_records = FALSE;
		if ($arguments->getOption('data')) {
			$li_limit = (int) $arguments->getOption('limit');

			/** @var string $lx_fields */
			$lx_fields = $arguments->getOption('fields') ?: '*';
			if ($lx_fields !== '*') {
				$lx_fields = explode(',', $lx_fields);
			}

			$lo_model = $this->getTableLocator()->get('BakeSeed', [
				'table' => $ls_table,
				'connection' => ConnectionManager::get($this->connection),
			]);

			if ($lo_model->hasBehavior('Authorize')) {
				/** @var \Awyiss\Model\Behavior\AuthorizeBehavior $lo_authorizeBehavior */
				$lo_authorizeBehavior = $lo_model->getBehavior('Authorize');
				$lo_authorizeBehavior->disable();
			}

			$lo_query = $lo_model->find('all')->enableHydration(FALSE);

			if ($li_limit) {
				$lo_query->limit($li_limit);
			}
			if ($lx_fields !== '*') {
				$lo_query->select($lx_fields);
			}

			/** @var array $la_records */
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
