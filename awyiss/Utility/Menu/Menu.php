<?php declare(strict_types=1);


namespace Awyiss\Utility\Menu;


use Awyiss\Authorization\IdentityPermissionsInterface;
use Awyiss\Utility\Menu\Exception\MenuValidationException;
use Cake\Core\InstanceConfigTrait;
use Generator;


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
	protected array $_defaultConfig = []; // phpcs:ignore
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
		if (!is_array($items)) {
			$items = (array)$items;
		}

		$this->level = $level;

		foreach ($items as $identifier => $item) {
			$item = (object)$item;

			// If the identifier is not a string and the item has an id, use the id as identifier
			if (!is_string($identifier) && isset($item->id)) {
				$identifier = $item->id;
			}

			// Make sure the item has an identifier
			if (!isset($item->identifier)) {
				$item->identifier = $identifier;
			}

			/** @uses \Awyiss\Utility\Menu\MenuItem */
			$this->items[ $identifier ] = new $config['menuItemClass']($item, $config, $level);
		}

		if (isset($config['identity'])) {
			$this->identity = $config['identity'];

			//Make sure to not set the identity in the config to avoid confusion
			unset($config['identity']);
		}

		$this->setConfig($config);
	}


	/**
	 * @param array $entries
	 * @param string $identifier
	 * @param bool $determineVisibility
	 * @return void
	 * @throws \ReflectionException
	 */
	public function appendEntries(array $entries, string $identifier, bool $determineVisibility = true): void {
		$item = $this->getItem($identifier);

		if (!$item) {
			// If an item to append to is still not found, throw an exception
			throw new MenuValidationException(sprintf('Cannot append entries to an unknown identifier. `%s` given.', $identifier));
		}

		if (!$entries) {
			throw new MenuValidationException('Cannot append empty entries.');
		}

		$subMenu = $item->getChildren();
		if (!$subMenu) {
			$item->setChildren($entries);
		}
		else {
			$subMenu->insertEntriesAfter($entries, null, false);
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
	 * @see /awyiss/config/menu-extension.schema.json
	 */
	public function extend(iterable|object $menuData): static {
		$menuData = (array)$menuData;

		foreach ($menuData['appendTo'] ?? [] as $identifier => $entries) {
			$this->appendEntries((array)$entries, $identifier, false);
		}

		foreach ($menuData['insertAfter'] ?? [] as $identifier => $entries) {
			$this->insertEntriesAfter((array)$entries, $identifier, false);
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
		$items = $deep ? $this->items() : $this->items;
		foreach ($items as $identifier => $item) {
			if ($identifier === $id) {
				return $item;
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
			throw new MenuValidationException(sprintf('Cannot insert entries after an unknown identifier. `%s` given.', $identifier));
		}

		$newMenu = new static($entries, $this->getConfig() + ['identity' => $this->identity], $this->level);

		if (!$identifier) {
			$this->items = $newMenu->getItems() + $this->getItems();

			if ($determineVisibility && $this->identity) {
				//Only after all elements are updated, the visibility can be calculated
				$this->determineVisibility();
			}


			return;
		}

		if (!isset($this->items[ $identifier ])) {
			/** @var array<\Awyiss\Utility\Menu\MenuItem> $items */
			$items = $this->items();
			foreach ($items as $item) {
				$children = $item->getChildren();

				if (!$children) {
					continue;
				}

				if ($children->getItem($identifier, false)) {
					$children->insertEntriesAfter($entries, $identifier, false);

					break;
				}
			}

			if ($determineVisibility && $this->identity) {
				//Only after all elements are updated, the visibility can be calculated
				$this->determineVisibility();
			}

			return;
		}

		$count = 0;
		$items = $this->getItems();
		foreach ($items as $itemIdentifier => $item) {
			if ($itemIdentifier === $identifier) {
				break;
			}
			$count++;
		}

		$this->items = array_slice($items, 0, $count + 1, true) + $newMenu->getItems() + array_slice($items, $count, null, true);

		if ($determineVisibility && $this->identity) {
			//Only after all elements are updated, the visibility can be calculated
			$this->determineVisibility();
		}
	}


	/**
	 * @return \Generator<string|int, \Awyiss\Utility\Menu\MenuItem>
	 */
	public function items(int $maxLevel = -1): Generator {
		foreach ($this->items as $identifier => $item) {
			yield $identifier => $item;

			if ($maxLevel !== -1 && $maxLevel <= $this->level) {
				continue;
			}

			foreach ($item->children($maxLevel) as $childIdentifier => $child) {
				yield $childIdentifier => $child;
			}
		}
	}


	/**
	 * @param \Awyiss\Authorization\IdentityPermissionsInterface $identity
	 * @return $this
	 * @throws \ReflectionException
	 */
	public function setIdentity(IdentityPermissionsInterface $identity): static {
		foreach ($this->items() as $item) {
			//Don't let MenuItem::setIdentity loop through nested children since $this->items() already iterates over ALL items
			$item->setIdentity($identity, false);
		}

		//Only after all elements are updated, the visibility can be calculated
		$this->determineVisibility();


		return $this;
	}


	/**
	 * @return void
	 */
	public function determineVisibility(): void {
		foreach ($this->items as $item) {
			$item->determineVisibility(true);
		}
	}


	/**
	 * @return array<string|int, \Awyiss\Utility\Menu\MenuItem>
	 */
	public function toArray(): array {
		$items = [];

		/** @noinspection PhpLoopCanBeConvertedToArrayMapInspection */
		foreach ($this->items() as $identifier => $item) {
			$items[ $identifier ] = $item;
		}


		return $items;
	}
}
