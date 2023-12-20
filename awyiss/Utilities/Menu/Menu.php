<?php declare(strict_types=1);


namespace Awyiss\Utilities\Menu;


use Awyiss\Authorization\IdentityPermissionsInterface;
use Cake\Core\InstanceConfigTrait;
use Generator;
use RuntimeException;


class Menu {
	use InstanceConfigTrait;


	protected array $_defaultConfig = [];
	protected ?IdentityPermissionsInterface $identity = NULL;
	/**
	 * @var array<string|int, MenuItem>
	 */
	protected array $items = [];
	/**
	 * @var int
	 */
	protected int $level;


	public function __construct(iterable | object $items, array $config = [], int $level = 1) {
		if (!is_array($items)) {
			$items = (array) $items;
		}

		$this->level = $level;

		foreach ($items as $identifier => $item) {
			if (!is_string($identifier) && isset($item->id)) {
				$identifier = $item->id;
			}

			$this->items[ $identifier ] = new MenuItem($item, $config, $level);
		}

		if (isset($config['identity'])) {
			$this->identity = $config['identity'];

			//Make sure to not set the identity in the config to avoid confusion
			unset($config['identity']);
		}

		$this->setConfig($config);
	}


	public function appendEntries(array $aa_entries, string $as_identifier, bool $determineVisibility = TRUE) {
		$lo_item = $this->getItem($as_identifier);

		if (!$lo_item) {
			throw new RuntimeException(sprintf('Cannot append entries to an unknown identifier. `%s` given.', $as_identifier));
		}

		if (!$aa_entries) {
			throw new RuntimeException('Cannot append empty entries.');
		}

		$lo_subMenu = $lo_item->getChildren();
		if (!$lo_subMenu) {
			$lo_item->setChildren($aa_entries);
		}
		else {
			$lo_subMenu->insertEntriesAfter($aa_entries);
		}

		if ($determineVisibility) {
			//Only after all elements are updated, the visibility can be calculated
			$this->determineVisibility();
		}
	}


	/**
	 * @param mixed $ax_menuData
	 *
	 * @return $this
	 * @see awyiss/config/menu-extension.schema.json
	 */
	public function extend(iterable | object $ax_menuData): static {
		$la_menuData = (array) $ax_menuData;

		foreach ($la_menuData['appendTo'] ?? [] as $ls_identifier => $lx_entries) {
			$this->appendEntries((array) $lx_entries, $ls_identifier, FALSE);
		}

		foreach ($la_menuData['insertAfter'] ?? [] as $ls_identifier => $lx_entries) {
			$this->insertEntriesAfter((array) $lx_entries, $ls_identifier, FALSE);
		}

		//Only after all elements are updated, the visibility can be calculated
		$this->determineVisibility();


		return $this;
	}


	public function getItem(string | int $id, bool $ab_deep = TRUE): ?MenuItem {
		$items = $ab_deep ? $this->items() : $this->items;
		foreach ($items as $identifier => $item) {
			if ($identifier === $id) {
				return $item;
			}
		}


		return NULL;
	}


	public function getItems(): array {
		return $this->items;
	}


	public function insertEntriesAfter(array $aa_entries, string $as_identifier = NULL, bool $determineVisibility = TRUE): void {
		if ($as_identifier) {
			if (!isset($this->items[ $as_identifier ]) && !$this->getItem($as_identifier)) {
				throw new RuntimeException(sprintf('Cannot insert entries after an unknown identifier. `%s` given.', $as_identifier));
			}
		}

		$lo_newMenu = new static($aa_entries, $this->getConfig() + ['identity' => $this->identity], $this->level);

		if (!$as_identifier) {
			$this->items = $lo_newMenu->getItems() + $this->getItems();

			if ($determineVisibility) {
				//Only after all elements are updated, the visibility can be calculated
				$this->determineVisibility();
			}


			return;
		}


		if (!isset($this->items[ $as_identifier ])) {
			/** @var MenuItem[] $lo_items */
			$lo_items = $this->items();
			foreach ($lo_items as $item) {
				$lo_children = $item->getChildren();

				if (!$lo_children) {
					continue;
				}

				if ($lo_children->getItem($as_identifier, FALSE)) {
					$lo_children->insertEntriesAfter($aa_entries, $as_identifier, FALSE);

					break;
				}
			}

			if ($determineVisibility) {
				//Only after all elements are updated, the visibility can be calculated
				$this->determineVisibility();
			}


			return;
		}

		$li_count = 0;
		$la_items = $this->getItems();
		foreach ($la_items as $identifier => $item) {
			if ($identifier === $as_identifier) {
				break;
			}
			$li_count++;
		}

		$this->items = array_slice($la_items, 0, $li_count + 1, TRUE) + $lo_newMenu->getItems() + array_slice($la_items, $li_count);

		if ($determineVisibility) {
			//Only after all elements are updated, the visibility can be calculated
			$this->determineVisibility();
		}
	}


	/**
	 * @return Generator|MenuItem
	 */
	public function items(int $maxLevel = -1): Generator {
		foreach ($this->items as $identifier => $item) {
			yield $identifier => $item;

			foreach ($item->children($maxLevel) as $childIdentifier => $child) {
				yield $childIdentifier => $child;
			}
		}
	}


	public function setIdentity(IdentityPermissionsInterface $identity): static {
		foreach ($this->items() as $item) {
			//Don't let MenuItem::setIdentity loop through nested children since $this->items() already iterates over ALL items
			$item->setIdentity($identity, FALSE);
		}

		//Only after all elements are updated, the visibility can be calculated
		$this->determineVisibility();


		return $this;
	}


	public function determineVisibility(): void {
		foreach ($this->items as $item) {
			$item->determineVisibility(TRUE);
		}
	}


	public function toArray(): array {
		$items = [];

		foreach ($this->items() as $identifier => $item) {
			$items[ $identifier ] = $item;
		}


		return $items;
	}
}
