<?php declare(strict_types=1);


namespace Awyiss\Command\Migrations;


use Awyiss\Migration\ManagerFactory;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use DateTime;
use LogicException;
use Migrations\Command\DumpCommand;
use Migrations\Command\MigrateCommand as BaseMigrateCommand;
use Throwable;


/**
 * Custom Migrate command that uses the custom ManagerFactory
 */
class MigrateCommand extends BaseMigrateCommand {
	/**
	 * Re-implemented 1:1 to use the custom ManagerFactory
	 *
	 * {@inheritDoc}
	 */
	protected function executeMigrations(Arguments $args, ConsoleIo $io): ?int {
		$version = $args->getOption('target') !== null ? (int)$args->getOption('target') : null;
		$date = $args->getOption('date');
		$fake = (bool)$args->getOption('fake');

		$count = $args->getOption('count') !== null ? (int)$args->getOption('count') : null;
		if ($count !== null && $count < 1) {
			throw new LogicException('Count must be > 0.');
		}
		if ($count && $date) {
			throw new LogicException('Can only use one of `--count` or `--date` options at a time.');
		}
		if ($version && $date) {
			throw new LogicException('Can only use one of `--version` or `--date` options at a time.');
		}

		$factory = new ManagerFactory([
			'plugin' => $args->getOption('plugin'),
			'source' => $args->getOption('source'),
			'connection' => $args->getOption('connection'),
			'dry-run' => (bool)$args->getOption('dry-run'),
		]);

		$manager = $factory->createManager($io);
		$config = $manager->getConfig();

		$versionOrder = $config->getVersionOrder();

		if ($config->isDryRun()) {
			$io->info('DRY-RUN mode enabled');
		}
		$io->verbose('<info>using connection</info> ' . $args->getOption('connection'));
		$io->verbose('<info>using paths</info> ' . $config->getMigrationPath());
		$io->verbose('<info>ordering by</info> ' . $versionOrder . ' time');

		if ($fake) {
			$io->out('<warning>warning</warning> performing fake migrations');
		}

		try {
			// run the migrations
			$start = microtime(true);
			if ($date !== null) {
				$manager->migrateToDateTime(new DateTime((string)$date), $fake);
			}
			else {
				$manager->migrate($version, $fake, $count);
			}
			$end = microtime(true);
		}
		catch (Throwable $e) {
			$io->err('<error>' . $e->getMessage() . '</error>');
			$io->verbose($e->getTraceAsString());

			return self::CODE_ERROR;
		}

		$io->comment('All Done. Took ' . sprintf('%.4fs', $end - $start));
		$io->out('');

		$exitCode = self::CODE_SUCCESS;

		// Run dump command to generate lock file
		if (!$args->getOption('no-lock') && !$args->getOption('dry-run')) {
			$io->verbose('');
			$io->verbose('Dumping the current schema of the database to be used while baking a diff');
			$io->verbose('');

			$newArgs = DumpCommand::extractArgs($args);
			$exitCode = $this->executeCommand(DumpCommand::class, $newArgs, $io);
		}

		return $exitCode;
	}
}
