//noinspection JSUnusedGlobalSymbols

// Core SortableJS (without default plugins)
// noinspection NpmUsedModulesInstalled
import Sortable from 'SortableJS/sortable';

/**
 * Class to handle nested list sorting using SortableJS
 */
export default class NestedListHandler {
	/**
	 * Whether the event to handle the toggle button click has been bound
	 * @type {boolean}
	 */
	static handleToggleButtonClickBound = false;

	/**
	 * The event handler instance.
	 * @type {EventHandler}
	 */
	eventHandler = window.eventHandler;
	/**
	 * @property {string|Function|null} groupIdentifierAttribute - The attribute used to identify the grouping of lists
	 *  or a function that returns the group identifier.
	 */
	groupIdentifierAttribute = null; // Default value
	/**
	 * @property {string} parentIdentifierAttribute - The attribute used to identify the parent.
	 */
	parentIdentifierAttribute = 'id'; // Default value
	/**
	 * States of the nested lists (Expanded or Collapsed).
	 */
	nestedListStates = {};
	/**
	 * @property {string} selector - The selector for the nested list.
	 */
	selector;
	/**
	 * @property {Sortable} sortable - The SortableJS instance for the nested list.
	 */
	sortable;
	/**
	 * @property {NodeListOf<Element>} saveOrderButtons - A NodeList of all the save order buttons in the document.
	 */
	saveOrderButtons;
	/**
	 * @property {Array} defaultOrder - An array to store the default order of the list items.
	 * It will be populated with the ids of the list items when the SortableJS instance is created.
	 */
	defaultOrder;
	/**
	 * Timeout to disallow the auto scroll of the footer after drag start and end
	 * @property {number} scrollTimeout
	 */
	scrollTimeout;

	/**
	 * Constructor
	 * @param {string} selector - The selector for the nested list
	 */
	constructor(selector) {
		this.selector = selector;
		this.saveOrderButtons = document.querySelectorAll('.Button-SaveSystemOrder');

		const elements = document.querySelectorAll(`${this.selector}.Level1`);
		if (!elements.length) {
			return;
		}

		// Load all nested list states from localStorage
		this.nestedListStates = JSON.parse(localStorage.getItem('nestedListStates')) || {};

		// Loop through each nested list element
		elements.forEach(element => {
			this.initList(element);
		});

		// Add an event listener to the save buttons
		this.saveOrderButtons.forEach(button => {
			this.eventHandler.add('click', (event) => this.saveOrderButtonHandler(event), button);
		});
	}

	/**
	 * Initialize the nested list
	 *
	 * @param {HTMLElement} element - The nested list element
	 */
	initList(element) {
		// Check if the "nestable" data attribute is set to "false"
		if (element.dataset.nestable !== 'false') {
			// Add a child list to each list item that doesn't already have one
			this.addEmptyChildLists(element.querySelectorAll(`.ListItem`));
		}

		if (!element.classList.contains('NestedList-Compact')) {
			const items = element.querySelectorAll(`.ListItem`);

			// Add toggle buttons to all list items
			this.addToggleButtons(items);

			if (!NestedListHandler.handleToggleButtonClickBound) {
				NestedListHandler.handleToggleButtonClickBound = true;

				// Attach the event handler to the window
				this.eventHandler.add('click', this.handleToggleButtonClick.bind(this));
			}
		}

		// Check if the "sortable" data attribute is set to "false"
		if (element.dataset.sortable !== 'false') {
			this.defaultOrder = this.getOrderWithLevels();

			// Create an array of all nested list elements, including the current element
			let elements = [element];
			elements.push(...element.querySelectorAll('.NestedList'));

			// Initialize SortableJS
			this.initSortable(elements, {
				groupName: element.id,
				handle: element.classList.contains('NestedList-Compact') ? '.ListItem-Inner' : '.SortableHandle',
			});
		}
	}

