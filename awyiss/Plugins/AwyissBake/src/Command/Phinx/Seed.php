<?php declare(strict_types=1);


namespace AwyissBake\Command\Phinx;


use Cake\Core\Plugin;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;


/**
 *
 */
class Seed extends \Migrations\Command\Phinx\Seed {
	/**
	 * {@inheritDoc}
	 *
	 * Adds the `folder`-option
	 *
	 * @return void
	 */
	protected function configure(): void {
		$this->setName('seed')->setDescription('Seed the database with data')->setHelp('runs all available migrations, optionally up to a specific version')->addOption(
			'--seed',
			NULL,
			InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
			'What is the name of the seeder?'
		)->addOption('--plugin', '-p', InputOption::VALUE_REQUIRED, 'The plugin containing the migrations')->addOption(
			'--connection',
			'-c',
			InputOption::VALUE_REQUIRED,
			'The datasource connection to use'
		)
		//->addOption('--source', '-s', InputOption::VALUE_REQUIRED, 'The folder where migrations are in')
		->addOption('--folder', NULL, InputOption::VALUE_REQUIRED, 'The folder where seeds are in');
	}


	/**
	 * {@inheritDoc}
	 *
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


	/**
	 * {@inheritDoc}
	 *
	 * Added a logic that honors the `folder`-option and modifies the path accordingly.
	 *
	 * @param InputInterface $ao_input Input of the current command.
	 * @param string $as_default Default folder to set if no source option is found in the $ao_input param
	 *
	 * @return string
	 *
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	protected function getOperationsPath(InputInterface $ao_input, string $as_default = 'Migrations'): string {
		$ls_path = APP . 'config' . DS . $as_default;

		$ls_plugin = $this->getPlugin($ao_input);
		if ($ls_plugin !== NULL) {
			$ls_path = Plugin::path($ls_plugin) . 'config' . DS . $as_default;
		}
		elseif ($ao_input->getOption('folder')) {
			$ls_path = $ao_input->getOption('folder');
			if (!in_array(substr($ls_path, 0, 1), ['/', DS])) {
				$ls_path = ROOT . DS . $ls_path;
			}
		}

		$ls_path = rtrim($ls_path, DS . '/');


		return str_replace('/', DS, $ls_path);
	}
}
