<?php declare(strict_types=1);


namespace Awyiss\Twig;


use Cake\Core\App;
use Cake\Core\Plugin;
use Twig\Error\LoaderError;


class FileLoader extends \Cake\TwigView\Twig\FileLoader {
	protected $paths = [];

	/**
	 * @param string[] $extensions Template file extensions
	 *
	 * @noinspection PhpMissingParentConstructorInspection
	 */
	public function __construct (array $aa_extensions) {
		$this->extensions = $aa_extensions;
		$this->paths = App::path('templates');
	}


	/**
	 * @param string $as_name Template name
	 *
	 * @return string
	 */
	public function findTemplate (string $as_name): string {
		if (file_exists($as_name)) {
			return $as_name;
		}

		$ls_name = $as_name;
		if (substr($ls_name, -5) === '.twig') {
			$ls_name = substr($ls_name, 0, -5);
		}

		[$ls_plugin, $ls_name] = pluginSplit($ls_name);
		$ls_name = str_replace('/', DS, $ls_name);

		if ($ls_plugin !== NULL) {
			$ls_templatePath = Plugin::templatePath($ls_plugin);
			$ls_path = $this->checkExtensions($ls_templatePath . $ls_name);
			if ($ls_path !== NULL) {
				return $ls_path;
			}

			$ls_error = "Could not find template `%s` in plugin `%s` in these paths:\n\n" . "- `%s`\n";
			throw new LoaderError(sprintf($ls_error, [$as_name, $ls_plugin, $ls_templatePath]));
		}

		$ls_namespace = \Cake\Core\Configure::read('App.namespace');
		if (substr($ls_name, 0, strlen($ls_namespace) + 2) === '@' . $ls_namespace . '/') {
			$ls_name = substr($ls_name, strlen($ls_namespace) + 2);
			$ls_path = $this->checkExtensions(ROOT . DS . APP_DIR . DS . 'Templates' . DS . $ls_name);

			if ($ls_path !== NULL) {
				return $ls_path;
			}
		}
		else {
			foreach ($this->paths as $ls_templatePath) {
				$ls_path = $this->checkExtensions($ls_templatePath . $ls_name);
				if ($ls_path !== NULL) {
					return $ls_path;
				}
			}
		}

		$ls_error = sprintf("Could not find template `%s` in these paths:\n", $as_name);
		foreach ($this->paths as $ls_templatePath) {
			$ls_error .= sprintf("- `%s`\n", $ls_templatePath);
		}
		throw new LoaderError($ls_error);
	}
}
