<?php declare(strict_types=1);


//TODO: rename
namespace AwyissBake;


use Cake\Console\CommandCollection;
use Cake\Core\BasePlugin;


/**
 * Plugin for AwyissBake
 */
class AwyissBakePlugin extends BasePlugin {
	/**
	 * @param CommandCollection $ao_commands
	 *
	 * @return CommandCollection
	 *
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function console(CommandCollection $ao_commands): CommandCollection {
		//Required because otherwise bake doesn't overwrite the "migrations migrate" command.
		$ao_commands->remove('migrations');
		$ao_commands->remove('migrations migrate');
		$ao_commands->remove('migrations seed');
		$ao_commands->remove('migrations.migrations');
		$ao_commands->remove('migrations.migrations migrate');
		$ao_commands->remove('migrations.migrations seed');
		$ao_commands->remove('i18n extract');

		$la_commands = $ao_commands->discoverPlugin($this->getName());


		return $ao_commands->addMany($la_commands);
	}
}
