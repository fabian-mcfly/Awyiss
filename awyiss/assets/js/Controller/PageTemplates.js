//noinspection JSUnusedGlobalSymbols

// Core SortableJS (without default plugins)
import Sortable from 'SortableJS/sortable';

export default class PageTemplatesController {
	/**
	 * The event handler instance.
	 * @type {EventHandler}
	 */
	eventHandler = window.eventHandler;

	constructor() {
		// In the overview, initialize the nested list handler
		if (document.querySelector('.PageTemplates.Overview')) {
			this.initializeNestedListHandler();
		}
		else if (document.querySelector('.PageTemplates.Form')) {
			this.initializeForm();
		}
	}

	/**
	 * Initialize the nested list handler
	 */
	initializeNestedListHandler() {
		window.nestedListHandler.setParentIdentifierAttribute('data-page-role-id');

		window.nestedListHandler.getOrder = function () {
			let order = {};

			// Select all lists
			const lists = document.querySelectorAll(this.selector);

			// Loop through each list
			lists.forEach(list => {
				// Get the data-page-role-id attribute of the list, or an empty string if it doesn't exist / is empty / equals false
				let pageRoleId = list.dataset.pageRoleId;
				if (!pageRoleId || pageRoleId === 'false') {
					pageRoleId = '';
				}

				// Get the order of the list items using the toArray method of SortableJS
				let items = list.sortable.toArray();

				if (!items.length) {
					return;
				}

				// Traverse the items and remove non-numeric characters from the start of the id
				items = items.map(item => {
					return parseInt(item.replace(/^\D+/g, ''));
				});

				// Add the items to the order object with the key
				order[pageRoleId] = items;
			});

			return order;
		}
	}

	/**
	 * Initialize the form
	 */
	initializeForm() {
		// Select the ContentAreaNew list and the Row element
		const contentAreaNew = document.querySelector('.ContentAreas');

		contentAreaNew.sortable = Sortable.create(contentAreaNew, {
			chosenClass: 'SortableChosen',
			fallbackOnBody: true,
			ghostClass: 'SortableGhost',
			invertSwap: true,
			preventOnFilter: false,
			swapThreshold: .4,
		});
	}
}

/**
 * Expose the class globally
 * @global
 * @type {PageTemplatesController}
 */
window.PageTemplatesController = PageTemplatesController;
