// noinspection JSUnusedGlobalSymbols

export default class AttributesController {
	/**
	 * The event handler instance.
	 * @type {EventHandler}
	 */
	eventHandler = window.eventHandler;

	constructor() {
		if (!document.body.classList.contains('AttributesController')) {
			return;
		}

		if (document.body.classList.contains('OverviewAction')) {
			this.initOverview();
		}
	}

	/**
	 * Initialize the overview
	 */
	initOverview() {
		window.nestedListHandler.setParentIdentifierAttribute('data-fieldset');

		window.nestedListHandler.getOrder = function () {
			let order = {};

			// Select all lists
			const lists = document.querySelectorAll(this.selector);

			// Loop through each list
			lists.forEach(list => {
				// Get the data-fieldset attribute of the list, or an empty string if it doesn't exist / is empty / equals false
				let fieldsetName = list.dataset.fieldset;
				if (!fieldsetName || fieldsetName === 'false') {
					fieldsetName = '';
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
				order[fieldsetName] = items;
			});

			return order;
		}
	}
}

/**
 * Expose the class globally
 * @global
 * @type {AttributesController}
 */
window.AttributesController = AttributesController;
