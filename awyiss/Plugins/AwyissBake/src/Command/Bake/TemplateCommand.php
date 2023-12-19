<?php declare(strict_types=1);


namespace AwyissBake\Command\Bake;


use Cake\Console\Arguments;
//use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\Core\Configure;
//use Cake\Utility\Inflector;
use InvalidArgumentException;


/**
 * Task class for creating view template files.
 */
class TemplateCommand extends \Bake\Command\TemplateCommand {
	/**
	 * @inheritDoc
	 */
	public $scaffoldActions = ['overview', 'add', 'edit'];


	/**
	 * @inheritDoc
	 */
	public $ext = 'twig';


	/**
	 * @inheritDoc
	 */
	public function initialize (): void {
		//Do not call parent's initialize
	}


	/**
	 * @inheritDoc
	 *
	 * Combines `\Bake\Command\TemplateCommand::getTemplatePath` and `\Bake\Command\BakeCommand::getTemplatePath`,
	 * but honors the `folder`-option.
	 *
	 * @param \Cake\Console\Arguments $ao_args The arguments
	 * @param string|null $as_container
	 *
	 * @see \Bake\Command\BakeCommand::getTemplatePath()
	 * @see \Bake\Command\TemplateCommand::getTemplatePath()
	 *
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function getTemplatePath (Arguments $ao_args, ?string $as_container = NULL): string {
		$la_paths = (array) Configure::read('App.paths.templates');
		if (empty($la_paths)) {
			throw new InvalidArgumentException('Could not read template paths. ' . 'Ensure `App.paths.templates` is defined in your application configuration.');
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

		if ( ! $lb_pathFound && $ls_folder) {
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
	 * This variation is required so the bake command outputs a .twig template file
	 * instead of the default .php extension
	 */
	/*public function bake (Arguments $ao_args, ConsoleIo $ao_io, string $as_template, $ax_content = '', ?string $as_outputFile = NULL): void {
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
	}*/


	/**
	 * {@inheritDoc}
	 *
	 * Adds the `folder`-option.
	 *
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function buildOptionParser (ConsoleOptionParser $ao_parser): ConsoleOptionParser {
		$lo_parser = parent::buildOptionParser($ao_parser);

		$lo_parser->addOption('folder', [
			'help' => 'The folder to save the views in. Can be either a custom path or they key of an item set in config `App.paths.templates`. Defaults to the the first item in config `App.paths.templates`.',
		]);

		return $lo_parser;
	}
}