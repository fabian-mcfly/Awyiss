// noinspection JSUnusedGlobalSymbols

/**
 * ResizableContents class is responsible for handling resizable contents.
 * It accepts an array to determine the column widths. Each element of the array is a map with the keys "class", "numerator", "denominator", and "label".
 */
export default class ResizableContents {
	/**
	 * The bound resize method.
	 * @type {function|null}
	 */
	boundResize;
	/**
	 * The bound stop method.
	 * @type {function|null}
	 */
	boundStop;
	/**
	 * The definitions for the columns in the resizable content.
	 * Each object in the array should have "cssClass", "numerator", "denominator", "label" properties.
	 * @type {Array.<{cssClass: string, numerator: number, denominator: number, label: string, calculated: number}>}
	 */
	columnDefinitions = [];
	/**
	 * The controller name.
	 * @type {string}
	 */
	controller;
	/**
	 * The element currently being resized.
	 * @type {HTMLElement|undefined}
	 */
	element = undefined;
	/**
	 * The event handler instance.
	 * @type {EventHandler}
	 */
	eventHandler = window.eventHandler;
	/**
	 * The maximum width the element can be resized to.
	 * @type {number|null}
	 */
	maxWidth = null;
	/**
	 * The list items min width threshold to be considered narrow.
	 * Narrow items will have a class "Narrow" added and be styled accordingly.
	 * @type {number}
	 */
	narrowWidthThreshold = 285;
	/**
	 * @property {string} selector - The selector for the nested list.
	 */
	selector = '.NestedList'
	/**
	 * The X-coordinate where the user started dragging.
	 * @type {number|null}
	 */
	startX = null;
	/**
	 * The original width of the element being resized.
	 * @type {number|null}
	 */
	startWidth = null;

	/**
	 * Constructor for the ResizableContents class.
	 * @param {string} controller - The controller name.
	 * @param {Array.<{cssClass: string, numerator: number, denominator: number, label: string, calculated: number}>} columnDefinitions
	 *  - An array of objects, each containing "class", "numerator", "denominator", and "label" properties.
	 * @param {string} selector - The selector for the nested list. Defaults to '.NestedList'.
	 */
	constructor(controller, columnDefinitions = [], selector) {
		if (selector) {
			this.selector = selector;
		}

		// Assign the controller name
		this.controller = controller;

		// Assign the column definitions
		this.columnDefinitions = columnDefinitions;

		// Select all list items and initialize them
		this.listItems = document.querySelectorAll('.ListItem');

		if (!this.listItems.length) {
			return;
		}

		this.listItems.forEach(item => {
			if (item.dataset.resizable === 'false') {
				return;
			}

			this.initListItem(item);
		});

		this.resetListItemWidths();

		// Bind the resize and stop methods to this instance
		this.boundResize = this.onResize.bind(this);
		this.boundStop = this.onEnd.bind(this);
	}

	/**
	 * Initialize the list item by adding the necessary elements and event listeners.
	 * @param {HTMLElement} listItem - The list item to initialize.
	 */
	initListItem(listItem) {
		// Create a new div for the resize info and add it to the list item
		const resizeInfo = document.createElement('div');
		resizeInfo.classList.add('ResizeInfo');
		listItem.resizeInfo = resizeInfo;
		listItem.querySelector('.ListItem-Inner').appendChild(resizeInfo);

		// Create a new div for the resizer and add it to the list item
		const resizer = document.createElement('div');
		resizer.classList.add('Resizer');
		this.eventHandler.add('mousedown', event => {
			this.onStart(event, listItem);
		}, resizer);
		listItem.resizer = resizer;
		listItem.appendChild(resizer);
	}

	/**
	 * Start the resizing process.
	 * @param {Event} event - The mousedown event.
	 * @param {HTMLElement} listItem - The list item being resized.
	 */
	onStart(event, listItem) {
		// Add the 'Resizing' class to the list item
		listItem.classList.add('Resizing');

		// Get the parent list item
		const parentList = listItem.closest('ul');

		// Store the list item and its initial properties
		this.element = listItem;
		// noinspection JSUnresolvedReference
		this.startX = event.clientX;
		this.startWidth = listItem.offsetWidth;
		this.maxWidth = parentList.offsetWidth;

		const maxColumnWidth = listItem.dataset.maxColumnWidth;

		// Calculate the widths for each column
		this.columnDefinitions.forEach(function (col) {
			// noinspection JSUnresolvedReference
			if (maxColumnWidth && col.percentage > maxColumnWidth) {
				col.calculated = false;
			}
			else {
				// noinspection JSUnresolvedReference
				col.calculated = this.maxWidth / col.denominator * col.numerator;
			}
		}.bind(this));

		// Sort the column definitions by width, false at the end
		this.columnDefinitions.sort(function (a, b) {
			if (a.calculated === false) {
				return 1;
			}
			if (b.calculated === false) {
				return -1;
			}

			return a.calculated - b.calculated;
		});

		// Remove any existing column class
		if (this.element.dataset.columnWidthClass) {
			this.element.classList.remove(this.element.dataset.columnWidthClass);
		}

		// Trigger the resize event once to set the initial width
		this.onResize(event);

		// Add event listeners for mousemove and mouseup events
		window.eventHandler.add('mousemove', this.boundResize);
		window.eventHandler.add('mouseup', this.boundStop);

		// Add the 'NoTextSelect' class to the body
		document.body.classList.add('NoTextSelect');
	}

