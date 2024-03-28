<?php declare(strict_types=1);


namespace Awyiss\View\Cell\Backend;


use Awyiss\Authorization\IdentityPermissionsInterface;
use Awyiss\Middleware\LocaleMiddleware;
use Awyiss\Utility\Menu\BackendMenu;
use Awyiss\Utility\Menu\MenuRenderer;
use Cake\I18n\DateTime;
use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\View\Cell;
use RuntimeException;


/**
 * Provides the backend menu with authorization check
 */
class MenuCell extends Cell {
	use LocatorAwareTrait;


	/**
	 * Generate the menu and load templates/Backend/cell/menu/menu
	 *
	 * @return void
	 * @throws \ReflectionException
	 * @throws \Exception
	 */
	public function display(): void {
		// Get the user's identity and session
		$lo_identity = $this->_getIdentity();
		$lo_session = $this->request->getSession();

		// Initialize an empty array for the menu data
		$la_menuData = [];

		// Define the session identifier for the menu
		$ls_sessionIdentifier = 'Backend.menu.' . LocaleMiddleware::getLanguage()->shortcode;

		// Try to read the menu from the session
		$ls_menu = $lo_session->read($ls_sessionIdentifier);


		// If the menu is in the session, decode it and get the time it was cached
		if ($ls_menu) {
			$la_menuData = json_decode($ls_menu, true);
			$lo_time = new DateTime($la_menuData['time']);

			// If the cached menu is outdated, clear the menu data
			if ($lo_time >= $lo_identity->changedOn) {
				$lo_table = $this->fetchTable('BackendMenuEntries');
				$lo_entity = $lo_table->find()->select('id')->find('withDeleted')->where([
					'OR' => [
						'created_on >' => $lo_time,
						'changed_on >' => $lo_time,
						'deleted_on >' => $lo_time,
					],
				])->first();

				if ($lo_entity) {
					$la_menuData = [];
				}
			}
		}
		// If the menu data is not in the session or is outdated, regenerate the menu data
		if (!$la_menuData || $lo_time < $lo_identity->changedOn) {
			$lo_menu = new BackendMenu($lo_identity);
			$la_menuData = $lo_menu->getDynamicMenu();

			// Cache the menu data and the time it was cached
			$lo_session->write($ls_sessionIdentifier, json_encode([
				'menuData' => serialize($la_menuData),
				'time' => new DateTime(),
			]));
		}
		else {
			// If the menu data is in the session and is not outdated, use the cached menu data
			$la_menuData = unserialize($la_menuData['menuData']);
		}

		// Create a new menu renderer with the menu data
		$lo_renderer = new MenuRenderer($la_menuData);

		// Set the current route in the menu renderer
		$lo_renderer->setCurrentRoute($this->request->getRequestTarget());

		// Render the menu
		$ls_menu = $lo_renderer->render('System');

		// Set the menu in the view variables
		$this->set([
			'as_menu' => $ls_menu,
		]);

		// Set the template for the view
		$this->viewBuilder()->setTemplatePath('Backend/cell/Menu')->setTemplate('menu');
	}


	/**
	 * Retreive the identity attribute from the current request
	 */
	protected function _getIdentity(): IdentityPermissionsInterface {
		/** @var IdentityPermissionsInterface|\Awyiss\Model\Entity\User|\Awyiss\Model\Entity\UsersExternal $lo_identity */
		$lo_identity = $this->request->getAttribute('identity');
		if (!($lo_identity instanceof IdentityPermissionsInterface)) {
			throw new RuntimeException(sprintf('Object `%s` does not implement `%s`', get_class($lo_identity), IdentityPermissionsInterface::class));
		}


		return $lo_identity;
	}
}
