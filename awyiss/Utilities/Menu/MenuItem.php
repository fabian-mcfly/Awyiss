<?php declare(strict_types=1);


namespace Awyiss\Utilities\Menu;


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
class MenuItem {
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
	 * @var \Awyiss\Utilities\Menu\Menu|null
	 */
	protected ?Menu $children = null;
	/**
	 * @var array
	 */
	protected array $_defaultConfig = [];
	/**
	 * @var \Awyiss\Authorization\IdentityPermissionsInterface|mixed|null
	 */
	protected ?IdentityPermissionsInterface $identity = null;
	/**
	 * @var mixed|int
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
	 * @return bool|null
	 */
	public function isAccessible(): ?bool {
		return $this->accessible;
	}


	/**
	 * @param \Awyiss\Authorization\IdentityPermissionsInterface|null $ao_identity
	 * @return bool|null
	 * @throws \ReflectionException
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
	 * @param bool|null $ab_isAccessible
	 * @return $this
	 */
	public function setAccessible(?bool $ab_isAccessible): static {
		$this->accessible = $ab_isAccessible;


		return $this;
	}


	/**
	 * @return bool
	 */
	public function getActive(): bool {
		return $this->active;
	}


	/**
	 * @return Generator|MenuItem
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
	 * @return \Awyiss\Utilities\Menu\Menu|null
	 */
	public function getChildren(): ?Menu {
		return $this->children;
	}


	/**
	 * @param object|iterable $ax_children
	 * @param array|null $aa_config
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
	 * @return bool
	 */
	public function hasChildren(): bool {
		return !empty($this->children);
	}


	/**
	 * @param \Awyiss\Authorization\IdentityPermissionsInterface $ao_identity
	 * @param bool $deep
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
	 * @return int
	 * @noinspection PhpUnused
	 */
	public function getLevel(): int {
		return $this->level;
	}


	/**
	 * @return object|null
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
	 * @return string|null
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
	 * @param bool $ab_reset
	 * @return bool|null
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
	 * @return bool|null
	 */
	public function isVisible(): ?bool {
		return $this->visible;
	}


	/**
	 * @param bool|null $isVisible
	 * @return $this
	 * @noinspection PhpUnused
	 */
	public function setVisible(?bool $isVisible): static {
		$this->visible = $isVisible;


		return $this;
	}


	/**
	 * @param string $as_field
	 * @return mixed
	 */
	public function __get(string $as_field): mixed {
		$ls_method = 'get' . Inflector::camelize($as_field);
		if (method_exists($this, $ls_method)) {
			return $this->$ls_method();
		}

		throw new RuntimeException(sprintf('Unknown field `%s` in `%s`', $as_field, static::class));
	}


	/**
	 * @param \Awyiss\Model\Entity\BackendMenuEntry $ao_data
	 * @return void
	 */
	protected function convertEntityLink(BackendMenuEntry $ao_data): void {
		if (empty($this->link)) {
			$this->link = null;
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
}
