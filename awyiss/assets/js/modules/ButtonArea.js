//noinspection JSUnusedGlobalSymbols

/**
 * ButtonArea class
 *
 * Handles the button area toggle functionality.
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
	 * @returns {void}
	 */
	constructor() {
		// The first .ButtonArea element
		this.buttonArea = document.querySelector('.ButtonArea');

		if (!this.buttonArea) {
			return;
		}

		this.eventHandler.add('click', this.handleClick.bind(this), document.body);

		// Listen for hash changes
		this.eventHandler.add('hashchange', this.handleHashChange.bind(this), window);
		// Check hash on initial page load
		this.handleHashChange();
	}


	/**
	 * Handle the click event.
	 * @param {Event} event
	 * @returns {void}
	 */
	handleClick(event) {
		if (!this.buttonArea) {
			return;
		}

		if (event.target.closest('.ButtonArea')) {
			return;
		}

		const wasVisible = this.buttonArea.classList.contains('Visible');

		if (!event.target.matches('#ButtonArea-Toggle')) {
			this.buttonArea.classList.remove('Visible');
			this.buttonArea.inert = !!document.getElementById('ButtonArea-Toggle')?.offsetParent;

			if (wasVisible) {
				// Go back one step in the history
				window.history.back();
			}

			return;
		}

		// Toggle the button area
		this.buttonArea.classList.toggle('Visible');

		const isVisible = this.buttonArea.classList.contains('Visible');

		// Create a new history entry if the button area is visible
		if (isVisible) {
			window.history.pushState({}, '', `${currentUrl}#ButtonArea`);
		}
		// Otherwise go back one step in the history
		else if (wasVisible) {
			window.history.back();
		}

		// Set the inert attribute to true if the button area is not visible
		this.buttonArea.inert = !isVisible && document.getElementById('ButtonArea-Toggle')?.offsetParent;
	}

	/**
	 * Handle URL hash changes
	 * @returns {void}
	 */
	handleHashChange() {
		const hasButtonAreaHash = window.location.hash === '#ButtonArea';

		this.buttonArea.classList.toggle('Visible', hasButtonAreaHash);
		this.buttonArea.inert = !hasButtonAreaHash && document.getElementById('ButtonArea-Toggle')?.offsetParent;
	}
}