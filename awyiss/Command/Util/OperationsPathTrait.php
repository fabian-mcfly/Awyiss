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
	 * @param \Symfony\Component\Console\Input\InputInterface $input Input of the current command.
	 * @param string $default Default folder to set if no folder option is found in the $input param
	 * @return string
	 * @see \Migrations\Util\UtilTrait::getOperationsPath
	 */
	protected function getOperationsPath(InputInterface $input, string $default = 'Migrations'): string {
		$ls_path = APP . 'config' . DS . $default;

		$ls_plugin = $this->getPlugin($input);
		if ($ls_plugin !== null) {
			$ls_path = Plugin::path($ls_plugin) . 'config' . DS . $default;
		}
		elseif ($input->getOption('folder')) {
			$ls_path = $input->getOption('folder');
			if (!in_array(substr($ls_path, 0, 1), ['/', DS])) {
				$ls_path = ROOT . DS . $ls_path;
			}
		}

		$ls_path = rtrim($ls_path, DS . '/');


		return str_replace('/', DS, $ls_path);
	}
}
