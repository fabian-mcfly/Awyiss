//noinspection JSUnusedGlobalSymbols

/**
 * OverflowMenu class
 * This class is used to handle the overflow menu functionality.
 */
export default class OverflowMenu {
	/**
	 * The event handler instance.
	 * @type {EventHandler}
	 */
	eventHandler = window.eventHandler;
	/**
	 * The class name for the "Show more" button.
	 * @type {string}
	 */
	buttonClass = 'ShowMore';
	/**
	 * The class name for the menu element when calculating widths.
	 * Should remove overflow hidden to calculate widths properly.
	 * @type {string}
	 */
	calculatingClass = 'Calculating';
	/**
	 * The overflow container class name.
	 * @type {string}
	 */
	overflowContainerClass = 'Overflow';
	/**
	 *
	 * @type {null}
	 */
	resizeRafId = null;
	/**
	 * The class name for the visible class of the overflow container.
	 * @type {string}
	 */
	visibleClass = 'Visible';

	/**
	 * Constructor for OverflowMenu class.
	 * @param {string} menuSelector - The selector for the menu element.
	 * @param {string} itemSelector - The selector for the item elements.
	 */
	constructor(menuSelector, itemSelector) {
		// Select the menu and item elements
		this.menu = document.querySelector(menuSelector);
		this.items = Array.from(this.menu.querySelectorAll(itemSelector));
		this.overflowItems = [];

		// Create the overflow container and append it to the menu
		this.overflowContainer = document.createElement('ul');
		this.overflowContainer.classList.add(this.overflowContainerClass);
		this.menu.appendChild(this.overflowContainer);

		// Create the "Show more" button and append it to the menu
		const button = document.createElement('button');
		button.classList.add(this.buttonClass);
		button.textContent = 'Show more';
		this.eventHandler.add('click', this.handleOverflowButtonClick.bind(this));
		this.menu.appendChild(button);

		// Debounce the resize event using requestAnimationFrame
		this.eventHandler.add('resize', function () {
			cancelAnimationFrame(this.resizeRafId);
			this.resizeRafId = requestAnimationFrame(() => this.calculateWidths());
		}.bind(this));

		// Calculate the widths of the items
		this.calculateWidths();
	}

	/**
	 * Calculates the widths of the items and determines which items should be in the overflow.
	 */
	calculateWidths() {
		// Add a class to hide overflow
		this.menu.classList.add(this.calculatingClass);

		// Get the parent element of the menu
		const parentElement = this.menu.parentElement;

		// Get the "Show more" button
		const showMoreButton = this.menu.querySelector(`.${this.buttonClass}`);
		showMoreButton.classList.add('Visible');

		// Calculate the total width of the parent element
		const parentWidth = parentElement.offsetWidth;

		// Calculate the total width of all sibling elements of the menu
		const siblingsWidth = Array.from(parentElement.children)
		.filter(child => child !== this.menu)
		// Start with 40 to account for the margin to the logo
		// Add 2 for each child to account for the margin between each child
		// And subtract 2 for the last child to account for the last margin being 0
		.reduce((total, child) => total + child.offsetWidth + 2, 40) - 2;

		// Calculate the available width for the menu
		// Subtract the width of the siblings and the "Show more" button
		// and 2 for the left margin of the show more button
		// and 4 for the border of the menu
		const menuWidth = parentWidth - siblingsWidth - showMoreButton.offsetWidth - 2 - 4;

		// Calculate the total width of the items
		let itemsWidth = this.items.reduce((total, item) => total + item.offsetWidth, 0);

		// Add the overflow items back to the main menu
		this.overflowItems.forEach(item => {
			item.classList.remove(this.visibleClass);
			this.overflowContainer.removeChild(item);
			this.menu.querySelector('ul.Level1').appendChild(item);
		});
		this.overflowItems = [];

		// If the total width of the items is greater than the available width, move items to the overflow
		if (itemsWidth > menuWidth) {
			this.items.reverse().forEach((item) => {
				if (itemsWidth > menuWidth) {
					itemsWidth -= item.offsetWidth;
					item.classList.add(this.visibleClass);
					//noinspection JSCheckFunctionSignatures
					this.overflowContainer.insertBefore(item, this.overflowContainer.firstChild);
					this.overflowItems.unshift(item);
				}
			});
			this.items.reverse();
		}

		// If the overflow menu has no items, remove the Visible class
		if (this.overflowItems.length === 0) {
			showMoreButton.classList.remove('Visible');
			this.overflowContainer.classList.remove('Visible');
		}

		// Remove the class to show overflow
		this.menu.classList.remove(this.calculatingClass);
	}

	/**
	 * Toggles the visibility of the overflow items.
	 * @param {Event} event - The click event.
	 */
	handleOverflowButtonClick(event) {
		// Make sure the click target is the "Show more" button and its parent is the menu
		if (
			!event.target.classList.contains(this.buttonClass) ||
			event.target.parentElement !== this.menu
		) {
			//If not, hide the overflow container
			this.overflowContainer.classList.remove(this.visibleClass);

			return;
		}

		this.overflowContainer.classList.toggle(this.visibleClass);
	}
}