<?php declare(strict_types=1);


namespace Awyiss\Utilities\Menu;


use Awyiss\Authorization\IdentityPermissionsInterface;
use RuntimeException;


class BackendMenu {
	protected IdentityPermissionsInterface $identity;
	protected Menu $menu;
	protected object $customMenu;


	public function __construct (IdentityPermissionsInterface $ao_identity) {
		$this->identity = $ao_identity;

		$la_config = [
			'identity' => $ao_identity,
			'validate' => [
				'schemaPath' => CONFIG . DS . 'menu.schema.json',
				'uniqueIdentifiers' => TRUE,
			],
		];

		$ls_filePath = realpath(CONFIG . DS . 'menu.json');
		$this->menu = MenuLoader::fromJsonFile($ls_filePath, $la_config);

		$ls_filePath = realpath(CUSTOM_CONFIG . DS . 'menu.json');
		if ($ls_filePath) {
			$lo_customMenuData = MenuLoader::loadJsonFile($ls_filePath);
			$lb_valid = MenuLoader::validateData($lo_customMenuData, [
				'schemaPath' => CONFIG . DS . 'menu.schema.json',
				'uniqueIdentifiers' => TRUE,
			]);

			if ( ! $lb_valid) {
				throw new RuntimeException('The data is not valid according to the required menu-extension.schema.json');
			}
		}
	}


	public function loadCustomMenu () {
		$ls_filePath = realpath(CUSTOM_CONFIG . DS . 'menu.json');
		dd($ls_filePath);
	}
}