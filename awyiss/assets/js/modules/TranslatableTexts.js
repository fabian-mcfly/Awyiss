//noinspection JSUnusedGlobalSymbols

/**
 * Class to add a translate-button to an input element.
 * The button shows a dialog with all translations when clicked.
 * Before the parent form is submitted, it checks if the required input is not empty.
 * If it is, it sets the value of the current language as its value.
 */
export default class TranslatableTexts {
	/**
	 * The class name for the translate-button.
	 * @type {string}
	 */
	buttonClass = 'ShowTranslationDialog';
	/**
	 * The selector for the elements with the translate-fields
	 * @type {string}
	 */
	elementSelector = '.TranslatableTexts';
	/**
	 * The event handler instance.
	 * @type {EventHandler}
	 */
	eventHandler = window.eventHandler;

	/**
	 * Constructor to initialize the Translator.
	 */
	constructor() {
		// Get all elements with the TranslatableTexts class
		this.elements = Array.from(document.querySelectorAll(this.elementSelector));

		/**
		 * @type {import('./Observer').default}
		 */
		const observer = window.observer;
		observer.addObserver(this.observeMutations.bind(this));

		// Create a single dialog for all elements
		this.createDialog();

		this.elements.forEach(element => {
			// Add a translate-button to each element
			this.addTranslateButton(element);

			// Add a listener to the form submit event for each element
			this.addFormSubmitListener(element);
		});
	}

	/**
	 * Creates a dialog element with Apply and Cancel buttons.
	 * The dialog is used to display translations for an element.
	 * @returns {HTMLDialogElement} The created dialog element.
	 */
	createDialog() {
		// Check if a dialog with the specified ID already exists
		this.dialog = document.getElementById('TranslationDialog');

		if (!this.dialog) {
			// Create dialog and its child elements
			this.dialog = document.createElement('dialog');
			this.dialog.message = document.createElement('p');
			this.dialog.confirmApply = document.createElement('button');
			this.dialog.confirmCancel = document.createElement('button');

			// Set IDs for later use
			this.dialog.id = 'TranslationDialog';
			this.dialog.message.id = 'TranslationDialog-Message';
			this.dialog.confirmApply.id = 'TranslationDialog-Apply';
			this.dialog.confirmApply.classList.add('Button', 'Button-Success');
			this.dialog.confirmCancel.id = 'TranslationDialog-Cancel';
			this.dialog.confirmCancel.classList.add('Button', 'Button-Error');

			// Set button text
			this.dialog.confirmApply.textContent = 'Apply';
			this.dialog.confirmCancel.textContent = 'Cancel';

			// Append elements to dialog
			this.dialog.appendChild(this.dialog.message);
			this.dialog.appendChild(this.dialog.confirmApply);
			this.dialog.appendChild(this.dialog.confirmCancel);

			// Append dialog to body
			document.body.appendChild(this.dialog);
		}
		else {
			// If the dialog already exists, get its child elements
			this.dialog.message = document.getElementById('TranslationDialog-Message');
			this.dialog.confirmApply = document.getElementById('TranslationDialog-Apply');
			this.dialog.confirmCancel = document.getElementById('TranslationDialog-Cancel');
		}

		// Add event listeners
		window.eventHandler.add('click', this.applyChanges.bind(this), this.dialog.confirmApply);
		window.eventHandler.add('click', () => this.dialog.close(), this.dialog.confirmCancel);
	}

	/**
	 * Adds a translate-button to the given element.
	 * The button shows the dialog with translations when clicked.
	 * @param {HTMLElement} element - The element to add the translate-button to.
	 */
	addTranslateButton(element) {
		// Create a new button element
		const button = document.createElement('button');
		button.classList.add('Button', 'Button-Medium', this.buttonClass);

		// Set the type of the button to "button" to prevent it from submitting the form
		button.type = 'button';

		// Set the text content of the button to the value of the 'data-translate-button' attribute
		button.textContent = element.dataset.buttonTitle || 'Translate';
		button.title = button.textContent;

		// Add an event listener to the button to show the dialog when clicked
		this.eventHandler.add('click', this.handleTranslateButtonClick.bind(this, element), button);

		// Insert the button next to the element
		element.parentNode.insertBefore(button, element.nextSibling);
	}

