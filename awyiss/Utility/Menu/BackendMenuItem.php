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
	 * Check if this item's currentRoute matches the current route
	 * This variant checks if the test currentRoute is the same as the currentRoute of this item
	 * The test currentRoute consists of the current controller but the overview action
	 *
	 * @param string $currentRoute The current route currentRoute
	 * @return bool
	 */
	public function isCurrentRoute(string $currentRoute): bool {
		static $ls_testUrl;

		if (!isset($ls_testUrl)) {
			$lo_request = Router::getRequest();
			$ls_controller = $lo_request->getParam('controller');

			// Some controllers depend on others, like contents on <pagerole>,
			// form entries on forms, menu entries on menus
			// They all should mark their "parent" controller as active
			$ls_controller = match ($ls_controller) {
				'Contents' => $this->getPageRoleFromUrl($currentRoute) ?? $ls_controller,
				'FormElements' => 'Forms',
				'MenuEntries' => 'Menus',
				default => $ls_controller,
			};

			$ls_testUrl = Router::url([
				'lang' => $lo_request->getParam('lang'),
				'controller' => $ls_controller,
				'action' => 'overview',
			], true);
		}

		if ($this->isCurrentRoute !== null) {
			return $this->isCurrentRoute;
		}

		$ls_itemUrl = $this->getLink()?->url;
		if (!$ls_itemUrl) {
			$this->isCurrentRoute = false;

			return false;
		}

		// Make sure the itemUrl is absolute
		if (!str_contains($ls_itemUrl, '//')) {
			$ls_itemUrl = Router::url($ls_itemUrl, true);
		}

		// Make sure the currentRoute is absolute
		if (!str_contains($currentRoute, '//')) {
			/** @noinspection PhpVariableNamingConventionInspection */
			$currentRoute = Router::url($currentRoute, true);
		}

		if ($ls_itemUrl === $currentRoute) {
			$this->isCurrentRoute = true;

			return true;
		}

		$this->isCurrentRoute = $ls_itemUrl === $ls_testUrl;

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
