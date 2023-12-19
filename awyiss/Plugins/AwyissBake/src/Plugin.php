<?php declare(strict_types=1);


namespace AwyissBake;


use Cake\Console\CommandCollection;
use Cake\Core\BasePlugin;


/**
 * Plugin for AwyissBake
 */
class Plugin extends BasePlugin {
	/**
	 * @param \Cake\Console\CommandCollection $ao_commands
	 *
	 * @return \Cake\Console\CommandCollection
	 *
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function console (CommandCollection $ao_commands): CommandCollection {
		//Required because otherwise bake doesn't overwrite the "migrations migrate" command and I don't know why.
		$ao_commands->remove('migrations');
		$ao_commands->remove('migrations.migrations');
		$ao_commands->remove('migrations migrate');
		$ao_commands->remove('migrations.migrations migrate');

		$la_commands = $ao_commands->discoverPlugin($this->getName());

		return $ao_commands->addMany($la_commands);
	}
}
