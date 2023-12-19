<?php declare(strict_types=1);


namespace Awyiss\Controller;


/**
 * @property \Authentication\Controller\Component\AuthenticationComponent $Authentication
 * @property \Awyiss\Controller\Component\AccessComponent $Access
 */
abstract class BackendController extends AppController {
	public $paginate = [
		'limit' => 20,
	];


	/**
	 * @throws \Exception
	 */
	public function initialize (): void {
		defined('IS_BACKEND') || define('IS_BACKEND', TRUE);

		parent::initialize();

		$lo_request = \Cake\Routing\Router::getRequest();
		if ($lo_request) {
			$ls_path = $lo_request->getUri()->getPath();
			if ( ! $lo_request->getParam('fullSlug') && substr($ls_path, -1) !== '/') {
				$this->redirect($ls_path . '/');
			}

			//TODO: change this to use the language saved in the session (saved after login)
			$lo_language = static::getUrlLanguage();
			ini_set('intl.default_locale', $lo_language->locale);

			$this->loadComponent('Authentication.Authentication');
			$this->loadComponent('Access');

			$ls_controller = \Cake\Utility\Inflector::underscore($this->getRequest()->getParam('controller'));
			$this->loadComponent('Flash', ['key' => $ls_controller]);
		}

		$this->loadComponent('RequestHandler');

		$this->viewBuilder()->setClassName('Backend');
	}


	/*public function beforeFilter (\Cake\Event\EventInterface $event) {
		parent::beforeFilter($event);
	}*/
}
