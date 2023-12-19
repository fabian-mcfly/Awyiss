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
	 * {@inheritDoc}
	 *
	 * Re-implemented to use `AwyissBake\MigrationsDispatcher`
	 * This requires calling `self::_defaultName()`, instead of `parent::_defaultName()`
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
	 * {@inheritDoc}
	 *
	 * Re-implemented to use `AwyissBake\MigrationsDispatcher`,
	 * `self::_defaultName()` instead of `parent::_defaultName()`,
	 * and `self::_getOptionParser()` instead of `parent::getOptionParser()`
	 */
	public function getOptionParser (): ConsoleOptionParser {
		if (self::_defaultName() === 'migrations') {
			return self::_getOptionParser();
		}

		$lo_parser = self::_getOptionParser();
		/** @var \Phinx\Console\Command\Migrate $lo_command */
		$lo_command = new MigrationsDispatcher::$phinxCommands[ static::$commandName ]();
		$lo_parser->setDescription($lo_command->getDescription());
		$lo_definition = $lo_command->getDefinition();

		foreach ($lo_definition->getOptions() as $lo_option) {
			if ( ! empty($lo_option->getShortcut())) {
				$lo_parser->addOption($lo_option->getName(), [
					'short' => $lo_option->getShortcut(),
					'help' => $lo_option->getDescription(),
				]);
				continue;
			}

			$lo_parser->addOption($lo_option->getName());
		}

		return $lo_parser;
	}


	/**
	 * @inheritDoc
	 */
	protected function getApp (): \Migrations\MigrationsDispatcher|MigrationsDispatcher {
		return new MigrationsDispatcher(PHINX_VERSION);
	}


	/**
	 * 1:1 re-implementation of `\Cake\Console\BaseCommand::getOptionParser` since `getOptionParser` cannot call
	 * its parent's parent.
	 *
	 * @return \Cake\Console\ConsoleOptionParser
	 *
	 * @see \Cake\Console\BaseCommand::getOptionParser()
	 */
	protected function _getOptionParser (): ConsoleOptionParser {
		[$ls_root, $ls_name] = explode(' ', $this->name, 2);

		$lo_parser = new ConsoleOptionParser($ls_name);
		$lo_parser->setRootName($ls_root);
		$lo_parser->setDescription(static::getDescription());

		$lo_parser = $this->buildOptionParser($lo_parser);
		if ($lo_parser->subcommands()) {
			throw new RuntimeException(
				'You cannot add sub-commands to `Command` sub-classes. Instead make a separate command.'
			);
		}

		return $lo_parser;
	}


	/**
	 * Re-implemented `\Cake\Console\BaseCommand::defaultName` 1:1 so that `defaultName`
	 * can retreive the name of the correct class (this one here)
	 *
	 * @return string
	 *
	 * @see \Cake\Console\BaseCommand::defaultName()
	 */
	protected static function _defaultName (): string {
		$li_pos = strrpos(static::class, '\\');
		/** @psalm-suppress PossiblyFalseOperand */
		$ls_name = substr(static::class, $li_pos + 1, -7);

		return Inflector::underscore($ls_name);
	}
}
