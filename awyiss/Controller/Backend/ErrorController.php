<?php declare(strict_types=1);


namespace Awyiss\Controller\Backend;


use Awyiss\Annotation\NoDirectAccess;
use Awyiss\Controller\BackendController as Controller;
use Cake\Event\EventInterface;
use Cake\ORM\Query\SelectQuery;


/**
 * Handles errors that occur in the backend-scope
 */
class ErrorController extends Controller {
	/**
	 * Initialization hook method.
	 *
	 * @return void
	 * @throws \Exception
	 */
	public function initialize(): void {
		//$this->loadComponent('RequestHandler');
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
		$lo_builder = $this->viewBuilder();
		//$ls_templatePath = 'Error';

		//if ($this->request->getParam('prefix') //&& in_array($lo_builder->getTemplate(), ['error400', 'error500', 'runtimeError', 'typeError'], true)) {
		$la_parts = explode(DS, (string)$lo_builder->getTemplatePath(), -1);
		$ls_templatePath = implode(DS, $la_parts) . DS . 'Error';
		//}

		$lo_builder->setTemplatePath($ls_templatePath);
		#dd('layout: ' . $lo_builder->getLayout(), 'layout-path: ' . $lo_builder->getLayoutPath(), 'theme: ' . $lo_builder->getTheme(), 'options: ', $lo_builder->getOptions(), 'name: ' . $lo_builder->getName(), 'classname: ' . $lo_builder->getClassName(), 'template: ' . $lo_builder->getTemplate(), 'templatePath: ' . $lo_builder->getTemplatePath(), $ls_templatePath);
	}
}
