<?php declare(strict_types=1);


namespace AwyissBake\Command\Phinx;


use Cake\Core\Plugin as CorePlugin;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;


/**
 *
 */
class Migrate extends \Migrations\Command\Phinx\Migrate {
	/**
	 * {@inheritDoc}
	 *
	 * Adds the `folder`-option
	 *
	 * @return void
	 */
	protected function configure (): void {
		parent::configure();

		$this->addOption('--folder', NULL, InputOption::VALUE_REQUIRED, 'The folder where migrations are in');
	}


	/**
	 * {@inheritDoc}
	 *
	 * Added a logic that honors the `folder`-option and modifies the path accordingly.
	 *
	 * @param \Symfony\Component\Console\Input\InputInterface $ao_input Input of the current command.
	 * @param string $as_default Default folder to set if no source option is found in the $ao_input param
	 *
	 * @return string
	 *
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	protected function getOperationsPath (InputInterface $ao_input, $as_default = 'Migrations'): string {
		$ls_path = APP . 'config' . DS . $as_default;

		$ls_plugin = $this->getPlugin($ao_input);
		if ($ls_plugin !== NULL) {
			$ls_path = CorePlugin::path($ls_plugin) . 'config' . DS . $as_default;
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