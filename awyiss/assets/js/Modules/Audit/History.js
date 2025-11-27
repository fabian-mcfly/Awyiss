//noinspection JSUnusedGlobalSymbols,NpmUsedModulesInstalled

import HistoryTables from 'Audit/HistoryTables';

/**
 * Class to handle the loading of audit history for a given element.
 * Shows the history in an overlay, allows the user to navigate through the history
 * and to restore a previous version, either partially or completely.
 *
 * If the element that triggers the audit history is a form,
 * restoring a previous version will only restore the values of the form fields,
 * without submitting the form.
 */
export default class AuditHistory {
	/**
	 * The overlay element.
	 *
	 * @type {HTMLDialogElement} overlay
	 */
	dialog;
	/**
	 * The selector for the elements that will have audit history loaded.
	 *
	 * @type {string}
	 */
	elementSelector = '.Button-AuditHistory';
	/**
	 * The event handler instance.
	 *
	 * @type {EventHandler}
	 */
	eventHandler = window.eventHandler;
	/**
	 * The form the audit history is loaded for.
	 *
	 * @type {HTMLFormElement|null}
	 */
	form = null;
	/**
	 * Whether the form has been changed when opening the overlay.
	 *
	 * @type {boolean} isFormChanged
	 */
	isFormChanged = false;
	/**
	 * The observer instance.
	 * @type {Observer}
	 */
	observer = window.observer;

	/**
	 * Creates a new instance of the AuditHistory class.
	 */
	constructor() {
		const elements = document.querySelectorAll(this.elementSelector);
		this.bindButtonEvents(elements);

		this.observer.addObserver(this.observeMutations.bind(this));
	}

	/**
	 * Open the overlay to view the audit history.
	 *
	 * @param {HTMLLinkElement} element - The element that triggered the event
	 * @param {Event} event - The event that triggered the function
	 * @returns {Promise<void>}
	 */
	async openOverlay(element, event) {
		event.preventDefault();

		// Store the form element if the element is a form
		this.form = element.closest('form') || element.closest('.Form').querySelector('form');

		// Store the form changed status
		this.isFormChanged = window.formLeaveConfirmation.isFormChanged;

		if (!this.dialog) {
			this.createDialog();
		}

		this.dialog.showModal();

		const history = await this.fetchHistory(element.href);

		if (!history) {
			return;
		}

		this.dialog.appendChild(history);

		// Scroll to the top of the wrapper
		const tables = this.dialog.querySelector('.AuditHistory-Tables');
		tables.scrollTop = 0;

		new HistoryTables();
	}

	/**
	 * Binds the 'click' event to the elements with the given selector.
	 *
	 * @param {NodeList|HTMLElement} elements
	 */
	bindButtonEvents(elements) {
		if (elements instanceof HTMLElement) {
			// noinspection JSValidateTypes
			elements = [elements];
		}

		elements.forEach(element => {
			this.eventHandler.add('click', this.openOverlay.bind(this, element), element);
		});
	}


	/**
	 * Create the dialog element.
	 */
	createDialog() {
		this.dialog = document.createElement('dialog');
		this.dialog.id = 'AuditHistoryDialog';

		this.dialog.addEventListener('close', () => {
			// Remove all children from the dialog
			while (this.dialog.firstChild) {
				this.dialog.removeChild(this.dialog.firstChild);
			}

			// Reset the form changed status
			window.formLeaveConfirmation.isFormChanged = this.isFormChanged;
		});

		this.dialog.addEventListener('click', event => this.handleDialogClick(event));

		// Append dialog to body
		document.body.appendChild(this.dialog);
	}


	/**
	 * Fetch the duplicate configuration form.
	 *
	 * @param {string} href - The URL to fetch the history from
	 * @returns {Promise<Element>}
	 */
	async fetchHistory(href) {
		const response = await fetch(href, {
			method: 'GET',
			headers: {
				'Content-Type': 'application/json',
				'X-Requested-With': 'XMLHttpRequest',
			}
		});

		const html = await response.text();

		const parser = new DOMParser();
		const doc = parser.parseFromString(html, 'text/html');

		return doc.querySelector('.Inner');
	}


