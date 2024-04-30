//noinspection JSUnusedGlobalSymbols

import CustomMouseEvent from 'CustomMouseEvent';

/**
 * The FormLeaveConfirmation class provides a way to prevent users from accidentally leaving a form with unsaved changes.
 * It listens for input changes on forms, clicks on links within the document, and form submit events.
 * When a user tries to leave the page with unsaved changes, a custom dialog is displayed to confirm their action.
 * The class also handles the beforeunload event to display a browser-native dialog when the user tries to leave the page.
 */
export default class FormLeaveConfirmation {
	/**
	 * The event handler instance.
	 * @type {EventHandler}
	 */
	eventHandler = window.eventHandler;
	/**
	 * A flag indicating whether a form has been changed.
	 * @type {boolean}
	 */
	isFormChanged = false;
	/**
	 * A flag indicating whether a form is currently being submitted.
	 * @type {boolean}
	 */
	isFormSubmitting = false;

	constructor() {
		// Listen for input changes on forms
		this.eventHandler.add('input', this.handleInputEvent.bind(this));

		// Create the custom dialog
		this.createDialog();
	}

	/**
	 * Handle input events on form elements.
	 * @param event
	 */
	handleInputEvent(event) {
		// Check if the input event is triggered by a form element
		if (!event.target.form || this.isFormChanged) {
			return;
		}

		// Mark the form as changed if any input changes
		this.isFormChanged = true;

		// Custom handling for clicks on links within the document for a better user experience
		window.eventHandler.add('click', this.handleNavigationAttempt.bind(this), window, {}, 999);

		// Listen for the beforeunload event to display a browser-native dialog
		this.eventHandler.add('beforeunload', this.handleBeforeUnload.bind(this), window, {}, 999);

		// Listen for form submit events
		this.eventHandler.add('submit', this.handleFormSubmit.bind(this), window, {}, 999);
	}

	/**
	 * Handle navigation attempts by intercepting clicks on links within the document.
	 * @param event
	 */
	handleNavigationAttempt(event) {
		if (event.defaultPrevented || event.sentFromFormLeaveConfirmDialog || event.target.tagName !== 'A' || !this.isFormChanged) {
			return;
		}

		event.preventDefault();

		this.showCustomDialog(() => {
			const newEvent = new CustomMouseEvent('click', {sentFromFormLeaveConfirmDialog: true}, {
				bubbles: true,
				cancelable: true,
				view: window
			});

			// Dispatch the event
			event.target.dispatchEvent(newEvent);
		});
	}

	/**
	 * Handle the beforeunload event to display a browser-native dialog.
	 * @param event
	 */
	handleBeforeUnload(event) {
		if (event.defaultPrevented) {
			return;
		}

		// Only trigger the beforeunload event if a form submit is not taking place
		if (this.isFormChanged && !this.isFormSubmitting) {
			event.returnValue = 'You have unsaved changes. Are you sure you want to leave?';
		}
	}

	/**
	 * Handle form submit events.
	 * @param event
	 */
	handleFormSubmit(event) {
		// If a form is being submitted, set isFormSubmitting to true
		if (event.target.tagName === 'FORM') {
			this.isFormSubmitting = true;
		}
	}

	/**
	 * Create the custom dialog.
	 * If a dialog with the specified ID already exists, get its child elements.
	 */
	createDialog() {
		// Check if a dialog with the specified ID already exists
		this.dialog = document.getElementById('FormLeaveConfirmDialog');

		if (!this.dialog) {
			// Create dialog and its child elements
			this.dialog = document.createElement('dialog');
			this.dialog.message = document.createElement('p');
			this.dialog.confirmLeave = document.createElement('button');
			this.dialog.confirmStay = document.createElement('button');

			// Set IDs for later use
			this.dialog.id = 'FormLeaveConfirmDialog';
			this.dialog.message.id = 'FormLeaveConfirmDialog-Message';
			this.dialog.confirmLeave.id = 'FormLeaveConfirmDialog-Button-Leave';
			this.dialog.confirmLeave.classList.add('Button', 'Button-Error');
			this.dialog.confirmStay.id = 'FormLeaveConfirmDialog-Button-Stay';
			this.dialog.confirmStay.classList.add('Button');

			this.dialog.message.textContent = 'You have unsaved changes. Are you sure you want to leave?';

			// Set button text
			this.dialog.confirmLeave.textContent = 'Leave page';
			this.dialog.confirmStay.textContent = 'Stay on page';

			// Append elements to dialog
			this.dialog.appendChild(this.dialog.message);
			this.dialog.appendChild(this.dialog.confirmLeave);
			this.dialog.appendChild(this.dialog.confirmStay);

			// Append dialog to body
			document.body.appendChild(this.dialog);
		}
		else {
			// If the dialog already exists, get its child elements
			this.dialog.message = document.getElementById('FormLeaveConfirmDialog-Message');
			this.dialog.confirmLeave = document.getElementById('FormLeaveConfirmDialog-Button-Leave');
			this.dialog.confirmStay = document.getElementById('FormLeaveConfirmDialog-Button-Stay');
		}

		// Attach close event handler
		this.eventHandler.add('click', function () {
			this.dialog.close();
		}.bind(this), this.dialog.confirmStay);
	}

	/**
	 * Show the custom dialog.
	 * @param onConfirm
	 */
	showCustomDialog(onConfirm) {
		const confirmLeaveButton = this.dialog.confirmLeave;

		// Remove any existing event listeners
		const newConfirmLeaveButton = confirmLeaveButton.cloneNode(true);
		this.dialog.replaceChild(newConfirmLeaveButton, confirmLeaveButton);
		this.dialog.confirmLeave = newConfirmLeaveButton;

		// Confirm navigation
		this.eventHandler.add('click', function () {
			this.dialog.close();
			this.isFormSubmitting = true;

			// Navigate away or perform the action that was intercepted
			onConfirm();
		}.bind(this), this.dialog.confirmLeave);

		this.dialog.showModal();
	}


	/**
	 * Unlock the form.
	 */
	unlock() {
		// Method to programmatically allow navigation regardless of form changes
		this.isFormChanged = false;
		this.isFormSubmitting = false;
	}
}
