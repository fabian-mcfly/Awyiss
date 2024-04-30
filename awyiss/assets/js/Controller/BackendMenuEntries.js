// noinspection JSUnusedGlobalSymbols

export default class BackendMenuEntriesController {
	/**
	 * The event handler instance.
	 * @type {EventHandler}
	 */
	eventHandler = window.eventHandler;

	constructor() {
		window.nestedListHandler.getOrder = this.getNestedListOrder;

		window.eventHandler.add('input', function (event) {
			// Check if the event target is the insert_after_id select element
			if (event.target.name === 'insert_after_id') {
				// Get the parent_id select element
				const parentIdSelect = document.querySelector('select[name="parent_id"]');

				// Reset the parent_id select element to its first option
				parentIdSelect.selectedIndex = 0;
			}
		}, window, {}, 9);
	}

	/**
	 * This method returns an object with an arbitrary amount of objects,
	 * extracted and built from a nested DOM structure of lists and list items.
	 * The order of the items is determined by their position in the DOM.
	 * The order resets to 1 whenever an item has a unique "insert_after_id".
	 *
	 * @returns {Object} The order of the list items
	 */
	getNestedListOrder() {
		// Initialize a set to keep track of all insert_after_id values
		const insertAfterIdSet = new Set();

		// Initialize a null value to track the last static ID globally, reset at each level
		let lastStaticItemId = null;

		// Define the identifier for the list
		const listIdentifier = 'BackendMenuEntries';

		// Initialize an empty object to store the order
		let order = {};

		// Initialize a stack for assigning order
		const orderCounterStack = [1];

		// Define a function to recursively process list items
		const processListItem = (listItem, parentId = null) => {
			// Get the ID of the list item
			const id = listItem.id.replace(`${listIdentifier}-ListItem`, '');

			// Check if the ID is a custom ID
			const isCustom = id.match(/^\d+$/);

			// Determine the appropriate insert_after_id based on presence of a parent
			let insert_after_id;
			if (isCustom && !parentId) {
				insert_after_id = lastStaticItemId;
			}
			else {
				insert_after_id = null;
			}

			// If the item is custom, update the order object
			if (isCustom) {
				// If the insert_after_id is unique, reset the order to 1
				if (insert_after_id && !insertAfterIdSet.has(insert_after_id)) {
					orderCounterStack[orderCounterStack.length - 1] = 1;
					insertAfterIdSet.add(insert_after_id);
				}

				// Update the order object
				order[id] = {
					id: id,
					insertAfterId: insert_after_id,
					parentId: parentId,
					systemOrder: orderCounterStack[orderCounterStack.length - 1]++,
				};
			}
			else {
				// Update lastStaticItemId for subsequent items in the same level
				lastStaticItemId = id;
			}


			// Process nested items. Reset lastStaticItemId to ensure it's scoped to this level
			const previousLastStaticItemId = lastStaticItemId;
			lastStaticItemId = null;

			// Add a new counter for the nested level
			orderCounterStack.push(1);

			// Process nested items
			const nestedListItems = listItem.querySelectorAll(`:scope > .${listIdentifier}-List > .ListItem`);
			nestedListItems.forEach(nestedListItem => processListItem(nestedListItem, isCustom ? id : parentId));

			// Remove the counter for the nested level
			orderCounterStack.pop();

			// Restore lastStaticItemId after processing nested items
			lastStaticItemId = previousLastStaticItemId;
		};

		// Select and process top-level list items
		//noinspection JSUnresolvedReference
		const topLevelListItems = document.querySelectorAll(`${this.selector}.Level1 > .ListItem`);
		topLevelListItems.forEach(item => processListItem(item));

		return order;
	}
}

/**
 * Expose the class globally
 * @global
 * @type {BackendMenuEntriesController}
 */
window.BackendMenuEntriesController = BackendMenuEntriesController;
