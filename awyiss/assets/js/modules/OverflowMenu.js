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

		this.menu.querySelectorAll('li').forEach(function (item) {
			// If the item has a sublist, add a toggle button
			const sublist = item.querySelector(':scope > ul');
			if (!sublist) {
				return;
			}

			const isActive = item.classList.contains('Active');

			const toggleButton = document.createElement('button');
			toggleButton.classList.add('Submenu-Toggle');
			toggleButton.setAttribute('aria-expanded', isActive);
			toggleButton.setAttribute('aria-controls', sublist.id);
			toggleButton.setAttribute('aria-label', 'Toggle Submenu');

			toggleButton.addEventListener('click', function (event) {
				event.stopPropagation();
				const expanded = toggleButton.getAttribute('aria-expanded') === 'true';
				toggleButton.setAttribute('aria-expanded', !expanded);
				sublist.classList.toggle('Visible');
			});

			sublist.classList.toggle('Visible', isActive);

			item.insertBefore(toggleButton, sublist);
		});

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
		let siblingsWidth = Array.from(parentElement.children)
		.filter(child => child !== this.menu)
		// Add each child and subtract 6 for the negative margin of the logout.
		.reduce((total, child) => {
			return total + child.offsetWidth;
		}, 0) - 6;

		// If the parent element has a LanguageSwitcher, subtract 6 for its negative margin
		if (parentElement.querySelector('.LanguageSwitcher')) {
			siblingsWidth -= 6;
		}

		// Add the overflow items back to the main menu
		this.overflowItems.forEach(item => {
			item.classList.remove(this.visibleClass);
			this.overflowContainer.removeChild(item);
			this.menu.querySelector('ul.Level1').appendChild(item);
		});
		this.overflowItems = [];

		// Calculate the available width for the menu
		// Subtract the width of the siblings, the inline padding of the header
		// and the inline border of the menu
		let availableWidth = parentWidth - siblingsWidth - 4;
		availableWidth -= document.body.clientWidth > 540 ? 40 : 20;

		// Calculate the total width of the items
		let itemsWidth = this.items.reduce((total, item) => {
			// Hidden elements should not be counted
			if (item.offsetWidth === 0) {
				return 0;
			}

			return total + item.offsetWidth + 2; // Add 2 to account for the right margin of the item
		}, 0) - 2; // Subtract 2 to account for the right margin of the last item

		// If the total width of the items is greater than the available width, move items to the overflow
		if (itemsWidth > availableWidth) {
			// Now that the overflow container must be visible,
			// the available width must be reduced by the width of the overflow container. Oh no.
			availableWidth -= showMoreButton.offsetWidth + 2; // Add 2 to account for the right margin of the button

			this.items.reverse().forEach((item) => {
				if (itemsWidth > availableWidth) {
					itemsWidth -= item.offsetWidth;
					item.classList.add(this.visibleClass);
					//noinspection JSCheckFunctionSignatures
					this.overflowContainer.insertBefore(item, this.overflowContainer.firstChild);
					this.overflowItems.unshift(item);
				}
			});
			this.items.reverse();

			this.checkConstraints();
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
		// If the target is not the button, close the overflow menu
		if (!event.target.closest('#Menu-System')) {
			//If not, hide the overflow container
			this.overflowContainer.classList.remove('Narrow');
			this.overflowContainer.classList.remove(this.visibleClass);

			return;
		}

		if (event.target.classList.contains(this.buttonClass)) {
			this.overflowContainer.classList.toggle(this.visibleClass);

			this.checkConstraints();
		}
	}

	/**
	 * If the overflow container is visible, check if it fits inside the viewport
	 */
	checkConstraints() {
		if (!this.overflowContainer.classList.contains(this.visibleClass)) {
			return;
		}

		this.overflowContainer.classList.remove('Narrow')

		// Get the right position of the overflow container
		const rightPosition = this.overflowContainer.getBoundingClientRect().right;

		// If the overflow container is wider than the available space to the left, add the "Narrow" class
		if (rightPosition <= (this.overflowContainer.clientWidth + 40)) {
			this.overflowContainer.classList.add('Narrow');
		}
	}
}