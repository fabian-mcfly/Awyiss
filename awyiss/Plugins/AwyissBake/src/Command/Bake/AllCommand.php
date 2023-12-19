<?php declare(strict_types=1);


namespace AwyissBake\Command\Bake;


/**
 * Command for `bake all`
 */
class AllCommand extends \Bake\Command\AllCommand {
	/**
	 * All commands to call.
	 *
	 * @var string[]
	 */
	protected $commands = [
		\Bake\Command\ModelCommand::class,
		ControllerCommand::class,
		TemplateCommand::class,
	];
}