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
		$lo_identity = $this->_getIdentity();
		$lo_session = $this->request->getSession();

		$la_menuData = [];
		$ls_sessionIdentifier = 'Backend.menu.' . LocaleMiddleware::getLanguage()->shortcode;
		$ls_menu = $lo_session->read($ls_sessionIdentifier);

		if ($ls_menu) {
			$la_menuData = json_decode($ls_menu, true);
			$lo_time = new DateTime($la_menuData['time']);

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

		/** @noinspection PhpUndefinedVariableInspection */
		if (!$la_menuData || $lo_time < $lo_identity->changedOn) {
			$lo_menu = new BackendMenu($lo_identity);
			$lo_renderer = new MenuRenderer($lo_menu->getDynamicMenu());
			$ls_menu = $lo_renderer->render('System');

			$lo_session->write($ls_sessionIdentifier, json_encode([
				'menu' => $ls_menu,
				'time' => new DateTime(),
			]));
		}
		else {
			$ls_menu = $la_menuData['menu'];
		}

		$this->set([
			'as_menu' => $ls_menu,
		]);

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
