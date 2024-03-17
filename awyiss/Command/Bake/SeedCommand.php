<?php declare(strict_types=1);


namespace Awyiss\Command\Bake;


use Awyiss\Command\Util\UtilTrait;
use Cake\Console\Arguments;
use Cake\Console\ConsoleOptionParser;
use Migrations\Command\BakeSeedCommand as BaseBakeSeedCommand;


/**
 * Task class for creating and updating controller files.
 */
class SeedCommand extends BaseBakeSeedCommand {
	use UtilTrait;


	/**
	 * {@inheritDoc}
	 *
	 * Adds the `folder`-option
	 *
	 * @param ConsoleOptionParser $ao_parser
	 * @return ConsoleOptionParser
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function buildOptionParser(ConsoleOptionParser $ao_parser): ConsoleOptionParser {
		$lo_parser = parent::buildOptionParser($ao_parser);

		$lo_parser->addOption('folder', [
			'help' => 'The folder to save the migration in.',
		]);

		$lo_parser->addOption('truncate', [
			'boolean' => true,
			'help' => 'Add the truncate command in the seed.',
			'short' => 't',
		]);


		return $lo_parser;
	}


	/**
	 * @inheritDoc
	 */
	public function template(): string {
		return 'Migration/Seed/seed';
	}


	/**
	 * Get template data.
	 *
	 * @param \Cake\Console\Arguments $arguments The arguments for the command
	 * @return array
	 * @phpstan-return array<string, mixed>
	 */
	public function templateData(Arguments $arguments): array {
		return parent::templateData($arguments) + ['truncate' => $arguments->getOption('truncate')];
	}
}
