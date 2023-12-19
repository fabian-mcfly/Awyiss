<?php declare(strict_types=1);


namespace AwyissBake\Command;


/**
 * This class is needed in order to provide a correct autocompletion feature
 * when using the CakePHP migrations plugin. It has no effect on the
 * migrations process.
 *
 * Required so `bin/cake migrations migrate` will accept the `--folder`-option by using
 * \AwyissBake\Command\MigrationsCommand
 */
class MigrationsMigrateCommand extends MigrationsCommand {
	/**
	 * Phinx command name.
	 *
	 * @var string
	 */
	protected static string $commandName = 'Migrate';
}
