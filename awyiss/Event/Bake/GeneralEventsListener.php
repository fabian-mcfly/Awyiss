<?php


namespace Awyiss\Event\Bake;


use Awyiss\Event\EventListenerTrait;
use Cake\Event\Event;
use Cake\Event\EventListenerInterface;


class GeneralEventsListener implements EventListenerInterface {
	use EventListenerTrait;


	/**
	 * @noinspection PhpArrayShapeAttributeCanBeAddedInspection
	 */
	public function implementedEvents (): array {
		return [
			'Bake.beforeRender.Controller.controller' => 'beforeRenderControllerController',
		];
	}


	/**
	 * In case the default actions ['index', 'view', 'add', 'edit', 'delete'] are used to bake a controller
	 * we overwrite those with ['overview', 'add', 'edit', 'delete'] since index is called overview
	 * and because having a "view" method is a stupid idea.
	 *
	 * @noinspection PhpUnused
	 */
	public function beforeRenderControllerController (Event $ao_event) {
		/** @var \Cake\View\View $ao_view */
		$ao_view = $ao_event->getSubject();

		if ($ao_view->get('actions') == ['index', 'view', 'add', 'edit', 'delete']) {
			$ao_view->set('actions', ['overview', 'add', 'edit', 'delete']);
		}
	}
}