<?php

declare(strict_types=1);


namespace AwyissBake;


use Cake\Core\BasePlugin;
use Cake\Core\PluginApplicationInterface;
use Cake\Event\EventInterface;
use Cake\Event\EventManager;


/**
 * Plugin for AwyissBake
 */
class Plugin extends BasePlugin {
	/**
	 * Load all the plugin configuration and bootstrap logic.
	 *
	 * The host application is provided as an argument. This allows you to load
	 * additional plugin dependencies, or attach events.
	 *
	 * @param \Cake\Core\PluginApplicationInterface $app The host application
	 *
	 * @return void
	 */
	public function bootstrap (PluginApplicationInterface $app): void {
		if (PHP_SAPI === 'cli') {
			EventManager::instance()->on('Bake.beforeRender.Controller.controller', function(EventInterface $event) {
				$view = $event->getSubject();
				$view->set('actions', [
					'overview',
					'add',
					'edit',
					'delete',
				]);
			});
		}
	}
}
