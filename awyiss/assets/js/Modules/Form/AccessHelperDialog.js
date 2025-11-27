// noinspection JSUnusedGlobalSymbols

export default class AccessHelperDialog {
	/**
	 * The event handler instance.
	 * @type {EventHandler}
	 */
	eventHandler = window.eventHandler;
	/**
	 * Remembered state of the form before the dialog was opened.
	 * @type {boolean}
	 */
	isFormChanged = false;
	/**
	 * The observer instance.
	 * @type {Observer}
	 */
	observer = window.observer;
	/**
	 * The access input element.
	 * @type {HTMLInputElement}
	 */
	openerElement;
	/**
	 * The selector for the access elements.
	 * @type {string}
	 */
	selector = 'input[data-access-helper="true"]';

	/**
	 * Constructor
	 * @param {string} selector
	 */
	constructor(selector) {
		if (selector) {
			this.selector = selector;
		}

		this.dialog = document.querySelector('#AccessHelperDialog');

		if (!this.dialog) {
			return;
		}

		const accessInputs = document.querySelectorAll(this.selector);
		accessInputs.forEach(accessInput => {
			this.initAccessInput(accessInput);
		});

		// Bind the apply button handler
		this.eventHandler.add('click', event => {
			event.preventDefault();

			const scope = this.dialog.querySelector('select[name="access_helper[scope]"]').value;
			const permission = this.dialog.querySelector('select[name="access_helper[permission]"]').value;

			// Set the value of the access input
			this.openerElement.value = `{"scope":"${scope}","identifier":"${permission}"}`;

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

		this.observer.addObserver(this.observeMutations.bind(this));
	}

	/**
	 * Initialize the access input element.
	 * @param {HTMLInputElement} accessInput
	 */
	initAccessInput(accessInput) {
		// Add a button to the access input
		const button = document.createElement('button');
		button.type = 'button';
		button.classList.add('Button', 'Button-AccessHelper', 'InFieldButton');
		button.textContent = accessInput.dataset.accessHelperButtonText;
		accessInput.parentElement.appendChild(button);

		// Add an event listener to the button
		this.eventHandler.add('click', this.handleButtonClick.bind(this), button);
	}

	/**
	 * Handle the button click event.
	 * @param {MouseEvent} event
	 */
	handleButtonClick(event) {
		event.preventDefault();

		this.openerElement = event.target.closest('.FormInput').querySelector(this.selector);

		const scopeSelect = this.dialog.querySelector('select[name="access_helper[scope]"]');
		const permissionSelect = this.dialog.querySelector('select[name="access_helper[permission]"]');

		// Get the access helper select
		const helperSelect = this.openerElement.parentElement.querySelector('.AccessHelper select');

		// Get all optgroups and put their titles as options
		const optgroups = helperSelect.querySelectorAll('optgroup');
		scopeSelect.innerHTML = '<option></option>';

		permissionSelect.disabled = true;
		permissionSelect.innerHTML = '';

		optgroups.forEach(optgroup => {
			const scopeOption = document.createElement('option');
			scopeOption.value = optgroup.label;
			scopeOption.textContent = optgroup.label;
			scopeSelect.appendChild(scopeOption);
		});

		// Check if the scope select has an event listener, if not add one
		if (!scopeSelect.hasEventListener) {
			scopeSelect.addEventListener('change', this.handleControllerSelectChange.bind(this));
			scopeSelect.hasEventListener = true;
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
	 * Handle the scope select change event.
	 * @param {Event} event
	 */
	handleControllerSelectChange(event) {
		const permissionSelect = this.dialog.querySelector('select[name="access_helper[permission]"]');

		// Get the selected scope
		const selectedController = event.target.value;

		// Get the access helper select
		const helperSelect = this.openerElement.parentElement.querySelector('.AccessHelper select');

		// Get the optgroup with the selected scope from the helper select
		const optgroup = helperSelect.querySelector(`optgroup[label="${selectedController}"]`);

		// Get all options from the optgroup and put them in the permission select
		const options = optgroup.querySelectorAll('option');
		permissionSelect.innerHTML = '<option></option>';

		options.forEach(option => {
			const permissionOption = document.createElement('option');
			permissionOption.value = option.textContent;
			permissionOption.textContent = option.textContent;
			permissionSelect.appendChild(permissionOption);
		});

		permissionSelect.disabled = false;
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
				this.initAccessInput(node);
			}

			// Check if the node contains elements that match the selector
			const accessInput = node.querySelector(this.selector);
			if (accessInput) {
				this.initAccessInput(accessInput);
			}
		});
	}
}