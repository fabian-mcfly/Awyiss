<?php declare(strict_types=1);


namespace Awyiss\Utility\Menu;


use ArrayAccess;
use Awyiss\Authorization\IdentityPermissionsInterface;
use Awyiss\Core\App;
use Awyiss\Routing\Router;
use Awyiss\Utility\Inflector;
use Cake\Core\InstanceConfigTrait;
use Generator;
use RuntimeException;


/**
 * A single menu item with its properties and optional children
 */
abstract class MenuItem implements ArrayAccess {
	use InstanceConfigTrait;


	/**
	 * @var \Awyiss\Utility\Menu\MenuItemAccess|null
	 */
	protected ?MenuItemAccess $access = null;
	/**
	 * @var bool|null
	 */
	protected ?bool $accessible = null;
	/**
	 * @var bool
	 */
	protected bool $active = true;
	/**
	 * @var \Awyiss\Utility\Menu\Menu|null
	 */
	protected ?Menu $children = null;
	/**
	 * @var array
	 */
	protected array $_defaultConfig = [];
	/**
	 * @var string|int|null
	 */
	protected string|int|null $identifier;
	/**
	 * @var \Awyiss\Authorization\IdentityPermissionsInterface|mixed|null
	 */
	protected ?IdentityPermissionsInterface $identity = null;
	/**
	 * @var bool|null
	 */
	protected ?bool $isCurrentRoute = null;
	/**
	 * @var int
	 */
	protected int $level = 0;
	/**
	 * @var \Awyiss\Utility\Menu\MenuItemLink|null
	 */
	protected ?MenuItemLink $link = null;
	/**
	 * @var mixed|null
	 */
	protected mixed $title = null;
	/**
	 * @var bool|null
	 */
	protected ?bool $visible = null;


	/**
	 * Checks if the menu item is accessible.
	 *
	 * @return bool|null Returns true if the menu item is accessible, false otherwise.
	 * If the accessibility is not set, it returns null.
	 */
	public function isAccessible(): ?bool {
		return $this->accessible;
	}


	/**
	 * Checks if the menu item is accessible by a specific identity.
	 *
	 * @param \Awyiss\Authorization\IdentityPermissionsInterface|null $identity The identity to check accessibility for.
	 * @return bool|null Returns true if the menu item is accessible by the provided identity, false otherwise.
	 * If the accessibility is not set, it returns null.
	 * @throws \ReflectionException If the class does not exist.
	 */
	public function isAccessibleBy(?IdentityPermissionsInterface $identity = null): ?bool {
		//No access settings means the item is always accessible
		if (!$this->access) {
			return true;
		}

		if (!isset($this->identity) && !$identity) {
			return null;
		}

		$lo_identity = $identity;
		if (!$lo_identity) {
			$lo_identity = $this->identity;
		}


		return $lo_identity->scopeIsAccessible($this->access->getScope(), $this->access->getAdditionalData() ?? [], $this->access->getIdentifier());
	}


	/**
	 * Sets the accessibility of the menu item.
	 *
	 * @param bool|null $isAccessible The accessibility to set.
	 * @return $this Returns the current instance.
	 */
	public function setAccessible(?bool $isAccessible): static {
		$this->accessible = $isAccessible;


		return $this;
	}


	/**
	 * Returns the active status of the menu item.
	 *
	 * @return bool The active status of the menu item.
	 */
	public function getActive(): bool {
		return $this->active;
	}


	/**
	 * Checks if the current route matches the route of the menu item.
	 *
	 * @param string $currentRoute The current route.
	 * @return bool True if the current route matches the route of the menu item, false otherwise.
	 */
	public function isCurrentRoute(string $currentRoute): bool {
		static $ls_fullBaseUrl;

		if ($this->isCurrentRoute !== null) {
			return $this->isCurrentRoute;
		}

		if (empty($currentRoute)) {
			$this->isCurrentRoute = false;
			return false;
		}

		$ls_testUrl = $this->getLink()?->getUrl();
		if (!$ls_testUrl) {
			$this->isCurrentRoute = false;
			return false;
		}

		$ls_currentRoute = rtrim($currentRoute, '/') . '/';
		$ls_testUrl = rtrim($ls_testUrl, '/') . '/';

		if (!isset($ls_fullBaseUrl)) {
			$ls_fullBaseUrl = Router::fullBaseUrl();
		}

		if (str_starts_with($ls_testUrl, $ls_fullBaseUrl)) {
			$ls_testUrl = substr_replace($ls_testUrl, '', 0, strlen($ls_fullBaseUrl));
		}

		if ($ls_testUrl === $ls_currentRoute) {
			$this->isCurrentRoute = true;
			return true;
		}

		// If there are parameters in the url, remove them and try again
		if (str_contains($ls_currentRoute, ':')) {
			$la_segments = explode('/', trim($ls_currentRoute, '/'));
			$la_segments = array_filter($la_segments, function (string $segment) {
				$this->isCurrentRoute = !str_contains($segment, ':');
				return $this->isCurrentRoute;
			});

			$ls_cleanRoute = '/' . implode('/', $la_segments) . '/';

			$this->isCurrentRoute = $ls_testUrl === $ls_cleanRoute;

			return $this->isCurrentRoute;
		}

		$this->isCurrentRoute = false;

		return false;
	}


