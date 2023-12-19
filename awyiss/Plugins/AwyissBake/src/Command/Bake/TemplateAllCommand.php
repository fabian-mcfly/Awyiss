<?php declare(strict_types=1);


namespace AwyissBake\Command\Bake;


/**
 * Task class for creating view template files.
 *
 * This one overwrites the default bake TemplateAllCommand to use
 * 		\AwyissBake\Command\Bake\TemplateCommand
 */
class TemplateAllCommand extends \Bake\Command\TemplateAllCommand {
	/**
	 * @inheritDoc
	 */
	public function initialize (): void {
		parent::initialize();
		$this->templateCommand = new TemplateCommand();
	}
}