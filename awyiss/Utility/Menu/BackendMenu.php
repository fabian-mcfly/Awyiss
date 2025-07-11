<?php declare(strict_types=1);


namespace Awyiss\Utility\Menu;


use RuntimeException;


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
		// If it does not exist, use 'system' as the identifier
		if (!$this->hasItem($identifier)) {
			if (!$this->hasItem('system')) {
				// If an item to append to is still not found, throw an exception
				throw new RuntimeException(sprintf('Cannot append entries to an unknown identifier. `%s` given.', $identifier));
			}

			/** @noinspection PhpVariableNamingConventionInspection */
			$identifier = 'system';
		}

		parent::appendEntries($entries, $identifier, $determineVisibility);
	}
}
