<?php declare(strict_types=1);


namespace Awyiss\Command\Util;


use Awyiss\Utility\Inflector;
use Cake\Console\Arguments;
use Composer\Autoload\ClassLoader;
use ReflectionClass;


/**
 * Used to offer a `getPath`-method that honors the `namespace`-option
 */
trait UtilTrait {
	/**
	 * Gets the path for output. Checks the plugin property
	 * and returns the correct path.
	 * Added a logic that honors the `namespace`-option and modifies the path accordingly.
	 *
	 * @param Arguments $args Arguments instance to read the prefix option from.
	 * @return string Path to output.
	 */
	public function getPath(Arguments $args, string $basePath = APP): string {
		$ls_pathFragment = rtrim($this->pathFragment ?? '', '\\' . DS) . DS;
		$ls_path = $basePath;
		if ($this->plugin) {
			$ls_path = $this->_pluginPath($this->plugin) . $ls_pathFragment;
		}
		elseif ($args->getOption('namespace')) {
			$ls_namespace = Inflector::dasherize($args->getOption('namespace'));
			$la_namespaceFolders = $this->getAutoloadPathsForNamespace($ls_namespace);

			if (isset($la_namespaceFolders[0])) {
				$ls_path = $la_namespaceFolders[0];
			}
			else {
				$ls_path = ROOT . DS . 'src' . DS . $ls_namespace . DS;
			}

			$ls_path .= $ls_pathFragment;
		}
		elseif ($args->getOption('folder')) {
			$ls_path = rtrim($args->getOption('folder'), '\\' . DS) . DS;
			if (!in_array($ls_path[0], ['/', DS])) {
				$ls_path = ROOT . DS . $ls_path;
			}
		}
		elseif ($ls_pathFragment && $ls_pathFragment !== DS) {
			$ls_path .= $ls_pathFragment;
		}

		$ls_prefix = $this->getPrefix($args);
		if ($ls_prefix) {
			$ls_path .= $ls_prefix . DS;
		}


		return str_replace('/', DS, $ls_path);
	}


	/**
	 * @param string $namespace
	 * @return array
	 */
	protected function getAutoloadPathsForNamespace(string $namespace): array {
		$la_autoloadFunctions = spl_autoload_functions();

		foreach ($la_autoloadFunctions as $lx_function) {
			if (is_array($lx_function) && $lx_function[0] instanceof ClassLoader) {
				$lo_classLoader = $lx_function[0];

				$lo_reflection = new ReflectionClass($lo_classLoader);

				$lo_property = $lo_reflection->getProperty('prefixDirsPsr4');
				/** @noinspection PhpExpressionResultUnusedInspection */
				$lo_property->setAccessible(true);

				$la_prefixDirsPsr4 = $lo_property->getValue($lo_classLoader);

				$ls_namespace = Inflector::camelize(rtrim($namespace, '\\')) . '\\';

				if (isset($la_prefixDirsPsr4[ $ls_namespace ])) {
					return $la_prefixDirsPsr4[ $ls_namespace ];
				}
			}
		}

		return [];
	}
}
