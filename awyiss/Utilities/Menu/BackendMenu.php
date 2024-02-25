<?php declare(strict_types=1);


namespace Awyiss\Utilities\Menu;


use Awyiss\Authorization\IdentityPermissionsInterface;
use Awyiss\Model\Entity\BackendMenuEntry;
use Cake\ORM\Locator\LocatorAwareTrait;
use RuntimeException;


/**
 * Build a menu based cn config/menu.json and customer/config/menu-extension.json
 */
class BackendMenu {
	use LocatorAwareTrait;


	protected ?IdentityPermissionsInterface $identity = null;
	protected ?Menu $menu = null;
	protected ?Menu $customMenu = null;
	protected ?Menu $dynamicMenu = null;


	/**
	 * @param \Awyiss\Authorization\IdentityPermissionsInterface|null $ao_identity
	 * @throws \ReflectionException
	 */
	public function __construct(?IdentityPermissionsInterface $ao_identity = null) {
		$this->identity = $ao_identity;

		$this->createMenu();

		$this->createCustomMenu();

		$this->createDynamicMenu();
	}


	/**
	 * @return \Awyiss\Utilities\Menu\Menu|null
	 */
	public function getMenu(): ?Menu {
		return $this->menu;
	}


	/**
	 * @return \Awyiss\Utilities\Menu\Menu|null
	 */
	public function getCustomMenu(): ?Menu {
		return $this->customMenu;
	}


	/**
	 * @return \Awyiss\Utilities\Menu\Menu|null
	 */
	public function getDynamicMenu(): ?Menu {
		return $this->dynamicMenu;
	}


	/**
	 * @return void
	 * @throws \ReflectionException
	 */
	protected function createMenu(): void {
		$la_config = [
			'identity' => $this->identity,
			'validate' => [
				'schemaPath' => CONFIG . DS . 'menu.schema.json',
				'uniqueIdentifiers' => true,
			],
		];

		$ls_filePath = realpath(CONFIG . DS . 'menu.json');
		$this->menu = MenuLoader::fromJsonFile($ls_filePath, $la_config);
	}


	/**
	 * @return void
	 * @throws \ReflectionException
	 */
	protected function createCustomMenu(): void {
		$ls_filePath = realpath(CUSTOM_CONFIG . DS . 'menu.json');
		if (!$ls_filePath) {
			return;
		}

		$lo_customMenuData = MenuLoader::loadJsonFile($ls_filePath);
		$lb_valid = MenuLoader::validateData($lo_customMenuData, [
			'schemaPath' => CONFIG . DS . 'menu-extension.schema.json',
			'uniqueIdentifiers' => true,
		]);

		if (!$lb_valid) {
			throw new RuntimeException('The data is not valid according to menu-extension.schema.json');
		}

		$this->customMenu = unserialize(serialize($this->getMenu()));
		$this->customMenu?->extend($lo_customMenuData);
	}


	/**
	 * @return void
	 * @throws \ReflectionException
	 */
	protected function createDynamicMenu(): void {
		/** @var \Awyiss\Model\Table\BackendMenuEntriesTable $lo_table */
		$lo_table = $this->fetchTable('BackendMenuEntries');

		$la_menuEntries = $lo_table->find('threaded')->all()->groupBy(function (BackendMenuEntry $ao_entity) {
			return $ao_entity->parentId ? 'appendTo' : 'insertAfter';
		})->map(function (array $aa_menuEntries) {
			return collection($aa_menuEntries)->groupBy(function (BackendMenuEntry $ao_entity) {
				return $ao_entity->parentId ?? $ao_entity->insertAfterId ?? '';
			})->toArray();
		})->toArray();

		$this->dynamicMenu = unserialize(serialize($this->getCustomMenu() ?? $this->getMenu()));
		$this->dynamicMenu->extend($la_menuEntries);
	}
}