	/**
	 * Checks if the current route matches the route of the menu item or any of its children.
	 *
	 * @param string $currentRoute The current route.
	 * @return bool True if the current route matches the route of the menu item or any of its children, false otherwise.
	 */
	public function hasCurrentRoute(string $currentRoute): bool {
		foreach ($this->children() as $lo_child) {
			if ($lo_child->isCurrentRoute($currentRoute)) {
				return true;
			}
		}


		return false;
	}


	/**
	 * Generates the children of the menu item.
	 *
	 * @param int $maxLevel The maximum level of children to generate.
	 * @return Generator<string, MenuItem> A generator that yields the children of the menu item.
	 */
	public function children(int $maxLevel = -1): Generator {
		if ($this->children === null) {
			return;
		}

		foreach ($this->children->items($maxLevel) as $lx_identifier => $lo_childItem) {
			yield $lx_identifier => $lo_childItem;
		}
	}


	/**
	 * Gets the children of the menu item.
	 *
	 * @return \Awyiss\Utility\Menu\Menu|null The children of the menu item.
	 */
	public function getChildren(): ?Menu {
		return $this->children;
	}


	/**
	 * Sets the children of the menu item.
	 *
	 * @param iterable $children The children to set.
	 * @param array|null $config The configuration for the children.
	 * @return void
	 */
	public function setChildren(iterable $children, ?array $config = null): void {
		$la_config = $config ?? $this->getConfig();

		if (!array_key_exists('identity', $la_config)) {
			$la_config['identity'] = $this->identity;
		}

		$la_config['menuItemClass'] ??= static::class;

		/** @var class-string<\Awyiss\Utility\Menu\Menu> $ls_menuClass */
		$ls_menuClass = App::className('Menu', 'Utility/Menu');

		/** @see \Awyiss\Utility\Menu\Menu::__construct() */
		$this->children = new $ls_menuClass($children, $la_config, $this->level + 1);
	}


	/**
	 * Checks if the menu item has children.
	 *
	 * @return bool True if the menu item has children, false otherwise.
	 */
	public function hasChildren(): bool {
		return !empty($this->children);
	}


	/**
	 * @return string|int|null
	 */
	public function getIdentifier(): string|int|null {
		return $this->identifier;
	}


	/**
	 * Sets the identity of the menu item.
	 *
	 * @param \Awyiss\Authorization\IdentityPermissionsInterface $identity The identity to set.
	 * @param bool $deep Whether to set the identity deeply.
	 * @return $this
	 * @throws \ReflectionException
	 */
	public function setIdentity(IdentityPermissionsInterface $identity, bool $deep = true): static {
		$this->identity = $identity;
		$this->accessible = $this->isAccessibleBy($identity);

		if (!$deep) {
			return $this;
		}

		if ($this->hasChildren()) {
			foreach ($this->children() as $lo_child) {
				$lo_child->setIdentity($identity);
			}
		}

		//Only after all children and children's children are updated, the visibility can be calculated
		$this->visible = $this->determineVisibility(true);


		return $this;
	}


	/**
	 * Gets the label of the menu item.
	 *
	 * @return string
	 */
	public function getLabel(): string {
		if (!$this->active) {
			return __d('menu', 'inactive') . ' ' . $this->getTitle();
		}


		return $this->getTitle();
	}


	/**
	 * Gets the level of the menu item.
	 *
	 * @return int The level of the menu item.
	 */
	public function getLevel(): int {
		return $this->level;
	}


	/**
	 * Gets the link of the menu item.
	 *
	 * @return \Awyiss\Utility\Menu\MenuItemLink|null The link of the menu item.
	 */
	public function getLink(): ?MenuItemLink {
		return $this->link;
	}


	/**
	 * Gets the title of the menu item.
	 *
	 * @return string|null The title of the menu item.
	 */
	public function getTitle(): ?string {
		return $this->title;
	}


