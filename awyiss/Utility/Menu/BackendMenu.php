<?php declare(strict_types=1);


namespace Awyiss\Utility\Menu;


use Awyiss\Utility\Menu\Exception\MenuValidationException;


/**
 * A menu class that represents one level of items
 * in the backend.
 */
class BackendMenu extends Menu {
	/**
	 * Appending entries in the backend allows for a fallback to the 'system' identifier.
	 * This is useful when a custom menu entry is no longer present,
	 * but an entry in the database or a custom json file still refers to it.
	 *
	 * @inheritDoc
	 */
	public function appendEntries(array $entries, string $identifier, bool $determineVisibility = true): void {
		// Try to find the item with the given identifier
		if ($this->hasItem($identifier)) {
			parent::appendEntries($entries, $identifier, $determineVisibility);
			return;
		}

		$la_children = $this->toArray();
		if (isset($la_children[ $identifier ])) {
			$this->appendInNestedChildren($la_children[ $identifier ], $entries, $determineVisibility);
			return;
		}

		// If there's no 'system' item to fall back to, throw an exception
		if ($this->hasItem('system')) {
			parent::appendEntries($entries, 'system', $determineVisibility);
			return;
		}

		// If an item to append to is still not found, throw an exception
		throw new MenuValidationException(sprintf('Cannot append entries to an unknown identifier. `%s` given.', $identifier));
	}


	/**
	 * Inserting entries after a specific identifier
	 * allows for a fallback to the last known identifier.
	 * This is useful when a custom menu entry is no longer present,
	 * but an entry in the database or a custom json file still refers to it.
	 *
	 * @inheritDoc
	 */
	public function insertEntriesAfter(array $entries, ?string $identifier = null, bool $determineVisibility = true): void {
		/**
		 * If the identifier doesn't exist in the menu
		 * or any of its children, set it to the last
		 * known identifier.
		 */
		if ($identifier && !isset($this->items[ $identifier ]) && !$this->getItem($identifier)) {
			/** @noinspection PhpVariableNamingConventionInspection */
			$identifier = array_key_last($this->items);
		}

		parent::insertEntriesAfter($entries, $identifier, $determineVisibility);
	}


	/**
	 * @param \Awyiss\Utility\Menu\MenuItem $item
	 * @param array $entries
	 * @param bool $determineVisibility
	 * @return void
	 * @throws \ReflectionException
	 */
	protected function appendInNestedChildren(MenuItem $item, array $entries, bool $determineVisibility): void {
		$lo_subMenu = $item->getChildren();
		if (!$lo_subMenu) {
			$item->setChildren($entries);
		}
		else {
			$lo_subMenu->insertEntriesAfter($entries, null, false);
		}

		if ($determineVisibility && $this->identity) {
			//Only after all elements are updated, the visibility can be calculated
			$this->determineVisibility();
		}
	}
}
