<?php declare(strict_types=1);


namespace Customer\Command;


use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;


/**
 * TestCommand
 */
class TestCommand extends Command {
	/**
	 * @param \Cake\Console\Arguments $args
	 * @param \Cake\Console\ConsoleIo $io
	 * @return void
	 */
	public function execute(Arguments $args, ConsoleIo $io): int {
		$io->error('Not implemented yet');

		return static::CODE_SUCCESS;
	}


	/**
	 * @param \Cake\Console\ConsoleOptionParser $parser
	 * @return \Cake\Console\ConsoleOptionParser
	 */
	protected function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser {
		$parser->addOption('shouldTest', [
			'boolean' => true,
			'help' => 'Testoption. Does nothing',
		]);

		return $parser;
	}
}
