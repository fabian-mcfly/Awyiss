<?php declare(strict_types=1);


namespace Awyiss\Utilities\Menu;


use Awyiss\Authorization\IdentityPermissionsInterface;
use Awyiss\Model\Entity\BackendMenuEntry;
use Awyiss\Routing\Router;
use Cake\Core\InstanceConfigTrait;
use Cake\Utility\Inflector;
use Generator;
use RuntimeException;


class MenuItem {
	use InstanceConfigTrait;


	protected mixed $access = NULL;
	protected ?bool $accessible = NULL;
	protected bool $active = TRUE;
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
		$this->active = $data->active ?? TRUE;
		$this->identity = $config['identity'] ?? NULL;
		$this->level = $level;
		$this->link = $data->link;
		$this->title = $data->title;

		if ($data instanceof BackendMenuEntry) {
			if ($this->access) {
				$this->access = (object) $this->access;
			}

			$this->convertEntityLink($data);
		}

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


	public function isAccessibleBy (?IdentityPermissionsInterface $ao_identity = NULL) {
		//No access settings means the item is always accessible
		if ( ! isset($this->access)) {
			return TRUE;
		}

		if ( ! isset($this->identity) && ! $ao_identity) {
			return NULL;
		}

		$lo_identity = $ao_identity;
		if ( ! $lo_identity) {
			$lo_identity = $this->identity;
		}

		return $lo_identity->scopeIsAccessible($this->access->scope, (array) ($this->access->additionalData ?? []), $this->access->identifier);
	}


	public function setAccessible (?bool $isAccessible): static {
		$this->accessible = $isAccessible;

		return $this;
	}


	public function getActive (): bool {
		return $this->active;
	}


	/**
	 * @return Generator|MenuItem
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

		if ( ! array_key_exists('identity', $config)) {
			$config['identity'] = $this->identity;
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


	public function determineVisibility (bool $reset = FALSE): ?bool {
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


	protected function convertEntityLink (BackendMenuEntry $data) {
		$la_parts = explode('::', $this->link);

		$ls_controller = array_shift($la_parts);
		$ls_action = array_shift($la_parts);

		$la_params = [];
		if ( ! empty($la_parts)) {
			foreach ($la_parts as $lx_value) {
				$la_innerParts = explode(':', $lx_value);
				$la_params[ $la_innerParts[0] ] = $la_innerParts[1] ?? NULL;
			}
			$la_params = array_filter($la_params, function ($ax_value) {
				return $ax_value !== NULL;
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
}
