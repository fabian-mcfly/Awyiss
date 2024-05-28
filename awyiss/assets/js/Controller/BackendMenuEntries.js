// noinspection JSUnusedGlobalSymbols

export default class BackendMenuEntriesController {
	/**
	 * The event handler instance.
	 * @type {EventHandler}
	 */
	eventHandler = window.eventHandler;

	constructor() {
		if (document.documentElement.classList.contains('OverviewAction')) {
			this.initOverview();
		}

		if (document.documentElement.classList.contains('AddAction') || document.documentElement.classList.contains('EditAction')) {
			this.initForm();

			this.accessHelper = new AccessHelper();
			this.linkHelper = new LinkHelper();
		}
	}

	/**
	 * Initialize the form related functionality.
	 */
	initForm() {
		window.eventHandler.add('input', function (event) {
			// Check if the event target is the insert_after_id select element
			if (event.target.name === 'insert_after_id') {
				// Get the parent_id select element
				const parentIdSelect = document.querySelector('select[name="parent_id"]');

				// Reset the parent_id select element to its first option
				parentIdSelect.selectedIndex = 0;
			}
		}, window, {}, 9);
	}

	/**
	 * Initialize the overview related functionality.
	 */
	initOverview() {
		window.nestedListHandler.getOrder = this.getNestedListOrder;
	}

	/**
	 * This method returns an object with an arbitrary amount of objects,
	 * extracted and built from a nested DOM structure of lists and list items.
	 * The order of the items is determined by their position in the DOM.
	 * The order resets to 1 whenever an item has a unique "insert_after_id".
	 *
	 * @returns {Object} The order of the list items
	 */
	getNestedListOrder() {
		// Initialize a set to keep track of all insert_after_id values
		const insertAfterIdSet = new Set();

		// Initialize a null value to track the last static ID globally, reset at each level
		let lastStaticItemId = null;

		// Define the identifier for the list
		const listIdentifier = 'BackendMenuEntries';

		// Initialize an empty object to store the order
		let order = {};

		// Initialize a stack for assigning order
		const orderCounterStack = [1];

		// Define a function to recursively process list items
		const processListItem = (listItem, parentId = null) => {
			// Get the ID of the list item
			const id = listItem.id.replace(`${listIdentifier}-ListItem`, '');

			// Check if the ID is a custom ID
			const isCustom = id.match(/^\d+$/);

			// Determine the appropriate insert_after_id based on presence of a parent
			let insert_after_id;
			if (isCustom && !parentId) {
				insert_after_id = lastStaticItemId;
			}
			else {
				insert_after_id = null;
			}

			// If the item is custom, update the order object
			if (isCustom) {
				// If the insert_after_id is unique, reset the order to 1
				if (insert_after_id && !insertAfterIdSet.has(insert_after_id)) {
					orderCounterStack[orderCounterStack.length - 1] = 1;
					insertAfterIdSet.add(insert_after_id);
				}

				// Update the order object
				order[id] = {
					id: id,
					insertAfterId: insert_after_id,
					parentId: parentId,
					systemOrder: orderCounterStack[orderCounterStack.length - 1]++,
				};
			}
			else {
				// Update lastStaticItemId for subsequent items in the same level
				lastStaticItemId = id;
			}


			// Process nested items. Reset lastStaticItemId to ensure it's scoped to this level
			const previousLastStaticItemId = lastStaticItemId;
			lastStaticItemId = null;

			// Add a new counter for the nested level
			orderCounterStack.push(1);

			// Process nested items
			const nestedListItems = listItem.querySelectorAll(`:scope > .${listIdentifier}-List > .ListItem`);
			nestedListItems.forEach(nestedListItem => processListItem(nestedListItem, isCustom ? id : parentId));

			// Remove the counter for the nested level
			orderCounterStack.pop();

			// Restore lastStaticItemId after processing nested items
			lastStaticItemId = previousLastStaticItemId;
		};

		// Select and process top-level list items
		//noinspection JSUnresolvedReference
		const topLevelListItems = document.querySelectorAll(`${this.selector}.Level1 > .ListItem`);
		topLevelListItems.forEach(item => processListItem(item));

		return order;
	}
}

export class LinkHelper {
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
	selector = '#MenuEntry-Link';

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
			window.formLeaveConfirmation.isFormChanged = true;

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
		button.classList.add('Button', 'Button-LinkHelper');
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

export class AccessHelper {
	/**
	 * The access input element.
	 * @type {HTMLInputElement}
	 */
	element;
	/**
	 * The event handler instance.
	 * @type {EventHandler}
	 */
	eventHandler = window.eventHandler;
	/**
	 * The select element for the access helper.
	 * @type {HTMLSelectElement}
	 */
	helperSelect;
	/**
	 * Remembered state of the form before the dialog was opened.
	 * @type {boolean}
	 */
	isFormChanged = false;
	/**
	 * The selector for the access elements.
	 * @type {string}
	 */
	selector = '#MenuEntry-Access';

	/**
	 * Constructor
	 * @param {string} selector
	 */
	constructor(selector) {
		if (selector) {
			this.selector = selector;
		}

		const accessInput = document.querySelector(this.selector);
		if (accessInput) {
			this.element = accessInput;
			this.initAccessInput(accessInput);
		}

		this.dialog = document.querySelector('#AccessHelperDialog');

		// Bind the apply button handler
		this.eventHandler.add('click', event => {
			event.preventDefault();

			const scope = this.dialog.querySelector('select[name="access_helper[scope]"]').value;
			const permission = this.dialog.querySelector('select[name="access_helper[permission]"]').value;

			// Set the value of the access input
			this.element.value = `{"scope":"${scope}","identifier":"${permission}"}`;

			// Mark the form as changed
			window.formLeaveConfirmation.isFormChanged = true;

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
	 * Initialize the access input element.
	 * @param {HTMLInputElement} accessInput
	 */
	initAccessInput(accessInput) {
		// Add a button to the access input
		const button = document.createElement('button');
		button.type = 'button';
		button.classList.add('Button', 'Button-AccessHelper');
		button.textContent = accessInput.dataset.accessHelperButtonText;
		accessInput.parentElement.appendChild(button);

		// Get the access helper select
		this.helperSelect = this.element.parentElement.querySelector('.AccessHelper select');

		// Add an event listener to the button
		this.eventHandler.add('click', this.handleButtonClick.bind(this), button);
	}

	/**
	 * Handle the button click event.
	 * @param {MouseEvent} event
	 */
	handleButtonClick(event) {
		event.preventDefault();

		const scopeSelect = this.dialog.querySelector('select[name="access_helper[scope]"]');
		const permissionSelect = this.dialog.querySelector('select[name="access_helper[permission]"]');

		// Get all optgroups and put their titles as options
		const optgroups = this.helperSelect.querySelectorAll('optgroup');
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

		// Get the optgroup with the selected scope from the helper select
		const optgroup = this.helperSelect.querySelector(`optgroup[label="${selectedController}"]`);

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

/**
 * Expose the class globally
 * @global
 * @type {BackendMenuEntriesController}
 */
window.BackendMenuEntriesController = BackendMenuEntriesController;
