<?php

declare(strict_types=1);


namespace Awyiss\Controller;


use Awyiss\Authorization\AuthorizationInterface;


/**
 * @property \Awyiss\Model\Table\LanguagesTable $Languages
 * @property \Awyiss\Model\Table\SystemConfigurationTable $SystemConfiguration
 * @property \Authentication\Controller\Component\AuthenticationComponent $Authentication
 */
abstract class BackendController extends AppController implements AuthorizationInterface {
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

			$ls_controller = \Cake\Utility\Inflector::underscore($this->getRequest()->getParam('controller'));
			$this->loadComponent('Flash', ['key' => $ls_controller]);
		}

		$this->loadComponent('RequestHandler');

		$this->viewBuilder()->setClassName('Backend');

		$lo_permissions = static::getPermissions();

	}
	/*public function beforeFilter (\Cake\Event\EventInterface $event) {
		parent::beforeFilter($event);
	}*/
}