	/**
	 * Initialize the sortable list
	 * @param {NodeListOf<Element>} elements - The list elements to initialize
	 * @param {Object} options - The options for the SortableJS instance
	 */
	initSortable(elements, options) {
		if (!elements.length) {
			return;
		}

		// Loop through each nested list element and initialize SortableJS
		elements.forEach(element => {
			if (options.handle === '.SortableHandle') {
				// Add handle to each list item in the current element
				Array.from(element.children).forEach(item => {
					if (item.dataset.sortable === 'false' || !options.handle) {
						return;
					}

					// Create a new handle
					const handle = document.createElement('div');
					handle.className = 'SortableHandle';
					handle.textContent = '::'; // Or any other content you want the handle to have

					// Select the .ListItem-Inner child of the list item
					const listItemInner = item.querySelector('.ListItem-Inner');
					if (listItemInner) {
						// Prepend the handle to the .ListItem-Inner child
						listItemInner.prepend(handle);
					}
				});
			}

			// noinspection JSUnresolvedReference
			const groupName = element.dataset.sortableGroup ?? options.groupName;

			element.sortable = Sortable.create(element, {
				chosenClass: 'SortableChosen',
				//direction: 'vertical',
				dataIdAttr: 'id',
				filter: '[data-sortable="false"]',
				ghostClass: 'SortableGhost',
				group: groupName,
				handle: options.handle || false,
				invertSwap: true,
				preventOnFilter: false,
				sort: options.sort ?? true,
				swapThreshold: .9,
				onAdd: (event) => {
					return options.onAdd ? options.onAdd(event) : this.onAdd(event);
				},
				onEnd: (event) => {
					return options.onEnd ? options.onEnd(event) : this.onEnd(event);
				},
				onMove: (event) => {
					return options.onMove ? options.onMove(event) : this.onMove(event);
				},
				onRemove: (event) => {
					return options.onRemove ? options.onRemove(event) : this.onRemove(event);
				},
				onStart: (event) => {
					return options.onStart ? options.onStart(event) : this.onStart(event);
				},
			});
		});
	}

	/**
	 * Adjust the scroll position based on the initial scroll position and the difference in position of the dragged element
	 * @param {HTMLElement} item - The item being dragged
	 * @param {boolean} isStart - Whether the drag operation has started or ended
	 */
	adjustScrollPosition(item, isStart) {
		clearTimeout(this.scrollTimeout);
		window.isAdjustingScroll = true;

		// Calculate the current position of the dragged element
		const currentElementPos = item.getBoundingClientRect().top + window.scrollY;

		// Calculate the difference in position of the dragged element
		// noinspection JSUnresolvedReference
		const elementPosDiff = currentElementPos - item.dragData.elementPos;

		// Adjust the scroll position based on the initial scroll position and the difference in position of the dragged element
		// noinspection JSUnresolvedReference
		window.scrollTo(0, item.dragData.scrollPos + elementPosDiff);

		// If 300ms have not passed since the start or end of the drag operation, call adjustScrollPosition again on the next animation frame
		// noinspection JSUnresolvedReference
		const referenceTime = item.dragData.time;
		if (Date.now() - referenceTime < 300) {
			requestAnimationFrame(() => this.adjustScrollPosition(item, isStart));
		}

		this.scrollTimeout = setTimeout(() => {
			window.isAdjustingScroll = false;
		}, 300);
	}

	/**
	 * Add a child list to each list item that doesn't already have one
	 * @param {NodeListOf<Element>} elements - The list items
	 */
	addEmptyChildLists(elements) {
		elements.forEach(element => {
			if (element.closest('ul').dataset.receivable === 'false') {
				return;
			}

			let childList = Array.from(element.children).find(child => child.tagName === 'UL');

			if (!childList) {
				childList = document.createElement('ul');
				element.appendChild(childList);

				// Copy classes from parent list to child list
				const parentList = element.parentElement;
				childList.className = parentList.className;

				// Increase the "LevelX" class by one
				const levelMatch = parentList.className.match(/Level(\d+)/);
				if (levelMatch) {
					const currentLevel = parseInt(levelMatch[1]);
					const newLevel = currentLevel + 1;
					childList.classList.remove(`Level${currentLevel}`);
					childList.classList.add(`Level${newLevel}`);
				}
			}
		});
	}

