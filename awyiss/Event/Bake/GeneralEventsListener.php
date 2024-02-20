<?php declare(strict_types=1);


namespace Awyiss\Event\Bake;


use Awyiss\Command\Bake\EnumCommand;
use Awyiss\Event\EventListenerTrait;
use Awyiss\Event\EventManager;
use Cake\Console\Arguments;
use Cake\Event\Event;
use Cake\Event\EventInterface;
use Cake\Event\EventListenerInterface;


/**
 * Event listeners for general events in the Bake environment
 */
class GeneralEventsListener implements EventListenerInterface {
	use EventListenerTrait;


	/**
	 * @var string
	 */
	protected static string $scope;


	/**
	 * @inheritDoc
	 */
	public function implementedEvents(): array {
		return [
			'Bake.beforeRender.Controller.controller' => 'beforeRenderControllerController',
			'Command.afterExecute' => 'afterCommandExecute',
		];
	}


	/**
	 * @param \Cake\Event\EventInterface $ao_event
	 * @param \Cake\Console\Arguments $ao_args
	 * @return void
	 */
	public function afterCommandExecute(EventInterface $ao_event, Arguments $ao_args): void {
		/** @var \Cake\Command\Command $foo */
		$lo_command = $ao_event->getSubject();

		if ($lo_command::class === EnumCommand::class && $ao_args->getOption('is-pagerole')) {
			/**
			 * Trigger the creation of the custom configuriation
			 *
			 * @see \Awyiss\Event\Backend\ConfigurationListener::createCustomConfiguration()
			 */
			$lo_eventManager = EventManager::instance();
			$lo_eventManager->dispatch('Configuration.deleteCustomConfiguration');
		}
	}


	/**
	 * In case the default actions ['index', 'view', 'add', 'edit', 'delete'] are used to bake a controller
	 * we overwrite those with ['overview', 'add', 'edit', 'delete', 'save'] since index is called overview
	 * and because having a "view" method is a stupid idea.
	 *
	 * @noinspection PhpUnused
	 */
	public function beforeRenderControllerController(Event $ao_event): void {
		/** @var \Cake\View\View $ao_view */
		$ao_view = $ao_event->getSubject();

		if ($ao_view->get('actions') == ['index', 'view', 'add', 'edit', 'delete']) {
			$ao_view->set('actions', ['overview', 'add', 'edit', 'delete', 'save']);
		}
	}
}
