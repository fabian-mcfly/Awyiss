<?php declare(strict_types=1);


namespace AwyissBake\Command\Bake;


use AwyissBake\Util\ColumnParser;
use Bake\Utility\TemplateRenderer;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\Core\Configure;
use Cake\Event\Event;
use Cake\Event\EventManager;
use Cake\Utility\Inflector;
use Migrations\Command\BakeMigrationCommand;


/**
 * Command class for generating migration snapshot files.
 */
class MigrationCommand extends BakeMigrationCommand {
	/**
	 * @inheritDoc
	 */
	public function template (): string {
		return 'AwyissBake.Migrations/config/skeleton';
	}


	/**
	 * @inheritDoc
	 *
	 * Implemented nearly 1:1 to use \AwyissBake\Util\ColumnParser instead
	 *
	 * @param Arguments $ao_arguments
	 *
	 * @return array
	 *
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function templateData (Arguments $ao_arguments): array {
		$ls_className = $this->_name;
		$ls_namespace = Configure::read('App.namespace');
		$ls_pluginPath = '';
		if ($this->plugin) {
			$ls_namespace = $this->_pluginNamespace($this->plugin);
			$ls_pluginPath = $this->plugin . '.';
		}

		$la_actions = $this->detectAction($ls_className);

		if (empty($la_actions)) {
			return [
				'plugin' => $this->plugin,
				'pluginPath' => $ls_pluginPath,
				'namespace' => $ls_namespace,
				'tables' => [],
				'action' => NULL,
				'name' => $ls_className,
			];
		}

		$la_arguments = $ao_arguments->getArguments();
		unset($la_arguments[0]);
		$lo_columnParser = new ColumnParser();
		$la_fields = $lo_columnParser->parseFields($la_arguments);
		$la_indexes = $lo_columnParser->parseIndexes($la_arguments);
		$la_primaryKeys = $lo_columnParser->parsePrimaryKey($la_arguments);

		if (in_array($la_actions[0], ['alter_table', 'add_field'], TRUE) && ! empty($la_primaryKeys)) {
			$this->io->abort('Adding a primary key to an already existing table is not supported.');
		}


		[$ls_action, $ls_table] = $la_actions;

		//If the requested action is to alter a column,
		if ($ls_action === 'alter_field') {
			//Extract the name of the column from the migration name
			if (preg_match('/^Alter(.+?)On(.*)/', $ls_className, $la_matches)) {
				//Get the field name of the column from inside the migration
				$ls_key = array_key_first($la_fields);

				$ls_fieldName = Inflector::underscore($la_matches[1]);
				if ($ls_key != $ls_fieldName) {
					/**
					 * If the column name in the migration name and the one inside the migration differ,
					 * we want to rename the column. This is something the normal CakePHP-migration does not offer.
					 *
					 * This way, we can check for the `originalName`-key of the field inside the `skeleton.twig`-file
					 * and call the `rename`-method of the migration
					 *
					 * @see awyiss/plugins/AwyissBake/templates/bake/migrations/config/skeleton.twig:53
					 * @link awyiss/plugins/AwyissBake/templates/bake/migrations/config/skeleton.twig:53
					 */
					$la_fields[ $ls_key ]['originalName'] = $ls_fieldName;
				}
			}
		}

		return [
			'plugin' => $this->plugin,
			'pluginPath' => $ls_pluginPath,
			'namespace' => $ls_namespace,
			'tables' => [$ls_table],
			'action' => $ls_action,
			'columns' => [
				'fields' => $la_fields,
				'indexes' => $la_indexes,
				'primaryKey' => $la_primaryKeys,
			],
			'name' => $ls_className,
		];
	}


	/**
	 * {@inheritDoc}
	 *
	 * Re-implemented `\Migrations\Command\BakeSimpleMigrationCommand::bake()` because it's not possible to call a parent's parent.
	 *
	 * @param string    $as_name
	 * @param Arguments $ao_arguments
	 * @param ConsoleIo $ao_io
	 *
	 * @return void
	 *
	 * @see \Migrations\Command\BakeSimpleMigrationCommand::bake()
	 *
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function bake (string $as_name, Arguments $ao_arguments, ConsoleIo $ao_io): void {
		EventManager::instance()->on('Bake.initialize', function (Event $event) {
			$event->getSubject()->loadHelper('Migrations.Migration');
		});


		$this->_name = $as_name;
		$ls_path = $this->getPath($ao_arguments);

		//Remember the name of the table
		[, $ls_table] = $this->detectAction($this->_name);

		$this->io = $ao_io;

		//If migration(s) with the same name already exist(s)
		$la_migrationWithSameName = glob($ls_path . '*_' . $this->_name . '.php');
		if ( ! empty($la_migrationWithSameName)) {
			//Shell the migration be overwritten?
			if ($ao_arguments->getOption('force')) {
				//If so, delete all existing migrations
				$ao_io->info(sprintf('A migration with the name `%s` already exists, it will be deleted.', $this->_name));
				foreach ($la_migrationWithSameName as $ls_migration) {
					$ao_io->info(sprintf('Deleting migration file `%s`...', $ls_migration));
					if (unlink($ls_migration)) {
						$ao_io->success(sprintf('Deleted `%s`', $ls_migration));
					}
					else {
						$ao_io->err(sprintf('An error occurred while deleting `%s`', $ls_migration));
					}
				}
			}
			else {
				/*
				 * No "--force" option provided means we will neither overwrite nor delete existing migrations
				 * but we will append a version number to the filename.
				 */
				$li_version = 2;
				while (glob($ls_path . '*_' . $this->_name . 'V' . $li_version . '.php')) {
					$li_version++;
				}

				$this->_name .= 'V' . $li_version;
			}
		}

		$lo_renderer = new TemplateRenderer($this->theme);
		$lo_renderer->set('name', $this->_name);
		$lo_renderer->set($this->templateData($ao_arguments));

		/*
		 * Manually set the remembered name of the table as a view variable, since versionizing a migration will
		 * result in a wrong table name.
		 *
		 * For example, a migration 'CreateAttributesContentsV2' would create a table named 'attributes_contents_v2'
		 */
		$lo_renderer->set('tables', [$ls_table]);

		$ls_contents = $lo_renderer->generate($this->template());

		$ls_filename = $ls_path . $this->fileName($this->_name);
		$this->createFile($ls_filename, $ls_contents, $ao_arguments, $ao_io);

		$ls_emptyFile = $ls_path . '.gitkeep';
		$this->deleteEmptyFile($ls_emptyFile, $ao_io);
	}


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
	public function buildOptionParser (ConsoleOptionParser $ao_parser): ConsoleOptionParser {
		$lo_parser = parent::buildOptionParser($ao_parser);

		$lo_parser->addOption('folder', [
			'help' => 'The folder to save the migration in.',
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
	public function getPath (Arguments $ao_args): string {
		$ls_path = APP . $this->pathFragment;
		if ($this->plugin) {
			$ls_path = $this->_pluginPath($this->plugin) . $this->pathFragment;
		}
		elseif ($ao_args->getOption('folder')) {
			$ls_path = $ao_args->getOption('folder');
			if ( ! in_array(substr($ls_path, 0, 1), ['/', DS])) {
				$ls_path = ROOT . DS . $ls_path;
			}
		}

		$ls_path = rtrim($ls_path, DS . '/') . DS;

		return str_replace('/', DS, $ls_path);
	}
}
