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
		 *
		 * @noinspection PhpVariableNamingConventionInspection
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
			$lo_request = Router::getRequest();
			$ls_controller = $lo_request->getParam('controller');

			// Some controllers depend on others, like contents on any page-role,
			// form entries on forms, menu entries on menus
			// They all should mark their "parent" controller as active
			$ls_controller = match ($ls_controller) {
				'Contents' => $this->getPageRoleFromUrl($currentRoute) ?? $ls_controller,
				'FormElements' => 'Forms',
				'MenuEntries' => 'Menus',
				default => $ls_controller,
			};

			static::$testUrl = Router::url([
				'lang' => $lo_request->getParam('lang'),
				'controller' => $ls_controller,
				'action' => 'overview',
			], true);
		}

		if ($this->isCurrentRoute !== null) {
			return $this->isCurrentRoute;
		}

		$ls_itemUrl = $this->getLink()?->getUrl();
		if (!$ls_itemUrl) {
			$this->isCurrentRoute = false;
			return false;
		}

		$ls_currentRoute = rtrim($currentRoute, '/') . '/';
		$ls_itemUrl = rtrim($ls_itemUrl, '/') . '/';

		// Make sure the itemUrl is absolute
		if (!str_contains($ls_itemUrl, '//')) {
			$ls_itemUrl = Router::url($ls_itemUrl, true);
		}

		// Make sure the currentRoute is absolute
		if (!str_contains($ls_currentRoute, '//')) {
			/** @noinspection PhpVariableNamingConventionInspection */
			$ls_currentRoute = Router::url($ls_currentRoute, true);
		}

		$this->isCurrentRoute = $ls_itemUrl === $ls_currentRoute || $ls_itemUrl === static::$testUrl;

		return $this->isCurrentRoute;
	}


	/**
	 * Get the page role from the URL
	 *
	 * @param string $currentRoute
	 * @return string|null
	 */
	protected function getPageRoleFromUrl(string $currentRoute): ?string {
		$la_parts = explode('/', trim($currentRoute, '/'));

		// Filter out all parameters
		$la_parts = array_filter($la_parts, static function ($item) {
			return !str_contains($item, ':');
		});

		// The page role is the second to last part
		$ls_pageRole = array_slice($la_parts, -2, 1)[0] ?? null;

		if (!$ls_pageRole) {
			return null;
		}

		/** @var class-string<\Awyiss\Model\Enum\PageRoleEnumInterface> $ls_pageRoleEnum */
		$ls_pageRoleEnum = App::className('PageRole', 'Model/Enum');

		if ($ls_pageRoleEnum::tryFromName($ls_pageRole)) {
			return $ls_pageRole;
		}

		return null;
	}
}
