<?php declare(strict_types=1);


namespace Awyiss\Utility\Menu;


use Awyiss\Authorization\IdentityPermissionsInterface;
use Cake\Core\InstanceConfigTrait;
use Generator;
use RuntimeException;


/**
 * A menu class that represents one level of items
 */
abstract class Menu {
	use InstanceConfigTrait;


	/**
	 * Default configuration values
	 *
	 * @var array
	 */
	protected array $_defaultConfig = [];
	/**
	 * The identity of the user
	 *
	 * @var \Awyiss\Authorization\IdentityPermissionsInterface|null
	 */
	protected ?IdentityPermissionsInterface $identity = null;
	/**
	 * @var array<string|int, \Awyiss\Utility\Menu\MenuItem>
	 */
	protected array $items = [];
	/**
	 * @var int
	 */
	protected int $level;


	/**
	 * @param object|iterable $items
	 * @param array $config
	 * @param int $level
	 */
	public function __construct(object|iterable $items, array $config = [], int $level = 1) {
		$la_items = $items;
		$la_config = $config;

		if (!is_array($items)) {
			$la_items = (array)$items;
		}

		$this->level = $level;

		foreach ($la_items as $lx_identifier => $lx_item) {
			$lo_item = (object)$lx_item;

			// If the identifier is not a string and the item has an id, use the id as identifier
			if (!is_string($lx_identifier) && isset($lo_item->id)) {
				$lx_identifier = $lo_item->id;
			}

			// Make sure the item has an identifier
			if (!isset($lo_item->identifier)) {
				$lo_item->identifier = $lx_identifier;
			}

			$this->items[ $lx_identifier ] = new $la_config['menuItemClass']($lo_item, $la_config, $level);
		}

		if (isset($la_config['identity'])) {
			$this->identity = $la_config['identity'];

			//Make sure to not set the identity in the config to avoid confusion
			unset($la_config['identity']);
		}

		$this->setConfig($la_config);
	}


	/**
	 * @param array $entries
	 * @param string $identifier
	 * @param bool $determineVisibility
	 * @return void
	 * @throws \ReflectionException
	 */
	public function appendEntries(array $entries, string $identifier, bool $determineVisibility = true): void {
		$lo_item = $this->getItem($identifier);

		if (!$lo_item) {
			// If an item to append to is still not found, throw an exception
			throw new RuntimeException(sprintf('Cannot append entries to an unknown identifier. `%s` given.', $identifier));
		}

		if (!$entries) {
			throw new RuntimeException('Cannot append empty entries.');
		}

		$lo_subMenu = $lo_item->getChildren();
		if (!$lo_subMenu) {
			$lo_item->setChildren($entries);
		}
		else {
			$lo_subMenu->insertEntriesAfter($entries, null, false);
		}

		if ($determineVisibility && $this->identity) {
			//Only after all elements are updated, the visibility can be calculated
			$this->determineVisibility();
		}
	}


	/**
	 * @param mixed $menuData
	 * @return $this
	 * @throws \ReflectionException
	 * @see awyiss/config/menu-extension.schema.json
	 */
	public function extend(iterable|object $menuData): static {
		$la_menuData = (array)$menuData;

		foreach ($la_menuData['appendTo'] ?? [] as $ls_identifier => $lx_entries) {
			$this->appendEntries((array)$lx_entries, $ls_identifier, false);
		}

		foreach ($la_menuData['insertAfter'] ?? [] as $ls_identifier => $lx_entries) {
			$this->insertEntriesAfter((array)$lx_entries, $ls_identifier, false);
		}

		//Only after all elements are updated, the visibility can be calculated
		$this->determineVisibility();


		return $this;
	}


	/**
	 * Returns whether the menu an item with the given identifier.
	 *
	 * @param string|int $id
	 * @return bool
	 */
	public function hasItem(string|int $id): bool {
		return isset($this->items[ $id ]);
	}


