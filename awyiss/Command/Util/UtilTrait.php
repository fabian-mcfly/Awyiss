<?php declare(strict_types=1);


namespace Awyiss\Command\Util;


use Cake\Console\Arguments;
use Cake\Utility\Inflector;


/**
 * Used to offer a `getPath`-method that honors the `namespace`-option
 */
trait UtilTrait {
	/**
	 * Gets the path for output. Checks the plugin property
	 * and returns the correct path.
	 *
	 * Added a logic that honors the `namespace`-option and modifies the path accordingly.
	 *
	 * @param Arguments $ao_args Arguments instance to read the prefix option from.
	 * @return string Path to output.
	 */
	public function getPath(Arguments $ao_args, string $as_basePath = APP): string {
		$ls_pathFragment = rtrim($this->pathFragment ?? '', '\\' . DS) . DS;
		$ls_path = $as_basePath;
		if ($this->plugin) {
			$ls_path = $this->_pluginPath($this->plugin) . $ls_pathFragment;
		}
		elseif ($ao_args->getOption('namespace')) {
			$ls_namespace = Inflector::dasherize($ao_args->getOption('namespace'));
			$ls_path = ROOT . DS . $ls_namespace . DS . $ls_pathFragment;
		}
		elseif ($ao_args->getOption('folder')) {
			$ls_path = rtrim($ao_args->getOption('folder'), '\\' . DS) . DS;
			if (!in_array($ls_path[0], ['/', DS])) {
				$ls_path = ROOT . DS . $ls_path;
			}
		}

		$ls_prefix = $this->getPrefix($ao_args);
		if ($ls_prefix) {
			$ls_path .= $ls_prefix . DS;
		}


		return str_replace('/', DS, $ls_path);
	}
}
