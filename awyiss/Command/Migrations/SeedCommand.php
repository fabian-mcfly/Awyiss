<?php /** @noinspection PhpInternalEntityUsedInspection */


declare(strict_types=1); // phpcs:ignore


namespace Awyiss\Command\Migrations;


use Awyiss\Migration\ManagerFactory;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Migrations\Command\SeedCommand as BaseSeedCommand;
use Migrations\Util\Util;
use Throwable;


/**
 * Custom Seed command that uses the custom ManagerFactory
 */
class SeedCommand extends BaseSeedCommand {
	/**
	 * Re-implemented 1:1 to use the custom ManagerFactory
	 *
	 * {@inheritDoc}
	 */
	protected function executeSeeds(Arguments $args, ConsoleIo $io): ?int {
		$factory = new ManagerFactory([
			'plugin' => $args->getOption('plugin'),
			'source' => $args->getOption('source'),
			'connection' => $args->getOption('connection'),
			'dry-run' => (bool)$args->getOption('dry-run'),
		]);

		$manager = $factory->createManager($io);
		$config = $manager->getConfig();

		// Get seed names from arguments
		$seeds = [];
		if ($args->hasArgument('seed')) {
			$seedArg = $args->getArgument('seed');
			if ($seedArg !== null) {
				// Split by comma to support comma-separated list
				$seedList = explode(',', $seedArg);
				foreach ($seedList as $seed) {
					$trimmed = trim($seed);
					if ($trimmed !== '') {
						$seeds[] = $trimmed;
					}
				}
			}
		}

		$versionOrder = $config->getVersionOrder();

		$fake = (bool)$args->getOption('fake');

		if ($config->isDryRun()) {
			$io->info('DRY-RUN mode enabled');
		}
		if ($fake) {
			$io->warning('performing fake seeding');
		}
		$io->verbose('<info>using connection</info> ' . $args->getOption('connection'));
		$io->verbose('<info>using paths</info> ' . $config->getMigrationPath());
		$io->verbose('<info>ordering by</info> ' . $versionOrder . ' time');

		$start = microtime(true);
		if (!$seeds) {
			// Get all available seeds and ask for confirmation
			try {
				$availableSeeds = $manager->getSeeds();
			}
			catch (Throwable $e) {
				$io->err('<error>Failed to load seeds: ' . $e->getMessage() . '</error>');
				$io->verbose($e->getTraceAsString());

				return static::CODE_ERROR;
			}

			if (!$availableSeeds) {
				$io->warning('No seeds found.');

				return self::CODE_SUCCESS;
			}

			// Skip confirmation in quiet mode
			if ($io->level() > ConsoleIo::QUIET) {
				$force = (bool)$args->getOption('force');

				// Determine which seeds will actually run
				$willRun = [];
				foreach ($availableSeeds as $seed) {
					$displayName = Util::getSeedDisplayName($seed->getName());
					if ($seed->isIdempotent()) {
						$willRun[] = $displayName . ' <info>(idempotent)</info>';
					}
					elseif ($force || !$manager->isSeedExecuted($seed)) {
						$willRun[] = $displayName;
					}
				}

				$io->out();
				if (!$willRun) {
					$io->out('All seeds have already been executed. Use --force to re-run.');
					$io->out();

					return self::CODE_SUCCESS;
				}

				$io->out('<info>The following seeds will be executed:</info>');
				foreach ($willRun as $name) {
					$io->out('  - ' . $name);
				}
				$io->out();
				if ($force) {
					$io->out('<warning>Warning:</warning> Running with --force will re-execute all seeds,');
					$io->out('potentially creating duplicate data. Ensure your seeds are idempotent.');
				}
				$io->out();

				// Ask for confirmation
				$continue = $io->askChoice('Do you want to continue?', ['y', 'n'], 'n');
				if ($continue !== 'y') {
					$io->warning('Seed operation aborted.');

					return self::CODE_SUCCESS;
				}
			}

			// run all the seed(ers)
			$manager->seed(null, (bool)$args->getOption('force'), $fake);
		}
		else {
			// run seed(ers) specified as arguments
			foreach ($seeds as $seed) {
				$manager->seed(trim($seed), (bool)$args->getOption('force'), $fake);
			}
		}
		$end = microtime(true);

		$io->comment('All Done. Took ' . sprintf('%.4fs', $end - $start));

		return self::CODE_SUCCESS;
	}
}
