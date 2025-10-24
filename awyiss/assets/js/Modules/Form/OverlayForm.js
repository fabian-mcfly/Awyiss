// noinspection JSUnusedGlobalSymbols

export default class OverlayForm {
	/**
	 * The overlay close button
	 * @type {HTMLElement}
	 */
	closeButton;
	/**
	 * The overlay element
	 * @type {HTMLDialogElement}
	 */
	dialog;
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
	 * The element that opened the overlay.
	 * @type {HTMLElement}
	 */
	opener;
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
		this.dialog = document.getElementById('OverlayForm');

		if (this.dialog) {
			this.eventHandler.add('click', this.handleClick.bind(this), this.dialog);
			this.eventHandler.add('submit', this.handleFormSubmit.bind(this), this.dialog, true);
			this.eventHandler.add('close', this.handleClose.bind(this), this.dialog);
			this.eventHandler.add('cancel', (event) => {
				if (window.formLeaveConfirmation.isFormChanged) {
					event.preventDefault();

					window.formLeaveConfirmation.showCustomDialog(() => {
						this.closeOverlay();
					});
				}
			}, this.dialog);
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
		this.eventHandler.add('click', this.openOverlay.bind(this, element), element);
	}

	/**
	 * Close the overlay.
	 * Remove the form from the overlay and move the close button back to the overlay.
	 * Reset the form changed flag to the saved state.
	 */
	closeOverlay() {
		this.dialog.close();
	}

	/**
	 * Handle the close event of the overlay.
	 */
	handleClose() {
		// Remove the form from the overlay
		const form = this.dialog.querySelector('.Form');
		if (form) {
			const buttonArea = this.dialog.querySelector(':scope > .ButtonArea');
			const closeButton = buttonArea.querySelector('.Button-Close');

			// Reset width and height of the button since the mouse leave event doesn't fire
			const hoverElement = closeButton.querySelector('.Hover');
			if (hoverElement) {
				hoverElement.style.width = '';
				hoverElement.style.height = '';
			}

			// Move the close button back to the overlay
			this.dialog.append(closeButton);

			// Unlock the loaded entity
			this.unlockEntity(form);

			// Remove the form
			form.remove();
			// Remove the button area
			buttonArea.remove();
		}

		// Reset the form changed flag
		window.formLeaveConfirmation.isFormChanged = this.savedIsFormChanged;
	}

	/**
	 * Handle the click event on the overlay.
	 * @param {Event} event
	 */
	handleClick(event) {
		if (event.target.classList.contains('Button-Close')) {
			this.closeOverlay();
		}
	}

