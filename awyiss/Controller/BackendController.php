<?php declare(strict_types=1);


namespace Awyiss\Controller;


/**
 * @property \Authentication\Controller\Component\AuthenticationComponent $Authentication
 * @property \Awyiss\Controller\Component\AccessComponent $Access
 * @property \Awyiss\Controller\Component\CategoriesComponent $Categories
 * @property \Awyiss\Controller\Component\SystemOrderComponent $SystemOrder
 */
abstract class BackendController extends AppController {
	public array $access = [];
	public array $categorize = [];
	public array $eventTrigger = [];
	public /*array*/ $paginate = [];
	public array $systemOrder = [];
	protected array $overviewWhere;


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

			/** @var \Awyiss\Middleware\LocaleMiddleware $lo_locale */
			$lo_locale = $this->getRequest()->getAttribute('locale');
			$ls_lang = $lo_locale->getLanguageFromUrl(TRUE)?->shortcode ?? NULL;

			$ls_testPath = \Cake\Routing\Router::url(['_name' => 'backend'] + $lo_request->getParam('parts') + ['lang' => $ls_lang]);
			if ( ! str_starts_with($ls_path, $ls_testPath)) {
				$this->redirect(\Cake\Routing\Router::url(['_name' => 'backend', '?' => $lo_request->getParam('?'),] + $lo_request->getParam('parts') + ['lang' => $ls_lang]), 301);
			}

			if ( ! $lo_request->getParam('fullSlug') && ! str_ends_with($ls_path, '/')) {
				$this->redirect($ls_path . '/', 301);
			}

			$this->loadComponent('Authentication.Authentication');
			$this->loadComponent('Access', $this->access);
			$this->loadComponent('Categories', $this->categorize + [
				'tableName' => $this->defaultTable ?? $this->getName()
			]);
			$this->loadComponent('EventTrigger', $this->eventTrigger);
			$this->loadComponent('SystemOrder', $this->systemOrder + [
				'tableName' => $this->defaultTable ?? $this->getName()
			]);

			$ls_controller = \Cake\Utility\Inflector::underscore($this->getRequest()->getParam('controller'));
			$this->loadComponent('Flash', ['key' => $ls_controller]);
		}

		$this->viewBuilder()->setClassName('Backend');
	}


	/**
	 * @param null|string $ax_key
	 * @param null $ax_default
	 *
	 * @return mixed
	 *
	 * @noinspection PhpUnused
	 */
	public function getOverviewWhere (string $ax_key = NULL, $ax_default = NULL): mixed {
		if (!isset($this->overviewWhere)) {
			$this->initializeOverviewWhere();
		}

		if ($ax_key) {
			return $this->overviewWhere[ $ax_key ] ?? $ax_default;
		}

		return $this->overviewWhere;
	}


	/**
	 * @param string|array $ax_key
	 * @param null $ax_value
	 *
	 * @return \Awyiss\Controller\BackendController
	 * @noinspection PhpUnused
	 */
	public function setOverviewWhere (string|array $ax_key, $ax_value = NULL): self {
		if (is_string($ax_key)) {
			$this->overviewWhere[ $ax_key ] = $ax_value;
		}
		else {
			$this->overviewWhere = $ax_key;
		}

		return $this;
	}


	protected function initializeOverviewWhere () {
		$this->overviewWhere = [];
	}
}
