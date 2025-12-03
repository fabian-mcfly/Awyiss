<?php declare(strict_types=1);


namespace Awyiss\View\Cell\Backend;


use Awyiss\Authorization\IdentityPermissionsInterface;
use Awyiss\Core\App;
use Awyiss\Middleware\LocaleMiddleware;
use Awyiss\Routing\Router;
use Awyiss\Utility\Inflector;
use Awyiss\Utility\Menu\Exception\MenuDuplicateIdentifierException;
use Awyiss\Utility\Menu\Exception\MenuFileException;
use Awyiss\Utility\Menu\Exception\MenuValidationException;
use Cake\I18n\DateTime;
use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\View\Cell;
use Cake\View\StringTemplate;
use RuntimeException;


/**
 * Provides the backend menu with authorization check
 */
class MenuCell extends Cell {
	use LocatorAwareTrait;


	/**
	 * Options for the menu renderer
	 *
	 * @var array $rendererOptions
	 * @noinspection HtmlUnknownAttribute
	 */
	protected array $rendererOptions = [
		'formatters' => [],
		'templates' => [],
	];


	/**
	 * Set the formatters if they are not set
	 *
	 * @return void
	 */
	public function initialize(): void {
		$this->rendererOptions['formatters']['noLink'] ??= $this->renderNoLink(...);
	}


	/**
	 * Generate the menu and load templates/Backend/cell/menu/menu
	 *
	 * @param string|null $currentPageRole
	 * @return void
	 * @throws \ReflectionException
	 * @throws \Exception
	 */
	public function display(?string $currentPageRole = null): void {
		// Get the user's identity and session
		$identity = $this->_getIdentity();
		$session = $this->request->getSession();
		// Define the session identifier for the menu
		$sessionIdentifier = 'Backend.menu.' . LocaleMiddleware::getLanguage()->shortcode;

		// Initialize an empty array for the menu data
		$menuData = [];

		// Try to read the menu from the session
		$menu = $session->read($sessionIdentifier);

		// If the menu is in the session, decode it and get the time it was cached
		if ($menu) {
			$menuData = json_decode($menu, true);
			$time = new DateTime($menuData['time']);

			/**
			 * If the user hasn't changed since the menu was changed,
			 * check if there are any new, changed or deleted menu entries
			 * in the database since the menu was cached. If there are, invalidate the menu cache.
			 */
			if ($time >= $identity->changedOn) {
				$backendMenuEntriesTable = $this->fetchTable('BackendMenuEntries');
				/** @uses \Awyiss\Model\Behavior\SoftDeleteBehavior::findWithDeleted() */
				$entity = $backendMenuEntriesTable->find()->select('id')->find('withDeleted')->where([
					'OR' => [
						'created_on >' => $time,
						'changed_on >' => $time,
						'deleted_on >' => $time,
					],
				])->first();

				// If there are newer menu entries, invalidate the menu cache.
				if ($entity) {
					$menuData = [];
				}
			}
			else {
				// If the user has changed since the menu was cached, invalidate the menu cache.
				$menuData = [];
			}
		}

		if (!$menuData) {
			/** @var class-string<\Awyiss\Utility\Menu\BackendMenuProvider> $backendMenuProviderClass */
			$backendMenuProviderClass = App::className('BackendMenuProvider', 'Utility/Menu');

			try {
				$menu = new $backendMenuProviderClass();
				$menu = $menu->getDynamicMenu();
			}
			catch (MenuDuplicateIdentifierException | MenuFileException | MenuValidationException $ex) {
				// Set the menu in the view variables
				$this->set([
					'menu' => '<div id="MenuException">' . $ex->getMessage() . '</div>',
				]);

				// Set the template for the view
				$this->viewBuilder()->setTemplatePath('Backend/cell/Menu')->setTemplate('menu');

				return;
			}

			// Cache the menu data and the time it was cached
			$session->write($sessionIdentifier, json_encode([
				'menuData' => serialize($menu),
				'time' => new DateTime(),
			]));
		}
		else {
			// If the menu data is in the session and is not outdated, use the cached menu data
			$menu = unserialize($menuData['menuData']);
		}

		$menu->setIdentity($identity);

		/** @var class-string<\Awyiss\Utility\Menu\MenuRenderer> $menuRendererClass */
		$menuRendererClass = App::className('MenuRenderer', 'Utility/Menu');
		// Create a new menu renderer with the menu data
		$renderer = new $menuRendererClass($menu, $this->rendererOptions);

		// Set the current route in the menu renderer
		$url = '/backend/' . $this->request->getParam('lang') . '/';
		if ($currentPageRole) {
			$url .= $currentPageRole . '/overview/';
		}
		else {
			$url .= Inflector::dasherize($this->request->getParam('controller'));
			$url .= '/' . $this->request->getParam('action') . '/';
		}
		$renderer->setCurrentRoute(Router::url($url));

		// Render the menu
		$menu = $renderer->render('System');

		// Set the menu in the view variables
		$this->set([
			'menu' => $menu,
		]);

		// Set the template for the view
		$this->viewBuilder()->setTemplatePath('Backend/cell/Menu')->setTemplate('menu');
	}


	/**
	 * Retrieve the identity attribute from the current request
	 */
	protected function _getIdentity(): IdentityPermissionsInterface {
		/** @var IdentityPermissionsInterface|\Awyiss\Model\Entity\User $identity */
		$identity = $this->request->getAttribute('identity');

		if (!$identity) {
			throw new RuntimeException('No identity found in the request.');
		}

		if (!($identity instanceof IdentityPermissionsInterface)) {
			throw new RuntimeException(sprintf('Object `%s` does not implement `%s`', get_class($identity), IdentityPermissionsInterface::class));
		}


		return $identity;
	}


	/**
	 * @param array $data
	 * @param \Cake\View\StringTemplate $template
	 * @return string
	 */
	public function renderNoLink(array $data, StringTemplate $template): string {
		$data['tabindex'] = '';
		if (!empty($data['children'])) {
			$data['tabindex'] = ' tabindex="0"';
		}

		return $template->format('noLink', $data);
	}
}
