<?php declare(strict_types=1);


namespace Awyiss\Utility\Menu;


use Awyiss\Authorization\IdentityPermissionsInterface;
use Cake\Core\InstanceConfigTrait;
use Generator;
use RuntimeException;


/**
 * A menu class that represents one level of items
 */
class Menu {
	use InstanceConfigTrait;


	protected array $_defaultConfig = [];
	protected ?IdentityPermissionsInterface $identity = null;
	/**
	 * @var array<string|int, MenuItem>
	 */
	protected array $items = [];
	/**
	 * @var int
	 */
	protected int $level;


	/**
	 * @param object|iterable $ax_items
	 * @param array $aa_config
	 * @param int $ai_level
	 * @throws \ReflectionException
	 */
	public function __construct(object|iterable $ax_items, array $aa_config = [], int $ai_level = 1) {
		$la_items = $ax_items;
		if (!is_array($ax_items)) {
			$la_items = (array)$ax_items;
		}

		$this->level = $ai_level;

		foreach ($la_items as $lx_identifier => $lo_item) {
			if (!is_string($lx_identifier) && isset($lo_item->id)) {
				$lx_identifier = $lo_item->id;
			}

			$this->items[ $lx_identifier ] = new MenuItem($lo_item, $aa_config, $ai_level);
		}

		if (isset($aa_config['identity'])) {
			$this->identity = $aa_config['identity'];

			//Make sure to not set the identity in the config to avoid confusion
			unset($aa_config['identity']);
		}

		$this->setConfig($aa_config);
	}


	/**
	 * @param array $aa_entries
	 * @param string $as_identifier
	 * @param bool $ab_determineVisibility
	 * @return void
	 * @throws \ReflectionException
	 */
	public function appendEntries(array $aa_entries, string $as_identifier, bool $ab_determineVisibility = true): void {
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

		if ($ab_determineVisibility) {
			//Only after all elements are updated, the visibility can be calculated
			$this->determineVisibility();
		}
	}


	/**
	 * @param mixed $ax_menuData
	 * @return $this
	 * @throws \ReflectionException
	 * @see awyiss/config/menu-extension.schema.json
	 */
	public function extend(iterable|object $ax_menuData): static {
		$la_menuData = (array)$ax_menuData;

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
	 * @param string|int $ax_id
	 * @param bool $ab_deep
	 * @return \Awyiss\Utility\Menu\MenuItem|null
	 */
	public function getItem(string|int $ax_id, bool $ab_deep = true): ?MenuItem {
		$la_items = $ab_deep ? $this->items() : $this->items;
		foreach ($la_items as $lx_identifier => $lo_item) {
			if ($lx_identifier === $ax_id) {
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
	 * @param array $aa_entries
	 * @param string|null $as_identifier
	 * @param bool $ab_determineVisibility
	 * @return void
	 * @throws \ReflectionException
	 */
	public function insertEntriesAfter(array $aa_entries, ?string $as_identifier = null, bool $ab_determineVisibility = true): void {
		if ($as_identifier) {
			if (!isset($this->items[ $as_identifier ]) && !$this->getItem($as_identifier)) {
				throw new RuntimeException(sprintf('Cannot insert entries after an unknown identifier. `%s` given.', $as_identifier));
			}
		}

		$lo_newMenu = new static($aa_entries, $this->getConfig() + ['identity' => $this->identity], $this->level);

		if (!$as_identifier) {
			$this->items = $lo_newMenu->getItems() + $this->getItems();

			if ($ab_determineVisibility) {
				//Only after all elements are updated, the visibility can be calculated
				$this->determineVisibility();
			}


			return;
		}


		if (!isset($this->items[ $as_identifier ])) {
			/** @var array<MenuItem> $lo_items */
			$lo_items = $this->items();
			foreach ($lo_items as $lo_item) {
				$lo_children = $lo_item->getChildren();

				if (!$lo_children) {
					continue;
				}

				if ($lo_children->getItem($as_identifier, false)) {
					$lo_children->insertEntriesAfter($aa_entries, $as_identifier, false);

					break;
				}
			}

			if ($ab_determineVisibility) {
				//Only after all elements are updated, the visibility can be calculated
				$this->determineVisibility();
			}


			return;
		}

		$li_count = 0;
		$la_items = $this->getItems();
		foreach ($la_items as $lx_identifier => $lo_item) {
			if ($lx_identifier === $as_identifier) {
				break;
			}
			$li_count++;
		}

		$this->items = array_slice($la_items, 0, $li_count + 1, true) + $lo_newMenu->getItems() + array_slice($la_items, $li_count);

		if ($ab_determineVisibility) {
			//Only after all elements are updated, the visibility can be calculated
			$this->determineVisibility();
		}
	}


	/**
	 * @return Generator|MenuItem
	 */
	public function items(int $ai_maxLevel = -1): Generator {
		foreach ($this->items as $lx_identifier => $lo_item) {
			yield $lx_identifier => $lo_item;

			foreach ($lo_item->children($ai_maxLevel) as $lx_childIdentifier => $lo_child) {
				yield $lx_childIdentifier => $lo_child;
			}
		}
	}


	/**
	 * @param \Awyiss\Authorization\IdentityPermissionsInterface $ao_identity
	 * @return $this
	 * @throws \ReflectionException
	 */
	public function setIdentity(IdentityPermissionsInterface $ao_identity): static {
		foreach ($this->items() as $lo_item) {
			//Don't let MenuItem::setIdentity loop through nested children since $this->items() already iterates over ALL items
			$lo_item->setIdentity($ao_identity, false);
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
	 * @return array
	 */
	public function toArray(): array {
		$la_items = [];

		foreach ($this->items() as $lx_identifier => $lo_item) {
			$la_items[ $lx_identifier ] = $lo_item;
		}


		return $la_items;
	}
}
