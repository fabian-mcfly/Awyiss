<?php declare(strict_types=1);


namespace Awyiss\Command\Util;


use Cake\Core\Plugin;
use Symfony\Component\Console\Input\InputInterface;


/**
 * Replaces the OperationsPath getter for Phinx migrations.
 * No source but a folder-option
 */
trait OperationsPathTrait {
	/**
	 * Added a logic that honors the `folder`-option and modifies the path accordingly.
	 *
	 * @param \Symfony\Component\Console\Input\InputInterface $ao_input Input of the current command.
	 * @param string $as_default Default folder to set if no folder option is found in the $ao_input param
	 * @return string
	 * @see \Migrations\Util\UtilTrait::getOperationsPath
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
