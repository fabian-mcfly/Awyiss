<?php declare(strict_types=1);


namespace Awyiss\Command\Bake;


use Awyiss\Command\Util\UtilTrait;
use Bake\Command\TemplateCommand as BaseTemplateCommand;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\Core\Configure;
use Cake\View\Exception\MissingTemplateException;
use InvalidArgumentException;
use RuntimeException;


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
	 * @inheritDoc
	 * @param \Cake\Console\Arguments $args
	 * @param \Cake\Console\ConsoleIo $io
	 * @return int|null
	 */
	public function execute(Arguments $args, ConsoleIo $io): ?int {
		$li_returnCode = parent::execute($args, $io);

		if ($li_returnCode === static::CODE_ERROR) {
			return $li_returnCode;
		}

		$la_vars = $this->_loadController($io);

		if ($args->getOption('prefix') === 'Frontend') {
			return static::CODE_SUCCESS;
		}

		try {
			$ls_content = $this->getContent($args, $io, 'form', $la_vars);
			$this->bake($args, $io, 'form', $ls_content);
		}
		catch (MissingTemplateException $ex) {
			$io->verbose($ex->getMessage());
		}
		catch (RuntimeException $ex) {
			$io->error($ex->getMessage());
		}


		return static::CODE_SUCCESS;
	}


	/**
	 * Combines `\Bake\Command\TemplateCommand::getTemplatePath` and `\Bake\Command\BakeCommand::getTemplatePath`, but honors the `folder`-option.
	 *
	 * @inheritDoc
	 * @param Arguments $args The arguments
	 * @param string|null $container
	 * @see \Bake\Command\BakeCommand::getTemplatePath()
	 * @see \Bake\Command\TemplateCommand::getTemplatePath()
	 */
	public function getTemplatePath(Arguments $args, ?string $container = null): string {
		$la_paths = (array)Configure::read('App.paths.templates');
		if (empty($la_paths)) {
			throw new InvalidArgumentException('Could not read template paths. ' . 'Ensure `App.paths.templates` is defined in your application configuration.');
		}

		$ls_basePath = reset($la_paths);

		$ls_path = $this->getPath($args, $ls_basePath);
		$ls_path .= $this->controllerName . DS;


		return $ls_path;
	}


	/**
	 * @param \Cake\Console\Arguments $args
	 * @param \Cake\Console\ConsoleIo $io
	 * @param string $action
	 * @param array|null $vars
	 * @return string
	 */
	public function getContent(Arguments $args, ConsoleIo $io, string $action, ?array $vars = null): string {
		$la_vars = $vars;
		if (!$la_vars) {
			$la_vars = $this->_loadController($io);
		}

		if (empty($la_vars['primaryKey'])) {
			$io->error('Cannot generate views for models with no primary key');
			$this->abort();
		}

		if (in_array($action, $this->excludeHiddenActions)) {
			$la_vars['fields'] = array_diff($la_vars['fields'], $la_vars['hidden']);
		}

		$lo_renderer = $this->createTemplateRenderer()->set('action', $action)->set('plugin', $this->plugin)->set($la_vars);

		$li_indexColumns = 0;
		if ($action === 'index' && $args->getOption('index-columns') !== null) {
			$li_indexColumns = $args->getOption('index-columns');
		}

		$lo_renderer->set('indexColumns', $li_indexColumns);


		return $lo_renderer->generate('template/' . $action);
	}


	/**
	 * Adds the `folder`-option.
	 *
	 * @inheritDoc
	 */
	public function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser {
		$lo_parser = parent::buildOptionParser($parser);

		$la_paths = (array)Configure::read('App.paths.templates');
		$ls_basePath = reset($la_paths);

		$lo_parser->addOption('folder', [
			'help' => 'The folder to save the templates in. Defaults to the the first item in config `App.paths.templates` (`' . $ls_basePath . '`).',
		]);


		return $lo_parser;
	}
}
