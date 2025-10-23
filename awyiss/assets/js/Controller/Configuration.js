// noinspection JSUnusedGlobalSymbols

export default class ConfigurationController {
	/**
	 * The event handler instance.
	 * @type {EventHandler}
	 */
	eventHandler = window.eventHandler;

	constructor() {
		if (!document.body.classList.contains('ConfigurationController')) {
			return;
		}

		if (document.body.classList.contains('AddAction') || document.body.classList.contains('EditAction')) {
			this.initForm();
		}
	}

	/**
	 * Initialize the form related functionality.
	 */
	initForm() {
		const observer = window.observer;
		observer.addObserver(this.observeMutations.bind(this));

		const valueInput = document.getElementById('Configuration-Value');
		valueInput?.addEventListener('input', this.handleColorChange.bind(this));
	}

	/**
	 * Handle color change events.
	 * @param {InputEvent} event
	 */
	handleColorChange(event) {
		const value = event.target.value;

		if (value) {
			// Add the value as a custom property to the html
			document.documentElement.style.setProperty('--colorSuccess', value);
		}
		else {
			// Remove the custom property from the html
			document.documentElement.style.removeProperty('--colorSuccess');
		}
	}

	/**
	 * Observe mutations.
	 * @param {MutationRecord} mutation
	 */
	observeMutations(mutation) {
		mutation.addedNodes.forEach(node => {
			if (node.nodeType !== Node.ELEMENT_NODE) {
				return;
			}

			const valueInput = node.querySelector('#Configuration-Value');
			valueInput?.addEventListener('input', this.handleColorChange.bind(this));
		});
	}
}


/**
 * Expose the class globally
 * @global
 * @type {ConfigurationController}
 */
window.ConfigurationController = ConfigurationController;