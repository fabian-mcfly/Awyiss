<?php declare(strict_types=1);


namespace Awyiss\Command\Twig;


use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Symfony\Component\Process\Process;


/**
 * Removes twig folders, depending on the provided type
 */
class ClearCacheCommand extends Command {
	/**
	 * @inheritDoc
	 */
	public static function getDescription(): string {
		return 'Removes cached compiled twig files';
	}


	/**
	 * @inheritDoc
	 * @param \Cake\Console\Arguments $args
	 * @param \Cake\Console\ConsoleIo $io
	 * @return int
	 */
	public function execute(Arguments $args, ConsoleIo $io): int {
		$ls_folderPath = CACHE . 'twig_view' . DS;

		$io->out('Emptying twig cache... ', 0);

		$lo_process = new Process([
			'rm',
			'-r',
			$ls_folderPath,
		]);
		$lo_process->run();

		if ($lo_process->isSuccessful()) {
			$io->success('Succeeded');

			return static::CODE_SUCCESS;
		}

		$io->error('Failed');

		return static::CODE_ERROR;
	}
}
