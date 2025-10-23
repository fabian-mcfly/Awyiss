// noinspection JSUnusedGlobalSymbols

export default class UserConfigurationController {
	/**
	 * The event handler instance.
	 * @type {EventHandler}
	 */
	eventHandler = window.eventHandler;

	constructor() {
		if (!document.body.classList.contains('UserConfigurationController')) {
			return;
		}

		if (document.body.classList.contains('AddAction') || document.body.classList.contains('EditAction')) {
			this.initForm(document.querySelector('input[name="value"]'));

			const observer = window.observer;
			observer.addObserver(this.observeMutations.bind(this));
		}
	}

	/**
	 * Initialize the form related functionality.
	 */
	initForm(input) {
		if (!input || !input.closest('.clr-field')) {
			return;
		}

		// Add an input event listener to the input field
		input.addEventListener('input', event => {
			const value = event.target.value;

			if (value) {
				// Add the value as a custom property to the html
				document.documentElement.style.setProperty('--colorSuccess', value);
			}
			else {
				// Remove the custom property from the html
				document.documentElement.style.removeProperty('--colorSuccess');
			}
		});
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

			this.initForm(node.querySelector('input[name="value"]'));
		});
	}
}


/**
 * Expose the class globally
 * @global
 * @type {UserConfigurationController}
 */
window.UserConfigurationController = UserConfigurationController;