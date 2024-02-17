<?php declare(strict_types=1);


namespace Awyiss\Command\Bake;


use Awyiss\Command\Util\UtilTrait;
use Bake\Command\TemplateCommand as BaseTemplateCommand;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\Core\Configure;
use InvalidArgumentException;


/**
 * Task class for creating view template files.
 */
class TemplateCommand extends BaseTemplateCommand {
	use UtilTrait;


	/**
	 * @inheritDoc
	 */
	public array $scaffoldActions = ['overview', 'add', 'edit', 'form'];
	/**
	 * @inheritDoc
	 */
	public string $ext = 'twig';


	/**
	 * @inheritDoc
	 */
	public function initialize(): void {
		//Do not call parent's initialize
	}


	/**
	 * Combines `\Bake\Command\TemplateCommand::getTemplatePath` and `\Bake\Command\BakeCommand::getTemplatePath`, but honors the `folder`-option.
	 *
	 * @inheritDoc
	 * @param Arguments $ao_args The arguments
	 * @param string|null $as_container
	 * @see \Bake\Command\BakeCommand::getTemplatePath()
	 * @see \Bake\Command\TemplateCommand::getTemplatePath()
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function getTemplatePath(Arguments $ao_args, ?string $as_container = null): string {
		$la_paths = (array)Configure::read('App.paths.templates');
		if (empty($la_paths)) {
			throw new InvalidArgumentException('Could not read template paths. ' . 'Ensure `App.paths.templates` is defined in your application configuration.');
		}

		$ls_basePath = reset($la_paths);

		$ls_path = $this->getPath($ao_args, $ls_basePath);
		$ls_path .= $this->controllerName . DS;


		return $ls_path;
	}


	/**
	 * @param \Cake\Console\Arguments $ao_args
	 * @param \Cake\Console\ConsoleIo $ao_io
	 * @param string $as_action
	 * @param array|null $aa_vars
	 * @return string
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function getContent(Arguments $ao_args, ConsoleIo $ao_io, string $as_action, ?array $aa_vars = null): string {
		$la_vars = $aa_vars;
		if (!$la_vars) {
			$la_vars = $this->_loadController($ao_io);
		}

		if (empty($la_vars['primaryKey'])) {
			$ao_io->error('Cannot generate views for models with no primary key');
			$this->abort();
		}

		if (in_array($as_action, $this->excludeHiddenActions)) {
			$la_vars['fields'] = array_diff($la_vars['fields'], $la_vars['hidden']);
		}

		$lo_renderer = $this->createTemplateRenderer()->set('action', $as_action)->set('plugin', $this->plugin)->set($la_vars);

		$li_indexColumns = 0;
		if ($as_action === 'index' && $ao_args->getOption('index-columns') !== null) {
			$li_indexColumns = $ao_args->getOption('index-columns');
		}

		$lo_renderer->set('indexColumns', $li_indexColumns);


		return $lo_renderer->generate('Template/' . $as_action);
	}


	/**
	 * Adds the `folder`-option.
	 *
	 * @inheritDoc
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function buildOptionParser(ConsoleOptionParser $ao_parser): ConsoleOptionParser {
		$lo_parser = parent::buildOptionParser($ao_parser);

		$la_paths = (array)Configure::read('App.paths.templates');
		$ls_basePath = reset($la_paths);

		$lo_parser->addOption('folder', [
			'help' => 'The folder to save the templates in. Defaults to the the first item in config `App.paths.templates` (`' . $ls_basePath . '`).',
		]);


		return $lo_parser;
	}
}
