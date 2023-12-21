<?php declare(strict_types=1);


namespace AwyissBake\Command\Bake;


//use Bake\Command\ControllerCommand;
//use Bake\Command\ModelCommand;
use Bake\Command\AllCommand as BaseAllCommand;


/**
 * Command for `bake all`
 *
 * This one overwrites the default bake AllCommand to use
 *        \AwyissBake\Command\Bake\ControllerCommand
 *        \AwyissBake\Command\Bake\ModelCommand
 *        \AwyissBake\Command\Bake\TemplateCommand
 */
class AllCommand extends BaseAllCommand {
	/**
	 * @inheritDoc
	 */
	protected array $commands = [
		ModelCommand::class,
		ControllerCommand::class,
		PolicyCommand::class,
		TemplateCommand::class,
	];
}
