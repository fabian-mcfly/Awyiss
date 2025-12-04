<?php declare(strict_types=1);


namespace Awyiss\Utility\Menu;


use Awyiss\Authorization\IdentityPermissionsInterface;
use Awyiss\Core\App;
use Awyiss\Model\Entity\BackendMenuEntry;
use Awyiss\Utility\Menu\Exception\MenuValidationException;
use Cake\Core\Plugin;
use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\ORM\Query\SelectQuery;


/**
 * Build a menu, based on config/menu.json
 * and customer/config/menu-extension.json
 */
class BackendMenuProvider {
	use LocatorAwareTrait;


	/**
	 * @var \Awyiss\Authorization\IdentityPermissionsInterface|null
	 */
	protected ?IdentityPermissionsInterface $identity = null;
	/**
	 * @var \Awyiss\Utility\Menu\BackendMenu|null
	 */
	protected ?Menu $menu = null;
	/**
	 * @var \Awyiss\Utility\Menu\BackendMenu|null
	 */
	protected ?Menu $customMenu = null;
	/**
	 * @var \Awyiss\Utility\Menu\BackendMenu|null
	 */
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
	 */
	protected function createMenu(): void {
		$config = [
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

		$filePath = realpath(CONFIG . 'menu.json');
		$this->menu = MenuLoader::fromJsonFile($filePath, $config);
	}


	/**
	 * @return void
	 * @throws \ReflectionException
	 */
	protected function createCustomMenu(): void {
		foreach (Plugin::getCollection() as $plugin) {
			$this->appendMenuExtension($plugin->getConfigPath() . 'menu.json');
		}

		$this->appendMenuExtension(realpath(CUSTOM_CONFIG . 'menu.json'));
	}


	/**
	 * @param \Cake\ORM\Query\SelectQuery|null $query
	 * @return void
	 * @throws \ReflectionException
	 */
	protected function createDynamicMenu(?SelectQuery $query = null): void {
		$query ??= $this->fetchTable('BackendMenuEntries');

		$menuEntries = $query->find('threaded')->all()->groupBy(function (BackendMenuEntry $entity) {
			return $entity->parentId ? 'appendTo' : 'insertAfter';
		})->map(function (array $menuEntries) {
			return collection($menuEntries)->groupBy(function (BackendMenuEntry $entity) {
				return $entity->parentId ?? $entity->insertAfterId ?? '';
			})->toArray();
		})->toArray();

		/**
		 * Serialize and unserialize to ensure that the menu is a new instance
		 * since cloning will not clone nested objects
		 */
		$this->dynamicMenu = unserialize(serialize($this->getCustomMenu() ?? $this->getMenu()));
		$this->dynamicMenu->extend($menuEntries);
	}


	/**
	 * @return void
	 * @throws \ReflectionException
	 */
	protected function appendMenuExtension(string $filePath): void {
		if (!$filePath || !file_exists($filePath)) {
			return;
		}

		$customMenuData = MenuLoader::loadJsonFile($filePath);
		$valid = MenuLoader::validateData($customMenuData, [
			'schemaPath' => CONFIG . 'menu-extension.schema.json',
			'uniqueIdentifiers' => true,
		]);

		if (!$valid) {
			throw new MenuValidationException('The data is not valid according to menu-extension.schema.json');
		}

		/**
		 * Serialize and unserialize to ensure that the menu is a new instance
		 * since cloning will not clone nested objects
		 */
		$this->customMenu ??= unserialize(serialize($this->getMenu()));
		$this->customMenu?->extend($customMenuData);
	}
}
