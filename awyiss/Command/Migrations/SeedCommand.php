<?php declare(strict_types=1);


namespace Awyiss\Command\Migrations;


use Awyiss\Command\MigrationsCommand;


/**
 * This class is needed in order to provide a correct autocompletion feature
 * when using the CakePHP migrations plugin. It has no effect on the
 * migrations process.
 * Required so `bin/cake migrations seed` will accept the `--folder`-option by using
 * \AwyissBake\Command\MigrationsCommand
 */
class SeedCommand extends MigrationsCommand {
	/**
	 * Phinx command name.
	 *
	 * @var string
	 */
	protected static string $commandName = 'Seed';
}
