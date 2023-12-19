<?php declare(strict_types=1);


//TODO: move to own plugin
namespace AwyissBake\Command;


use AwyissBake\MigrationsDispatcher;
use Cake\Console\ConsoleOptionParser;
use Cake\Utility\Inflector;
use Phinx\Console\Command\Migrate;


/**
 * @inheritDoc
 *
 * Everything's here only to use \AwyissBake\MigrationsDispatcher
 */
class MigrationsCommand extends \Migrations\Command\MigrationsCommand {
	/**
	 * {@inheritDoc}
	 *
	 * Re-implemented to use `\AwyissBake\MigrationsDispatcher`
	 * This requires calling `self::_defaultName()`, instead of `parent::defaultName()`
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
	 * `self::_defaultName()` instead of `parent::defaultName()`,
	 * and `self::_getOptionParser()` instead of `parent::getOptionParser()`
	 */
	public function getOptionParser (): ConsoleOptionParser {
		if (self::_defaultName() === 'migrations') {
			return self::_getOptionParser();
		}

		$lo_parser = self::_getOptionParser();
		/** @var Migrate $lo_command */
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
	protected function getApp (): MigrationsDispatcher {
		return new MigrationsDispatcher(PHINX_VERSION);
	}


	/**
	 * 1:1 re-implementation of `\Cake\Console\BaseCommand::getOptionParser` since `getOptionParser` cannot call
	 * its parent's parent.
	 *
	 * @return ConsoleOptionParser
	 *
	 * @see \Cake\Console\BaseCommand::getOptionParser()
	 */
	protected function _getOptionParser (): ConsoleOptionParser {
		[$ls_root, $ls_name] = explode(' ', $this->name, 2);

		$lo_parser = new ConsoleOptionParser($ls_name);
		$lo_parser->setRootName($ls_root);
		$lo_parser->setDescription(static::getDescription());


		return $this->buildOptionParser($lo_parser);
	}


	/**
	 * Re-implemented `\Cake\Console\BaseCommand::defaultName` 1:1 so that `defaultName`
	 * can retreive the name of the correct class (this one here)
	 *
	 * It's required to use this one since calling parent::defaultName() will return the one from MigrationsCommand,
	 * not BaseCommand
	 *
	 * @return string
	 *
	 * @see \Cake\Console\BaseCommand::defaultName()
	 * @see \Migrations\Command\MigrationsCommand::defaultName()
	 */
	protected static function _defaultName (): string {
		$li_pos = strrpos(static::class, '\\');
		/** @psalm-suppress PossiblyFalseOperand */
		$ls_name = substr(static::class, $li_pos + 1, -7);

		return Inflector::underscore($ls_name);
	}
}
