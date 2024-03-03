<?php declare(strict_types=1);


namespace Awyiss\Command\Phinx;


use Awyiss\Command\Util\OperationsPathTrait;
use Migrations\Command\Phinx\Seed as BaseSeed;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;


/**
 * @inheritDoc
 */
class Seed extends BaseSeed {
	use OperationsPathTrait;


	/**
	 * Adds the `folder`-option
	 * Removes the `source`-option
	 *
	 * @inheritDoc
	 * @return void
	 */
	protected function configure(): void {
		$this->setName('seed')
			->setDescription('Seed the database with data')
			->setHelp('runs all available migrations, optionally up to a specific version')
			->addOption(
				'--seed',
				null,
				InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
				'What is the name of the seeder?'
			)
			->addOption('--plugin', '-p', InputOption::VALUE_REQUIRED, 'The plugin containing the migrations')
			->addOption(
				'--connection',
				'-c',
				InputOption::VALUE_REQUIRED,
				'The datasource connection to use'
			)
			//->addOption('--source', '-s', InputOption::VALUE_REQUIRED, 'The folder where migrations are in')
			->addOption('--folder', null, InputOption::VALUE_REQUIRED, 'The folder where seeds are in');
	}


	/**
	 * @inheritDoc
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	protected function execute(InputInterface $ao_input, OutputInterface $ao_output): int {
		$lx_seed = $ao_input->getOption('seed');
		if (!empty($lx_seed)) {
			if (!is_array($lx_seed)) {
				$lx_seed = [$lx_seed];
			}

			foreach ($lx_seed as &$ls_seed) {
				if (!str_ends_with($ls_seed, 'Seed')) {
					$ls_seed .= 'Seed';
				}
			}

			$ao_input->setOption('seed', $lx_seed);
		}


		return parent::execute($ao_input, $ao_output);
	}
}