	/**
	 * Add toggle buttons to all list items
	 *
	 * @param {NodeListOf<Element>} elements - The list items
	 */
	addToggleButtons(elements) {
		elements.forEach(element => {
			// Check if the list element has a direct nested list child
			const childList = Array.from(element.children).find(child => child.tagName === 'UL');

			// If no child list, return
			if (!childList) {
				return;
			}

			// Create a new button
			const button = document.createElement('div');
			button.textContent = 'Toggle';
			button.classList.add('NestedListToggle');

			// Set the type attribute to 'button' to prevent form submission
			button.type = 'button';

			// Select the .ListItem-Inner child of the list element
			const listItemInner = element.querySelector('.ListItem-Inner');
			if (listItemInner) {
				// Prepend the handle to the .ListItem-Inner child
				listItemInner.prepend(button);
			}

			// Set the initial state
			const state = this.nestedListStates[element.id];

			// Check the state of the child list and the stored state in localStorage
			if (childList.children.length === 0) {
				// If the child list is empty, add the 'Hidden' class to the button
				button.classList.add('Hidden');
			}
			else {
				// noinspection JSUnresolvedReference
				if (state === 'Collapsed' || (!state && element.dataset.collapsedInitial === 'true')) {
					// If the stored state is 'Collapsed', add the 'Collapsed' class to both the button and the child list
					button.classList.add('Collapsed');
					childList.classList.add('Collapsed');
				}
					// If the stored state is not 'Collapsed' and the child list is not empty, remove the 'Collapsed' class from both the button and the child list
				// unless the list element has the data attribute "collapsedInitial" set to "true"
				else {
					// noinspection JSUnresolvedReference
					if (!element.dataset.collapsedInitial) {
						button.classList.remove('Collapsed');
						childList.classList.remove('Collapsed');
					}
				}
			}
		});
	}

	/**
	 * Proxy method for the save method, so the save method can be overridden
	 * @param {Event} event - The event object
	 */
	saveOrderButtonHandler(event) {
		event.preventDefault();
		// noinspection JSIgnoredPromiseFromCall
		this.saveSystemOrder();
	}

	/**
	 * Event handler for when an item is added to the list
	 * @param {Event} event - The event object
	 */
	onAdd(event) {
		// Check if the item being added has the `data-nestable="false"` attribute
		// and if the list it's being added to does not have the `Level1` class
		// noinspection JSUnresolvedReference
		if (event.item.dataset.nestable === 'false' && !event.to.classList.contains('Level1')) {
			// If both conditions are met, revert the move
			// noinspection JSUnresolvedReference
			event.from.insertBefore(event.item, event.from.children[event.oldIndex]);
			return;
		}

		// Update the visibility of the toggle button for the list the item was added to
		// noinspection JSUnresolvedReference
		this.updateToggleButton(event.to);
	}

	/**
	 * Event handler for when the sorting has ended
	 * @param {Event} event - The event object
	 */
	onEnd(event) {
		// Remove the class from the first level nested list when dragging ends
		const firstLevelLists = Array.from(document.querySelectorAll(`${this.selector}.Level1`));
		firstLevelLists.forEach(item => {
			item.classList.remove('SortableDragging');
		});

		// Update the end time of the drag operation, scroll position, and element position directly on the event.item object
		// noinspection JSUnresolvedReference
		event.item.dragData = {
			time: Date.now(),
			scrollPos: window.scrollY,
			elementPos: event.item.getBoundingClientRect().top + window.scrollY
		};

		clearTimeout(this.initTimeout);
		this.sortingEnabled = true;

		// Start adjusting the scroll position
		// noinspection JSUnresolvedReference
		this.adjustScrollPosition(event.item, false);

		// Enable or disable the save buttons based on the current order
		const isDefaultOrder = this.isDefaultOrder();
		this.saveOrderButtons.forEach(button => {
			button.disabled = isDefaultOrder;
			button.classList.toggle('Button-Success', !isDefaultOrder);
		});

		if (window.formLeaveConfirmation) {
			if (!isDefaultOrder) {
				window.formLeaveConfirmation.formChanged();
			}
			else {
				// If the dragged item is not part of a form, reset the form changed state
				// Otherwise it is not possible to determine if anything else in the form has changed
				if (!event.item.closest('form')) {
					window.formLeaveConfirmation.isFormChanged = false;
				}
			}
		}
	}

