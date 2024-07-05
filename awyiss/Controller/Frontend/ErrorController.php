<?php declare(strict_types=1);


namespace Awyiss\Controller\Frontend;


use Awyiss\Controller\AppController;
use Cake\Core\Configure;
use Cake\Event\EventInterface;


/**
 * Handles errors that occur in the backend-scope
 */
class ErrorController extends AppController {
	/**
	 * beforeRender callback.
	 *
	 * @param EventInterface<\Cake\Controller\Controller> $event Event.
	 * @return void
	 */
	public function beforeRender(EventInterface $event): void {
		parent::beforeRender($event);

		if (Configure::read('debug')) {
			return;
		}

		$lo_builder = $this->viewBuilder();

		$lo_builder->setClassName('Frontend');
		$lo_builder->setLayout('error');
		$lo_builder->setTemplatePath('Frontend/Error');
	}
}
