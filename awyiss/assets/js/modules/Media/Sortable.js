// noinspection JSUnusedGlobalSymbols,NpmUsedModulesInstalled

import MultiSelect from 'MultiSelect';
import {Sortable as SortableJS} from '../../SortableJS/sortable.core.esm.js';

export default class Sortable {
	/**
	 * The default order of the media items
	 * @type {array}
	 */
	defaultOrder;
	/**
	 * The delete buttons
	 * @type {NodeListOf<Element>}
	 */
	deleteButtons;
	/**
	 * The detached items when dragging multiple selected items
	 * @type {HTMLElement[]}
	 */
	detachedItems = [];
	/**
	 * The event handler instance.
	 * @type {EventHandler}
	 */
	eventHandler = window.eventHandler
	/**
	 * The list of media items
	 * @type {HTMLElement}
	 */
	mediaList;
	/**
	 * The multi selection instance
	 * @type {MultiSelect}
	 */
	multiSelection;
	/**
	 * The overlay instance.
	 * @type {Overlay}
	 */
	overlay = null;
	/**
	 * A NodeList of all the save order buttons in the document.
	 * @property {NodeListOf<Element>}
	 */
	saveOrderButtons;
	/**
	 * A NodeList of all the use files buttons in the document.
	 * @property {NodeListOf<Element>}
	 */
	useFilesButtons;

	/**
	 * @param {HTMLElement} mediaList
	 * @param {object} settings
	 */
	constructor(mediaList, settings) {
		this.mediaList = mediaList;

		const defaultSettings = {
			deleteButtons: document.querySelectorAll('.Button-Delete'),
			saveOrderButtons: document.querySelectorAll('.Button-SaveSystemOrder'),
			useFilesButtons: document.querySelectorAll('.Button-UseFiles'),
		}

		for (const key in defaultSettings) {
			this[key] = settings.hasOwnProperty(key) ? settings[key] : defaultSettings[key];
		}

		// Init sorable on #Media-List
		this.initSortable(mediaList);

		this.multiSelection = new MultiSelect(mediaList, '.Media-ListItem', '.UploadQueue-Item');

		this.eventHandler.add('selectionChanged', this.selectionChanged.bind(this), mediaList);

		this.deleteButtons.forEach(button => {
			// If the element is a link, remove the href attribute to prevent the browser from navigating to the URL
			if (button.tagName === 'A' && button.hasAttribute('href')) {
				// Remember the original href attribute value
				// noinspection JSUnresolvedReference
				button.dataset.href = button.getAttribute('href');
				button.removeAttribute('href');
			}

			this.eventHandler.add('click', this.deleteItems.bind(this), button);
		});

		// Add an event listener to the save buttons
		this.saveOrderButtons.forEach(button => {
			this.eventHandler.add('click', this.saveSystemOrder.bind(this), button);
		});
	}

	/**
	 * Initialize the sortable on the selector
	 * @param {HTMLElement} element - The element to initialize the sortable on
	 */
	initSortable(element) {
		if (!element) {
			return;
		}

		element.sortable = SortableJS.create(element, {
			chosenClass: 'SortableChosen',
			dataIdAttr: 'id',
			fallbackOnBody: true,
			filter: '[data-sortable="false"], .UploadQueue-Item',
			ghostClass: 'SortableGhost',
			group: 'Media',
			//invertSwap: true,
			preventOnFilter: false,
			swapThreshold: .6,
			onStart: (event) => {
				return this.onStart(event);
			},
			onEnd: (event) => {
				return this.onEnd(event);
			},
		});

		window.eventHandler.add('uploadQueueProcessingStarted', () => {
			// noinspection JSUnresolvedReference
			element.sortable.option('disabled', true);
		}, element);

		window.eventHandler.add('uploadQueueProcessingFinished', () => {
			// noinspection JSUnresolvedReference
			element.sortable.option('disabled', false);

			if (!this.isDefaultOrder()) {
				// Save the order after the upload queue has finished processing in case there were upload errors
				// that could have caused gaps in the order
				this.saveSystemOrder();
			}

			this.toggleButtonState(this.multiSelection.getSelectedItems().length);
		}, element);

		// noinspection JSUnresolvedReference
		this.defaultOrder = element.sortable.toArray();
	}

	/**
	 * Event handler for when the sorting has started
	 */
	onStart() {
		if (this.multiSelection.selectionRectangle) {
			document.body.removeChild(this.multiSelection.selectionRectangle);
			this.multiSelection.selectionRectangle = null;
		}

		// If there are multiple items selected, remove every item but the one with the SortableChosen class from the dom
		this.detachedItems = [];
		const selectedItems = document.querySelectorAll('.Media-ListItem.Selected');
		if (selectedItems.length > 1) {
			selectedItems.forEach((item, index) => {
				if (!item.classList.contains('SortableChosen')) {
					this.detachedItems.push(selectedItems[index].parentNode.removeChild(selectedItems[index]));
				}
			});
		}
	}

