<?php declare(strict_types=1);


namespace AwyissBake\Command\Bake;


use Bake\Utility\TemplateRenderer;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Event\Event;
use Cake\Event\EventManager;


/**
 * Command class for generating migration snapshot files.
 */
class BakeMigrationCommand extends \Migrations\Command\BakeMigrationCommand {
	public function template (): string {
		return 'AwyissBake.migrations/config/skeleton';
	}


	public function templateData (Arguments $ao_arguments): array {
		$la_templateData = parent::templateData($ao_arguments);

		if ($la_templateData['action'] === 'alter_field') {
			if (preg_match('/^Alter(.+?)On(.*)/', $la_templateData['name'], $la_matches)) {
				$ls_fieldName = \Cake\Utility\Inflector::underscore($la_matches[1]);
				$ls_key = array_key_first($la_templateData['columns']['fields']);
				if ($ls_key != $ls_fieldName) {
					$la_templateData['columns']['fields'][ $ls_key ]['originalName'] = $ls_fieldName;
				}
			}
		}

		return $la_templateData;
	}


	/**
	 * @inheritDoc
	 */
	public function bake (string $as_name, Arguments $ao_arguments, ConsoleIo $ao_io): void {
		EventManager::instance()->on('Bake.initialize', function (Event $event) {
			$event->getSubject()->loadHelper('Migrations.Migration');
		});


		$this->_name = $as_name;
		$ls_path = $this->getPath($ao_arguments);

		[, $ls_table] = $this->detectAction($this->_name);

		$this->io = $ao_io;
		$la_migrationWithSameName = glob($ls_path . '*_' . $this->_name . '.php');
		if ( ! empty($la_migrationWithSameName)) {
			$lb_force = $ao_arguments->getOption('force');
			if ($lb_force) {
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
		$lo_renderer->set('tables', [$ls_table]);
		$ls_contents = $lo_renderer->generate($this->template());

		$ls_filename = $ls_path . $this->fileName($this->_name);
		$this->createFile($ls_filename, $ls_contents, $ao_arguments, $ao_io);

		$ls_emptyFile = $ls_path . '.gitkeep';
		$this->deleteEmptyFile($ls_emptyFile, $ao_io);
	}


	/**
	 * @inheritDoc
	 *
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function buildOptionParser (\Cake\Console\ConsoleOptionParser $ao_parser): \Cake\Console\ConsoleOptionParser {
		$lo_parser = parent::buildOptionParser($ao_parser);

		$lo_parser->addOption('folder', [
			'help' => 'The folder to save the migration in.',
		]);

		return $lo_parser;
	}


	/**
	 * @inheritDoc
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