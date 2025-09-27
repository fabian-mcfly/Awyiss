// noinspection JSUnusedGlobalSymbols

export default class MultiSelect {
	/**
	 * The bound handle mouse up function
	 * @type {function}
	 */
	boundHandleMouseUp;
	/**
	 * The element that contains the items to be selected
	 * @type {HTMLElement}
	 */
	element;
	/**
	 * The event handler instance.
	 * @type {EventHandler}
	 */
	eventHandler = window.eventHandler;
	/**
	 * The timeout for the selection changed event
	 * @type {number}
	 */
	eventTimeout;
	/**
	 * The selector for the items that cannot be selected
	 * @type {string}
	 */
	filterItemSelector;
	/**
	 * The initial position of the mouse when the user starts dragging
	 * @type {null}
	 */
	initialPosition = null;
	/**
	 * Whether the user is currently dragging the mouse
	 * @type {boolean}
	 */
	isDragging = false;
	/**
	 * The selector for the items that can be selected
	 * @type {string}
	 */
	itemSelector = '.ListItem';
	/**
	 * The last selected item
	 * @type {HTMLElement}
	 */
	lastSelected = null;
	/**
	 * The parent element that contains the selection rectangle
	 * @type {HTMLElement}
	 */
	parent;
	/**
	 * The selection rectangle element
	 * @type {HTMLElement}
	 */
	selectionRectangle = null;
	/**
	 * The map of items that are within the selection rectangle
	 * @type {Map<any, any>}
	 */
	withinRectangleStates = new Map();

	/**
	 * Create a new instance of the MultiSelect class.
	 * @param {HTMLElement|string} selector
	 * @param {string} itemSelector
	 * @param {string} filterItemSelector
	 */
	constructor(selector, itemSelector, filterItemSelector) {
		this.element = typeof selector === 'string' ? document.querySelector(selector) : selector;
		this.itemSelector = itemSelector;
		this.filterItemSelector = filterItemSelector;
		this.parent = this.element;

		const rectangle = this.parent.querySelector('.SelectionRectangle');
		if (rectangle) {
			this.selectionRectangle = rectangle;
		}

		// Handle ctrl+click selection
		this.eventHandler.add('click', this.handleClick.bind(this), this.element);

		// Handle rectangular selection
		this.eventHandler.add('mousedown', this.handleMouseDown.bind(this), this.element);

		// Handle mouse movement
		this.eventHandler.add('mousemove', this.handleMouseMove.bind(this), this.element);
	}

	/**
	 * Get the selected items.
	 * @returns {NodeListOf<Element>}
	 */
	getSelectedItems() {
		return this.element.querySelectorAll('.Selected');
	}

	/**
	 * Handle the click event.
	 * If the ctrl key is pressed, toggle the selected state of the item.
	 * If the ctrl key is not pressed, remove the selected class from all items and add it to the clicked item.
	 *
	 * @param {MouseEvent} event
	 */
	handleClick(event) {
		if (event.defaultPrevented) {
			return;
		}

		const selectedItems = this.getSelectedItems();

		const item = event.target.closest(this.itemSelector);

		if (item) {
			// If the item is a filter item, don't do anything
			if (item.matches(this.filterItemSelector)) {
				return;
			}

			if (event.target.matches('.Link')) {
				event.preventDefault();
			}

			if (event.ctrlKey) {
				item.classList.toggle('Selected');
			}
			else if (!this.isDragging) {
				// Remove the selected class from all items if the ctrl key is not pressed
				selectedItems.forEach(item => {
					item.classList.remove('Selected');
				});

				item.classList.add('Selected');
			}

			/**
			 * If the item is now selected and the shift key is not pressed,
			 * set it as the last selected item
			 */
			if (item.classList.contains('Selected') && !event.shiftKey) {
				this.lastSelected = item;
			}

			/**
			 * If the shift key is pressed, select all items between
			 * the last selected item and the current item.
			 */
			if (event.shiftKey) {
				const items = this.element.querySelectorAll(this.itemSelector);
				const start = this.lastSelected ? Array.from(items).indexOf(this.lastSelected) : 0;
				const end = Array.from(items).indexOf(item);

				const selected = Array.from(items).slice(Math.min(start, end), Math.max(start, end) + 1);

				selected.forEach(item => {
					item.classList.add('Selected');
				});
			}
		}
		else if (!this.isDragging) {
			// Remove the selected class from all items if the click was not on an item
			selectedItems.forEach(item => {
				item.classList.remove('Selected');
			});

			this.lastSelected = null;
		}

		const selectedItemsAfter = this.getSelectedItems();
		if (selectedItems.length !== selectedItemsAfter.length) {
			this.emitEvent('selectionChanged', {
				selectedItems: selectedItemsAfter
			});
		}
	}

