<?php declare(strict_types=1);


namespace Awyiss\Command\Awyiss\Trait;


use Cake\Console\ConsoleIo;
use Cake\Datasource\FactoryLocator;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;


/**
 * Shared maintenance routines used by both {@see \Awyiss\Command\Awyiss\InstallCommand} and
 * {@see \Awyiss\Command\Awyiss\UpdateCommand}: running database migrations, rebuilding the customer-namespace PageRole enum from
 * the current `page_roles` table state, and recreating the asset symlinks.
 */
trait MaintenanceTrait {
	/**
	 * @var string The customer's folder name, falls back to CUSTOM_DIR when not explicitly set.
	 */
	protected string $customerName;
	/**
	 * @var bool Whether the current run is a dry run.
	 */
	protected bool $dryRun = false;
	/**
	 * @var \Symfony\Component\Filesystem\Filesystem The filesystem.
	 */
	protected Filesystem $filesystem;
	/**
	 * @var \Cake\Console\ConsoleIo The console I/O.
	 */
	protected ConsoleIo $io;


	/**
	 * Runs the core and Queue plugin migrations.
	 *
	 * @return void
	 */
	protected function runMigrations(): void {
		$this->runCommand(['bin' . DS . 'cake', 'migrations', 'migrate', '-q', '--no-lock']);
		$this->io->success('Migrations completed.');

		$this->runCommand(['bin' . DS . 'cake', 'migrations', 'migrate', '-q', '-p', 'Queue']);
		$this->io->success('Queue migrations completed.');
	}


	/**
	 * Seeds the database.
	 *
	 * @return void
	 */
	protected function runSeeds(): void {
		$this->runCommand(['bin' . DS . 'cake', 'seeds', 'run', '-q']);
		$this->io->success('Seeding completed.');
	}


	/**
	 * Rebuilds the customer-namespace {@see \Awyiss\Model\Enum\PageRole} enum from the current state of the
	 * `page_roles` table. Mirrors {@see \Awyiss\Event\Backend\PageRolesListener::bakePageRoleEnum()}, which
	 * triggers the same bake command on save/delete of a single page role; this variant re-derives the full
	 * list unconditionally, so it is safe to run after every update regardless of which migrations actually ran.
	 *
	 * @return void
	 */
	protected function bakePageRoleEnum(): void {
		/** @var \Awyiss\Model\Table\PageRolesTable $pageRolesTable */
		$pageRolesTable = FactoryLocator::get('Table')->get('PageRoles');

		$pageRoles = [];
		/** @var \Awyiss\Model\Entity\PageRole $pageRole */
		foreach ($pageRolesTable->find() as $pageRole) {
			$pageRoles[] = $pageRole->identifier . ':' . $pageRole->id;
		}

		$this->runCommand([
			'bin' . DS . 'cake',
			'bake',
			'enum',
			'PageRole',
			implode(',', $pageRoles),
			'-i',
			'--namespace',
			CUSTOM_NAMESPACE,
			'--is-pagerole',
			'--force',
		]);

		$this->io->success('PageRole enum rebuilt.');
	}


