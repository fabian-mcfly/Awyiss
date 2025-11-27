//noinspection JSUnusedGlobalSymbols

// Core SortableJS (without default plugins)
import Sortable from 'SortableJS/sortable';

export default class PageTemplatesController {
	/**
	 * The event handler instance.
	 * @type {EventHandler}
	 */
	eventHandler = window.eventHandler;
	/**
	 * The observer instance.
	 * @type {Observer}
	 */
	observer = window.observer;

	constructor() {
		if (!document.body.classList.contains('PageTemplatesController')) {
			return;
		}

		// In the overview, initialize the nested list handler
		if (document.body.classList.contains('OverviewAction')) {
			this.initializeNestedListHandler();
		}

		if (document.body.classList.contains('AddAction') || document.body.classList.contains('EditAction')) {
			this.initForm(document.querySelector('.PageTemplates.Form'));
		}
	}

	/**
	 * Initialize the nested list handler
	 * @param {HTMLElement} form The form element
	 */
	initializeNestedListHandler(form) {
		window.nestedListHandler.setParentIdentifierAttribute('data-page-role-id');

		window.nestedListHandler.getOrder = function () {
			let order = {};

			// Select all lists
			const lists = form.querySelectorAll(this.selector);

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
	 *
	 * @param {HTMLElement} form The form element
	 */
	initForm(form) {
		this.observer.addObserver(this.observeMutations.bind(this), form);

		this.initSortable(form);
	}

	/**
	 * Initialize SortableJS on the content areas list
	 * @param {HTMLElement} form The form element
	 */
	initSortable(form) {
		// Select the ContentAreaNew list and the Row element
		const contentAreas = form.querySelectorAll('.ContentAreas-List');
		contentAreas.forEach(contentArea => {
			if (contentArea.dataset.sortableInitialized === 'true') {
				return;
			}

			contentArea.dataset.sortableInitialized = 'true';

			contentArea.sortable = Sortable.create(contentArea, {
				chosenClass: 'SortableChosen',
				direction: 'vertical',
				group: 'ContentAreas',
				ghostClass: 'SortableGhost',
				invertSwap: true,
				preventOnFilter: false,
				swapThreshold: .9,
				onEnd: (event) => {
					const target = event.item;
					const list = event.to;

					setTimeout(() => {
						const checkbox = target.querySelector('input[type="checkbox"]');
						checkbox.checked = list.classList.contains('ContentAreas-List-Assigned');
					}, 50);
				}
			});
		});
	}

	/**
	 * Observe mutations in the DOM and initialize the form if necessary.
	 *
	 * @param {MutationRecord} mutation - The mutation to observe.
	 */
	observeMutations(mutation) {
		if (!mutation.addedNodes.length > 0) {
			return;
		}

		mutation.addedNodes.forEach((node) => {
			if (node.nodeType !== Node.ELEMENT_NODE) {
				return;
			}

			const selector = '.ContentAreas-List';
			if (node.matches(selector) || node.querySelectorAll(selector).length) {
				this.initSortable();
			}
		});
	}
}

/**
 * Expose the class globally
 * @global
 * @type {PageTemplatesController}
 */
window.PageTemplatesController = PageTemplatesController;
