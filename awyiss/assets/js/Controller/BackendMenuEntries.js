// noinspection JSUnusedGlobalSymbols


export default class BackendMenuEntriesController {
	/**
	 * The event handler instance.
	 * @type {EventHandler}
	 */
	eventHandler = window.eventHandler;

	constructor() {
		if (!document.body.classList.contains('BackendMenuEntriesController')) {
			return
		}

		if (document.body.classList.contains('OverviewAction')) {
			this.initOverview();
		}

		if (document.body.classList.contains('AddAction') || document.body.classList.contains('EditAction')) {
			this.initForm(document.querySelector('.BackendMenuEntries.Form'));
		}
	}

	/**
	 * Initialize the overview related functionality.
	 */
	initOverview() {
		window.nestedListHandler.getOrder = this.getNestedListOrder;
	}

	/**
	 * Initialize the form related functionality.
	 * @param {HTMLElement} form The form element
	 */
	initForm(form) {
		window.eventHandler.add('input', function (event) {
			// Check if the event target is the insertAfterId select element
			if (event.target.name === 'insertAfterId') {
				// Get the parentId select element
				const parentIdSelect = form.querySelector('select[name="parentId"]');

				// Reset the parentId select element to its first option
				parentIdSelect.selectedIndex = 0;
			}
		}, window, {}, 9);
		// phpcs:enable
	}

	/**
	 * Get the order of all list items
	 * The order resets to 1 whenever an item has a unique "insertAfterId".
	 *
	 * @returns {Object} The order of the list items
	 */
	getNestedListOrder() {
		// Initialize a set to keep track of all insertAfterId values
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

			// Determine the appropriate insertAfterId based on presence of a parent
			let insertAfterId;
			if (isCustom && !parentId) {
				insertAfterId = lastStaticItemId;
			}
			else {
				insertAfterId = null;
			}

			// If the item is custom, update the order object
			if (isCustom) {
				// If the insertAfterId is unique, reset the order to 1
				if (insertAfterId && !insertAfterIdSet.has(insertAfterId)) {
					orderCounterStack[orderCounterStack.length - 1] = 1;
					insertAfterIdSet.add(insertAfterId);
				}

				// Update the order object
				order[id] = {
					id: id,
					insertAfterId: insertAfterId,
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