	/**
	 * Creates the asset symlinks (own assets, TinyMCE, customer assets). Re-running this after a
	 * `composer update` recreates any symlink that a vendor package update replaced with a plain
	 * directory, e.g. TinyMCE re-extracting into `vendor/tinymce/tinymce`.
	 *
	 * @return bool|null Null when skipped (dry run).
	 */
	protected function createAssetSymlinks(): ?bool {
		if ($this->dryRun) {
			$this->io->success('Skipping symlink creation in dry run mode.');

			return null;
		}

		if (!isset($this->customerName)) {
			if (!defined('CUSTOM_DIR')) {
				$this->io->abort('CUSTOM_DIR is not defined. Cannot create symlinks.');
			}
			else {
				$this->customerName = CUSTOM_DIR;
			}
		}

		if (strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN') {
			try {
				$this->removeExistingAssetSymlinks();

				$this->filesystem->symlink(APP . 'assets', WWW_ROOT . 'awyiss' . DS . 'assets');
				$this->filesystem->symlink(
					ROOT . DS . 'vendor' . DS . 'tinymce' . DS . 'tinymce',
					WWW_ROOT . 'awyiss' . DS . 'assets' . DS . 'js' . DS . 'TinyMCE' . DS . 'tinymce'
				);
				$this->filesystem->symlink(ROOT . DS . $this->customerName . DS . 'assets', WWW_ROOT . 'assets');
			}
			catch (IOExceptionInterface) {
				$this->io->error('Failed to create symlinks.', 0);

				$this->io->out('Please create the symlinks manually:');

				$this->io->comment('ln -s ' . APP . 'assets ' . WWW_ROOT . 'awyiss' . DS . 'assets');
				$this->io->comment(
					'ln -s'
					. ' '
					. ROOT . DS . 'vendor' . DS . 'tinymce' . DS . 'tinymce'
					. ' '
					. WWW_ROOT . 'awyiss' . DS . 'assets' . DS . 'js' . DS . 'TinyMCE' . DS . 'tinymce'
				);
				$this->io->comment('ln -s ' . ROOT . DS . $this->customerName . DS . 'assets ' . WWW_ROOT . 'assets');

				return false;
			}

			return true;
		}

		$this->io->info('Trying to create symlinks using the mklink command...');

		$commands = [
			[
				'cmd',
				'/c',
				'mklink',
				'/D',
				WWW_ROOT . 'awyiss' . DS . 'assets',
				APP . 'assets',
			],
			[
				'cmd',
				'/c',
				'mklink',
				'/D',
				WWW_ROOT . 'awyiss' . DS . 'assets' . DS . 'js' . DS . 'TinyMCE' . DS . 'tinymce',
				ROOT . DS . 'vendor' . DS . 'tinymce' . DS . 'tinymce',
			],
			[
				'cmd',
				'/c',
				'mklink',
				'/D',
				WWW_ROOT . 'assets',
				ROOT . DS . $this->customerName . DS . 'assets',
			],
		];

		try {
			foreach ($commands as $command) {
				$this->runCommand($command);
			}
		}
		catch (ProcessFailedException) {
			$this->io->error('Failed to create symlinks.', 0);

			$this->io->out('Please create the symlinks manually:');

			$this->io->comment('mklink /D ' . WWW_ROOT . 'awyiss\assets ' . APP . '\assets');
			$this->io->comment('mklink /D ' . WWW_ROOT . 'awyiss\assets\js\TinyMCE\tinymce ' . ROOT . DS . 'vendor\tinymce\tinymce');
			$this->io->comment('mklink /D ' . WWW_ROOT . 'assets ' . ROOT . DS . $this->customerName . DS . 'assets');

			return false;
		}

		return true;
	}


	/**
	 * Removes existing asset symlinks (or now-plain directories left behind by a vendor package update, e.g. a
	 * TinyMCE re-extract) before recreating them, so a stale directory does not make the symlink() call fail.
	 *
	 * @return void
	 */
	protected function removeExistingAssetSymlinks(): void {
		foreach (
			[
				WWW_ROOT . 'awyiss' . DS . 'assets',
				WWW_ROOT . 'awyiss' . DS . 'assets' . DS . 'js' . DS . 'TinyMCE' . DS . 'tinymce',
				WWW_ROOT . 'assets',
			] as $path
		) {
			if (is_link($path) || is_dir($path)) {
				$this->filesystem->remove($path);
			}
		}
	}


	/**
	 * Executes a command using the Symfony Process component. If the command fails, the person is asked
	 * whether to continue or abort.
	 *
	 * @param array $command An array of strings representing the command to be executed.
	 * @return void
	 * @throws \Symfony\Component\Process\Exception\ProcessFailedException If the command fails.
	 */
	protected function runCommand(array $command): void {
		$process = new Process($command);

		try {
			$process->mustRun();
		}
		catch (ProcessFailedException $ex) {
			$this->io->error('The command failed. Error: ' . $ex->getMessage());

			if ($this->io->askChoice('Do you want to continue?', ['y', 'n'], 'n') === 'n') {
				$this->io->abort('Aborted.');
			}
		}
	}
}
