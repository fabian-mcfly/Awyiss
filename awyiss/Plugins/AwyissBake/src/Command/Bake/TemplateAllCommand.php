<?php declare(strict_types=1);


namespace AwyissBake\Command\Bake;


/**
 * Task class for creating view template files.
 */
class TemplateAllCommand extends \Bake\Command\TemplateAllCommand {
	/**
	 * initialize
	 *
	 * @return void
	 */
	public function initialize (): void {
		parent::initialize();
		$this->templateCommand = new TemplateCommand();
	}
}