<?php declare(strict_types=1);


namespace AwyissBake\Command\Bake;


use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Core\App;
use Cake\Core\Configure;
use Cake\Utility\Inflector;
use InvalidArgumentException;


/**
 * Task class for creating view template files.
 */
class TemplateCommand extends \Bake\Command\TemplateCommand {
	public $la_scaffoldActions = ['overview', 'add', 'edit'];


	public function initialize(): void {
		$la_paths = App::path('templates');
		$this->path = end($la_paths);
	}


	/**
	 * Get a list of actions that can / should have view templates baked for them.
	 *
	 * @return string[] Array of action names that should be baked
	 */
	protected function _methodsToBake (): array {
		$ls_base = Configure::read('App.namespace');

		$la_methods = [];
		if (class_exists($this->controllerClass)) {
			$la_methods = array_diff(array_map('Cake\Utility\Inflector::underscore', get_class_methods($this->controllerClass)), array_map('Cake\Utility\Inflector::underscore', get_class_methods($ls_base . '\Controller\AppController')));
		}

		if (empty($la_methods)) {
			$la_methods = $this->la_scaffoldActions;
		}

		foreach ($la_methods as $i => $ls_method) {
			if ($ls_method[0] === '_') {
				unset($la_methods[ $i ]);
			}

			if ($ls_method == 'index' && strpos($this->controllerClass, $ls_base . '\Controller\Backend') === 0) {
				unset($la_methods[ $i ]);
			}
		}

		return $la_methods;
	}


	/**
	 * Get the path base for view templates.
	 *
	 * @param \Cake\Console\Arguments $aa_args The arguments
	 * @param string|null $as_container Unused.
	 *
	 * @return string
	 */
	public function getTemplatePath (Arguments $aa_args, ?string $as_container = null): string {
		$la_paths = (array)Configure::read('App.paths.templates');
		if (empty($la_paths)) {
			throw new InvalidArgumentException(
				'Could not read template paths. ' .
				'Ensure `App.paths.templates` is defined in your application configuration.'
			);
		}

		$ls_path = end($la_paths);
		if ($this->plugin) {
			$ls_path = $this->_pluginPath($this->plugin) . 'templates' . DS;
		}

		if ($as_container) {
			$ls_path .= $as_container . DS;
		}

		$ls_prefix = $this->getPrefix($aa_args);
		if ($ls_prefix) {
			$ls_path .= $ls_prefix . DS;
		}

		$ls_path = str_replace('/', DS, $ls_path);
		$ls_path .= $this->controllerName . DS;

		return $ls_path;
	}


	/**
	 * Assembles and writes bakes the view file.
	 *
	 * @param \Cake\Console\Arguments $aa_args CLI arguments
	 * @param \Cake\Console\ConsoleIo $ao_io Console io
	 * @param string $as_template Template file to use.
	 * @param string|true $ax_content Content to write.
	 * @param string $as_outputFile The output file to create. If null will use `$as_template`
	 *
	 * @return void
	 */
	public function bake (
		Arguments $aa_args, ConsoleIo $ao_io, string $as_template, $ax_content = '', ?string $as_outputFile = NULL
	): void {
		$ls_outputFile = $as_outputFile;
		if ($ls_outputFile === NULL) {
			$ls_outputFile = $as_template;
		}

		$lx_content = $ax_content;
		if ($lx_content === TRUE) {
			$lx_content = $this->getContent($aa_args, $ao_io, $as_template);
		}

		if (empty($lx_content)) {
			$ao_io->err(sprintf("<warning>No generated content for '%s.twig', not generating template.</warning>", $as_template));

			return;
		}

		$ls_path = $this->getTemplatePath($aa_args);
		$ls_filename = $ls_path . Inflector::underscore($ls_outputFile) . '.twig';

		$ao_io->out("\n" . sprintf('Baking `%s` view template file...', $ls_outputFile), 1, ConsoleIo::QUIET);
		$ao_io->createFile($ls_filename, $lx_content, $aa_args->getOption('force'));
	}
}