	/**
	 * Handle the mouse down event.
	 * Store the initial position of the mouse, and create a selection rectangle.
	 *
	 * @param {MouseEvent} event
	 */
	handleMouseDown(event) {
		this.initialPosition = {
			x: event.clientX/* + window.scrollX*/,
			y: event.clientY/* + window.scrollY*/,
		};

		this.selectionRectangle ??= document.createElement('div');

		this.selectionRectangle.classList.add('SelectionRectangle');
		this.parent.appendChild(this.selectionRectangle)

		// Prevent text selection
		this.element.classList.add('NoTextSelect');

		// Handle mouse up
		this.boundHandleMouseUp = this.handleMouseUp.bind(this);
		this.eventHandler.add('mouseup', this.boundHandleMouseUp);
	}

	/**
	 * Handle the mouse move event.
	 * If the user is dragging the mouse, create a selection rectangle.
	 *
	 * If the ctrl key is pressed, toggle the selected state of each item within the rectangle.
	 * If the ctrl key is not pressed, add the selected class to each item within the rectangle.
	 *
	 * @param {MouseEvent} event
	 */
	handleMouseMove(event) {
		if (!this.selectionRectangle) {
			return;
		}

		// Only if the selection has a certain width and height, it will be considered a selection
		if (!this.isDragging && (
			Math.abs(this.initialPosition.x - (event.clientX/* + window.scrollX*/)) > 10 ||
			Math.abs(this.initialPosition.y - (event.clientY/* + window.scrollY*/)) > 10
		)) {
			this.isDragging = true;

			// If the ctrl key isn't pressed, remove the selected class from all items
			if (!event.ctrlKey) {
				this.getSelectedItems().forEach(item => {
					item.classList.remove('Selected');
				});
			}
		}

		if (!this.isDragging) {
			return;
		}

		clearTimeout(this.eventTimeout);

		const width = Math.abs(this.initialPosition.x - (event.clientX/* + window.scrollX*/)) + 'px';
		const height = Math.abs(this.initialPosition.y - (event.clientY/* + window.scrollY*/)) + 'px';
		const top = Math.min(this.initialPosition.y, event.clientY/* + window.scrollY*/) + 'px';
		const left = Math.min(this.initialPosition.x, event.clientX/* + window.scrollX*/) + 'px';

		Object.assign(this.selectionRectangle.style, {
			width,
			height,
			top,
			left,
		});

		// Update the selection state of each item
		let items = this.element.querySelectorAll(this.itemSelector);

		// Filter out items that should not be selected
		items = Array.from(items).filter(item => !item.matches(this.filterItemSelector));

		items.forEach(item => {
			const rect1 = item.getBoundingClientRect();
			const rect2 = this.selectionRectangle.getBoundingClientRect();

			// Determine if the item is currently within the rectangle
			const isWithinRectangle = rect1.left < rect2.right && rect1.right > rect2.left &&
				rect1.top < rect2.bottom && rect1.bottom > rect2.top;

			// Retrieve the previous state of the item being within the rectangle
			const wasWithinRectangle = this.withinRectangleStates.get(item) || false;

			// Check if there is a transition
			if (isWithinRectangle !== wasWithinRectangle) {
				if (isWithinRectangle) {
					// If the ctrl key is pressed, toggle the selected state of the item
					if (event.ctrlKey) {
						item.classList.toggle('Selected');
					}
					// If the ctrl key is not pressed, add the selected class to the item
					else {
						item.classList.add('Selected');
					}
				}
				else if (!event.ctrlKey) {
					item.classList.remove('Selected');
				}

				// Update the within-rectangle state for future reference
				this.withinRectangleStates.set(item, isWithinRectangle);
			}
		});
	}

	/**
	 * Handle the mouse up event.
	 * If the user is not dragging, remove the selection rectangle.
	 */
	handleMouseUp() {
		// If the user is not dragging, remove the selection rectangle
		if (!this.isDragging) {
			if (this.selectionRectangle) {
				this.parent.removeChild(this.selectionRectangle);
				this.selectionRectangle = null;
			}

			// Handle mouse up
			this.eventHandler.remove('mouseup', this.boundHandleMouseUp);
			this.boundHandleMouseUp = null;

			return;
		}

		if (this.selectionRectangle) {
			this.parent.removeChild(this.selectionRectangle);
		}

		this.selectionRectangle = null;

		setTimeout(() => {
			this.isDragging = false;
		}, 100);

		// Allow text selection
		this.element.classList.remove('NoTextSelect');

		// Seems like the user was dragging the mouse
		// Send a custom event to notify that the selection has changed
		this.emitEvent('selectionChanged', {
			selectedItems: this.getSelectedItems()
		});

		// Handle mouse up
		this.eventHandler.remove('mouseup', this.boundHandleMouseUp);
		this.boundHandleMouseUp = null;
	}


	/**
	 * Emits a custom event.
	 * @param {string} eventName - The name of the event.
	 * @param {object} data - The data to send with the event.
	 */
	emitEvent(eventName, data) {
		const event = new CustomEvent(eventName, {bubbles: true, detail: data});

		this.element.dispatchEvent(event);
	}
}