	/**
	 * Event handler for when an item is moved
	 * @param {Event} event - The event object
	 * @returns {boolean}
	 */
	onMove(event) {
		// If the drag operation has started less than 500ms ago, prevent the move
		// noinspection JSUnresolvedReference
		if (Date.now() - event.dragged.dragData.time < 500) {
			return false;
		}

		// Check if the item being moved has the `data-nestable="false"` attribute
		// and if the list it's being moved to does not have the `Level1` class
		// noinspection JSUnresolvedReference
		if (event.dragged.dataset.nestable === 'false' && !event.to.classList.contains('Level1')) {
			return false;
		}

		// Check if the item being moved has the `data-disallowTopLevel="true"` attribute
		// and if the list it's being moved to has the `Level1` class
		// noinspection JSUnresolvedReference
		if (event.dragged.dataset.disallowTopLevel === 'true' && event.to.classList.contains('Level1')) {
			return false;
		}

		// Check if the list the item is being moved from has the `data-movable-between-groups="false"` attribute
		// and if the list it's being moved to is not the same list
		// noinspection JSUnresolvedReference
		if (event.from.dataset.movableBetweenGroups === 'false' && event.from !== event.to) {
			return false;
		}

		// Check if the list the item is being moved to has the `data-receivable="false"` attribute
		// noinspection JSUnresolvedReference
		if (event.to.dataset.receivable === 'false') {
			return false;
		}
	}

	/**
	 * Event handler for when an item is removed from the list
	 * @param {Event} event - The event object
	 */
	onRemove(event) {
		// Update the visibility of the toggle button for the list the item was removed from
		// noinspection JSUnresolvedReference
		this.updateToggleButton(event.from);
	}

	/**
	 * Event handler for when the sorting starts
	 * @param {Event} event - The event object
	 */
	onStart(event) {
		// Add a class to the first level nested list when dragging starts
		const firstLevelLists = Array.from(document.querySelectorAll(`${this.selector}.Level1`));
		firstLevelLists.forEach(item => {
			item.classList.add('SortableDragging');
		});

		// Store the start time of the drag operation, initial scroll position, initial element position, and the dragged element directly on the event.item object
		// noinspection JSUnresolvedReference
		event.item.dragData = {
			time: Date.now(),
			scrollPos: window.scrollY,
			elementPos: event.item.getBoundingClientRect().top + window.scrollY
		};

		// Set the sortingEnabled flag to true
		this.sortingEnabled = false;

		// Enable sorting after 300ms
		this.initTimeout = setTimeout(() => {
			this.sortingEnabled = true;
		}, 500);

		// Start adjusting the scroll position
		// noinspection JSUnresolvedReference
		this.adjustScrollPosition(event.item, true);
	}

	/**
	 * Event handler for the toggle button click
	 *
	 * @param {MouseEvent} event - The event object
	 */
	handleToggleButtonClick(event) {
		// Check if the event target is a NestedListToggle button
		if (!event.target.classList.contains('NestedListToggle')) {
			return;
		}

		// Get the parent list item and child list associated with the button
		const listItem = event.target.closest('.ListItem');
		const childList = listItem.querySelector('.NestedList');

		// If the childList is empty, return
		if (!childList.children.length) {
			event.target.classList.remove('Collapsed');
			event.target.classList.add('Hidden');
			childList.classList.remove('Collapsed');

			return;
		}

		event.target.classList.toggle('Collapsed');

		this.toggleListState(listItem, childList);

		// If the ctrl key is pressed, toggle the state of all child lists
		if (event.ctrlKey) {
			const childLists = Array.from(childList.querySelectorAll('.NestedList'));
			childLists.forEach(childList => {
				const listItem = childList.closest('.ListItem');
				this.toggleListState(listItem, childList, event.target.classList.contains('Collapsed'));
			});
		}
	}

