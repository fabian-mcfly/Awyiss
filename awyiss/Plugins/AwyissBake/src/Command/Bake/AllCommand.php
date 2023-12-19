<?php declare(strict_types=1);


namespace AwyissBake\Command\Bake;


use Bake\Command\ControllerCommand;
#use Bake\Command\ModelCommand;


/**
 * Command for `bake all`
 *
 * This one overwrites the default bake AllCommand to use
 * 		\AwyissBake\Command\Bake\TemplateCommand
 *
 * TODO: Bake Policy
 */
class AllCommand extends \Bake\Command\AllCommand {
	/**
	 * @inheritDoc
	 */
	protected $commands = [
		ModelCommand::class,
		ControllerCommand::class,
		TemplateCommand::class,
	];
}