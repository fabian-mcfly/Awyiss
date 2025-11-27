//noinspection JSUnusedGlobalSymbols

export default class PasswordReveal {
	/**
	 * The class name for the reveal password button.
	 * @type {string}
	 */
	buttonClass = 'RevealPassword';

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

	/**
	 * Selects all password input fields and adds a button to toggle visibility of each password.
	 */
	constructor() {
		// Select all password input fields
		let passwordFields = document.querySelectorAll('input[type="password"]');

		// Iterate over each password field
		passwordFields.forEach((passwordField) => {
			this.addShowPasswordButton(passwordField);
		});

		this.observer.addObserver(this.observeForNewPasswordFields.bind(this));

		// Attach the event handler to the window
		this.eventHandler.add('click', this.handleShowPasswordButtonClick.bind(this));
	}

	/**
	 * Adds a button to toggle visibility of a password field.
	 * @param {HTMLInputElement} passwordField - The password field.
	 */
	addShowPasswordButton(passwordField) {
		// Create a new button
		let showPasswordButton = document.createElement('button');
		showPasswordButton.textContent = passwordField.dataset.showPasswordLabel ?? 'Show Password';
		showPasswordButton.classList.add('RevealPassword');

		// Set the type of the button to 'button' to prevent form submission
		showPasswordButton.type = 'button';

		// Append the button next to the password field
		passwordField.parentNode.insertBefore(showPasswordButton, passwordField.nextSibling);
	}

	/**
	 * Handles the click event on the Show Password button.
	 * @param {Event} event - The event object.
	 */
	handleShowPasswordButtonClick(event) {
		// Check if the event target is a RevealPassword button
		if (!event.target.classList.contains(this.buttonClass)) {
			return;
		}

		// Get the password field associated with the button
		const passwordField = event.target.previousSibling;

		// Toggle the type of the password field
		if (passwordField.type === 'password') {
			passwordField.type = 'text';
			event.target.classList.add('Revealed');
		}
		else {
			passwordField.type = 'password';
			event.target.classList.remove('Revealed');
		}
	}

	/**
	 * Observes the document for added password fields.
	 * @param {MutationRecord} mutation - The mutation to observe.
	 */
	observeForNewPasswordFields(mutation) {
		mutation.addedNodes.forEach(node => {
			if (node.nodeType !== Node.ELEMENT_NODE) {
				return;
			}

			if (node.matches('input[type="password"]')) {
				this.addShowPasswordButton(node);
			}

			// Also check the children of the node
			node.querySelectorAll('input[type="password"]').forEach((childNode) => {
				this.addShowPasswordButton(childNode);
			});
		});
	}
}