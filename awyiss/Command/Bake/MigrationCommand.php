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
		$className = $this->_name;
		$namespace = Configure::read('App.namespace');
		$pluginPath = '';
		if ($this->plugin) {
			$namespace = $this->_pluginNamespace($this->plugin);
			$pluginPath = $this->plugin . '.';
		}

		$arguments = $arguments->getArguments();
		unset($arguments[0]);
		$columnParser = new ColumnParser();
		$fields = $columnParser->parseFields($arguments);
		$indexes = $columnParser->parseIndexes($arguments);
		$primaryKeys = $columnParser->parsePrimaryKey($arguments);

		$actions = $this->detectAction($className);

		if (!$actions && count($fields)) {
			/** @psalm-suppress PossiblyNullReference */
			$this->io->abort(
				'When applying fields the migration name should start with one of the following prefixes: `Create`, `Drop`, `Add`, `Remove`, `Alter`. See: https://book.cakephp.org/migrations/4/en/index.html#migrations-file-name'
			);
		}

		if (empty($actions)) {
			return [
				'plugin' => $this->plugin,
				'pluginPath' => $pluginPath,
				'namespace' => $namespace,
				'tables' => [],
				'action' => null,
				'name' => $className,
				'backend' => Configure::read('Migrations.backend', 'builtin'),
			];
		}

		if (in_array($actions[0], ['alter_table', 'add_field'], true) && !empty($primaryKeys)) {
			/** @psalm-suppress PossiblyNullReference */
			$this->io->abort('Adding a primary key to an already existing table is not supported.');
		}

		[$action, $table] = $actions;

		//If the requested action is to alter a column,
		if ($action === 'alter_field') {
			//Extract the name of the column from the migration name
			if (preg_match('/^Alter(.+?)On(.*)/', $className, $matches)) {
				//Get the field name of the column from inside the migration
				$key = array_key_first($fields);

				$fieldName = Inflector::underscore($matches[1]);
				if ($key != $fieldName) {
					/**
					 * If the column name in the migration name and the one inside the migration differ,
					 * we want to rename the column. This is something the normal CakePHP-migration does not offer.
					 *
					 * This way, we can check for the `originalName`-key of the field inside the `skeleton.twig`-file
					 * and call the `rename`-method of the migration
					 */
					$fields[ $key ]['originalName'] = $fieldName;
				}
			}
		}


		return [
			'plugin' => $this->plugin,
			'pluginPath' => $pluginPath,
			'namespace' => $namespace,
			'tables' => [$table],
			'action' => $action,
			'columns' => [
				'fields' => $fields,
				'indexes' => $indexes,
				'primaryKey' => $primaryKeys,
			],
			'name' => $className,
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
		$this->_name = $name;

		//Remember the name of the table
		[, $table] = $this->detectAction($this->_name);

		$this->io = $io;
		$this->args = $arguments;
		if ($this->isReservedKeyword($name)) {
			$prefix = $io->ask('Reserved keywords cannot be used for class names. What prefix would you like to use? Defaults to `Migration`.', 'Migration');
			$this->_name = $prefix . ucfirst($name);
		}

		$this->pathFragment .= DS . $arguments->getOption('source');

		$path = $this->getPath($arguments);

		//If migration(s) with the same name already exist(s)
		$migrationWithSameName = glob($path . '*_' . $this->_name . '.php');
		if ($migrationWithSameName) {
			//Shell the migration be overwritten?
			if ($arguments->getOption('force')) {
				//If so, delete all existing migrations
				$io->info(sprintf('A migration with the name `%s` already exists, it will be deleted.', $this->_name));
				foreach ($migrationWithSameName as $migration) {
					$io->info(sprintf('Deleting migration file `%s`...', $migration));
					if (unlink($migration)) {
						$io->success(sprintf('Deleted `%s`', $migration));
					}
					else {
						$io->err(sprintf('An error occurred while deleting `%s`', $migration));
					}
				}
			}
			else {
				/*
				 * No "--force" option provided means we will neither overwrite nor delete existing migrations
				 * but we will append a version number to the filename.
				 */
				$version = 2;
				while (glob($path . '*_' . $this->_name . 'V' . $version . '.php')) {
					$version++;
				}

				$this->_name .= 'V' . $version;
			}
		}

		$renderer = new TemplateRenderer($this->theme);
		$renderer->set('name', $this->_name);
		$renderer->set($this->templateData($arguments));

		/*
		 * Manually set the remembered name of the table as a view variable, since versionizing a migration will
		 * result in a wrong table name.
		 *
		 * For example, a migration 'CreateAttributesContentsV2' would create a table named 'attributes_contents_v2'
		 */
		$renderer->set('tables', [$table]);

		$contents = $renderer->generate($this->template());

		$filePath = $path . $this->fileName($this->_name);
		$this->createFile($filePath, $contents, $arguments, $io);

		$emptyFile = $path . '.gitkeep';
		$this->deleteEmptyFile($emptyFile, $io);
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
		$parser = parent::buildOptionParser($parser);

		$parser->addOption('folder', [
			'help' => 'The folder to save the migration in.',
		]);


		return $parser;
	}
}