	/**
	 * Handle the form submit event.
	 * @param {Event} event
	 */
	handleFormSubmit(event) {
		event.preventDefault();
		event.stopPropagation();

		const form = this.dialog.querySelector('form');

		if (form.dataset.locked === 'true') {
			return;
		}

		const formData = new FormData(form);
		formData.append('submit_type', 'submit_close');

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

				const parentForm = this.opener?.closest('form');
				if (parentForm.length) {
					// If the opener is inside a form, reload the form
					window.formUpdater.sendRequest(parentForm);
				}

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
	 * @param {HTMLElement} opener
	 * @param {Event} event
	 */
	openOverlay(opener, event) {
		event.preventDefault();

		this.opener = opener;
		const element = event.target;

		this.savedIsFormChanged = window.formLeaveConfirmation.isFormChanged;
		window.formLeaveConfirmation.isFormChanged = false;

		// If the overlay element doesn't exist yet, create it.
		if (!this.dialog) {
			this.dialog = document.createElement('dialog');
			this.dialog.id = 'OverlayForm';

			// Add a close button
			const closeButton = document.createElement('button');
			closeButton.classList.add('Button', 'Button-Close');
			closeButton.innerHTML = 'Close';
			this.dialog.append(closeButton);

			// Add a submit event to the overlay
			this.eventHandler.add('submit', this.handleFormSubmit.bind(this), this.dialog, true);

			// Append the overlay to the body
			document.body.append(this.dialog);

			this.eventHandler.add('close', this.handleClose.bind(this), this.dialog);
		}

		let form = this.dialog.querySelector('.Form');
		if (!form) {
			form = document.createElement('div');
			form.classList.add('Form');
			this.dialog.append(form);
		}

		let target = element.getAttribute('href');
		if (!target.endsWith('/')) {
			target += '/';
		}
		target += 'ajax-form:1/';

		// Show the overlay and mark it as loading
		this.dialog.classList.add('FetchInProgress');
		this.dialog.showModal();

		let controllerData = {};

		// Fetch the target URL
		fetch(target, {
			method: 'GET',
			headers: {
				'X-Requested-With': 'XMLHttpRequest'
			},
		})
		.then(response => {
			const overlayFormController = response.headers.get('X-OverlayForm-Controller');
			const overlayFormControllerClass = response.headers.get('X-OverlayForm-ControllerClass');

			controllerData['controller'] = overlayFormController || null;
			controllerData['controllerClass'] = overlayFormControllerClass || null;

			return response.text();
		})
		.then(html => {
			// Parse the response text to HTML
			const parser = new DOMParser();
			const doc = parser.parseFromString(html, 'text/html');

			form.replaceWith(doc.querySelector('.Form'));
			form = this.dialog.querySelector('.Form');

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
				this.dialog.setAttribute('data-title', title.textContent);
				title.remove();
			}

			// Get the first button area
			const buttonArea = form.querySelector('.ButtonArea');

			// Move the button area above of the form
			form.insertAdjacentElement('beforebegin', buttonArea);

			// Move the close button to the first button area
			const closeButton = this.dialog.querySelector('.Button-Close');
			buttonArea.append(closeButton);

			// Get the first save button, append it to the form and give it an offset
			let saveButton = form.querySelector('.Button-SaveClose');
			saveButton.classList.add('Button-Success');

			form.querySelector('.Headlines').remove();
			form.querySelector('#ButtonArea-Toggle').remove();

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
			saveButton = form.querySelector('.ButtonArea-Bottom .Button-SaveClose');
			saveButton.classList.add('Button-Success');

			// Add a clone of the close button to the form
			const closeButtonClone = closeButton.cloneNode(true);
			closeButtonClone.type = 'button';
			saveButton.parentElement.append(closeButtonClone);

			// Show the overlay
			this.dialog.classList.remove('FetchInProgress');

			// Scroll the form to the top
			form.scrollTo(0, 0);

			// When everything is ready, dispatch an event
			const event = new CustomEvent('overlayFormLoaded', {
				bubbles: true,
				cancelable: true,
				detail: {
					form: realForm,
				}
			});
			document.dispatchEvent(event);

			this.dialog.focus();

			this.loadControllerClass(controllerData);
		})
	}

	/**
	 * Load the controller class for the overlay form, if specified.
	 *
	 * @param {Object} controllerData
	 */
	async loadControllerClass(controllerData) {
		if (!controllerData.controllerClass) {
			return;
		}

		const controllerClassName = `${controllerData.controller}Controller`;

		if (window[controllerClassName]) {
			return;
		}

		// Dynamically import the controller class
		const module = await import(`../../Controller/${controllerData.controller}.js`);
		if (module && module.default) {
			const controllerInstance = new module.default();

			// If there's an `initForm` method, call it
			if (typeof controllerInstance.initForm === 'function') {
				controllerInstance.initForm(this.dialog.querySelector('.Form'));
			}
		}
	}

	/**
	 * Sends a request to unlock the entity,
	 * just like the beacon does when the form is closed regularly.
	 *
	 * @param form
	 */
	unlockEntity(form) {
		// Check if there's a lock dialog inside the form
		const lockDialog = form.querySelector('.LockDialog');
		if (!lockDialog) {
			return;
		}

		window.formLock.handleUnload('#OverlayForm form');
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