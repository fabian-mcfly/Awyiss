// noinspection JSUnusedGlobalSymbols

export default class LinkHelperDialog {
	/**
	 * The link input element.
	 * @type {HTMLInputElement}
	 */
	element;
	/**
	 * The event handler instance.
	 * @type {EventHandler}
	 */
	eventHandler = window.eventHandler;
	/**
	 * The select element for the link helper.
	 * @type {HTMLSelectElement}
	 */
	helperSelect;
	/**
	 * Remembered state of the form before the dialog was opened.
	 * @type {boolean}
	 */
	isFormChanged = false;
	/**
	 * The selector for the link elements.
	 * @type {string}
	 */
	selector = 'input[data-link-helper="true"]';

	/**
	 * Constructor
	 * @param {string} selector
	 */
	constructor(selector) {
		if (selector) {
			this.selector = selector;
		}

		const linkInput = document.querySelector(this.selector);
		if (linkInput) {
			this.element = linkInput;
			this.initLinkInput(linkInput);
		}

		this.dialog = document.querySelector('#LinkHelperDialog');

		if (!this.dialog) {
			return;
		}

		// Bind the apply button handler
		this.eventHandler.add('click', event => {
			event.preventDefault();

			const controller = this.dialog.querySelector('select[name="link_helper[controller]"]').value;
			const method = this.dialog.querySelector('select[name="link_helper[method]"]').value;

			// Set the value of the link input
			this.element.value = `${controller}::${method}`;

			// Get all keys and values
			const keys = Array.from(this.dialog.querySelectorAll('input[name*="param["][name$="[key]"]'));
			const values = Array.from(this.dialog.querySelectorAll('input[name*="param["][name$="[value]"]'));

			// Create an array of parameters where both the key and value are set
			let parameters = [];
			keys.forEach((key, index) => {
				// Check if both key and value are not empty
				if (key.value && values[index] && values[index].value) {
					parameters.push(`${key.value}:${values[index].value}`);
				}
			});

			// Join the parameters into a string
			const paramString = parameters.join('::');

			// Set the value of the link input
			this.element.value += paramString ? `::${paramString}` : '';

			// Mark the form as changed
			window.formLeaveConfirmation.formChanged();

			// Close the dialog
			this.dialog.close();
		}, this.dialog.querySelector('.Button-Apply'));

		// Bind the close button handler
		this.eventHandler.add('click', event => {
			event.preventDefault();

			this.dialog.close();

			window.formLeaveConfirmation.isFormChanged = this.isFormChanged;
		}, this.dialog.querySelector('.Button-Cancel'));

		const observer = window.observer;
		observer.addObserver(this.observeMutations.bind(this));
	}

	/**
	 * Initialize the link input element.
	 * @param {HTMLInputElement} linkInput
	 */
	initLinkInput(linkInput) {
		// Add a button to the link input
		const button = document.createElement('button');
		button.type = 'button';
		button.classList.add('Button', 'Button-LinkHelper', 'InFieldButton');
		button.textContent = linkInput.dataset.linkHelperButtonText;
		linkInput.parentElement.appendChild(button);

		// Get the link helper select
		this.helperSelect = this.element.parentElement.querySelector('.LinkHelper select');

		// Add an event listener to the button
		this.eventHandler.add('click', this.handleButtonClick.bind(this), button);
	}

	/**
	 * Handle the button click event.
	 * @param {MouseEvent} event
	 */
	handleButtonClick(event) {
		event.preventDefault();

		const controllerSelect = this.dialog.querySelector('select[name="link_helper[controller]"]');
		const methodSelect = this.dialog.querySelector('select[name="link_helper[method]"]');

		// Get all optgroups and put their titles as options
		const optgroups = this.helperSelect.querySelectorAll('optgroup');
		controllerSelect.innerHTML = '<option></option>';

		methodSelect.disabled = true;
		methodSelect.innerHTML = '';

		optgroups.forEach(optgroup => {
			const controllerOption = document.createElement('option');
			controllerOption.value = optgroup.label;
			controllerOption.textContent = optgroup.label;
			controllerSelect.appendChild(controllerOption);
		});

		// Check if the controller select has an event listener, if not add one
		if (!controllerSelect.hasEventListener) {
			controllerSelect.addEventListener('change', this.handleControllerSelectChange.bind(this));
			controllerSelect.hasEventListener = true;
		}

		// Remove all list items except the first one
		const listItems = this.dialog.querySelectorAll('.FormInputType-ListItem');
		listItems.forEach((listItem, index) => {
			if (index > 0) {
				listItem.remove();
			}
			else {
				// Reset all input values
				const parameters = listItem.querySelectorAll('input');
				parameters.forEach(parameter => {
					parameter.value = '';
				});
			}
		});

		this.isFormChanged = window.formLeaveConfirmation.isFormChanged;

		this.dialog.showModal();
	}

	/**
	 * Handle the controller select change event.
	 * @param {Event} event
	 */
	handleControllerSelectChange(event) {
		const methodSelect = this.dialog.querySelector('select[name="link_helper[method]"]');

		// Get the selected controller
		const selectedController = event.target.value;

		// Get the optgroup with the selected controller from the helper select
		const optgroup = this.helperSelect.querySelector(`optgroup[label="${selectedController}"]`);

		// Get all options from the optgroup and put them in the method select
		const options = optgroup.querySelectorAll('option');
		methodSelect.innerHTML = '<option></option>';

		options.forEach(option => {
			const methodOption = document.createElement('option');
			methodOption.value = option.textContent;
			methodOption.textContent = option.textContent;
			methodSelect.appendChild(methodOption);
		});

		methodSelect.disabled = false;
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

			// Check if the node itself matches the selector
			if (node.matches(this.selector)) {
				this.initLinkInput(node);
			}

			// Check if the node contains elements that match the selector
			const linkInput = node.querySelector(this.selector);
			if (linkInput) {
				this.initLinkInput(linkInput);
			}
		});
	}
}