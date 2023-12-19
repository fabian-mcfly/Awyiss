<?php declare(strict_types=1);


namespace AwyissBake\Command\Bake;


use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;


/**
 * Task class for creating and updating controller files.
 */
class ControllerCommand extends \Bake\Command\ControllerCommand {
	/**
	 * {@inheritDoc}
	 */
	public function bakeController (string $controllerName, array $data, Arguments $args, ConsoleIo $io): void {
		if ($data['actions'] == ['index', 'view', 'add', 'edit', 'delete']) {
			$data['actions'] = ['overview', 'add', 'edit', 'delete'];
		}

		parent::bakeController($controllerName, $data, $args, $io);
	}
}