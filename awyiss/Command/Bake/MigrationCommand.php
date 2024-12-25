<?php declare(strict_types=1);


namespace Awyiss\Command\Bake;


use Awyiss\Command\Util\UtilTrait;
use Awyiss\Migration\ColumnParser;
use Awyiss\Utility\Inflector;
use Bake\Utility\TemplateRenderer;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\Core\Configure;
use Cake\Event\Event;
use Cake\Event\EventManager;
use Migrations\Command\BakeMigrationCommand as BaseBakeMigrationCommand;


/**
 * Command class for generating migration snapshot files.
 */
class MigrationCommand extends BaseBakeMigrationCommand {
	use UtilTrait;


	/**
	 * @inheritDoc
	 */
	public function template(): string {
		return 'Migration/migration';
	}


	/**
	 * Implemented nearly 1:1 to use \AwyissBake\Util\ColumnParser instead
	 * Also allow renaming fields using the alter_field-action
	 *
	 * @inheritDoc
	 * @param Arguments $arguments
	 * @return array
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function templateData(Arguments $arguments): array {
		$ls_className = $this->_name;
		$ls_namespace = Configure::read('App.namespace');
		$ls_pluginPath = '';
		if ($this->plugin) {
			$ls_namespace = $this->_pluginNamespace($this->plugin);
			$ls_pluginPath = $this->plugin . '.';
		}

		$la_arguments = $arguments->getArguments();
		unset($la_arguments[0]);
		$lo_columnParser = new ColumnParser();
		$la_fields = $lo_columnParser->parseFields($la_arguments);
		$la_indexes = $lo_columnParser->parseIndexes($la_arguments);
		$la_primaryKeys = $lo_columnParser->parsePrimaryKey($la_arguments);

		$la_actions = $this->detectAction($ls_className);

		if (!$la_actions && count($la_fields)) {
			/** @psalm-suppress PossiblyNullReference */
			$this->io->abort(
				'When applying fields the migration name should start with one of the following prefixes: `Create`, `Drop`, `Add`, `Remove`, `Alter`. See: https://book.cakephp.org/migrations/4/en/index.html#migrations-file-name'
			);
		}

		if (empty($la_actions)) {
			return [
				'plugin' => $this->plugin,
				'pluginPath' => $ls_pluginPath,
				'namespace' => $ls_namespace,
				'tables' => [],
				'action' => null,
				'name' => $ls_className,
				'backend' => Configure::read('Migrations.backend', 'builtin'),
			];
		}

		if (in_array($la_actions[0], ['alter_table', 'add_field'], true) && !empty($la_primaryKeys)) {
			/** @psalm-suppress PossiblyNullReference */
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
					 * @see  awyiss/plugins/AwyissBake/templates/bake/migrations/config/skeleton.twig:53
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
			'backend' => Configure::read('Migrations.backend', 'builtin'),
		];
	}


	/**
	 * {@inheritDoc}
	 *
	 * Re-implemented `\Migrations\Command\BakeSimpleMigrationCommand::bake()` because it's not possible to call a parent's parent.
	 *
	 * @param string $name
	 * @param Arguments $arguments
	 * @param ConsoleIo $io
	 * @return void
	 * @see \Migrations\Command\BakeSimpleMigrationCommand::bake()
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function bake(string $name, Arguments $arguments, ConsoleIo $io): void {
		EventManager::instance()->on('Bake.initialize', function (Event $event): void {
			$event->getSubject()->loadHelper('Migrations.Migration');
		});

		$this->pathFragment .= DS . $arguments->getOption('source');

		$this->_name = $name;
		$ls_path = $this->getPath($arguments);

		//Remember the name of the table
		[, $ls_table] = $this->detectAction($this->_name);

		$this->io = $io;

		//If migration(s) with the same name already exist(s)
		$la_migrationWithSameName = glob($ls_path . '*_' . $this->_name . '.php');
		if (!empty($la_migrationWithSameName)) {
			//Shell the migration be overwritten?
			if ($arguments->getOption('force')) {
				//If so, delete all existing migrations
				$io->info(sprintf('A migration with the name `%s` already exists, it will be deleted.', $this->_name));
				foreach ($la_migrationWithSameName as $ls_migration) {
					$io->info(sprintf('Deleting migration file `%s`...', $ls_migration));
					if (unlink($ls_migration)) {
						$io->success(sprintf('Deleted `%s`', $ls_migration));
					}
					else {
						$io->err(sprintf('An error occurred while deleting `%s`', $ls_migration));
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
		$lo_renderer->set($this->templateData($arguments));

		/*
		 * Manually set the remembered name of the table as a view variable, since versionizing a migration will
		 * result in a wrong table name.
		 *
		 * For example, a migration 'CreateAttributesContentsV2' would create a table named 'attributes_contents_v2'
		 */
		$lo_renderer->set('tables', [$ls_table]);

		$ls_contents = $lo_renderer->generate($this->template());

		$ls_filePath = $ls_path . $this->fileName($this->_name);
		$this->createFile($ls_filePath, $ls_contents, $arguments, $io);

		$ls_emptyFile = $ls_path . '.gitkeep';
		$this->deleteEmptyFile($ls_emptyFile, $io);
	}


	/**
	 * {@inheritDoc}
	 *
	 * Adds the `folder`-option
	 *
	 * @param ConsoleOptionParser $parser
	 * @return ConsoleOptionParser
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser {
		$lo_parser = parent::buildOptionParser($parser);

		$lo_parser->addOption('folder', [
			'help' => 'The folder to save the migration in.',
		]);


		return $lo_parser;
	}
}
