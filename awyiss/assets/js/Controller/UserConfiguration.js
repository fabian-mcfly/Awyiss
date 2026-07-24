// noinspection JSUnusedGlobalSymbols

export default class UserConfigurationController {
	/**
	 * The event handler instance.
	 * @type {EventHandler}
	 */
	eventHandler = window.eventHandler;
	/**
	 * The observer instance.
	 * @type {Observer}
	 */
	observer = window.observer;

	constructor() {
		if (!document.body.classList.contains('UserConfigurationController')) {
			return;
		}

		if (document.body.classList.contains('AddAction') || document.body.classList.contains('EditAction')) {
			this.initForm(document.querySelector('.Configuration.Form'));
		}
	}

	/**
	 * Initialize the form related functionality.
	 *
	 * @param {HTMLElement} form The form element
	 */
	initForm(form) {
		this.observer.addObserver(this.observeMutations.bind(this), form);

		const valueInput = form.querySelector('#Configuration-Value');
		valueInput?.addEventListener('input', this.handleColorChange.bind(this));
	}

	/**
	 * Handle color change events.
	 * @param {InputEvent} event
	 */
	handleColorChange(event) {
		const value = event.target.value;
		const scopeInput = document.querySelector('#Configuration-Scope');
		const identifierInput = document.querySelector('#Configuration-Identifier');

		if (
			value &&
			scopeInput?.value === 'System' &&
			identifierInput?.value === 'interface.highlightColor'
		) {
			// Add the value as a custom property to the html
			document.documentElement.style.setProperty('--colorSuccess', value);
		}
		else {
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
 * @type {UserConfigurationController}
 */
window.UserConfigurationController = UserConfigurationController;