<?php declare(strict_types=1);


namespace AwyissBake\Command;


use AwyissBake\MigrationsDispatcher;
use Cake\Console\ConsoleOptionParser;
use Cake\Utility\Inflector;
use RuntimeException;


/**
 * @inheritDoc
 */
class MigrationsCommand extends \Migrations\Command\MigrationsCommand {
	/**
	 * @inheritDoc
	 */
	public static function defaultName (): string {

		if (self::_defaultName() === 'migrations') {
			return 'migrations';
		}
		$lo_command = new MigrationsDispatcher::$phinxCommands[ static::$commandName ]();
		$ls_name = $lo_command->getName();

		return 'migrations ' . $ls_name;
	}


	/**
	 * @inheritDoc
	 */
	public function getOptionParser (): ConsoleOptionParser {
		if (self::_defaultName() === 'migrations') {
			return self::_getOptionParser();
		}
		$parser = self::_getOptionParser();
		$command = new MigrationsDispatcher::$phinxCommands[ static::$commandName ]();
		$parser->setDescription($command->getDescription());
		$definition = $command->getDefinition();

		foreach ($definition->getOptions() as $key => $option) {
			if ( ! empty($option->getShortcut())) {
				$parser->addOption($option->getName(), [
					'short' => $option->getShortcut(),
					'help' => $option->getDescription(),
				]);
				continue;
			}
			$parser->addOption($option->getName());
		}

		return $parser;
	}


	/**
	 * @inheritDoc
	 */
	protected function getApp() {
		return new MigrationsDispatcher(PHINX_VERSION);
	}


	protected function _getOptionParser (): ConsoleOptionParser {
		[$root, $name] = explode(' ', $this->name, 2);
		$parser = new ConsoleOptionParser($name);
		$parser->setRootName($root);
		$parser->setDescription(static::getDescription());

		$parser = $this->buildOptionParser($parser);
		if ($parser->subcommands()) {
			throw new RuntimeException(
				'You cannot add sub-commands to `Command` sub-classes. Instead make a separate command.'
			);
		}

		return $parser;
	}


	protected static function _defaultName (): string {
		$li_pos = strrpos(static::class, '\\');
		/** @psalm-suppress PossiblyFalseOperand */
		$ls_name = substr(static::class, $li_pos + 1, -7);

		return Inflector::underscore($ls_name);
	}
}
