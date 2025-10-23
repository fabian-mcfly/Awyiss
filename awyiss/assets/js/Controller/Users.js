//noinspection JSUnusedGlobalSymbols

export default class UsersController {
	/**
	 * The event handler instance.
	 * @type {EventHandler}
	 */
	eventHandler = window.eventHandler;
	/**
	 * The selector for the password field.
	 * @type {string}
	 */
	passwordFieldSelector = '.FormInputName-Password';

	constructor() {
		if (!document.body.classList.contains('UsersController')) {
			return;
		}

		if (document.body.classList.contains('AddAction') || document.body.classList.contains('EditAction')) {
			this.initForm();
		}
	}

	/**
	 * Set up the password field to contain
	 * a password generator field.
	 */
	initForm() {
		const elements = document.querySelectorAll(this.passwordFieldSelector);
		elements.forEach((element) => {
			this.setupPasswordGenerator(element);
		});

		const observer = window.observer;
		observer.addObserver(this.observeMutations.bind(this));
	}

	/**
	 * Set up the password generator field.
	 *
	 * @param {HTMLElement} element - The element to set up the password
	 */
	setupPasswordGenerator(element) {
		const passwordGeneratorButton = document.createElement('span');
		passwordGeneratorButton.classList.add('Button', 'PasswordGenerator');
		passwordGeneratorButton.innerHTML = 'Generate Password';
		passwordGeneratorButton.addEventListener('click', this.generatePassword.bind(this));

		element.appendChild(passwordGeneratorButton);
	}

	/**
	 * Generate a password and set it to the password field.
	 *
	 * @param {Event} event - The event to
	 */
	generatePassword(event) {
		event.preventDefault();

		const password = this.generateRandomPassword();

		const form = event.target.closest('form');
		const passwordField = form.querySelector(this.passwordFieldSelector).querySelector('input[name="password"]');
		const passwordFieldConfirm = form.querySelector('input[name="password_confirm"]');

		passwordField.type = 'text';
		passwordField.value = password;
		passwordFieldConfirm.value = password;
	}

	/**
	 * Generate a random password.
	 */
	generateRandomPassword() {
		const length = 20;
		const charset = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()_+{}:"<>?';
		let password = '';

		for (let i = 0; i < length; i++) {
			const at = Math.floor(Math.random() * charset.length);
			password += charset.charAt(at);
		}

		return password;
	}

	/**
	 * Observe mutations in the DOM and set up the duplicate of configuration.
	 *
	 * @param {MutationRecord} mutation - The mutation to observe.
	 */
	observeMutations(mutation) {
		if (!mutation.addedNodes.length > 0) {
			return;
		}

		mutation.addedNodes.forEach((node) => {
			if (node.matches(this.passwordFieldSelector)) {
				this.setupPasswordGenerator(node);
			}

			const elements = node.querySelectorAll(this.passwordFieldSelector);
			elements.forEach((element) => {
				this.setupPasswordGenerator(element);
			});
		});
	}
}


/**
 * Expose the class globally
 * @global
 * @type {UsersController}
 */
window.UsersController = UsersController;
