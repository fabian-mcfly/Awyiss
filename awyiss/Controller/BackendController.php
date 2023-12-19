<?php declare(strict_types=1);


namespace Awyiss\Controller;


/**
 * @property \Authentication\Controller\Component\AuthenticationComponent $Authentication
 * @property \Awyiss\Controller\Component\AccessComponent $Access
 * @property \Awyiss\Controller\Component\CategoriesComponent $Categories
 * @property \Awyiss\Controller\Component\SystemOrderComponent $SystemOrder
 */
abstract class BackendController extends AppController {
	public array $categorize = [];
	public array $eventTrigger = [];
	public $paginate = [];
	public array $systemOrder = [];
	protected array $overviewWhere = [];


	/**
	 * @throws \Exception
	 */
	public function initialize (): void {
		defined('IS_BACKEND') || define('IS_BACKEND', TRUE);

		parent::initialize();

		\Awyiss\Event\EventListenersProvider::loadListener($this->getName(), 'backend');

		$lo_request = \Cake\Routing\Router::getRequest();
		if ($lo_request) {
			$ls_path = $lo_request->getUri()->getPath();

			$ls_testPath = \Cake\Routing\Router::url(['_name' => 'backend',] + $lo_request->getParam('parts'));
			if ( ! str_starts_with($ls_path, $ls_testPath)) {
				$this->redirect(\Cake\Routing\Router::url(['_name' => 'backend', '?' => $lo_request->getParam('?'),] + $lo_request->getParam('parts')), 301);
			}

			if ( ! $lo_request->getParam('fullSlug') && ! str_ends_with($ls_path, '/')) {
				$this->redirect($ls_path . '/', 301);
			}

			$this->loadComponent('Authentication.Authentication');
			$this->loadComponent('Access');
			$this->loadComponent('Categories', $this->categorize);
			$this->loadComponent('EventTrigger', $this->eventTrigger);
			$this->loadComponent('SystemOrder', $this->systemOrder);

			$ls_controller = \Cake\Utility\Inflector::underscore($this->getRequest()->getParam('controller'));
			$this->loadComponent('Flash', ['key' => $ls_controller]);
		}

		$this->loadComponent('RequestHandler');

		$this->viewBuilder()->setClassName('Backend');
	}
}
