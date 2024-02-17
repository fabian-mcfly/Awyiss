<?php declare(strict_types=1);


namespace Awyiss\Command\Bake;


use Bake\Command\TemplateAllCommand as BaseTemplateAllCommand;
use Cake\Console\ConsoleOptionParser;


/**
 * Task class for creating view template files.
 * This one overwrites the default bake TemplateAllCommand to use
 *  	\AwyissBake\Command\Bake\TemplateCommand
 */
class TemplateAllCommand extends BaseTemplateAllCommand {
	/**
	 * @inheritDoc
	 */
	public function initialize(): void {
		parent::initialize();
		$this->templateCommand = new TemplateCommand();
	}


	/**
	 * Adds the `folder`-option.
	 *
	 * @inheritDoc
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function buildOptionParser(ConsoleOptionParser $ao_parser): ConsoleOptionParser {
		$lo_parser = parent::buildOptionParser($ao_parser);

		$lo_parser->addOption('folder', [
			'help' => 'The folder to save the templates in. Defaults to the the first item in config `App.paths.templates`.',
		]);


		return $lo_parser;
	}
}