	/**
	 * Adds a listener to the form submit event.
	 * If the required input is empty, it sets its value to the value of the current language.
	 * @param {HTMLElement} element - The input element to add the-translate button to.
	 */
	addFormSubmitListener(element) {
		// Find the form that contains the element
		const form = element.closest('form');

		// If the element is not enclosed within a form, exit the function early
		if (!form) {
			return;
		}

		form.noValidate = true;

		// Add an event listener to the submit event of the form
		this.eventHandler.add('submit', (event) => {
			if (event.defaultPrevented) {
				return;
			}

			// Prevent the form from being submitted
			event.preventDefault();

			// Get the required input element
			const requiredInput = element.querySelector('input[required], textarea[required], select[required]');
			// If the required input is empty, set its value to the value of the current language
			if (requiredInput && requiredInput.value === '') {
				const currentLanguageElement = element.querySelector('.IsCurrentLanguage input, .IsCurrentLanguage textarea, .IsCurrentLanguage select');
				if (requiredInput.type === 'checkbox' || requiredInput.type === 'radio') {
					requiredInput.checked = currentLanguageElement.checked;
				}
				else if (requiredInput.tagName === 'SELECT') {
					requiredInput.selectedIndex = currentLanguageElement.selectedIndex;
				}
				else {
					requiredInput.value = currentLanguageElement.value;
				}
			}

			// Manually trigger validation
			if (form.checkValidity()) {
				// Get the button that triggered the submit event
				const submitButton = event.submitter;

				// If the button has a name and value, create a hidden input with the same name and value
				if (submitButton && submitButton.name && submitButton.value) {
					const hiddenInput = document.createElement('input');
					hiddenInput.type = 'hidden';
					hiddenInput.name = submitButton.name;
					hiddenInput.value = submitButton.value;
					form.appendChild(hiddenInput);
				}

				const submitFormFunction = Object.getPrototypeOf(form).submit;
				submitFormFunction.call(form);
			}
			else {
				// If the form is not valid, report the validation errors
				form.reportValidity();
			}
		}, form);
	}

	/**
	 * Applies the changes made in the dialog to the corresponding input elements in the document.
	 * This method is called when the Apply button in the dialog is clicked.
	 */
	applyChanges() {
		// Get the current element from the dialog
		const element = this.dialog.currentElement;

		// Get all input, textarea, and select elements in the dialog
		const formElements = this.dialog.querySelectorAll('input, textarea, select');
		formElements.forEach((formElement) => {
			// Get the corresponding form element in the document
			const name = formElement.name;
			const correspondingFormElement = element.querySelector(`input[name="${name}"], textarea[name="${name}"], select[name="${name}"]`);

			// If the corresponding form element exists, set its value to the value of the form element in the dialog
			if (!correspondingFormElement) {
				return;
			}

			if (formElement.type === 'checkbox' || formElement.type === 'radio') {
				correspondingFormElement.checked = formElement.checked;
			}
			else if (formElement.tagName === 'SELECT') {
				correspondingFormElement.selectedIndex = formElement.selectedIndex;
			}
			else {
				correspondingFormElement.value = formElement.value;
			}

			// Dispatch the input event on the corresponding form element
			const event = new Event('input', {bubbles: true});
			correspondingFormElement.dispatchEvent(event);
		});

		// Close the dialog
		this.dialog.close();
	}

	/**
	 * Handles the click event on the translate-button.
	 *
	 * @param {HTMLElement} element
	 */
	handleTranslateButtonClick(element) {
		// Remove existing .FormInput elements from the dialog
		this.dialog.querySelectorAll('.FormInput').forEach(node => node.remove());

		// Get the .FormInput elements from the clicked element
		const formInputs = element.querySelectorAll('.FormInput');

		// Insert each .FormInput before the first button in the dialog
		formInputs.forEach(input => {
			const clonedInput = input.cloneNode(true);
			if (input.type === 'checkbox' || input.type === 'radio') {
				clonedInput.checked = input.checked;
			}
			else if (input.tagName === 'SELECT') {
				clonedInput.selectedIndex = input.selectedIndex;
			}
			else {
				clonedInput.value = input.value;
			}
			this.dialog.insertBefore(clonedInput, this.dialog.querySelector('button'));
		});

		// Set the dialog title
		this.dialog.setAttribute('data-title', element.dataset.dialogTitle);

		// Set the button text
		this.dialog.confirmApply.firstChild.nodeValue = element.dataset.dialogApply || 'Apply';
		this.dialog.confirmCancel.firstChild.nodeValue = element.dataset.dialogCancel || 'Cancel';

		// Store the current element as a property on the dialog
		this.dialog.currentElement = element;

		// Show the dialog
		this.dialog.showModal();
	}

	/**
	 * Observes mutations in the document and adds the translation functionality to new elements with the TranslatableTexts class.
	 * This method is called whenever a mutation occurs in the document.
	 *
	 * @param {MutationRecord} mutation - The mutation record.
	 */
	observeMutations(mutation) {
		// For each added node
		mutation.addedNodes.forEach(node => {
			// Check if the node is an element node
			if (node.nodeType !== Node.ELEMENT_NODE) {
				return;
			}

			// If the node has the TranslatableTexts class
			if (node.classList.contains('TranslatableTexts')) {
				// Add a translate-button to the node
				this.addTranslateButton(node);

				// Add a listener to the form submit event for the node
				this.addFormSubmitListener(node);
			}

			// Also check the children of the node
			node.querySelectorAll(this.elementSelector).forEach(childElement => {
				// Add a translate-button to the child element
				this.addTranslateButton(childElement);

				// Add a listener to the form submit event for the child element
				this.addFormSubmitListener(childElement);
			});
		});
	}
}