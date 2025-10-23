// noinspection JSUnusedGlobalSymbols

export default class MenuEntriesController {
	/**
	 * The event handler instance.
	 * @type {EventHandler}
	 */
	eventHandler = window.eventHandler;

	constructor() {
		if (!document.body.classList.contains('MenuEntriesController')) {
			return;
		}

		if (document.body.classList.contains('OverviewAction')) {
			this.initOverview();
		}
	}

	/**
	 * Initialize the overview logic
	 * @returns {void}
	 */
	initOverview() {
		const pagesLists = document.querySelectorAll('ul.Pages-List');

		pagesLists.forEach(element => {
			element.removeAttribute('data-sortable');
		});

		window.nestedListHandler.initSortable(pagesLists, {
			groupName: {
				name: 'MenuEntries-List',
				pull: 'clone',
				put: false,
			},
			onEnd: function (event) {
				if (event.to.matches('.Pages-List')) {
					this.onEnd(event);

					// Disable the save buttons as the order is the default order
					this.saveOrderButtons.forEach(button => {
						button.disabled = true;
						button.classList.toggle('Button-Success', false);
					});

					return false;
				}

				if (event.to.matches('.MenuEntries-List')) {
					const selector = '.MenuEntries-List.Level1';

					this.initSortable(event.item.querySelectorAll('.NestedList'), {
						groupName: 'MenuEntries-List',
					});

					this.saveSystemOrder(document.querySelector(selector)).then(() => {
						const list = document.querySelector(selector);
						const menuId = list.dataset.menuId;
						const url = `${baseUrl}backend/${languageShortcode}/menu-entries/overview/menu-id:${menuId}`;

						list.classList.add('FetchInProgress');

						// Add a class to the body to show that a save operation is in progress
						document.body.classList.add('FetchInProgress');

						fetch(url, {
							method: 'GET',
							headers: {
								'X-Requested-With': 'XMLHttpRequest',
							},
						})
						.then(response => {
							if (response.ok) {
								return response.text();
							}
						})
						.then(response => {
							const parser = new DOMParser();
							const newDocument = parser.parseFromString(response, 'text/html');

							const newMenuEntries = newDocument.querySelector(selector);
							const oldMenuEntries = document.querySelector(selector);

							// Replace the old menu entries with the new menu entries
							oldMenuEntries.replaceWith(newMenuEntries);

							// Initialize the nested list for the replaced menu entries
							this.initList(document.querySelector(selector));
						})
						.catch(error => {
							console.error('There has been a problem with the fetch operation:', error);
						})
						.finally(() => {
							// Remove the class from the element(s) to show that the save operation is complete
							document.querySelectorAll('.MenuEntries-List').forEach(element => element.classList.remove('FetchInProgress'));

							// Remove the class from the body to show that the save operation is complete
							document.body.classList.remove('FetchInProgress');
						});
					});
				}

				this.onEnd(event);
			},
			sort: false,
		});

		window.nestedListHandler.saveOrderButtonHandler = function (event) {
			event.preventDefault();

			this.saveSystemOrder(document.querySelector('.MenuEntries-List.Level1'));
		}

		window.nestedListHandler.getOrder = function () {
			let order = {};

			const newEntries = {};

			const rootList = document.querySelector('.MenuEntries-List.Level1');
			let lists = [rootList];
			lists.push(...rootList.querySelectorAll('.NestedList'));

			// Loop through each list
			lists.forEach(list => {
				// Get the parent list item with class .ListItem
				const parentListItem = list.closest('.ListItem');

				// Get the order of the list items using the toArray method of SortableJS
				let items = list.sortable.toArray();

				if (!items.length) {
					return;
				}

				// Traverse the items and remove non-numeric characters from the start of the id
				items = items.map(item => {
					if (item.startsWith('Pages-ListItem')) {
						const element = rootList.querySelector(`#${item}`);
						if (element) {
							const newId = Math.floor(Math.random() * 1000000);

							// Create a new random ID for the element
							element.id = `Pages-ListItemNew${newId}`;

							newEntries['New' + newId] = {
								menu_id: parseInt(rootList.dataset.menuId),
								title: element.dataset.title,
								link: element.dataset.link,
								active: element.classList.contains('Inactive') ? 0 : 1,
								language_shortcode: languageShortcode,
							};

							return 'New' + newId;
						}
						else {
							return item;
						}
					}

					return parseInt(item.replace(/^\D+/g, ''));
				});

				// If there is no parent list item, add the items to the order object with a null key
				if (!parentListItem) {
					// Add the items to the order object with a null key
					order[0] = items;

					return;
				}

				// Remove all non-numeric characters from the start of the string
				let parentId = parentListItem.id.replace(/^\D+/g, '');

				if (parentListItem.id.startsWith('Pages-ListItem')) {
					parentId = 'New' + parentId;
				}

				// Add the items it the order object with the parent ID as the key
				order[parentId] = items;
			});

			return {
				order: order,
				new_entries: newEntries,
			};
		}

	}
}

/**
 * Expose the class globally
 * @global
 * @type {MenuEntriesController}
 */
window.MenuEntriesController = MenuEntriesController;
