//noinspection JSUnusedGlobalSymbols

export default class SystemController {
	/**
	 * The event handler instance.
	 * @type {EventHandler}
	 */
	eventHandler = window.eventHandler;

	constructor() {
		const clearCacheButton = document.querySelector('.Button-ClearCache');
		if (clearCacheButton) {
			this.eventHandler.add('click', this.clearCache.bind(this, clearCacheButton), clearCacheButton);
		}
	}

	/**
	 * Clear the cache.
	 * @param {HTMLElement} button - The button that was clicked.
	 * @param {Event} event - The event object.
	 */
	clearCache(button, event) {
		event.preventDefault();

		if (button.classList.contains('FetchInProgress')) {
			return;
		}

		// Disable the button
		button.appendChild(document.createElement('div')).className = 'Loading';
		button.classList.add('FetchInProgress');

		// Reset width and height of the button since disabled buttons have no pointer events
		// so the mouse leave event won't be triggered
		const hoverElement = button.querySelector('.Hover');
		if (hoverElement) {
			hoverElement.style.width = '';
			hoverElement.style.height = '';
		}

		// Send the request
		this.sendClearCacheRequest(button);
	}

	/**
	 * Send the request to clear the cache.
	 *
	 * @param {HTMLElement} button - The button that was clicked.
	 * @returns {Promise}
	 */
	sendClearCacheRequest(button) {
		return fetch(button.href, {
			method: 'POST',
			headers: {
				'X-Requested-With': 'XMLHttpRequest',
			},
		}).then(response => {
			if (!response.ok) {
				button.classList.remove('FetchInProgress');

				throw new Error('Failed to clear the cache.');
			}

			return response.json();
		}).then(data => {
			if (data.runningJob.completed) {
				button.classList.remove('FetchInProgress');
			}
			else {
				setTimeout(() => this.sendClearCacheRequest(button), 500);
			}
		});
	}

}


/**
 * Expose the class globally
 * @global
 * @type {SystemController}
 */
window.SystemController = SystemController;
