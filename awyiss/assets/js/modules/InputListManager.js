//noinspection JSUnusedGlobalSymbols

// Core SortableJS (without default plugins)
import Sortable from 'SortableJS/sortable';

/**
 * Class to manage FormInputType-List rows
 */
export default class InputListManager {
	/**
	 * The class of the add button
	 * @type {string}
	 */
	buttonAddClass = 'Button-Add';
	/**
	 * The class of the remove button
	 * @type {string}
	 */
	buttonRemoveClass = 'Button-Remove';
	/**
	 * The class of the list items
	 * @type {string}
	 */
	elementClass = 'FormInputType-ListItem';
	/**
	 * The event handler instance.
	 * @type {EventHandler}
	 */
	eventHandler = window.eventHandler;
	/**
	 * Selector for FormInputType-List elements
	 * @type {string}
	 */
	selector = '.FormInputType-List';

	/**
	 * Constructor
	 */
	constructor(selector) {
		// Set selector
		if (selector) {
			this.selector = selector;
		}

		// Global event handler
		this.eventHandler.add('click', this.handleClick.bind(this));

		// Initialize on existing elements
		this.initList(document.body);

		const observer = window.observer;
		observer.addObserver(this.observeMutations.bind(this));
	}


	/**
	 * Initialize FormInputType-List elements
	 * @param {HTMLElement} root - Root element to search for FormInputType-List elements
	 */
	initList(root) {
		// Get all FormInputType-List elements in the root
		const formInputs = root.querySelectorAll(this.selector);
		formInputs.forEach((formInput) => {
			// Get all rows in the form input
			const rows = formInput.querySelectorAll(`.${this.elementClass}`);

			// If there is only one row, replace the default class with 0
			if (rows.length === 1) {
				rows[0].classList.remove(`${this.elementClass}-Default`);
			}
			// If there are multiple rows, remove the default row
			else {
				formInput.querySelector(`.${this.elementClass}-Default`).remove();
			}

			// Add an add button to the form input
			this.addButton(formInput, formInput.dataset.listItemAdd, this.buttonAddClass);

			// For each row, add a remove button and disable it if it's the only row
			rows.forEach((row) => {
				this.addButton(row, formInput.dataset.listItemRemove, this.buttonRemoveClass);

				if (rows.length <= 2) {
					row.querySelector(`.${this.buttonRemoveClass}`).classList.add('Disabled');
				}

				// Create a new handler element
				const handler = document.createElement('div');
				// Add the Handler class to the handler element
				handler.classList.add('SortableHandle');
				// Append the handler to the row
				row.appendChild(handler);
			});

			// Initialize Sortable on the form input
			Sortable.create(formInput, {
				chosenClass: 'SortableChosen',
				draggable: `.${this.elementClass}`,
				filter: '.Button-Add, .Label',
				ghostClass: 'SortableGhost',
				handle: '.SortableHandle',
				/*onEnd: function () {
					// Get the first input in the element
					const firstInput = formInput.querySelector('input, select');
					// Trigger an input event on the first input
					firstInput.dispatchEvent(new Event('input', {bubbles: true}));
				},*/
			});
		});
	}

	/**
	 * Add a button to an element
	 * @param {HTMLElement} element - Element to add the button to
	 * @param {string} text - Button text
	 * @param {string} className - Class to add to the button
	 */
	addButton(element, text, className) {
		// Create a new button element
		const button = document.createElement('button');
		// Set the button type to button
		button.type = 'button';
		// Set the button text
		button.textContent = text;
		// Add the Button and the provided class to the button
		button.classList.add('Button', className);
		// Append the button to the element
		element.appendChild(button);
	}

	/**
	 * Handle click events
	 * @param {Event} event - The click event
	 */
	handleClick(event) {
		// Make sure the target is a child of the FormInputType-List
		if (!event.target.closest(this.selector)) {
			return;
		}

		if (event.target.matches(`.${this.buttonAddClass}`)) {
			this.handleAdd(event.target);
		}

		if (event.target.matches(`.${this.buttonRemoveClass}`)) {
			this.handleRemove(event.target);
		}
	}

