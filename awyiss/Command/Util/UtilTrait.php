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
	 * @param string $basePath Base path for generated files.
	 * @return string Path to output.
	 */
	public function getPath(Arguments $args, string $basePath = APP): string {
		$pathFragment = rtrim($this->pathFragment ?? '', '\\' . DS) . DS;
		$path = $basePath;
		if ($this->plugin) {
			$path = $this->_pluginPath($this->plugin) . $pathFragment;
		}
		elseif ($args->getOption('namespace')) {
			$namespaceFolders = $this->getAutoloadPathsForNamespace($args->getOption('namespace'));

			if (isset($namespaceFolders[0])) {
				$path = rtrim($namespaceFolders[0], DS) . DS;
			}
			else {
				$path = ROOT . DS . Inflector::underscore($args->getOption('namespace')) . DS;
			}

			$path .= $pathFragment;
		}
		elseif ($args->getOption('folder')) {
			$path = rtrim($args->getOption('folder'), '\\' . DS) . DS;
			if (!in_array($path[0], ['/', DS])) {
				$path = ROOT . DS . $path;
			}
		}
		elseif ($pathFragment && $pathFragment !== DS) {
			$path .= $pathFragment;
		}

		$prefix = $this->getPrefix($args);
		if ($prefix) {
			$path .= $prefix . DS;
		}


		return str_replace('/', DS, $path);
	}


	/**
	 * @param string $namespace
	 * @return array
	 */
	protected function getAutoloadPathsForNamespace(string $namespace): array {
		$autoloadFunctions = spl_autoload_functions();

		$namespace = rtrim($namespace, '\\') . '\\';

		foreach ($autoloadFunctions as $function) {
			if (is_array($function) && $function[0] instanceof ClassLoader) {
				$classLoader = $function[0];

				$reflection = new ReflectionClass($classLoader);

				$property = $reflection->getProperty('prefixDirsPsr4');
				$property->setAccessible(true);

				$prefixDirsPsr4 = $property->getValue($classLoader);

				if (isset($prefixDirsPsr4[ $namespace ])) {
					return $prefixDirsPsr4[ $namespace ];
				}
			}
		}

		return [];
	}
}
