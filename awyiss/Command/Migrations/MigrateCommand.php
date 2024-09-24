<?php declare(strict_types=1);


namespace Awyiss\Command\Migrations;


use Awyiss\Command\MigrationsCommand;


/**
 * Required so `bin/cake migrations migrate` will accept the `--folder`-option by using
 * \AwyissBake\Command\MigrationsCommand
 *
 * @inheritDoc
 */
class MigrateCommand extends MigrationsCommand {
	/**
	 * Phinx command name.
	 *
	 * @var string
	 */
	protected static string $commandName = 'Migrate';
}