	/**
	 * Toggle the state of a list
	 *
	 * @param {HTMLElement} listItem
	 * @param {HTMLElement} childList
	 * @param {string} forceState
	 */
	toggleListState(listItem, childList, forceState) {
		// Toggle the visibility of the child list
		childList.classList.toggle('Collapsed', forceState);

		// Update the state in nestedListStates
		this.nestedListStates[ listItem.id ] = childList.classList.contains('Collapsed') ? 'Collapsed' : 'Expanded';

		// Save the state to localStorage
		localStorage.setItem('nestedListStates', JSON.stringify(this.nestedListStates));
	}

	/**
	 * Update the visibility of the toggle button for a list
	 * @param {HTMLElement} list - The list
	 */
	updateToggleButton(list) {
		const parentListItem = list.parentElement;

		// If the parent is not a list item, return
		if (!parentListItem.classList.contains('ListItem')) {
			return;
		}

		// Get the .ListItem-Inner child of the parent list item
		const listItemInner = parentListItem.querySelector('.ListItem-Inner');

		// Get the .NestedListToggle child of the .ListItem-Inner child
		const button = listItemInner.querySelector('.NestedListToggle');

		if (!button) {
			return;
		}

		const childList = parentListItem.querySelector('.NestedList');

		button.classList.toggle('Hidden', !childList || !childList.children.length);
	}


	/**
	 * Get the order of all list items with their nesting level and parent list
	 * @returns {Array}
	 */
	getOrderWithLevels() {
		let order = [];

		// Select all first level lists
		const lists = document.querySelectorAll(this.selector);

		// Loop through each list
		lists.forEach(list => {
			// Select all list items in the current list
			const items = list.querySelectorAll('.ListItem');

			// Loop through each list item
			items.forEach(item => {
				// Get the id of the list item
				const id = item.id;

				// Get the nesting level of the list item
				const level = this.getNestingLevel(item);

				// Get the parent identifier of the list item
				const parentIdentifier = item.parentElement.getAttribute(this.parentIdentifierAttribute);

				// Add the id, the nesting level, and the parent list to the order array
				order.push({id, level, parent: parentIdentifier});
			});
		});

		return order;
	}

	/**
	 * Get the nesting level of a list item
	 * @param {HTMLElement} item - The list item
	 * @returns {number}
	 */
	getNestingLevel(item) {
		let level = 0;
		let parent = item.parentElement;

		// Traverse up the DOM tree and increment the level for each parent list
		while (parent && parent !== document.querySelector(this.selector)) {
			if (parent.tagName === 'UL') {
				level++;
			}
			parent = parent.parentElement;
		}

		return level;
	}

	/**
	 * Check if the current order is the default order
	 * @returns {boolean}
	 */
	isDefaultOrder() {
		// Get the current order of the list items with their nesting level
		const currentOrder = this.getOrderWithLevels();
		return JSON.stringify(currentOrder) === JSON.stringify(this.defaultOrder);
	}

	/**
	 * Set the group identifier attribute
	 * @param {string|Function|null} identifierAttribute - The attribute used to identify the grouping of lists
	 */
	setGroupIdentifierAttribute(identifierAttribute) {
		this.groupIdentifierAttribute = identifierAttribute;

		if (typeof identifierAttribute !== 'function') {
			// Reset the default order
			this.defaultOrder = this.getOrderWithLevels();
		}
	}

	/**
	 * Set the parent identifier attribute
	 * @param {string} identifierAttribute - The attribute used to identify the parent
	 */
	setParentIdentifierAttribute(identifierAttribute) {
		this.parentIdentifierAttribute = identifierAttribute;

		// Reset the default order
		this.defaultOrder = this.getOrderWithLevels();
	}

