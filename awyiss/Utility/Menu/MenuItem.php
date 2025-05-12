<?php declare(strict_types=1);


namespace Awyiss\Utility\Menu;


use ArrayAccess;
use Awyiss\Authorization\IdentityPermissionsInterface;
use Awyiss\Core\App;
use Awyiss\Model\Entity;
use Awyiss\Model\Entity\BackendMenuEntry;
use Awyiss\Model\Entity\MenuEntry;
use Awyiss\Routing\Router;
use Awyiss\Utility\Inflector;
use Cake\Core\InstanceConfigTrait;
use Generator;
use RuntimeException;


/**
 * A single menu item with its properties and optional children
 */
class MenuItem implements ArrayAccess {
	use InstanceConfigTrait;


	/**
	 * @var mixed|object|null
	 */
	protected mixed $access = null;
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
	 * @var \Awyiss\Model\Entity
	 */
	protected Entity $entity;
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
	 * @var mixed|null
	 */
	protected mixed $link = null;
	/**
	 * @var mixed|null
	 */
	protected mixed $title = null;
	/**
	 * @var bool|null
	 */
	protected ?bool $visible = null;


	/**
	 * @param object $data
	 * @param array $config
	 * @param int $level
	 * @throws \ReflectionException
	 */
	public function __construct(object $data, array $config = [], int $level = 1) {
		$la_config = $config;

		$this->access = $data->access ?? null;
		$this->active = $data->active ?? true;
		$this->identifier = $data->identifier ?? null;
		$this->identity = $la_config['identity'] ?? null;
		$this->level = $level;
		$this->link = $data->link;
		$this->title = $data->title;

		if ($data instanceof MenuEntry || $data instanceof BackendMenuEntry) {
			if ($data instanceof BackendMenuEntry) {
				if ($this->access) {
					$this->access = (object)$this->access;
				}
			}

			$this->convertEntityLink($data);

			$this->entity = $data;
		}

		if (!empty($data->children)) {
			$this->setChildren($data->children, $la_config);
		}

		if (isset($la_config['identity'])) {
			$this->setIdentity($la_config['identity']);

			//Make sure to not set the identity in the config to avoid confusion
			unset($la_config['identity']);
		}

		$this->setConfig($la_config);
	}


