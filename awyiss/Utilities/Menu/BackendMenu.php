<?php declare(strict_types=1);


namespace Awyiss\Utilities\Menu;


use Awyiss\Authorization\IdentityPermissionsInterface;
use Awyiss\Model\Entity\BackendMenuEntry;
use Awyiss\Model\Table\BackendMenuEntriesTable;
use Cake\ORM\Locator\LocatorAwareTrait;
use RuntimeException;


class BackendMenu {
	use LocatorAwareTrait;


	protected ?IdentityPermissionsInterface $identity = NULL;
	protected ?Menu $menu = NULL;
	protected ?Menu $customMenu = NULL;
	protected ?Menu $dynamicMenu = NULL;


	public function __construct (?IdentityPermissionsInterface $ao_identity = NULL) {
		$this->identity = $ao_identity;

		$this->createMenu();

		$this->createCustomMenu();

		$this->createDynamicMenu();
	}


	protected function createMenu () {
		$la_config = [
			'identity' => $this->identity,
			'validate' => [
				'schemaPath' => CONFIG . DS . 'menu.schema.json',
				'uniqueIdentifiers' => TRUE,
			],
		];

		$ls_filePath = realpath(CONFIG . DS . 'menu.json');
		$this->menu = MenuLoader::fromJsonFile($ls_filePath, $la_config);
	}


	public function getMenu (): ?Menu {
		return $this->menu;
	}


	protected function createCustomMenu (): void {
		$ls_filePath = realpath(CUSTOM_CONFIG . DS . 'menu.json');
		if ($ls_filePath) {
			$lo_customMenuData = MenuLoader::loadJsonFile($ls_filePath);
			$lb_valid = MenuLoader::validateData($lo_customMenuData, [
				'schemaPath' => CONFIG . DS . 'menu-extension.schema.json',
				'uniqueIdentifiers' => TRUE,
			]);

			if ( ! $lb_valid) {
				throw new RuntimeException('The data is not valid according to menu-extension.schema.json');
			}

			$this->customMenu = unserialize(serialize($this->getMenu()));
			$this->customMenu?->extend($lo_customMenuData);
		}
	}


	public function getCustomMenu (): ?Menu {
		return $this->customMenu;
	}


	protected function createDynamicMenu () {
		/** @var BackendMenuEntriesTable $lo_table */
		$lo_table = $this->fetchTable('BackendMenuEntries');

		$la_menuEntries = $lo_table->find('threaded')->applyOptions([
			'authorize' => [
				'skip' => TRUE,
			],
		])->all()->groupBy(function(BackendMenuEntry $ao_entity) {
			return $ao_entity->parentId ? 'appendTo' : 'insertAfter';
		})->map(function(array $aa_menuEntries) {
			return collection($aa_menuEntries)->groupBy(function(BackendMenuEntry $ao_entity) {
				return $ao_entity->parentId ?? $ao_entity->insertAfterId ?? '';
			})->toArray();
		})->toArray();

		$this->dynamicMenu = unserialize(serialize($this->getCustomMenu() ?? $this->getMenu()));
		$this->dynamicMenu->extend($la_menuEntries);
	}


	public function getDynamicMenu (): ?Menu {
		return $this->dynamicMenu;
	}
}