	/**
	 * Find elements with the given selector in the
	 * added nodes and bind events to them
	 *
	 * @param {MutationRecord} mutation - The mutation to observe.
	 */
	observeMutations(mutation) {
		if (!mutation.addedNodes.length > 0) {
			return;
		}

		// Iterate over each added node
		mutation.addedNodes.forEach(node => {
			if (node.nodeType !== Node.ELEMENT_NODE) {
				return;
			}

			// If the node matches the element selector, bind the events
			if (node.matches(this.elementSelector)) {
				this.bindButtonEvents(node);
			}

			const elements = node.querySelectorAll(this.elementSelector);
			if (elements) {
				this.bindButtonEvents(elements);
			}
		});
	}

	/**
	 * Handle the click event on the dialog.
	 *
	 * @param {MouseEvent} event - The click event
	 */
	handleDialogClick(event) {
		if (event.target.matches('.Button-Revert')) {
			event.preventDefault();

			const row = event.target.closest('tr');
			const inputName = row.dataset.field;

			// Check if the input is a multi-media input
			if (inputName.startsWith('media_assignments[') && inputName.endsWith('[]')) {
				this.useMultiFile(inputName, row);
			}
			// Check if the input is a data input
			else if (inputName === 'data') {
				this.useData(row);
			}
			else {
				const input = this.form.querySelector(`[name="${inputName}"]`);

				this.useOldValue(input, inputName, row);
			}

			// Force a form reload
			if (typeof window.formUpdater === 'object') {
				this.isFormChanged = true;
				window.formLeaveConfirmation.formChanged();
				window.formUpdater.sendRequest(this.form);
			}

			this.dialog.close();

			return;
		}

		if (event.target.matches('.Button-Close')) {
			event.preventDefault();
			this.dialog.close();
		}
	}

	/**
	 * Use the data input.
	 *
	 * @param {HTMLElement} row - The row element
	 */
	useData(row) {
		const inputs = row.querySelectorAll('input');
		const formInput = this.form.querySelector('.FormInputName-Data');

		// Remove all existing data inputs
		const existingInputs = formInput.querySelectorAll('input');
		existingInputs.forEach((input) => {
			input.remove();
		});

		// If there is only one input (regular, non-array input), add a new input
		if (inputs.length <= 1) {
			const input = document.createElement('input');
			input.type = 'text';
			input.name = 'data[]';
			formInput.appendChild(input);

			return;
		}

		// Add the new data inputs
		inputs.forEach(input => {
			formInput.appendChild(input);
		});

	}

	/**
	 * Use the multi-media input.
	 *
	 * @param {string} inputName - The name of the input element
	 * @param {HTMLElement} row - The row element
	 */
	useMultiFile(inputName, row) {
		// If the input is a multi file selector, remove all existing files and add the new ones
		const inputs = this.form.querySelectorAll(`[name="${inputName}"]`);

		inputs.forEach(input => {
			input.parentElement.remove();
		});

		// Get all the inputs from the row
		const newInputs = row.querySelectorAll('input');
		const fieldsets = this.form.querySelector('.Fieldsets');

		newInputs.forEach(newInput => {
			fieldsets.appendChild(newInput);
		});
	}

	/**
	 * Use the old value of the row in the form.
	 *
	 * @param {HTMLInputElement} input - The input element
	 * @param {string} inputName - The name of the input element
	 * @param {HTMLTableRowElement} row - The row element
	 * @returns {void}
	 */
	useOldValue(input, inputName, row) {
		if (input) {
			const originalValue = row.querySelector('input').value;

			if (input.type === 'hidden') {
				// If the input is hidden, and the next sibling is a checkbox, update the checkbox
				if (input.nextElementSibling && input.nextElementSibling.type === 'checkbox') {
					input.nextElementSibling.checked = originalValue === '1';
				}
				else {
					input.value = originalValue;
				}
			}
			else if (input.type === 'checkbox') {
				input.checked = originalValue === '1';
			}
			else {
				input.value = originalValue;
			}
		}
		else {
			// Clone the input and add it to the form
			const newInput = row.querySelector('input').cloneNode(true);
			const fieldsets = this.form.querySelector('.Fieldsets');

			fieldsets.appendChild(newInput);
		}
	}
}