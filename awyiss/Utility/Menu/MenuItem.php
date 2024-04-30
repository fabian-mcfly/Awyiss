<?php declare(strict_types=1);


namespace Awyiss\Utility\Menu;


use ArrayAccess;
use Awyiss\Authorization\IdentityPermissionsInterface;
use Awyiss\Model\Entity\BackendMenuEntry;
use Awyiss\Routing\Router;
use Cake\Core\InstanceConfigTrait;
use Cake\Utility\Inflector;
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
	 * @var string|int|null
	 */
	protected string|int|null $identifier;
	/**
	 * @var \Awyiss\Authorization\IdentityPermissionsInterface|mixed|null
	 */
	protected ?IdentityPermissionsInterface $identity = null;
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
	 * @param object $ao_data
	 * @param array $aa_config
	 * @param int $ai_level
	 * @throws \ReflectionException
	 */
	public function __construct(object $ao_data, array $aa_config = [], int $ai_level = 1) {
		$this->access = $ao_data->access ?? null;
		$this->active = $ao_data->active ?? true;
		$this->identifier = $ao_data->identifier ?? null;
		$this->identity = $aa_config['identity'] ?? null;
		$this->level = $ai_level;
		$this->link = $ao_data->link;
		$this->title = $ao_data->title;

		if ($ao_data instanceof BackendMenuEntry) {
			if ($this->access) {
				$this->access = (object)$this->access;
			}

			$this->convertEntityLink($ao_data);
		}

		if (!empty($ao_data->children)) {
			$this->setChildren($ao_data->children, $aa_config);
		}

		if (isset($aa_config['identity'])) {
			$this->setIdentity($aa_config['identity']);

			//Make sure to not set the identity in the config to avoid confusion
			unset($aa_config['identity']);
		}

		$this->setConfig($aa_config);
	}


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
	 * @param \Awyiss\Authorization\IdentityPermissionsInterface|null $ao_identity The identity to check accessibility for.
	 * @return bool|null Returns true if the menu item is accessible by the provided identity, false otherwise.
	 * If the accessibility is not set, it returns null.
	 * @throws \ReflectionException If the class does not exist.
	 */
	public function isAccessibleBy(?IdentityPermissionsInterface $ao_identity = null): ?bool {
		//No access settings means the item is always accessible
		if (!isset($this->access)) {
			return true;
		}

		if (!isset($this->identity) && !$ao_identity) {
			return null;
		}

		$lo_identity = $ao_identity;
		if (!$lo_identity) {
			$lo_identity = $this->identity;
		}


		return $lo_identity->scopeIsAccessible($this->access->scope, (array)($this->access->additionalData ?? []), $this->access->identifier);
	}


	/**
	 * Sets the accessibility of the menu item.
	 *
	 * @param bool|null $ab_isAccessible The accessibility to set.
	 * @return $this Returns the current instance.
	 */
	public function setAccessible(?bool $ab_isAccessible): static {
		$this->accessible = $ab_isAccessible;


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

		$ls_testUrl = $this->getLink()?->url;
		if (!$ls_testUrl) {
			return false;
		}

		if (!isset($ls_fullBaseUrl)) {
			$ls_fullBaseUrl = Router::fullBaseUrl();
		}

		$ls_testUrl = substr_replace($ls_testUrl, '', 0, strlen($ls_fullBaseUrl));
		if ($ls_testUrl === $currentRoute) {
			return true;
		}

		// If there are parameters in the url, remove them and try again
		if (str_contains($currentRoute, ':')) {
			$la_segments = explode('/', trim($currentRoute, '/'));
			$la_segments = array_filter($la_segments, function (string $segment) {
				return !str_contains($segment, ':');
			});

			$ls_cleanRoute = '/' . implode('/', $la_segments) . '/';


			return $ls_testUrl === $ls_cleanRoute;
		}

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
	 * @param int $ai_maxLevel The maximum level of children to generate.
	 * @return Generator|MenuItem A generator that yields the children of the menu item.
	 */
	public function children(int $ai_maxLevel = -1): Generator {
		if ($this->children === null) {
			return;
		}

		foreach ($this->children->items($ai_maxLevel) as $lx_identifier => $lo_childItem) {
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
	 * @param object|iterable $ax_children The children to set.
	 * @param array|null $aa_config The configuration for the children.
	 * @return void
	 * @throws \ReflectionException
	 */
	public function setChildren(object|iterable $ax_children, ?array $aa_config = null): void {
		if ($aa_config === null) {
			$aa_config = $this->getConfig();
		}

		if (!array_key_exists('identity', $aa_config)) {
			$aa_config['identity'] = $this->identity;
		}

		$this->children = new Menu($ax_children, $aa_config, $this->level + 1);
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
	 * @param \Awyiss\Authorization\IdentityPermissionsInterface $ao_identity The identity to set.
	 * @param bool $deep Whether to set the identity deeply.
	 * @return $this
	 * @throws \ReflectionException
	 */
	public function setIdentity(IdentityPermissionsInterface $ao_identity, bool $deep = true): static {
		$this->identity = $ao_identity;
		$this->accessible = $this->isAccessibleBy($ao_identity);

		if (!$deep) {
			return $this;
		}

		if ($this->hasChildren()) {
			foreach ($this->getChildren() as $lo_child) {
				$lo_child->setIdentity($ao_identity);
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
	 * @return object|null The link of the menu item.
	 */
	public function getLink(): ?object {
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
	 * @param bool $ab_reset Whether to reset the visibility.
	 * @return bool|null The visibility of the menu item.
	 */
	public function determineVisibility(bool $ab_reset = false): ?bool {
		// If reset is false and visible property is not null, use the current visibility
		if (!$ab_reset && $this->visible !== null) {
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
				$lb_childIsVisible = $lo_child->determineVisibility($ab_reset);

				// If any child is visible, set the parent item to be visible as well
				if ($lb_childIsVisible) {
					$lb_isVisible = true;
					break;
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
	 * @param string $as_field The property to get.
	 * @return mixed The value of the property.
	 */
	public function __get(string $as_field): mixed {
		$ls_method = 'get' . Inflector::camelize($as_field);
		if (method_exists($this, $ls_method)) {
			return $this->$ls_method();
		}

		throw new RuntimeException(sprintf('Unknown field `%s` in `%s`', $as_field, static::class));
	}


	/**
	 * Converts the link of the menu item from a BackendMenuEntry.
	 *
	 * @param \Awyiss\Model\Entity\BackendMenuEntry $ao_data The BackendMenuEntry to convert from.
	 * @return void
	 */
	protected function convertEntityLink(BackendMenuEntry $ao_data): void {
		if (empty($this->link)) {
			$this->link = null;


			return;
		}

		if (!str_contains($this->link, '::')) {
			$la_linkData = [
				'url' => !str_contains($this->link, '//') ? Router::url($this->link) : $this->link,
			];

			if ($ao_data->external) {
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
			$la_params = array_filter($la_params, function ($ax_value) {
				return $ax_value !== null;
			});
		}

		$la_linkData = [
			'url' => [
				'controller' => $ls_controller,
				'action' => $ls_action,
			] + $la_params,
		];

		if ($ao_data->external) {
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
