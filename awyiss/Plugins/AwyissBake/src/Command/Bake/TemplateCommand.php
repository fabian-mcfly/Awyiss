<?php declare(strict_types=1);


namespace AwyissBake\Command\Bake;


use Awyiss\Core\App;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Core\Configure;
use Cake\Utility\Inflector;
use InvalidArgumentException;


/**
 * Task class for creating view template files.
 */
class TemplateCommand extends \Bake\Command\TemplateCommand {
	/**
	 * @inheritDoc
	 *
	 * @var string[]
	 */
	public $scaffoldActions = ['overview', 'add', 'edit'];


	/**
	 * @inheritDoc
	 */
	public function initialize(): void {
		//$la_paths = App::path('templates');
		//$this->path = 'FOOBAR';
	}


	/**
	 * Get a list of actions that can / should have view templates baked for them.
	 *
	 * @return string[] Array of action names that should be baked
	 */
	/*protected function _methodsToBake (): array {
		$ls_base = Configure::read('App.namespace');

		$la_methods = [];
		if (class_exists($this->controllerClass)) {
			$la_methods = array_diff(
				array_map('Cake\Utility\Inflector::underscore', get_class_methods($this->controllerClass)),
				array_map('Cake\Utility\Inflector::underscore', get_class_methods($ls_base . '\Controller\AppController'))
			);
		}

		if (empty($la_methods)) {
			$la_methods = $this->scaffoldActions;
		}

		foreach ($la_methods as $i => $ls_method) {
			if ($ls_method[0] === '_') {
				unset($la_methods[ $i ]);
			}
		}

		return $la_methods;
	}*/


	/**
	 * @inheritDoc
	 *
	 *
	 *
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function getTemplatePath (Arguments $ao_args, ?string $as_container = null): string {
		$la_paths = (array)Configure::read('App.paths.templates');
		if (empty($la_paths)) {
			throw new InvalidArgumentException(
				'Could not read template paths. ' .
				'Ensure `App.paths.templates` is defined in your application configuration.'
			);
		}

		$ls_path = reset($la_paths);

		$lb_pathFound = FALSE;
		if ($ls_folder = $ao_args->getOption('folder')) {
			if (isset($la_paths[ $ls_folder ])) {
				$ls_path = $la_paths[ $ls_folder ];
				$lb_pathFound = TRUE;
			}
		}

		if ($this->plugin) {
			$ls_path = $this->_pluginPath($this->plugin) . 'templates' . DS;
		}

		if ($as_container) {
			$ls_path .= $as_container . DS;
		}

		if (!$lb_pathFound && $ls_folder) {
			$ls_path = $ls_folder . DS;
		}

		$ls_prefix = $this->getPrefix($ao_args);
		if ($ls_prefix) {
			$ls_path .= $ls_prefix . DS;
		}

		$ls_path = str_replace('/', DS, $ls_path);
		$ls_path .= $this->controllerName . DS;

		return $ls_path;
	}


	/**
	 * @inheritDoc
	 *
	 * This variation is required so the bake command outputs a .twig template file
	 * instead of the default .php extension
	 *
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function bake (
		Arguments $ao_args, ConsoleIo $ao_io, string $as_template, $ax_content = '', ?string $as_outputFile = NULL
	): void {
		$ls_outputFile = $as_outputFile;
		if ($ls_outputFile === NULL) {
			$ls_outputFile = $as_template;
		}

		$lx_content = $ax_content;
		if ($lx_content === TRUE) {
			$lx_content = $this->getContent($ao_args, $ao_io, $as_template);
		}

		if (empty($lx_content)) {
			$ao_io->err(sprintf("<warning>No generated content for '%s.twig', not generating template.</warning>", $as_template));

			return;
		}

		$ls_path = $this->getTemplatePath($ao_args);
		$ls_filename = $ls_path . Inflector::underscore($ls_outputFile) . '.twig';

		$ao_io->out("\n" . sprintf('Baking `%s` view template file...', $ls_outputFile), 1, ConsoleIo::QUIET);
		$ao_io->createFile($ls_filename, $lx_content, $ao_args->getOption('force'));
	}


	/**
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function buildOptionParser (\Cake\Console\ConsoleOptionParser $ao_parser): \Cake\Console\ConsoleOptionParser {
		$lo_parser = parent::buildOptionParser($ao_parser);

		$lo_parser->addOption('folder', [
			'help' => 'The folder to save the views in. Can be either a custom path or an item set in config `App.paths.templates`. Defaults to the the first item in config `App.paths.templates`.',
		]);

		return $lo_parser;
	}
}