	/**
	 * Save the current order by making a POST request
	 * @param {HTMLElement} element - The element to get the order and controller name from
	 * @returns {Promise<void>}
	 */
	saveSystemOrder(element) {
		// Get the current order of all list items
		const order = this.getOrder();

		if (order === false || !Object.keys(order).length) {
			return;
		}

		element = element || document.querySelector(this.selector);

		// Get the controller name
		let controller = element.dataset.controller;

		// Add a class to the element(s) to show that a reload operation is in progress
		const elements = Array.from(document.querySelectorAll(`${this.selector}.Level1`));
		elements.forEach(element => {
			element.classList.add('FetchInProgress');
		});

		// Add a class to the body to show that a save operation is in progress
		document.body.classList.add('FetchInProgress');

		// Make a POST request to the '/save-order' URL with the current order and the controller name
		return fetch(`/backend/${languageShortcode}/${controller}/save-system-order/`, {
			method: 'POST',
			headers: {
				'Accept': 'application/json',
				'Content-Type': 'application/json',
				'X-Requested-With': 'XMLHttpRequest',
			},
			body: JSON.stringify({order, controller}),
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

			this.defaultOrder = this.getOrderWithLevels();
		})
		.catch(error => {
			console.error('There has been a problem with the fetch operation:', error);
		})
		.finally(() => {
			// Remove the class from the element(s) to show that the save operation is complete
			elements.forEach(element => {
				element.classList.remove('FetchInProgress');
			});

			// Remove the class from the body to show that the save operation is complete
			document.body.classList.remove('FetchInProgress');
		});
	}


	/**
	 * Get the order of all list items
	 * @param {HTMLElement} element - The element to get the order from
	 * @returns {Object}
	 */
	getOrder(element) {
		let order = {};

		let lists;
		if (element) {
			// Select all lists
			lists = [element];
			lists.push(...element.querySelectorAll(this.selector));
		}
		else {
			// Select all lists
			lists = document.querySelectorAll(this.selector);
		}

		// Loop through each list
		lists.forEach(list => {
			// Get the parent list item with class .ListItem
			const parentListItem = list.closest('.ListItem');

			// Get the order of the list items using the toArray method of SortableJS
			let items = list.sortable?.toArray();

			if (!items?.length) {
				return;
			}

			// Traverse the items and remove non-numeric characters from the start of the id
			items = items.map(item => {
				return parseInt(item.replace(/^\D+/g, ''));
			});

			let groupIdentifier = null;
			// If the groupIdentifierAttribute is set, group the items by it
			if (this.groupIdentifierAttribute) {
				groupIdentifier = typeof this.groupIdentifierAttribute === 'function' ? this.groupIdentifierAttribute(list, items) : parentListItem.getAttribute(this.groupIdentifierAttribute);
			}

			// If there is no parent list item, add the items to the order object with a null key
			if (!parentListItem) {
				// If there is a group identifier, add the items to the order object with the group identifier as the key
				if (groupIdentifier) {
					// If the group identifier is not already a key in the order object, add it
					if (!order[groupIdentifier]) {
						order[groupIdentifier] = {};
					}

					// Add the items to the order object with the group identifier as the key
					order[groupIdentifier][0] = items;
				}
				else {
					// Add the items to the order object with a null key
					order[0] = items;
				}

				return;
			}

			// Remove all non-numeric characters from the start of the string
			const parentId = parentListItem.id.replace(/^\D+/g, '');

			// If there is a group identifier, add the items to the order object with the group identifier as the key
			if (groupIdentifier) {
				// If the group identifier is not already a key in the order object, add it
				if (!order[groupIdentifier]) {
					order[groupIdentifier] = {};
				}

				// Add the items it the order object with the parent ID as the key
				order[groupIdentifier][parentId] = items;
			}
			else {
				// Add the items it the order object with the parent ID as the key
				order[parentId] = items;
			}
		});

		return order;
	}
}