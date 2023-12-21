<?php declare(strict_types=1);


namespace AwyissBake\Command\Phinx;


use Cake\Core\Plugin;
use Migrations\Command\Phinx\Migrate as BaseMigrate;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;


/**
 * @inheritDoc
 */
class Migrate extends BaseMigrate {
	/**
	 * {@inheritDoc}
	 *
	 * Adds the `folder`-option
	 *
	 * @return void
	 */
	protected function configure(): void {
		$this->setName('migrate')->setDescription('Migrate the database')->setHelp('runs all available migrations, optionally up to a specific version')->addOption(
			'--target',
			'-t',
			InputOption::VALUE_REQUIRED,
			'The version number to migrate to'
		)->addOption('--date', '-d', InputOption::VALUE_REQUIRED, 'The date to migrate to')->addOption(
			'--dry-run',
			'-x',
			InputOption::VALUE_NONE,
			'Dump queries to standard output instead of executing it'
		)->addOption(
			'--plugin',
			'-p',
			InputOption::VALUE_REQUIRED,
			'The plugin containing the migrations'
		)->addOption('--connection', '-c', InputOption::VALUE_REQUIRED, 'The datasource connection to use')
		//->addOption('--source', '-s', InputOption::VALUE_REQUIRED, 'The folder where migrations are in')
		->addOption('--folder', null, InputOption::VALUE_REQUIRED, 'The folder where migrations are in')->addOption(
			'--fake',
			null,
			InputOption::VALUE_NONE,
			"Mark any migrations selected as run, but don't actually execute them"
		)->addOption(
			'--no-lock',
			null,
			InputOption::VALUE_NONE,
			'If present, no lock file will be generated after migrating'
		);
	}


	/**
	 * {@inheritDoc}
	 *
	 * Added a logic that honors the `folder`-option and modifies the path accordingly.
	 *
	 * @param InputInterface $ao_input Input of the current command.
	 * @param string $as_default Default folder to set if no folder option is found in the $ao_input param
	 * @return string
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	protected function getOperationsPath(InputInterface $ao_input, string $as_default = 'Migrations'): string {
		$ls_path = APP . 'config' . DS . $as_default;

		$ls_plugin = $this->getPlugin($ao_input);
		if ($ls_plugin !== null) {
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
