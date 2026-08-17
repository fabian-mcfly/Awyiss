<?php declare(strict_types=1);


namespace Awyiss\Utility\Menu;


use Awyiss\Core\App;
use Awyiss\Routing\Router;


/**
 * Class representing a menu item for the backend.
 *
 * Overrides the isCurrentRoute method to check if the current route matches
 * the overview action of the controller.
 */
class BackendMenuItem extends MenuItem {
	/**
	 * The url to check against.
	 * Holds the url for the `overview`-action of the current controller.
	 *
	 * In special cases, like the contents controller, it holds the url for the
	 * `overview`-action of the controller that is the parent of the current controller.
	 *
	 * @var string|null
	 */
	protected static ?string $testUrl = null;


	/**
	 * @param \Awyiss\Model\Entity\BackendMenuEntry|\stdClass $entity
	 * @param array $config
	 * @param int $level
	 * @throws \ReflectionException
	 * @noinspection DuplicatedCode
	 */
	public function __construct(
		object $entity,
		array $config = [],
		int $level = 1
	) {
		$this->access = $this->convertAccess($entity);
		$this->active = $entity->active ?? true;
		$this->identifier = $entity->identifier ?? $entity->id;
		$this->level = $level;
		$this->link = $this->convertLink($entity);
		$this->title = $this->convertTitle($entity);

		if (isset($entity->children)) {
			$this->setChildren((array)$entity->children, $config);
		}

		if (!empty($config['identity'])) {
			$this->setIdentity($config['identity']);
		}
		/**
		 * Make sure to not set the identity in the config to avoid confusion
		 */
		unset($config['identity']);
		$this->setConfig($config);
	}


	/**
	 * Check if this item's currentRoute matches the current route
	 * This variant checks if the test currentRoute is the same as the currentRoute of this item
	 * The test currentRoute consists of the current controller but the overview action
	 *
	 * @param string $currentRoute The current route currentRoute
	 * @return bool
	 */
	public function isCurrentRoute(string $currentRoute): bool {
		if (!isset(static::$testUrl)) {
			$request = Router::getRequest();
			$controller = $request->getParam('controller');

			/**
			 * Some controllers depend on others, like contents on any page-role,
			 * form entries on forms, menu entries on menus.
			 * They all should mark their "parent" controller as active.
			 *
			 * The $currentRoute will contain a page role as the `controller`-part
			 * if the current controller is `Contents`.
			 *
			 * @see \Awyiss\View\Cell\Backend\MenuCell::display()
			 */
			$controller = match ($controller) {
				'Contents' => $this->getPageRoleFromUrl($currentRoute) ?? $controller,
				'FormElements' => 'Forms',
				'MenuEntries' => 'Menus',
				default => $controller,
			};

			static::$testUrl = Router::url([
				'lang' => $request->getParam('lang'),
				'controller' => $controller,
				'action' => 'overview',
			], true);
		}

		if ($this->isCurrentRoute !== null) {
			return $this->isCurrentRoute;
		}

		$itemUrl = $this->getLink()?->getUrl();
		if (!$itemUrl) {
			$this->isCurrentRoute = false;

			return false;
		}

		$currentRoute = rtrim($currentRoute, '/') . '/';
		$itemUrl = rtrim($itemUrl, '/') . '/';

		// Make sure the itemUrl is absolute
		if (!str_contains($itemUrl, '//')) {
			$itemUrl = Router::url($itemUrl, true);
		}

		// Make sure the currentRoute is absolute
		if (!str_contains($currentRoute, '//')) {
			$currentRoute = Router::url($currentRoute, true);
		}

		$this->isCurrentRoute = $itemUrl === $currentRoute || $itemUrl === static::$testUrl;

		return $this->isCurrentRoute;
	}


	/**
	 * Get the page role from the URL
	 *
	 * @param string $currentRoute
	 * @return string|null
	 */
	protected function getPageRoleFromUrl(string $currentRoute): ?string {
		$parts = explode('/', trim($currentRoute, '/'));

		// Filter out all parameters
		$parts = array_filter($parts, static function ($item) {
			return !str_contains($item, ':');
		});

		// The page role is the second to last part
		$pageRole = array_slice($parts, -2, 1)[0] ?? null;

		if (!$pageRole) {
			return null;
		}

		/** @var class-string<\Awyiss\Model\Enum\PageRoleEnumInterface> $pageRoleEnum */
		$pageRoleEnum = App::className('PageRole', 'Model/Enum');

		if ($pageRoleEnum::tryFromName($pageRole)) {
			return $pageRole;
		}

		return null;
	}
}
