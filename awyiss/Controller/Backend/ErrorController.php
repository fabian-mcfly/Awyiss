<?php declare(strict_types=1);


namespace Awyiss\Controller\Backend;


use Awyiss\Controller\BackendController as Controller;
use Cake\Event\EventInterface;


class ErrorController extends Controller {
	/**
	 * Initialization hook method.
	 *
	 * @return void
	 * @throws \Exception
	 */
	public function initialize (): void {
		$this->loadComponent('RequestHandler');
	}


	/**
	 * beforeRender callback.
	 *
	 * @param \Cake\Event\EventInterface $ao_event Event.
	 *
	 * @return null|void
	 *
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function beforeRender (EventInterface $ao_event) {
		$lo_builder = $this->viewBuilder();
		$ls_templatePath = 'Error';

		if ($this->request->getParam('prefix') && in_array($lo_builder->getTemplate(), ['error400', 'error500'], TRUE)) {
			$la_parts = explode(DS, (string) $lo_builder->getTemplatePath(), -1);
			$ls_templatePath = implode(DS, $la_parts) . DS . 'Error';
		}

		//dd('layout: ' . $lo_builder->getLayout(), 'layout-path: ' . $lo_builder->getLayoutPath(), 'theme: ' . $lo_builder->getTheme(), 'options: ', $lo_builder->getOptions(), 'name: ' . $lo_builder->getName(), 'classname: ' . $lo_builder->getClassName(), 'template: ' . $lo_builder->getTemplate(), 'templatePath: ' . $lo_builder->getTemplatePath(), $ls_templatePath);
		$lo_builder->setTemplatePath($ls_templatePath);
	}
}