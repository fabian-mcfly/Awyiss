// noinspection JSUnusedGlobalSymbols

export default class OverlayForm {
	/**
	 * The overlay close button
	 * @type {HTMLElement}
	 */
	closeButton;

	/**
	 * The event handler instance.
	 * @type {EventHandler}
	 */
	eventHandler = window.eventHandler;
	/**
	 * The element selector that will open the overlay.
	 */
	elementSelector = 'a.OverlayForm';
	/**
	 * The Observer instance.
	 * @type {Observer}
	 */
	observer = window.observer;
	/**
	 * The overlay element
	 * @type {HTMLElement}
	 */
	overlayElement;
	/**
	 * The remembered state of the form changed flag.
	 * @type {boolean}
	 */
	savedIsFormChanged = false;

	/**
	 * Initialize the overlay form.
	 * Bind a click event to the close button.
	 * Add a submit event to the overlay.
	 */
	constructor() {
		this.overlayElement = document.getElementById('OverlayForm');

		if (this.overlayElement) {
			// Bind a click event to the close button
			this.eventHandler.add('click', this.handleCloseButton.bind(this), this.overlayElement.querySelector('.Button-Close'));

			// Add a submit event to the overlay
			this.eventHandler.add('submit', this.handleFormSubmit.bind(this), this.overlayElement, true);
		}

		this.observer.addObserver(this.observeMutations.bind(this));

		// Bind a click event to all links that should open their target in an overlay.
		const elements = document.querySelectorAll(this.elementSelector);
		for (const element of elements) {
			this.bindOpenOverlayButton(element);
		}
	}

	/**
	 * Bind a click event to open the overlay.
	 * @param {HTMLElement} element
	 */
	bindOpenOverlayButton(element) {
		this.eventHandler.add('click', this.openOverlay.bind(this), element);
	}

	/**
	 * Close the overlay.
	 * Remove the form from the overlay and move the close button back to the overlay.
	 * Reset the form changed flag to the saved state.
	 */
	closeOverlay() {
		this.overlayElement.classList.remove('Visible');

		// Remove the form from the overlay
		const form = this.overlayElement.querySelector('.Form');
		if (form) {
			const closeButton = this.overlayElement.querySelector('.Button-Close');
			// Reset width and height of the button since the mouse leave event doesn't fire
			closeButton.querySelector('.Hover').style.width = '';
			closeButton.querySelector('.Hover').style.height = '';

			// Move the close button back to the overlay
			this.overlayElement.append(closeButton);

			// Remove the form
			form.remove();
		}

		// Reset the form changed flag
		window.formLeaveConfirmation.isFormChanged = this.savedIsFormChanged;
	}

	/**
	 * Handle the close button click event.
	 * If the form has been changed, show a confirmation dialog.
	 * @param {Event} event
	 */
	handleCloseButton(event) {
		event.preventDefault();

		if (window.formLeaveConfirmation.isFormChanged) {
			window.formLeaveConfirmation.showCustomDialog(() => {
				this.closeOverlay();
			});
		}
		else {
			this.closeOverlay();
		}
	}

	/**
	 * Handle the form submit event.
	 * @param {Event} event
	 */
	handleFormSubmit(event) {
		event.preventDefault();

		const form = this.overlayElement.querySelector('form');

		const formData = new FormData(form);
		formData.append('submit', 'submit_close');

		// Add a class to the form to show that a reload operation is in progress
		form.parentElement.classList.add('FetchInProgress');

		// Add a class to the body to show that a reload operation is in progress
		document.body.classList.add('FetchInProgress');

		fetch(form.action, {
			method: form.method,
			headers: {
				'X-Requested-With': 'XMLHttpRequest'
			},
			body: formData,
			redirect: 'manual',
		})
		.then(response => {
			if (response.type === 'opaqueredirect') {
				// Dispatching an event seems to be a good idea here
				// to let other scripts know that the form was successfully submitted
				const event = new CustomEvent('overlayFormSubmitted', {
					bubbles: true,
					cancelable: true,
					detail: {
						form: form,
						response: response,
					}
				});
				document.dispatchEvent(event);

				// A redirect was attempted, which means the form was successfully submitted
				this.closeOverlay();
			}
			else {
				// No redirect was attempted, handle the response normally
				return response.text();
			}
		})
		.then(html => {
			if (!html) {
				return;
			}

			// noinspection JSCheckFunctionSignatures
			const newForm = new DOMParser().parseFromString(html, 'text/html').querySelector('form');

			form.querySelector('.Fieldsets').replaceWith(newForm.querySelector('.Fieldsets'));

			// Get the close button
			const closeButton = this.overlayElement.querySelector('.Button-Close').cloneNode(true);
			// Bind a click event to the close button
			this.eventHandler.add('click', this.handleCloseButton.bind(this), closeButton);
			// Append a clone of the close button to the form
			form.querySelector('.ButtonArea.Bottom').append(closeButton.cloneNode(true));
		})
		.catch(error => console.error('Error:', error))
		.finally(() => {
			// Remove the class from the form to show that the reload operation is complete
			form.parentElement.classList.remove('FetchInProgress');

			// Remove the class from the body to show that the reload operation is complete
			document.body.classList.remove('FetchInProgress');
		});
	}