	/**
	 * Handle add button click
	 * @param {HTMLElement} button - Button that was clicked
	 */
	handleAdd(button) {
		// Get the form input that contains the button
		const formInput = button.parentElement;

		// Get all the rows in the form input
		const rows = formInput.querySelectorAll(`.${this.elementClass}`);

		// If there is currently only one row, enable the remove button
		if (rows.length === 1) {
			rows[0].querySelector(`.${this.buttonRemoveClass}`).classList.remove('Disabled');
		}

		// Get the last row in the form input
		const lastRow = rows[rows.length - 1];

		// Clone the last row
		const newRow = lastRow.cloneNode(true);

		// Get all inputs in the new row
		const inputs = newRow.querySelectorAll('input, select, textarea');

		let newIndex = 0;
		// Clear the input values in the new row and update their names
		inputs.forEach((input) => {
			// If the input is not hidden, clear the value
			if (input.type !== 'hidden') {
				input.value = '';
			}

			const name = input.name;

			// If the name contains "[_translations]", remove the placeholder
			if (name.includes('[_translations]')) {
				input.placeholder = '';
			}

			//noinspection RegExpRedundantEscape
			newIndex = parseInt(name.match(/\[(\d+)\]/)[1], 10) + 1;
			//noinspection RegExpRedundantEscape
			input.name = name.replace(/\[\d+\]/, `[${newIndex}]`);
		});

		const formElements = newRow.querySelectorAll('.FormInput');

		formElements.forEach((formElement) => {
			// Get the class name that starts with "FormInputName"
			const className = Array.from(formElement.classList).find(name => name.startsWith("FormInputName"));

			// Check if the class contains a number prefixed by a hyphen and followed by a hyphen
			if (className.match(/-\d+-/)) {
				// Replace the number with the new index
				const newClassName = className.replace(/-\d+-/, `-${newIndex}-`);

				// Replace the class name
				formElement.classList.replace(className, newClassName);
			}

			// Find ids and for attributes that also contain a number separated by a hyphen in all children
			const ids = formElement.querySelectorAll('[id]');
			ids.forEach((id) => {
				// Replace the number with the new index
				id.id = id.id.replace(/-\d+-/, `-${newIndex}-`);
			});

			const fors = formElement.querySelectorAll('[for]');
			fors.forEach((forElement) => {
				// Replace the number with the new index
				forElement.htmlFor = forElement.htmlFor.replace(/-\d+-/, `-${newIndex}-`);
			});
		});

		// Find the remove button in the new row
		const removeButton = newRow.querySelector(`.${this.buttonRemoveClass}`);
		// Remove the disabled class from the remove button
		removeButton.classList.remove('Disabled');

		// Insert the new row before the button
		formInput.insertBefore(newRow, button);

		// Trigger an input event on the first input in the first new input
		//inputs[0].dispatchEvent(new Event('input', {bubbles: true}));
	}

	/**
	 * Handle remove button click
	 * @param {HTMLElement} button - Button that was clicked
	 */
	handleRemove(button) {
		// If the button is not disabled and there is more than one row, remove the clicked button's parent element
		if (button.classList.contains('Disabled')) {
			return;
		}

		// Get the form input that contains the button
		const formInput = button.parentElement.parentElement;

		// Get all the rows in the form input
		const rows = formInput.querySelectorAll(`.${this.elementClass}`);

		// Get the first row input and trigger an input event
		//const firstInput = formInput.querySelector('input, select');
		//firstInput.dispatchEvent(new Event('input', {bubbles: true}));

		button.parentElement.remove();

		// If there is only one row left, disable the remove button
		if (rows.length === 2) {
			const remainingRow = formInput.querySelector(`.${this.elementClass}`);
			remainingRow.querySelector(`.${this.buttonRemoveClass}`).classList.add('Disabled');
		}
	}

	/**
	 * Handle a mutation
	 * @param {MutationRecord} mutation - The mutation record
	 */
	observeMutations(mutation) {
		mutation.addedNodes.forEach(node => {
			if (node.nodeType !== Node.ELEMENT_NODE) {
				return;
			}

			// Check if the node itself matches the selector
			if (node.matches(this.selector)) {
				this.initList(node);
			}

			// Check if any child of the node matches the selector
			node.querySelectorAll(this.selector).forEach((child) => {
				this.initList(child);
			});
		});
	}
}
