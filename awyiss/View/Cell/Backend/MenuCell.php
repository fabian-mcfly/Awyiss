<?php declare(strict_types=1);


namespace Awyiss\View\Cell\Backend;


use Awyiss\Authorization\IdentityPermissionsInterface;
use Awyiss\Model\Entity\User;
use Awyiss\Model\Entity\UsersExternal;
use Awyiss\Utilities\Menu\BackendMenu;
use Awyiss\Utilities\Menu\MenuRenderer;
use Cake\View\Cell;
use RuntimeException;


class MenuCell extends Cell {
	/**
	 * Generate the menu and load templates/Backend/cell/menu/menu
	 *
	 * @return void
	 */
	public function display (): void {
		$lo_identity = $this->_getIdentity();

		$lo_menu = new BackendMenu($lo_identity);
		$lo_renderer = new MenuRenderer($lo_menu);

		$this->set([
			'ao_menu' => $lo_menu,
			'ao_renderer' => $lo_renderer,
		]);

		$this->viewBuilder()
			->setTemplatePath('Backend/cell/Menu')
			->setTemplate('menu');
	}


	/**
	 * Retreive the identity attribute from the current request
	 */
	protected function _getIdentity (): IdentityPermissionsInterface {
		/** @var IdentityPermissionsInterface|User|UsersExternal $lo_identity */
		$lo_identity = $this->request->getAttribute('identity');
		if ( ! ($lo_identity instanceof IdentityPermissionsInterface)) {
			throw new RuntimeException(sprintf('Object `%s` does not implement `%s`', get_class($lo_identity), IdentityPermissionsInterface::class));
		}

		return $lo_identity;
	}
}