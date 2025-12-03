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
		$returnCode = parent::execute($args, $io);

		if ($returnCode === static::CODE_ERROR) {
			return $returnCode;
		}

		$vars = $this->_loadController($io);

		if ($args->getOption('prefix') === 'Frontend') {
			return static::CODE_SUCCESS;
		}

		try {
			$content = $this->getContent($args, $io, 'form', $vars);
			$this->bake($args, $io, 'form', $content);
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
		$paths = (array)Configure::read('App.paths.templates');
		if (empty($paths)) {
			throw new InvalidArgumentException('Could not read template paths. ' . 'Ensure `App.paths.templates` is defined in your application configuration.');
		}

		$basePath = reset($paths);

		$path = $this->getPath($args, $basePath);
		$path .= $this->controllerName . DS;


		return $path;
	}


	/**
	 * @param \Cake\Console\Arguments $args
	 * @param \Cake\Console\ConsoleIo $io
	 * @param string $action
	 * @param array|null $vars
	 * @return string
	 */
	public function getContent(Arguments $args, ConsoleIo $io, string $action, ?array $vars = null): string {
		if (!$vars) {
			$vars = $this->_loadController($io);
		}

		if (empty($vars['primaryKey'])) {
			$io->error('Cannot generate views for models with no primary key');
			$this->abort();
		}

		if (in_array($action, $this->excludeHiddenActions)) {
			$vars['fields'] = array_diff($vars['fields'], $vars['hidden']);
		}

		$renderer = $this->createTemplateRenderer()->set('action', $action)->set('plugin', $this->plugin)->set($vars);

		$indexColumns = 0;
		if ($action === 'index' && $args->getOption('index-columns') !== null) {
			$indexColumns = $args->getOption('index-columns');
		}

		$renderer->set('indexColumns', $indexColumns);

		// If the template to bake is a page template,
		if ($args->getOption('prefix') === 'Frontend' && $args->getOption('controller') === 'page') {
			$renderer->set('action', $args->getArgument('action'));
			$renderer->set('isCategory', str_ends_with($args->getArgument('action'), 'category'));
		}

		return $renderer->generate('template/' . $action);
	}


	/**
	 * Adds the `folder`-option.
	 *
	 * @inheritDoc
	 */
	public function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser {
		$parser = parent::buildOptionParser($parser);

		$paths = (array)Configure::read('App.paths.templates');
		$basePath = reset($paths);

		$parser->addOption('folder', [
			'help' => 'The folder to save the templates in. Defaults to the the first item in config `App.paths.templates` (`' . $basePath . '`).',
		]);


		return $parser;
	}
}
