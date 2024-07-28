//noinspection JSUnusedGlobalSymbols

/**
 * ButtonArea class is used to check if the width of the first h1 and the first element of class .ButtonArea
 * fit together into the width of the parent of the h1.
 * If they do, it adds a class to the .ButtonArea that indicates that it can be moved upwards.
 *
 * It also attaches an event listener to the window.eventHandler instance that rechecks
 * the widths on the resize event.
 */
export default class ButtonArea {
	/**
	 * The button area element.
	 * @type {HTMLElement}
	 */
	buttonArea;
	/**
	 * The event handler instance.
	 * @type {EventHandler}
	 */
	eventHandler = window.eventHandler;
	/**
	 * The first h1 element.
	 * @type {HTMLHeadingElement}
	 */
	h1;
	/**
	 * The first h2 element.
	 * @type {HTMLHeadingElement}
	 */
	h2;
	/**
	 * The parent of the h1 and button area elements.
	 * @type {HTMLElement}
	 */
	parent;

	/**
	 * The constructor initializes the h1, buttonArea, and parent elements, attaches the event listener to the
	 * window.eventHandler instance, and performs the initial check.
	 * @returns {void}
	 */
	constructor() {
		// The first h1 element
		this.h1 = document.querySelector('h1');
		this.h2 = document.querySelector('h2');

		// The first .ButtonArea element
		this.buttonArea = document.querySelector('.ButtonArea');

		if (!this.h1 || !this.buttonArea) {
			return;
		}

		// The parent of the h1 element
		this.parent = this.h1.parentElement;

		// Attach the event listener to the window.eventHandler instance
		this.eventHandler.add('resize', this.checkWidths.bind(this));

		// Perform the initial check
		this.checkWidths();
	}

	/**
	 * The checkWidths method checks if the widths of the h1 and .ButtonArea elements fit into the width of the parent
	 * of the h1 element. If they do, it adds a class 'CannotMoveUpwards' to the .ButtonArea element. If they don't, it
	 * removes the class from the .ButtonArea element.
	 * @returns {void}
	 */
	checkWidths() {
		// Widest element is either h1 or h2
		const widestElement = this.h1.offsetWidth > (this.h2?.offsetWidth ?? 0) ? this.h1 : this.h2;

		if (widestElement.offsetWidth + this.buttonArea.offsetWidth + 40 <= this.parent.offsetWidth) {
			// If the widths fit, add the class 'CannotMoveUpwards' to the .ButtonArea element
			this.buttonArea.classList.remove('CannotMoveUpwards');
		}
		else {
			// If the widths don't fit, remove the class 'CannotMoveUpwards' from the .ButtonArea element
			this.buttonArea.classList.add('CannotMoveUpwards');
		}
	}
}