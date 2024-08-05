//noinspection JSUnusedGlobalSymbols

/**
 * FormUpdater class
 * This class is used to handle form updates and reloads.
 */
export default class FormUpdater {
	/**
	 * The data attribute used to select elements.
	 * @type {string}
	 */
	dataAttribute = 'formUpdater';
	/**
	 * The event handler instance.
	 * @type {EventHandler}
	 */
	eventHandler = window.eventHandler;// Add a new property to store the last focused element's id
	/**
	 * The id or name of the last focused element.
	 * @type {string|null}
	 */
	lastFocusedElementId = null;
	/**
	 * The timeout id for the input event.
	 * @type {number|null}
	 */
	timeoutId = null;

	/**
	 * @param {string|null} dataAttribute - The data attribute used to select elements.
	 */
	constructor(dataAttribute) {
		if (dataAttribute) {
			// Set the data attribute
			if (dataAttribute.startsWith('data-')) {
				dataAttribute = dataAttribute.substring(5);
			}

			this.dataAttribute = this.camelCase(dataAttribute);
		}

		// Attach a single event listener to the document
		this.eventHandler.add('input', this.handleInputEvent.bind(this));
	}

	/**
	 * Handles the input event by sending a request if the event target is within a form.
	 *
	 * @param {Event} event - The event object.
	 */
	handleInputEvent(event) {
		if (event.target.matches('select[data-options-for]')) {
			const targetInputId = event.target.getAttribute('data-options-for');
			const targetInput = document.getElementById(targetInputId);

			if (targetInput) {
				targetInput.value = event.target.value;
				event.target.value = ''; // reset the select dropdown

				targetInput.instantUpdate = true;
				//Create a new 'change' event and dispatch it on the target input
				targetInput.dispatchEvent(new Event('input', {bubbles: true}));
				targetInput.instantUpdate = false;
			}

			return;
		}

		// Check if the event target has the required data attribute
		if (!event.target.dataset[this.dataAttribute]) {
			return;
		}

		const form = this.getForm(event.target);
		const dataAttributeValue = event.target.dataset[this.dataAttribute];

		if (!form || !dataAttributeValue || dataAttributeValue === '0' || dataAttributeValue.toLowerCase() === 'false') {
			return;
		}

		// Clear the previous timeout if it exists
		if (this.timeoutId !== null) {
			clearTimeout(this.timeoutId);
			this.timeoutId = null;
		}

		// Set the lastFocusedElementId to the eventTargetId
		this.lastFocusedElementId = event.target.id || event.target.name;
		// Add the 'focusin' event listener
		this.eventHandler.add('focusin', this.handleFocus.bind(this));

		// If the input type is text, date, number or textarea, set a timeout
		if (
			(
				['text', 'date', 'number'].includes(event.target.type) ||
				event.target.nodeName.toLowerCase() === 'textarea'
			) &&
			event.target.instantUpdate !== true
		) {
			this.timeoutId = setTimeout(() => {
				this.sendRequest(form);
			}, 1000);
		}
		else {
			// For other input types, send the request immediately
			this.sendRequest(form);
		}
	}

	/**
	 * Add a new method to handle the 'focusin' event
	 * @param {Event} event - The event object.
	 */
	handleFocus(event) {
		this.lastFocusedElementId = event.target.id || event.target.name;
	};

	/**
	 * Finds the closest form element to the given element.
	 *
	 * @param {HTMLElement} element - The element to start the search from.
	 * @returns {HTMLFormElement|null} The closest form element, or null if none is found.
	 */
	getForm(element) {
		while (element && element.nodeName.toLowerCase() !== 'form') {
			element = element.parentNode instanceof HTMLElement ? element.parentNode : null;
		}

		return element;
	}

	/**
	 * Sends a request with the form data, then replaces the form with the new form from the server response.
	 * Re-attaches event listeners to the new form and re-enables the form inputs.
	 * @param {HTMLFormElement} form - The form to send the request from.
	 */
	sendRequest(form) {
		const formData = new FormData(form);
		// append "reload_form" key with value "1"
		formData.append('reload_form', '1');

		// Add a class to the form to show that a reload operation is in progress
		form.classList.add('FetchInProgress');

		// Add a class to the body to show that a reload operation is in progress
		document.body.classList.add('FetchInProgress');

		fetch(form.action, {
			method: form.method,
			headers: {
				'X-Requested-With': 'XMLHttpRequest'
			},
			body: formData
		})
		.then(response => response.text())
		.then(html => {
			const newForm = new DOMParser().parseFromString(html, 'text/html').querySelector('form');

			form.querySelector('.Fieldsets').replaceWith(newForm.querySelector('.Fieldsets'));

			try {
				const newFocusElement = newForm.querySelector(`#${this.lastFocusedElementId}`) || newForm.querySelector(`[name="${this.lastFocusedElementId}"]`);
				if (newFocusElement) {
					newFocusElement.focus();
					// Check if the newFocusElement is an input or textarea before calling setSelectionRange
					if (['input', 'textarea'].includes(newFocusElement.tagName.toLowerCase())) {
						newFocusElement.setSelectionRange(newFocusElement.value.length, newFocusElement.value.length);
					}
				}
			}
			catch (error) {
				// Do nothing
			}
		})
		.catch(error => console.error('Error:', error))
		.finally(() => {
			// Remove the class from the form to show that the reload operation is complete
			form.classList.remove('FetchInProgress');

			// Remove the class from the body to show that the reload operation is complete
			document.body.classList.remove('FetchInProgress');

			// Remove the 'focusin' event listener
			this.eventHandler.remove('focusin', this.handleFocus.bind(this));
		});
	}

	/**
	 * Converts a string to camel case.
	 *
	 * @param string
	 * @returns {string}
	 */
	camelCase(string) {
		return string.split('-').map((word, index) => {
			// Don't change the first word
			if (index === 0) {
				return word;
			}
			// Capitalize the first letter of the rest
			return word.charAt(0).toUpperCase() + word.slice(1);
		}).join('');
	}
}
