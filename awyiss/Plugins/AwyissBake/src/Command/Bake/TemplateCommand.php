<?php

declare(strict_types=1);


namespace AwyissBake\Command\Bake;


use Cake\Core\Configure;


/**
 * Task class for creating view template files.
 */
class TemplateCommand extends \Bake\Command\TemplateCommand {
	public $scaffoldActions = ['overview', 'add', 'edit'];


	/**
	 * Get a list of actions that can / should have view templates baked for them.
	 *
	 * @return string[] Array of action names that should be baked
	 */
	protected function _methodsToBake (): array {
		$base = Configure::read('App.namespace');

		$methods = [];
		if (class_exists($this->controllerClass)) {
			$methods = array_diff(array_map('Cake\Utility\Inflector::underscore', get_class_methods($this->controllerClass)), array_map('Cake\Utility\Inflector::underscore', get_class_methods($base . '\Controller\AppController')));
		}

		if (empty($methods)) {
			$methods = $this->scaffoldActions;
		}

		foreach ($methods as $i => $method) {
			if ($method[0] === '_') {
				unset($methods[ $i ]);
			}

			if ($method == 'index' && strpos($this->controllerClass, 'Awyiss\Controller\Backend') === 0) {
				unset($methods[ $i ]);
			}
		}

		return $methods;
	}
}