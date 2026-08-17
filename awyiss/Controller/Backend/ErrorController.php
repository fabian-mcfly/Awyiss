<?php declare(strict_types=1);


namespace Awyiss\Controller\Backend;


use Awyiss\Annotation\NoDirectAccess;
use Awyiss\Controller\BackendController as Controller;
use Cake\Core\Configure;
use Cake\Event\EventInterface;
use Cake\ORM\Query\SelectQuery;


/**
 * Handles errors that occur in the backend-scope
 */
class ErrorController extends Controller {
	/**
	 * @inheritDoc
	 */
	public function initialize(): void {
		// Do not initialize the parent class
	}


	/**
	 * @inheritDoc
	 */
	#[NoDirectAccess]
	public function getOverviewQuery(): ?SelectQuery {
		return null;
	}


	/**
	 * beforeRender callback.
	 *
	 * @param EventInterface<\Cake\Controller\Controller> $event Event.
	 * @return void
	 */
	public function beforeRender(EventInterface $event): void {
		parent::beforeRender($event);

		if (!Configure::read('debug')) {
			$this
				->viewBuilder()
				->setTemplatePath('Backend/Error')
				->setClassName('Backend')
			;
		}
	}
}