	/**
	 * Resize the element based on the mouse movement.
	 * @param {Event} event - The mousemove event.
	 */
	onResize(event) {
		// Calculate the new width
		// noinspection JSUnresolvedReference
		let width = event.clientX ? (this.startWidth + event.clientX - this.startX) : this.startWidth;
		if (width > this.maxWidth) {
			width = this.maxWidth;
		}

		let columnDefinitions = this.columnDefinitions;
		// Filter out all columns that have a calculated width of false
		columnDefinitions = columnDefinitions.filter(col => col.calculated !== false);

		// Find the closest column width
		let previousWidth = columnDefinitions[0].calculated;
		const columnDefinitionsLength = columnDefinitions.length;
		for (let i = 0; i < columnDefinitionsLength; i++) {
			if (width > columnDefinitions[i].calculated) {
				previousWidth = columnDefinitions[i].calculated;
			}

			if (width <= columnDefinitions[i].calculated) {
				const diffPrev = width - previousWidth;
				const diffNext = columnDefinitions[i].calculated - width;

				let usedIndex = i;
				if (diffPrev > 0 && diffPrev < diffNext) {
					usedIndex--;
				}

				// Get the resize info element
				// noinspection JSUnresolvedReference
				const resizeInfo = this.element.resizeInfo;

				// Update the element with the new column class and width
				resizeInfo.innerHTML = columnDefinitions[usedIndex].label;
				this.element.dataset.columnWidthClass = columnDefinitions[usedIndex].cssClass;
				this.element.dataset.columnWidth = `${columnDefinitions[usedIndex].numerator}/${columnDefinitions[usedIndex].denominator}`;

				break;
			}
		}

		// If the width is smaller than the last column, use the last column
		if (width < columnDefinitions[0].calculated) {
			width = columnDefinitions[0].calculated;
		}

		// Update the element's width
		this.element.style.width = `${width}px`;
	}

	/**
	 * Stop the resizing process.
	 */
	onEnd() {
		// Remove the event listeners
		window.removeEventListener('mousemove', this.boundResize);
		window.removeEventListener('mouseup', this.boundStop);

		// Reset the element's width and update its class
		this.element.style.width = null;
		this.element.classList.remove('Resizing');
		this.element.classList.add(this.element.dataset.columnWidthClass);

		// Toggle a class "Narrow" if the element is narrower than this.narrowWidthThreshold
		this.setNarrowClass(this.element);

		// Add a class to the element(s) to show that a reload operation is in progress
		const elements = Array.from(document.querySelectorAll(`${this.selector}.Level1`));
		elements.forEach(element => {
			element.classList.add('FetchInProgress');
		});

		// Add a class to the body to show that a save operation is in progress
		document.body.classList.add('FetchInProgress');

		// Send a request to save the column width
		fetch(`${baseUrl}backend/${languageShortcode}/${this.controller}/save-column-width/`, {
			method: 'POST',
			headers: {
				'Accept': 'application/json',
				'Content-Type': 'application/json',
				'X-Requested-With': 'XMLHttpRequest',
			},
			body: JSON.stringify({
				id: parseInt(this.element.id.replace(/^\D+/g, '')),
				width: this.element.dataset.columnWidth,
				method: 'ajax'
			})
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

		// Add the 'NoTextSelect' class to the body
		document.body.classList.remove('NoTextSelect');
	}

	/**
	 * Reset the list item widths.
	 * @returns {void}
	 */
	resetListItemWidths() {
		// Get all level 1 list items from this.listItems
		const level1Items = Array.from(this.listItems).filter(item => item.parentElement.classList.contains('Level1'));
		// Trigger the narrow class on all level 1 items (handles children as well)
		level1Items.forEach(listItem => {
			this.setNarrowClass(listItem);
		});
	}

	/**
	 * Set the "Narrow" class on the element if it is narrower than this.narrowWidthThreshold.
	 * @param {HTMLElement} element
	 */
	setNarrowClass(element) {
		// Remove the class "Narrow" from the element, so the calculation is correct
		element.classList.remove('Narrow');

		// Check if the element is narrow
		const isNarrow = element.offsetWidth < this.narrowWidthThreshold;

		// Add the class "Narrow" to the element if it is narrow
		if (isNarrow) {
			element.classList.add('Narrow');
		}

		// Trigger the narrow class on all nested children
		const nestedItems = element.querySelectorAll(`:scope > ${this.selector} > .ListItem`);
		nestedItems.forEach(nestedItem => {
			this.setNarrowClass(nestedItem);
		});
	}
}