	/**
	 * Checks if the menu item is accessible.
	 *
	 * @return bool|null Returns true if the menu item is accessible, false otherwise.
	 * If the accessibility is not set, it returns null.
	 */
	public function isAccessible(): ?bool {
		if ($this->accessible === null && ($this->entity ?? null) instanceof MenuEntry) {
			return true;
		}

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
		if (!isset($this->access)) {
			return true;
		}

		if (!isset($this->identity) && !$identity) {
			return null;
		}

		$lo_identity = $identity;
		if (!$lo_identity) {
			$lo_identity = $this->identity;
		}


		return $lo_identity->scopeIsAccessible($this->access->scope, (array)($this->access->additionalData ?? []), $this->access->identifier);
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

		$ls_testUrl = $this->getLink()?->url;
		if (!$ls_testUrl) {
			$this->isCurrentRoute = false;
			return false;
		}

		$ls_testUrl = rtrim($ls_testUrl, '/') . '/';

		if (!isset($ls_fullBaseUrl)) {
			$ls_fullBaseUrl = Router::fullBaseUrl();
		}

		if (str_starts_with($ls_testUrl, $ls_fullBaseUrl)) {
			$ls_testUrl = substr_replace($ls_testUrl, '', 0, strlen($ls_fullBaseUrl));
		}

		if ($ls_testUrl === $currentRoute) {
			$this->isCurrentRoute = true;
			return true;
		}

		// If there are parameters in the url, remove them and try again
		if (str_contains($currentRoute, ':')) {
			$la_segments = explode('/', trim($currentRoute, '/'));
			$la_segments = array_filter($la_segments, function (string $segment) {
				$this->isCurrentRoute = !str_contains($segment, ':');
				return $this->isCurrentRoute;
			});

			$ls_cleanRoute = '/' . implode('/', $la_segments);

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
	 * @return Generator|MenuItem A generator that yields the children of the menu item.
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
	 * @param object|iterable $children The children to set.
	 * @param array|null $config The configuration for the children.
	 * @return void
	 * @throws \ReflectionException
	 */
	public function setChildren(object|iterable $children, ?array $config = null): void {
		$la_config = $config;

		if ($la_config === null) {
			$la_config = $this->getConfig();
		}

		if (!array_key_exists('identity', $la_config)) {
			$la_config['identity'] = $this->identity;
		}

		$la_config['menuItemClass'] ??= static::class;

		/** @var class-string<\Awyiss\Utility\Menu\Menu> $ls_menuClass */
		$ls_menuClass = App::className('Menu', 'Utility/Menu');

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
	 * Gets the entity of the menu item.
	 *
	 * @return \Awyiss\Model\Entity
	 */
	public function getEntity(): Entity {
		return $this->entity;
	}


	/**
	 * Checks if the menu item has an entity.
	 *
	 * @return bool
	 */
	public function hasEntity(): bool {
		return isset($this->entity);
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
			foreach ($this->getChildren() as $lo_child) {
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
	 * @param string|null $currentRoute
	 * @return object|null The link of the menu item.
	 */
	public function getLink(?string $currentRoute = null): ?object {
		if ($this->link === null) {
			return null;
		}

		if (is_string($this->link)) {
			$this->link = (object)[
				'url' => $this->link,
			];
		}

		if (is_object($this->link->url)) {
			$this->link->url = Router::url((array)$this->link->url, true);
		}

		if (!isset($this->link->attributes)) {
			$this->link->attributes = [];
		}
		else {
			$this->link->attributes = (array)$this->link->attributes;
		}

		if ($currentRoute) {
			$ls_requestTarget = Router::getRequest()?->getRequestTarget();

			// If the request is the homepage and the link is as well, set the link '/'
			if ($ls_requestTarget === '/' && $this->link->url === $currentRoute) {
				$this->link->url = Router::url('/', true);
			}

			// If the link is the current route and contains a '#', set the link to '#'
			if (str_contains($this->link->url, '#')) {
				$la_parts = explode('#', $this->link->url);
				$la_parts[0] = '/' . trim($la_parts[0], '/');

				if ($la_parts[0] === $currentRoute) {
					$this->link->url = '#' . $la_parts[1];
				}
			}
		}

		return $this->link;
	}


	/**
	 * Gets the title of the menu item.
	 *
	 * @return string|null The title of the menu item.
	 */
	public function getTitle(): ?string {
		if (is_object($this->title)) {
			if (!isset($this->title->translate)) {
				throw new RuntimeException(sprintf('Missing property `translate` for `title` in `%s`', static::class));
			}

			$this->title = __d(... (array)$this->title->translate);
		}


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

		if ($lb_isVisible && !$this->getLink() && !$lb_childIsVisible) {
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
		// If the item is a menu entry, it is always visible
		if (($this->entity ?? null) instanceof MenuEntry) {
			return true;
		}

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
	 * Converts the link of the menu item from a BackendMenuEntry.
	 *
	 * @param \Awyiss\Model\Entity\MenuEntry|\Awyiss\Model\Entity\BackendMenuEntry $data The BackendMenuEntry to convert from.
	 * @return void
	 */
	protected function convertEntityLink(MenuEntry|BackendMenuEntry $data): void {
		if (empty($this->link)) {
			$this->link = null;


			return;
		}

		if (!str_contains($this->link, '::') || $data instanceof MenuEntry) {
			$la_linkData = [
				'url' => !str_contains($this->link, '//') ? Router::url($this->link) : $this->link,
			];

			if ($data->external) {
				$la_linkData['attributes'] = [
					'target' => '_blank',
				];
			}

			$this->link = json_decode(json_encode($la_linkData));

			return;
		}

		$la_parts = explode('::', $this->link);

		$ls_controller = array_shift($la_parts);
		$ls_action = array_shift($la_parts);

		$la_params = [];
		if (!empty($la_parts)) {
			foreach ($la_parts as $lx_value) {
				$la_innerParts = explode(':', $lx_value);
				$la_params[ $la_innerParts[0] ] = $la_innerParts[1] ?? null;
			}
			$la_params = array_filter($la_params, function ($value) {
				return $value !== null;
			});
		}

		$la_linkData = [
			'url' => [
				'controller' => $ls_controller,
				'action' => $ls_action,
			] + $la_params,
		];

		if ($data->external) {
			$la_linkData['attributes'] = [
				'target' => '_blank',
			];
		}

		$this->link = json_decode(json_encode($la_linkData));
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