	/**
	 * Determines the visibility of the menu item.
	 *
	 * @param bool $reset Whether to reset the visibility.
	 * @return bool|null The visibility of the menu item.
	 */
	public function determineVisibility(bool $reset = false): ?bool {
		// If reset is false and visible property is not null, use the current visibility
		if (!$reset && $this->visible !== null) {
			return $this->visible;
		}

		// Determine visibility based on the item's own accessibility
		$lb_isVisible = $this->isAccessible();

		$lo_children = $this->getChildren();
		$lb_childIsVisible = false;
		if ($lo_children) {
			// Check the visibility of child items
			foreach ($lo_children->items() as $lo_child) {
				// Determine and set visibility for each child
				$lb_childIsVisible = $lo_child->determineVisibility($reset);

				// If any child is visible, set the parent item to be visible as well
				if ($lb_childIsVisible) {
					$lb_isVisible = true;
					if (!$reset) {
						break;
					}
				}
			}
		}

		if ($lb_isVisible && !$this->getLink()?->getUrl() && !$lb_childIsVisible) {
			$lb_isVisible = false;
		}

		// Set the visibility for this item
		$this->visible = $lb_isVisible;

		return $lb_isVisible;
	}


	/**
	 * Gets the visibility of the menu item.
	 *
	 * @return bool|null The visibility of the menu item.
	 */
	public function isVisible(): ?bool {
		return $this->visible;
	}


	/**
	 * Sets the visibility of the menu item.
	 *
	 * @param bool|null $isVisible The visibility to set.
	 * @return $this
	 */
	public function setVisible(?bool $isVisible): static {
		$this->visible = $isVisible;

		return $this;
	}


	/**
	 * Converts the access of the menu item from an object.
	 *
	 * @param object $entity
	 * @return \Awyiss\Utility\Menu\MenuItemAccess|null
	 */
	protected function convertAccess(object $entity): ?MenuItemAccess {
		if (empty($entity->access)) {
			return null;
		}

		/**
		 * @var class-string<\Awyiss\Utility\Menu\MenuItemAccess> $ls_menuItemAccessClass
		 * @see \Awyiss\Utility\Menu\MenuItemAccess::__construct()
		 */
		$ls_menuItemAccessClass = App::className('MenuItemAccess', 'Utility/Menu');

		return new $ls_menuItemAccessClass((object)$entity->access);
	}


	/**
	 * Converts the link of the menu item from an object.
	 *
	 * @param object $entity
	 * @return \Awyiss\Utility\Menu\MenuItemLink|null
	 */
	protected function convertLink(object $entity): ?MenuItemLink {
		if (empty($entity->link)) {
			return null;
		}

		/**
		 * @var class-string<\Awyiss\Utility\Menu\MenuItem> $ls_menuItemLinkClass
		 * @see \Awyiss\Utility\Menu\MenuItemLink::__construct()
		 */
		$ls_menuItemLinkClass = App::className('MenuItemLink', 'Utility/Menu');

		$lo_link = new $ls_menuItemLinkClass($entity->link);

		if ($entity->external ?? false) {
			$lo_link->setTarget('_blank');
			$lo_link->setRel('noopener noreferrer');
		}

		return $lo_link;
	}


	/**
	 * Converts the title of the menu item from an object.
	 *
	 * @param object $entity
	 * @return string|null
	 */
	protected function convertTitle(object $entity): ?string {
		if (empty($entity->title)) {
			return null;
		}

		if (is_object($entity->title)) {
			if (!isset($entity->title->translate)) {
				throw new RuntimeException(sprintf('Missing property `translate` for `title` in `%s`', static::class));
			}

			return __d(... (array)$entity->title->translate);
		}

		return (string)$entity->title;
	}


	/**
	 * Gets a property of the menu item.
	 *
	 * @param string $field The property to get.
	 * @return mixed The value of the property.
	 */
	public function __get(string $field): mixed {
		$ls_method = 'get' . Inflector::camelize($field);
		if (method_exists($this, $ls_method)) {
			return $this->$ls_method();
		}

		throw new RuntimeException(sprintf('Unknown field `%s` in `%s`', $field, static::class));
	}


	/**
	 * @param mixed $offset
	 * @return bool
	 */
	public function offsetExists(mixed $offset): bool {
		return method_exists($this, 'get' . Inflector::camelize($offset));
	}


	/**
	 * @param mixed $offset
	 * @return mixed
	 */
	public function offsetGet(mixed $offset): mixed {
		return $this->{'get' . Inflector::camelize($offset)}();
	}


	/**
	 * @param mixed $offset
	 * @param mixed $value
	 * @return void
	 */
	public function offsetSet(mixed $offset, mixed $value): void {
		throw new RuntimeException('Setting values is not allowed');
	}


	/**
	 * @param mixed $offset
	 * @return void
	 */
	public function offsetUnset(mixed $offset): void {
		throw new RuntimeException('Unsetting values is not allowed');
	}
}
