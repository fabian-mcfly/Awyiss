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
	dataAttribute = 'data-form-updater';
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
	 * The observer instance.
	 * @type {Observer}
	 */
	observer = window.observer;
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
			this.dataAttribute = dataAttribute

			if (!this.dataAttribute.startsWith('data-')) {
				this.dataAttribute = 'data-' + this.dataAttribute;
			}
		}

		// Attach a single event listener to the document
		this.eventHandler.add('input', this.handleInputEvent.bind(this));

		this.eventHandler.add('change', this.handleChangeEvent.bind(this));

		const forms = document.querySelectorAll('form');
		forms.forEach((form) => {
			form.noValidate = true;
			this.eventHandler.add('submit', this.handleFormSubmit.bind(this), form, true);
		});

		this.observer.addObserver(this.observeMutations.bind(this));
	}


	/**
	 * Handles the change event and checks if the target is a checkbox or radio button.
	 *
	 * @param {Event} event - The event object.
	 */
	handleChangeEvent(event) {
		const target = event.target;
		if (
			!target.closest('form') &&
			(target.type === 'checkbox' || target.type === 'radio')
		) {
			this.handleInputEvent(event);
		}
	}


	/**
	 * Handles the input event by sending a request if the event target is within a form.
	 *
	 * @param {Event} event - The event object.
	 */
	handleInputEvent(event) {
		const form = event.target.form || event.target.closest('form');

		this.hideFlashMessages(form);

		if (event.target.matches('select[data-options-for]')) {
			const targetInputId = event.target.getAttribute('data-options-for');
			const targetInput = document.getElementById(targetInputId);

			if (targetInput) {
				// If the target input allows multiple values, concat the new value
				if (targetInput.dataset.allowMultipleValues === 'true') {
					targetInput.value += (targetInput.value ? ', ' : '') + event.target.value;
				}
				else {
					targetInput.value = event.target.value;
				}
				event.target.value = ''; // reset the select dropdown

				targetInput.instantUpdate = true;
				//Create a new 'change' event and dispatch it on the target input
				targetInput.dispatchEvent(new Event('input', {bubbles: true}));
				targetInput.instantUpdate = false;
			}

			return;
		}

		// Check if the event target has the required data attribute
		if (
			!event.target.matches(`[${this.dataAttribute}="true"]`) &&
			!event.target.closest(`[${this.dataAttribute}="1"]`)
		) {
			return;
		}

		const dataAttributeValue = event.target.getAttribute(this.dataAttribute);

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
				// noinspection JSIgnoredPromiseFromCall
				this.sendRequest(form);
			}, 1000);
		}
		else {
			// For other input types, send the request immediately
			// noinspection JSIgnoredPromiseFromCall
			this.sendRequest(form);
		}
	}

	/**
	 * Handle form submission
	 * If the form is invalid, check if any invalid field is inside a collapsed fieldset
	 * or the sidebar (if it exists and is not visible)
	 *
	 * @param {SubmitEvent} event - The form submit event
	 */
	handleFormSubmit(event) {
		const form = event.target;
		event.preventDefault();

		let hiddenInput = form.querySelector('input[name="submitType"]');
		if (!hiddenInput) {
			hiddenInput = document.createElement('input');
			hiddenInput.type = 'hidden';
			form.appendChild(hiddenInput);
		}

		const submitter = event.submitter;
		hiddenInput.name = submitter?.name ?? 'submitType';
		hiddenInput.value = submitter?.value ?? '';

		//form.noValidate = false;
		if (form.checkValidity()) {
			// If the form is valid, submit it
			form.submit();

			return;
		}

		// Get all invalid fields
		let invalidFields = Array.from(form.querySelectorAll(':invalid'));

		// Filter out fieldsets
		invalidFields = invalidFields.filter(field => field.tagName.toLowerCase() !== 'fieldset');

		// Get the first invalid element
		const firstInvalidElement = invalidFields[0];

		// Check if the first invalid element is not visible
		// .offsetParent is null if the element is not visible
		if (!firstInvalidElement.offsetParent) {
			let fieldset;
			// Expand all parent fieldsets
			while (fieldset = firstInvalidElement.closest('fieldset.Collapsed')) {
				fieldset.classList.remove('Collapsed');
			}
		}

		// Check if the first invalid element is inside the sidebar
		// And if the sidebar is inert
		const sidebar = firstInvalidElement.closest('.Sidebar-Fieldsets');
		const sidebarToggle = document.getElementById('Sidebar-Toggle');
		if (sidebar?.inert && sidebarToggle) {
			sidebarToggle.dispatchEvent(new MouseEvent('click', {bubbles: true, cancelable: true}));

			// Wait for the sidebar to be visible
			setTimeout(() => {
				if (!firstInvalidElement.offsetParent) {
					form.submit();
					return;
				}

				firstInvalidElement.reportValidity();
			}, 300);
			return;
		}

		// If the first invalid element is still invisible, which should never happen,
		// force the form to submit. The server will handle the validation.
		if (!firstInvalidElement.offsetParent) {
			form.submit();
			return;
		}

		// The first element is visible, so report its validity
		firstInvalidElement.reportValidity();

		// Check if the element is inside the current viewport
		const rect = firstInvalidElement.getBoundingClientRect();
		const isVisible = (
			rect.top >= 0 &&
			rect.left >= 0 &&
			rect.bottom <= window.innerHeight &&
			rect.right <= window.innerWidth
		);

		if (!isVisible || document.activeElement !== firstInvalidElement) {
			// The browser was not able to focus the element or scroll it into view
			// Since there's no practical way to do make it visible, submit the form
			form.submit();
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
	 * Hide flash messages when the form is changed.
	 *
	 * @returns {void}
	 */
	hideFlashMessages(form) {
		// Check if the main area has a flash message and remove it
		const formWrapper = form?.closest('.Form');
		const flashMessages = formWrapper?.parentElement.querySelectorAll('.FlashMessage');
		if (!flashMessages?.length) {
			return;
		}

		// Get the current scroll position
		const scrollPosition = window.scrollY;
		let messageHeight = 0;

		flashMessages.forEach((element) => {
			messageHeight += element.offsetHeight + 15;
			element.remove();
		})

		// Set the new scroll position
		window.scrollTo(0, scrollPosition - messageHeight);
	}

	/**
	 * Sends a request with the form data, then replaces the form with the new form from the server response.
	 * Re-attaches event listeners to the new form and re-enables the form inputs.
	 * @param {HTMLFormElement} form - The form to send the request from.
	 * @return {Promise<void>} A Promise that resolves to true if the request was sent, false if the form is locked.
	 */
	sendRequest(form) {
		if (form.dataset.locked === 'true') {
			return Promise.resolve(void(0)); // Always return a Promise
		}

		const formData = new FormData(form);
		// append "reloadForm" key with value "1"
		formData.append('reloadForm', '1');

		form.dispatchEvent(new CustomEvent('beforeUpdate', {
			bubbles: false,
			cancelable: false,
		}));

		// Add a class to the form to show that a reload operation is in progress
		form.classList.add('FetchInProgress');

		// Add a class to the body to show that a reload operation is in progress
		document.body.classList.add('FetchInProgress');

		return fetch(form.action, {
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
				const newFocusElement = form.querySelector(`#${this.lastFocusedElementId}`) || form.querySelector(`[name="${this.lastFocusedElementId}"]`);
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

			form.dispatchEvent(new CustomEvent('afterUpdate', {
				bubbles: false,
				cancelable: false,
			}));
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
	 * Observe mutations.
	 * @param {MutationRecord} mutation
	 */
	observeMutations(mutation) {
		if (!mutation.addedNodes.length) {
			return;
		}

		mutation.addedNodes.forEach(node => {
			if (node.nodeType !== Node.ELEMENT_NODE) {
				return;
			}

			if (node.matches('form')) {
				node.noValidate = true;
				this.eventHandler.add('submit', this.handleFormSubmit.bind(this), node, true);
			}

			// Also check all children
			const forms = node.querySelectorAll('form');
			forms.forEach((form) => {
				form.noValidate = true;
				this.eventHandler.add('submit', this.handleFormSubmit.bind(this), form, true);
			});
		});
	}
}
