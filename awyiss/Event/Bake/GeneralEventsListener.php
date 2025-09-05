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
	 * @param \Cake\Event\EventInterface $event
	 * @param \Cake\Console\Arguments $args
	 * @return void
	 */
	public function afterCommandExecute(EventInterface $event, Arguments $args): void {
		if ($event->getSubject() instanceof EnumCommand && $args->getOption('is-pagerole')) {
			/**
			 * Trigger the creation of the custom configuration
			 *
			 * @see \Awyiss\Event\Backend\ConfigurationListener::createCustomConfiguration()
			 * @see \Awyiss\Event\Backend\ConfigurationListener::deleteCustomConfiguration()
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
	public function beforeRenderControllerController(Event $event): void {
		/** @var \Cake\View\View $lo_view */
		$lo_view = $event->getSubject();

		if (array_diff(['index', 'view', 'add', 'edit', 'delete'], $lo_view->get('actions')) === []) {
			$lo_view->set('actions', ['overview', 'add', 'edit', 'delete', 'save']);
		}
	}
}