	/**
	 * @param string|int $id
	 * @param bool $deep
	 * @return \Awyiss\Utility\Menu\MenuItem|null
	 */
	public function getItem(string|int $id, bool $deep = true): ?MenuItem {
		$la_items = $deep ? $this->items() : $this->items;
		foreach ($la_items as $lx_identifier => $lo_item) {
			if ($lx_identifier === $id) {
				return $lo_item;
			}
		}


		return null;
	}


	/**
	 * @return array<\Awyiss\Utility\Menu\MenuItem>
	 */
	public function getItems(): array {
		return $this->items;
	}


	/**
	 * @param array $entries
	 * @param string|null $identifier
	 * @param bool $determineVisibility
	 * @return void
	 * @throws \ReflectionException
	 */
	public function insertEntriesAfter(array $entries, ?string $identifier = null, bool $determineVisibility = true): void {
		if ($identifier && !isset($this->items[ $identifier ]) && !$this->getItem($identifier)) {
			throw new RuntimeException(sprintf('Cannot insert entries after an unknown identifier. `%s` given.', $identifier));
		}

		$lo_newMenu = new static($entries, $this->getConfig() + ['identity' => $this->identity], $this->level);

		if (!$identifier) {
			$this->items = $lo_newMenu->getItems() + $this->getItems();

			if ($determineVisibility && $this->identity) {
				//Only after all elements are updated, the visibility can be calculated
				$this->determineVisibility();
			}


			return;
		}

		if (!isset($this->items[ $identifier ])) {
			/** @var array<\Awyiss\Utility\Menu\MenuItem> $lo_items */
			$lo_items = $this->items();
			foreach ($lo_items as $lo_item) {
				$lo_children = $lo_item->getChildren();

				if (!$lo_children) {
					continue;
				}

				if ($lo_children->getItem($identifier, false)) {
					$lo_children->insertEntriesAfter($entries, $identifier, false);

					break;
				}
			}

			if ($determineVisibility && $this->identity) {
				//Only after all elements are updated, the visibility can be calculated
				$this->determineVisibility();
			}

			return;
		}

		$li_count = 0;
		$la_items = $this->getItems();
		foreach ($la_items as $lx_identifier => $lo_item) {
			if ($lx_identifier === $identifier) {
				break;
			}
			$li_count++;
		}

		$this->items = array_slice($la_items, 0, $li_count + 1, true) + $lo_newMenu->getItems() + array_slice($la_items, $li_count, null, true);

		if ($determineVisibility && $this->identity) {
			//Only after all elements are updated, the visibility can be calculated
			$this->determineVisibility();
		}
	}


	/**
	 * @return \Generator<string|int, \Awyiss\Utility\Menu\MenuItem>
	 */
	public function items(int $maxLevel = -1): Generator {
		foreach ($this->items as $lx_identifier => $lo_item) {
			yield $lx_identifier => $lo_item;

			if ($maxLevel !== -1 && $maxLevel <= $this->level) {
				continue;
			}

			foreach ($lo_item->children($maxLevel) as $lx_childIdentifier => $lo_child) {
				yield $lx_childIdentifier => $lo_child;
			}
		}
	}


	/**
	 * @param \Awyiss\Authorization\IdentityPermissionsInterface $identity
	 * @return $this
	 * @throws \ReflectionException
	 */
	public function setIdentity(IdentityPermissionsInterface $identity): static {
		foreach ($this->items() as $lo_item) {
			//Don't let MenuItem::setIdentity loop through nested children since $this->items() already iterates over ALL items
			$lo_item->setIdentity($identity, false);
		}

		//Only after all elements are updated, the visibility can be calculated
		$this->determineVisibility();


		return $this;
	}


	/**
	 * @return void
	 */
	public function determineVisibility(): void {
		foreach ($this->items as $lo_item) {
			$lo_item->determineVisibility(true);
		}
	}


	/**
	 * @return array<string|int, \Awyiss\Utility\Menu\MenuItem>
	 */
	public function toArray(): array {
		$la_items = [];

		/** @noinspection PhpLoopCanBeConvertedToArrayMapInspection */
		foreach ($this->items() as $lx_identifier => $lo_item) {
			$la_items[ $lx_identifier ] = $lo_item;
		}


		return $la_items;
	}
}