	/**
	 * Event handler for when the sorting has ended
	 * @param {Event} event - The event object
	 */
	onEnd(event) {
		// If there are multiple items selected, reinsert the detached items back into the dom after the sorted item
		if (this.detachedItems.length > 0) {
			// noinspection JSUnresolvedReference
			const nextSibling = event.item.nextSibling;
			this.detachedItems.forEach(item => {
				// noinspection JSUnresolvedReference
				event.item.parentNode.insertBefore(item, nextSibling);
			});
		}

		// Items might have been removed from the list, so we need to get the current list of items
		const existingItems = this.mediaList.querySelectorAll('.Media-ListItem');

		// Enable or disable the save buttons based on the current order
		const isDefaultOrder = this.isDefaultOrder() || existingItems.length === 0;

		this.saveOrderButtons.forEach(button => {
			button.disabled = isDefaultOrder;
			button.classList.toggle('Button-Success', !isDefaultOrder);
		});

		if (existingItems.length === 0) {
			// If there are no items in the list, set the default order to an empty array
			this.isDefaultOrder(true);

			// Disable the delete buttons if there are no items in the list
			this.toggleButtonState(0);
		}
	}

	/**
	 * Check if the current order is the default order
	 * @returns {boolean}
	 */
	isDefaultOrder(reset = false) {
		// noinspection JSUnresolvedReference
		let elements = this.mediaList.sortable.toArray();
		elements = elements.map(id => {
			return id.indexOf('Media-ListItem') > -1 ? id : false;
		});
		elements = elements.filter(id => id !== false);

		const isDefaultOrder = JSON.stringify(this.defaultOrder) === JSON.stringify(elements);

		if (!isDefaultOrder && reset) {
			this.defaultOrder = elements;
		}

		return isDefaultOrder;
	}

	/**
	 * Delete the selected items by making a DELETE request to the server,
	 * containing the IDs of the selected items in the request body
	 */
	deleteItems() {
		let selectedItems = this.multiSelection.getSelectedItems();

		if (selectedItems.length === 0) {
			return;
		}

		let deleteIds = Array.from(selectedItems).map(element => {
			const id = element.getAttribute('id');
			return id.indexOf('Media-ListItem') > -1 ? parseInt(id.replace(/\D/g, '')) : -1;
		});

		deleteIds = deleteIds.filter(id => !isNaN(id) && id > 0);

		fetch(`/backend/${languageShortcode}/media/delete/`, {
			method: 'DELETE',
			headers: {
				'Accept': 'application/json',
				'Content-Type': 'application/json',
				'X-Requested-With': 'XMLHttpRequest',
			},
			body: JSON.stringify({
				ids: deleteIds,
				media_folder_id: this.mediaList.dataset.mediaFolderId,
			}),
		})
		.then(response => response.json()) // Parse the response as JSON
		.then(response => {
			if (!response.success) {
				throw new Error(response.message);
			}

			// Remove the selected items from the DOM
			selectedItems.forEach(item => {
				item.remove();
			});

			this.toggleButtonState(this.multiSelection.getSelectedItems().length);
		})
		.catch(error => {
			console.error('There has been a problem with the fetch operation:', error);
		});
	}

	/**
	 * Save the system order by making a POST request to the server
	 */
	saveSystemOrder() {
		const list = this.mediaList;

		// noinspection JSUnresolvedReference
		let order = list.sortable.toArray();
		order = order.map(id => {
			return id.indexOf('Media-ListItem') > -1 ? parseInt(id.replace(/\D/g, '')) : -1;
		});
		order = order.filter(id => !isNaN(id) && id > 0);

		// Add a class to the element to show that a save operation is in progress
		list.classList.add('FetchInProgress');

		// Add a class to the body to show that a save operation is in progress
		document.body.classList.add('FetchInProgress');

		// Make a POST request to the '/save-order' URL with the current order and the controller name
		fetch(`/backend/${languageShortcode}/media/save-system-order/`, {
			method: 'POST',
			headers: {
				'Accept': 'application/json',
				'Content-Type': 'application/json',
				'X-Requested-With': 'XMLHttpRequest',
			},
			body: JSON.stringify({
				order: order,
				media_folder_id: list.dataset.mediaFolderId,
			}),
		})
		.then(response => response.json()) // Parse the response as JSON
		.then(response => {
			// noinspection DuplicatedCode
			if (!response.success) {
				throw new Error(response.message);
			}

			// Disable the save button after successful POST request
			this.saveOrderButtons.forEach(button => {
				button.disabled = true;
				button.classList.remove('Button-Success');

				// Reset width and height of the button since disabled buttons have no pointer events
				// so the mouse leave event won't be triggered
				button.querySelector('.Hover').style.width = '';
				button.querySelector('.Hover').style.height = '';
			});

			// Update the default order to the current order
			this.isDefaultOrder(true);
		})
		.catch(error => {
			console.error('There has been a problem with the fetch operation:', error);
		}).finally(() => {
			// Remove the class from the element to show that the save operation is complete
			list.classList.remove('FetchInProgress');

			// Remove the class from the body to show that the save operation is complete
			document.body.classList.remove('FetchInProgress');
		});
	}

	/**
	 * Delete the selected items
	 * @param {CustomEvent} event
	 */
	selectionChanged(event) {
		const selectedItems = event.detail.selectedItems;

		this.toggleButtonState(selectedItems.length);
	};

	/**
	 * Toggle the delete buttons based on the number of selected items
	 * @param {int} selectedItemsLength
	 */
	toggleButtonState(selectedItemsLength) {
		this.useFilesButtons.forEach(button => {
			button.disabled = selectedItemsLength === 0 || !this.overlay.opener;
		});

		this.deleteButtons.forEach(button => {
			button.disabled = selectedItemsLength === 0;
		});
	}
}