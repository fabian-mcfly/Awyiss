<?php declare(strict_types=1);


namespace Awyiss\Command\Awyiss;


use Awyiss\Command\Awyiss\Trait\MaintenanceTrait;
use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Symfony\Component\Filesystem\Filesystem;


/**
 * Class UpdateCommand
 * This class handles the maintenance steps required after a `composer update`: running database migrations, rebuilding the
 * customer-namespace PageRole enum from the current `page_roles` table state, and recreating the asset symlinks that a vendor package
 * update (e.g. TinyMCE) can break.
 * Intended to be wired into Composer's `post-update-cmd` script, or run manually as `bin/cake awyiss update`.
 */
class UpdateCommand extends Command {
	use MaintenanceTrait;


	/**
	 * Main execution method.
	 *
	 * If no custom directory exists, this command will not run as it has been called by composer before a project has been created.
	 * In that case, it will output a message and return a success code.
	 *
	 * @param \Cake\Console\Arguments $args
	 * @param \Cake\Console\ConsoleIo $io
	 * @return int
	 */
	public function execute(Arguments $args, ConsoleIo $io): int {
		$this->filesystem = new Filesystem();
		$this->io = $io;
		$this->dryRun = (bool)$args->getOption('dry-run');

		if (!is_dir(CUSTOM_DIR)) {
			$this->io->out('No custom directory found, skipping update steps.');

			return static::CODE_SUCCESS;
		}

		$this->io->out('Starting Awyiss update process...');

		if (!$args->getOption('skip-migrations')) {
			$this->runMigrationsStep();
		}

		if (!$args->getOption('skip-bake')) {
			$this->runBakeStep();
		}

		if (!$args->getOption('skip-symlinks')) {
			// Reuses the same symlink logic as `bin/cake awyiss install --rebuild-symlinks`.
			$this->createAssetSymlinks();
		}

		$this->io->success('Awyiss Update completed.');


		return static::CODE_SUCCESS;
	}


	/**
	 * @inheritDoc
	 */
	protected function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser {
		$parser = parent::buildOptionParser($parser);

		$parser->addOption('dry-run', [
			'help' => 'Perform a dry run of the update process.'
				. ' This will output the commands that would be executed without actually running them.',
			'boolean' => true,
			'short' => 'x',
		]);

		$parser->addOption('skip-migrations', [
			'help' => 'Skip running the database migrations.',
			'boolean' => true,
		]);

		$parser->addOption('skip-bake', [
			'help' => 'Skip rebuilding the PageRole enum.',
			'boolean' => true,
		]);

		$parser->addOption('skip-symlinks', [
			'help' => 'Skip recreating the asset symlinks.',
			'boolean' => true,
		]);

		return $parser;
	}


	/**
	 * Runs the database migrations, honoring dry-run mode.
	 *
	 * @return void
	 */
	protected function runMigrationsStep(): void {
		if ($this->dryRun) {
			$this->io->out('Skipping database migrations in dry run mode.');

			return;
		}

		$this->runMigrations();
	}


	/**
	 * Rebuilds the PageRole enum, honoring dry-run mode.
	 *
	 * @return void
	 */
	protected function runBakeStep(): void {
		if ($this->dryRun) {
			$this->io->out('Skipping PageRole enum rebuild in dry run mode.');

			return;
		}

		$this->bakePageRoleEnum();
	}
}
