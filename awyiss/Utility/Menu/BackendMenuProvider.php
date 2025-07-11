<?php declare(strict_types=1);


namespace Awyiss\Utility\Menu;


use Awyiss\Authorization\IdentityPermissionsInterface;
use Awyiss\Core\App;
use Awyiss\Model\Entity\BackendMenuEntry;
use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\ORM\Query\SelectQuery;
use RuntimeException;


/**
 * Build a menu, based on config/menu.json
 * and customer/config/menu-extension.json
 */
class BackendMenuProvider {
	use LocatorAwareTrait;


	protected ?IdentityPermissionsInterface $identity = null;
	protected ?Menu $menu = null;
	protected ?Menu $customMenu = null;
	protected ?Menu $dynamicMenu = null;


	/**
	 * @param \Awyiss\Authorization\IdentityPermissionsInterface|null $identity
	 * @param \Cake\ORM\Query\SelectQuery|null $dynamicMenuQuery
	 * @throws \ReflectionException
	 */
	public function __construct(?IdentityPermissionsInterface $identity = null, ?SelectQuery $dynamicMenuQuery = null) {
		$this->identity = $identity;

		$this->createMenu();

		$this->createCustomMenu();

		$this->createDynamicMenu($dynamicMenuQuery);
	}


	/**
	 * @return \Awyiss\Utility\Menu\Menu|null
	 */
	public function getMenu(): ?Menu {
		return $this->menu;
	}


	/**
	 * @return \Awyiss\Utility\Menu\Menu|null
	 */
	public function getCustomMenu(): ?Menu {
		return $this->customMenu;
	}


	/**
	 * @return \Awyiss\Utility\Menu\Menu|null
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
			/** @see \Awyiss\Utility\Menu\BackendMenu::__construct */
			'menuClass' => App::className('BackendMenu', 'Utility/Menu'),
			/** @see \Awyiss\Utility\Menu\BackendMenuItem::__construct */
			'menuItemClass' => App::className('BackendMenuItem', 'Utility/Menu'),
			'identity' => $this->identity,
			'validate' => [
				'schemaPath' => CONFIG . 'menu.schema.json',
				'uniqueIdentifiers' => true,
			],
		];

		$ls_filePath = realpath(CONFIG . 'menu.json');
		$this->menu = MenuLoader::fromJsonFile($ls_filePath, $la_config);
	}


	/**
	 * @return void
	 * @throws \ReflectionException
	 */
	protected function createCustomMenu(): void {
		$ls_filePath = realpath(CUSTOM_CONFIG . 'menu.json');
		if (!$ls_filePath) {
			return;
		}

		$lo_customMenuData = MenuLoader::loadJsonFile($ls_filePath);
		$lb_valid = MenuLoader::validateData($lo_customMenuData, [
			'schemaPath' => CONFIG . 'menu-extension.schema.json',
			'uniqueIdentifiers' => true,
		]);

		if (!$lb_valid) {
			throw new RuntimeException('The data is not valid according to menu-extension.schema.json');
		}

		$this->customMenu = unserialize(serialize($this->getMenu()));
		$this->customMenu?->extend($lo_customMenuData);
	}


	/**
	 * @param \Cake\ORM\Query\SelectQuery|null $query
	 * @return void
	 * @throws \ReflectionException
	 */
	protected function createDynamicMenu(?SelectQuery $query = null): void {
		$lo_query = $query ?? $this->fetchTable('BackendMenuEntries');

		$la_menuEntries = $lo_query->find('threaded')->all()->groupBy(function (BackendMenuEntry $entity) {
			return $entity->parentId ? 'appendTo' : 'insertAfter';
		})->map(function (array $menuEntries) {
			return collection($menuEntries)->groupBy(function (BackendMenuEntry $entity) {
				return $entity->parentId ?? $entity->insertAfterId ?? '';
			})->toArray();
		})->toArray();

		$this->dynamicMenu = unserialize(serialize($this->getCustomMenu() ?? $this->getMenu()));
		$this->dynamicMenu->extend($la_menuEntries);
	}
}
