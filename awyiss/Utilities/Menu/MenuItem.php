<?php declare(strict_types=1);


namespace Awyiss\Utilities\Menu;


use Awyiss\Authorization\IdentityPermissionsInterface;
use Awyiss\Routing\Router;
use Cake\Core\InstanceConfigTrait;
use Cake\Utility\Inflector;
use Generator;
use RuntimeException;


class MenuItem {
	use InstanceConfigTrait;


	protected mixed $access = NULL;
	protected ?bool $accessible = NULL;
	/**
	 * @var array<string|int, MenuItem>
	 */
	protected ?Menu $children = NULL;
	protected $_defaultConfig = [];
	protected ?IdentityPermissionsInterface $identity = NULL;
	protected int $level = 0;
	protected mixed $link = NULL;
	protected mixed $title = NULL;
	protected ?bool $visible = NULL;


	public function __construct ($data, $config = [], $level = 1) {
		$this->access = $data->access ?? NULL;
		$this->identity = $config['identity'] ?? NULL;
		$this->level = $level;
		$this->link = $data->link;
		$this->title = $data->title;

		if ( ! empty($data->children)) {
			$this->setChildren($data->children, $config);
		}

		if (isset($config['identity'])) {
			$this->setIdentity($config['identity']);

			//Make sure to not set the identity in the config to avoid confusion
			unset($config['identity']);
		}

		$this->setConfig($config);
	}


	public function isAccessible (): ?bool {
		return $this->accessible;
	}


	public function isAccessibleBy (?IdentityPermissionsInterface $identity = NULL) {
		//No access settings means the item is always accessible
		if ( ! isset($this->access)) {
			return TRUE;
		}

		if ( ! isset($this->identity) && ! $identity) {
			return NULL;
		}

		if ( ! $identity) {
			$identity = $this->identity;
		}

		$lo_permissionCollection = $identity->getPermissionCollection();

		return $lo_permissionCollection->scopeIsAccessible($this->access->scope, (array) ($this->access->additionalData ?? []), $this->access->identifier);
	}


	public function setAccessible (?bool $isAccessible): static {
		$this->accessible = $isAccessible;

		return $this;
	}


	/**
	 * @return Generator
	 */
	public function children (int $maxLevel = -1): Generator {
		if ($this->children === NULL) {
			return;
		}

		foreach ($this->children->items($maxLevel) as $identifier => $childItem) {
			yield $identifier => $childItem;
		}
	}


	public function getChildren (): ?Menu {
		return $this->children;
	}


	public function hasChildren (): bool {
		return !empty($this->children);
	}


	public function setChildren (iterable|object $children, array $config = NULL) {
		if ($config === NULL) {
			$config = $this->getConfig();
		}

		$this->children = new Menu($children, $config, $this->level + 1);
	}


	public function setIdentity (IdentityPermissionsInterface $identity, bool $deep = TRUE): static {
		$this->identity = $identity;
		$this->accessible = $this->isAccessibleBy($identity);

		if ( ! $deep) {
			return $this;
		}

		if ($this->hasChildren()) {
			foreach ($this->getChildren() as $child) {
				$child->setIdentity($identity);
			}
		}

		//Only after all children and children's children are updated, the visibility can be calculated
		$this->visible = $this->determineVisibility(TRUE);

		return $this;
	}


	public function getLevel (): int {
		return $this->level;
	}


	public function getLink (): ?object {
		if ($this->link === NULL) {
			return NULL;
		}

		if (is_string($this->link)) {
			$this->link = (object)[
				'url' => $this->link
			];
		}

		if (is_object($this->link->url)) {
			$this->link->url = Router::url((array) $this->link->url, TRUE);
		}

		if ( ! isset($this->link->attributes)) {
			$this->link->attributes = [];
		}
		else {
			$this->link->attributes = (array) $this->link->attributes;
		}

		return $this->link;
	}


	public function getTitle (): ?string {
		if (is_object($this->title)) {
			if ( ! isset($this->title->translate)) {
				throw new RuntimeException(sprintf('Missing property `translate` for `title` in `%s`', static::class));
			}

			$this->title = __d(... (array) $this->title->translate);
		}

		return $this->title;
	}


	public function determineVisibility (bool $reset = FALSE): bool {
		// If reset is false and visible property is not null, use the current visibility
		if ( ! $reset && $this->visible !== NULL) {
			return $this->visible;
		}

		// Determine visibility based on the item's own accessibility
		$visibility = $this->isAccessible();

		if ($children = $this->getChildren()) {
			// Check the visibility of child items
			foreach ($children->items() as $child) {
				// Determine and set visibility for each child
				$childVisibility = $child->determineVisibility($reset);

				// If any child is visible, set the parent item to be visible as well
				if ($childVisibility) {
					$visibility = TRUE;
				}
			}
		}

		// Set the visibility for this item
		$this->visible = $visibility;

		return $visibility;
	}


	public function isVisible (): ?bool {
		return $this->visible;
	}


	public function setVisible (?bool $isVisible): static {
		$this->visible = $isVisible;

		return $this;
	}


	public function __get (string $as_field) {
		if (method_exists($this, $ls_method = 'get' . Inflector::camelize($as_field))) {
			return $this->$ls_method();
		}

		throw new RuntimeException(sprintf('Unknown field `%s` in `%s`', $as_field, static::class));
	}
}
