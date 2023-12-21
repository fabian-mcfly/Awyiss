<?php declare(strict_types=1);


namespace AwyissBake\Util;


use Cake\Console\Arguments;
use Cake\Utility\Inflector;


/**
 * Used to offer the `getPath`-method that honors the `namespace`-option
 * TODO: include 'folder'-option
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
	public function getPath(Arguments $ao_args): string {
		$ls_path = APP . $this->pathFragment;
		if ($this->plugin) {
			$ls_path = $this->_pluginPath($this->plugin) . 'src/' . $this->pathFragment;
		}
		elseif ($ao_args->getOption('namespace')) {
			$ls_namespace = Inflector::dasherize($ao_args->getOption('namespace'));
			$ls_path = ROOT . DS . $ls_namespace . DS . $this->pathFragment;
		}

		$ls_prefix = $this->getPrefix($ao_args);
		if ($ls_prefix) {
			$ls_path .= $ls_prefix . DIRECTORY_SEPARATOR;
		}


		return str_replace('/', DIRECTORY_SEPARATOR, $ls_path);
	}
}