	/**
	 * Open the overlay and fetch the target URL.
	 * Place the fetched content in the overlay, then show the overlay.
	 * For consistency, the overlay will not show the h1 and only the "save and close" button.
	 *
	 * @param {Event} event
	 */
	openOverlay(event) {
		event.preventDefault();

		const element = event.target;

		this.savedIsFormChanged = window.formLeaveConfirmation.isFormChanged;
		window.formLeaveConfirmation.isFormChanged = false;

		// If the overlay element doesn't exist yet, create it.
		if (!this.overlayElement) {
			this.overlayElement = document.createElement('div');
			this.overlayElement.id = 'OverlayForm';

			// Add a close button
			const closeButton = document.createElement('button');
			closeButton.classList.add('Button', 'Button-Close');
			closeButton.innerHTML = 'Close';
			this.overlayElement.append(closeButton);

			// Bind a click event to the close button
			this.eventHandler.add('click', this.handleCloseButton.bind(this), closeButton);

			// Add a submit event to the overlay
			this.eventHandler.add('submit', this.handleFormSubmit.bind(this), this.overlayElement, true);

			// Append the overlay to the body
			document.body.append(this.overlayElement);
		}

		let form = this.overlayElement.querySelector('.Form');
		if (!form) {
			form = document.createElement('div');
			form.classList.add('Form');
			this.overlayElement.append(form);
		}

		let target = element.getAttribute('href');
		if (!target.endsWith('/')) {
			target += '/';
		}
		target += 'ajax-form:1';

		// Fetch the target URL
		fetch(target, {
			method: 'GET',
			headers: {
				'X-Requested-With': 'XMLHttpRequest'
			},
		})
		.then(response => response.text())
		.then(html => {
			// Parse the response text to HTML
			const parser = new DOMParser();
			const doc = parser.parseFromString(html, 'text/html');

			form.replaceWith(doc.querySelector('.Form'));
			form = this.overlayElement.querySelector('.Form');

			// Remove the default save buttons
			const saveButtons = form.querySelectorAll('.Button-Success.Button-Save');
			for (const button of saveButtons) {
				button.remove();
			}

			// Remove .BackToOverview links
			const backToOverview = form.querySelectorAll('.BackToOverview');
			for (const link of backToOverview) {
				link.remove();
			}

			// Give the form the data-title attribute containing the h1 and remove the h1
			const title = form.querySelector('h1');
			if (title) {
				form.setAttribute('data-title', title.textContent);
				title.remove();
			}

			// Move the close button to the form
			const closeButton = this.overlayElement.querySelector('.Button-Close');
			form.append(closeButton);

			// Get the first save button, append it to the form and give it an offset
			let saveButton = form.querySelector('.Button-SaveClose');
			saveButton.classList.add('Button-Success');
			saveButton.style.setProperty('--offsetX', closeButton.offsetWidth + 'px');
			form.append(saveButton);

			// Set the form id and the form attribute of the save button
			const realForm = form.querySelector('form');
			let formId = realForm.getAttribute('id');
			if (!formId) {
				// Create a unique id for the form
				formId = `Form-${Date.now()}`;
				realForm.id = formId;
			}
			saveButton.setAttribute('form', formId);

			// Get the second save button
			saveButton = realForm.querySelector('.Button-SaveClose');
			saveButton.classList.add('Button-Success');

			// Add a clone of the close button to the form
			const closeButtonClone = closeButton.cloneNode(true);
			closeButtonClone.type = 'button';
			// Bind a click event to the close button
			this.eventHandler.add('click', this.handleCloseButton.bind(this), closeButtonClone);
			saveButton.parentElement.append(closeButtonClone);

			// Show the overlay
			this.overlayElement.classList.remove('FetchInProgress');
			this.overlayElement.classList.add('Visible');

			// When everything is ready, dispatch an event
			const event = new CustomEvent('overlayFormLoaded', {
				bubbles: true,
				cancelable: true,
				detail: {
					form: realForm,
				}
			});
			document.dispatchEvent(event);
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

		for (const node of mutation.addedNodes) {
			if (node.nodeType !== Node.ELEMENT_NODE) {
				return;
			}

			if (node.matches(this.elementSelector)) {
				this.bindOpenOverlayButton(node);
			}

			// Also check all children
			const elements = node.querySelectorAll(this.elementSelector);
			for (const element of elements) {
				this.bindOpenOverlayButton(element);
			}
		}
	}
}