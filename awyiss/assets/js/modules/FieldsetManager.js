//noinspection JSUnusedGlobalSymbols

/**
 * FieldsetManager class to manage fieldsets in a form.
 */
export default class FieldsetManager {
	/**
	 * The controller class of the current page.
	 * @type {string}
	 */
	controllerClass = document.body.className.split(' ').find(name => name.endsWith('Controller'));
	/**
	 * The event handler instance.
	 * @type {EventHandler}
	 */
	eventHandler = window.eventHandler;
	/**
	 * The fieldsets in the form.
	 * @type {HTMLElement[]}
	 */
	fieldsets
	/**
	 * The fieldset statuses object.
	 * @type {Object}
	 */
	fieldsetStatuses
	/**
	 * The selector for the fieldsets.
	 * @type {string}
	 */
	selector = '.Overview > fieldset, .Form .Fieldsets > fieldset, .Form .Main-Fieldsets > fieldset, .Form .Sidebar-Fieldsets > fieldset, fieldset.Collapsible';

	/**
	 * Constructor for FieldsetManager class.
	 * Selects all fieldsets that are direct children of .Form > form.
	 * Retrieves the collapsed fieldsets from localStorage.
	 */
	constructor() {
		// Load all collapsed fieldsets from localStorage
		this.fieldsetStatuses = JSON.parse(localStorage.getItem('fieldsetStatuses')) || {};

		// If this controller's fieldsets are not in the object, add them
		if (!this.fieldsetStatuses[this.controllerClass]) {
			this.fieldsetStatuses[this.controllerClass] = {};
		}

		this.fieldsets = Array.from(document.querySelectorAll(this.selector));

		// If the body tag has the class OverviewAction, don't hide the fieldsets
		if (!document.body.classList.contains('OverviewAction')) {
			this.fieldsets.forEach(fieldset => this.checkVisibleChildren(fieldset));

			// Check if the sidebar is visible
			this.checkSidebarVisibility();
		}

		// Set the initial collapse state of the fieldsets
		this.setInitialCollapseState();

		// Add observer for fieldsets
		const observer = window.observer;
		observer.addObserver(this.observeFieldsets.bind(this));

		this.eventHandler.add('click', this.handleClick.bind(this), document.body);
		this.eventHandler.add('click', this.handleLegendClick.bind(this), document.body);

		// Listen for hash changes
		this.eventHandler.add('hashchange', this.handleHashChange.bind(this), window);
		// Check hash on initial page load
		this.handleHashChange();
	}

	/**
	 * Checks if a fieldset has visible children and, if not, adds the class 'Hidden' to them.
	 */
	checkVisibleChildren(fieldset) {
		// If the fieldset is not hideable, bail early
		if (fieldset.dataset.hideable === 'false') {
			return;
		}

		fieldset.classList.remove('Hidden');

		const visibleChildren = Array.from(fieldset.children).some(child => child.offsetParent !== null && child.tagName.toLowerCase() !== 'legend');
		if (!visibleChildren) {
			fieldset.classList.add('Hidden');
		}
	}


	/**
	 * Handles the click event on the legend element.
	 * @param event
	 */
	handleClick(event) {
		const sidebar = document.querySelector('.Sidebar-Fieldsets');

		if (!sidebar) {
			return;
		}

		const wasVisible = sidebar.classList.contains('Visible');

		if (event.target.closest('.Sidebar-Fieldsets')) {
			return;
		}

		if (!event.target.matches('#Sidebar-Toggle')) {
			sidebar.classList.remove('Visible');
			sidebar.inert = !!document.getElementById('Sidebar-Toggle')?.offsetParent;

			if (wasVisible) {
				// Go back one step in the history
				window.history.back();
			}

			return;
		}

		sidebar.classList.toggle('Visible');

		const isVisible = sidebar.classList.contains('Visible');

		// Create a new history entry if the button area is visible
		if (isVisible) {
			window.history.pushState({}, '', `${currentUrl}#Sidebar`);
			sidebar.focus();
		}
		// Otherwise go back one step in the history
		else if (wasVisible) {
			window.history.back();
		}

		sidebar.inert = !isVisible && document.getElementById('Sidebar-Toggle')?.offsetParent;
	}


	/**
	 * Handles the click event on the legend element.
	 * @param event
	 */
	handleLegendClick(event) {
		if (
			event.target.tagName.toLowerCase() !== 'legend' ||
			!event.target.parentElement.matches(this.selector)
		) {
			return;
		}

		const fieldset = event.target.parentElement;
		const index = this.fieldsets.indexOf(fieldset);
		if (index !== -1) {
			this.toggleCollapse(index);
		}
	}


	/**
	 * Observes fieldsets for added nodes and checks if they have visible children.
	 * @param mutation
	 */
	observeFieldsets(mutation) {
		// If the addedNodes property has no nodes, bail early
		if (!mutation.addedNodes.length && !mutation.removedNodes.length) {
			return;
		}

		let fieldsetsChanged = false;

		mutation.addedNodes?.forEach(node => {
			// Check if the node is an element node
			if (node.nodeType !== Node.ELEMENT_NODE) {
				return;
			}

			// Check if the node is a fieldset
			if (node.tagName.toLowerCase() === 'fieldset') {
				this.checkVisibleChildren(node);
				fieldsetsChanged = true;
			}

			// Select all fieldsets within the node
			node.querySelectorAll('fieldset').forEach(fieldset => {
				// Check if the new fieldset has visible children
				this.checkVisibleChildren(fieldset);
				fieldsetsChanged = true;
			});
		});

		mutation.removedNodes?.forEach(node => {
			// Check if the node is an element node
			if (node.nodeType !== Node.ELEMENT_NODE) {
				return;
			}

			// Check if the node is or contains a fieldset
			if (node.tagName.toLowerCase() === 'fieldset' || node.querySelector('fieldset')) {
				fieldsetsChanged = true;
			}
		});

		if (fieldsetsChanged) {
			// Reset the fieldsets array
			this.fieldsets = Array.from(document.querySelectorAll(this.selector));

			// Set the initial collapse state of the fieldsets
			this.setInitialCollapseState();

			this.checkSidebarVisibility();
		}
	}

	/**
	 * Gets the identifier for a fieldset.
	 * @param {HTMLElement} fieldset - The fieldset to get the identifier for.
	 * @param {number} index - The index of the fieldset.
	 * @returns {string|number} - The identifier for the fieldset.
	 */
	getFieldsetIdentifier(fieldset, index) {
		let fieldsetIdentifier = null;

		// If the fieldset has an id, use it as the identifier as it should be unique
		if (fieldset.id) {
			return fieldset.id;
		}

		// Get the class name of the fieldset, excluding the "Collapsed" class
		let fieldsetClassName = Array.from(fieldset.classList).filter(className => className !== 'Collapsed').join(' ');

		// Use the class name as the identifier if there aren't multiple fieldsets with the same class name
		if (
			this.fieldsets.filter(fs => Array.from(fs.classList).filter(className => className !== 'Collapsed').join(' ') === fieldsetClassName).length === 1
		) {
			fieldsetIdentifier = fieldsetClassName;
		}
		else {
			// Serialize the dataset once and store it in a variable
			let serializedDataset = JSON.stringify(fieldset.dataset);

			// Use the serialized version of the dataset as the identifier if it's not empty
			if (serializedDataset !== "{}") {
				// Check if there are multiple fieldsets with the same serialized dataset
				// if there aren't, use the serialized dataset as the identifier
				if (
					this.fieldsets.filter(fs => JSON.stringify(fs.dataset) === serializedDataset).length === 1
				) {
					fieldsetIdentifier = serializedDataset;
				}
			}
		}

		// If the fieldset identifier is still null, use the index as the identifier
		if (fieldsetIdentifier === null) {
			fieldsetIdentifier = index;
		}

		return fieldsetIdentifier;
	}


	/**
	 * Toggles the 'collapsed' class on the fieldset.
	 * Updates the localStorage.
	 * @param {number} index - The index of the fieldset to toggle collapse on.
	 */
	toggleCollapse(index) {
		const fieldset = this.fieldsets[index];
		fieldset.classList.toggle('Collapsed');

		const isCollapsed = fieldset.classList.contains('Collapsed');

		const fieldsetIdentifier = this.getFieldsetIdentifier(fieldset, index);

		let controllerIdentifier = this.controllerClass;

		// If the fieldset is inside the MediaFolders-List element, use the MediaFoldersController as the controller identifier
		if (fieldset.closest('#MediaFolders-List')) {
			controllerIdentifier = 'MediaFoldersController';
		}

		if (this.fieldsetStatuses[controllerIdentifier][fieldsetIdentifier] === undefined) {
			this.fieldsetStatuses[controllerIdentifier][fieldsetIdentifier] = {};
		}
		this.fieldsetStatuses[controllerIdentifier][fieldsetIdentifier] = isCollapsed;

		// Trigger an event to update the layout
		const event = new CustomEvent('fieldsetCollapse', {
			bubbles: true,
			detail: {
				fieldset: fieldset,
				isCollapsed: isCollapsed,
			}
		});
		fieldset.dispatchEvent(event);

		// Trigger a resize event to update the layout in case the fieldset contains elements that listen to resize events
		window.dispatchEvent(new Event('resize'));

		localStorage.setItem('fieldsetStatuses', JSON.stringify(this.fieldsetStatuses));

	}

	/**
	 * Sets the initial collapse state of the fieldsets based on localStorage.
	 */
	setInitialCollapseState() {
		this.fieldsets.forEach((fieldset, index) => {
			let fieldsetIdentifier = this.getFieldsetIdentifier(fieldset, index);

			let controllerIdentifier = this.controllerClass;

			// If the fieldset is inside the MediaFolders-List element, use the MediaFoldersController as the controller identifier
			if (fieldset.closest('#MediaFolders-List')) {
				controllerIdentifier = 'MediaFoldersController';
			}

			// Check if this.fieldsetStatuses[controllerIdentifier] is defined, if not, initialize it to an empty object
			if (!this.fieldsetStatuses[controllerIdentifier]) {
				this.fieldsetStatuses[controllerIdentifier] = {};
			}

			if (typeof this.fieldsetStatuses[controllerIdentifier][fieldsetIdentifier] !== 'undefined') {
				fieldset.classList.toggle('Collapsed', this.fieldsetStatuses[controllerIdentifier][fieldsetIdentifier]);
			}
			// If the fieldset has the data attribute 'collapsedInitial' (and it's set to true), collapse it
			else if (fieldset.dataset.collapsedInitial === 'true') {
				fieldset.classList.add('Collapsed');
			}
		});
	}

	/**
	 * Checks if the sidebar has any visible
	 * fieldsets and if not, hides the sidebar toggle button.
	 */
	checkSidebarVisibility() {
		const sidebar = document.querySelector('.Sidebar-Fieldsets');
		const sidebarToggle = document.getElementById('Sidebar-Toggle');

		if (!sidebarToggle) {
			return;
		}

		if (!sidebar) {
			sidebarToggle.classList.remove('Visible');
			sidebar.inert = true;

			return;
		}

		const wasVisible = window.location.hash === '#Sidebar';

		const fieldsets = sidebar.querySelectorAll('fieldset:not(.Hidden)');

		sidebarToggle.classList.toggle('Visible', fieldsets.length > 0);

		if (wasVisible) {
			if (fieldsets.length > 0) {
				sidebar.classList.add('Visible', 'NoTransition');
				setTimeout(() => {
					sidebar.classList.remove('NoTransition');
				}, 300);
			}
			else {
				window.history.back();
			}
		}

		const isVisible = sidebar.classList.contains('Visible');
		sidebar.inert = !isVisible && document.getElementById('Sidebar-Toggle')?.offsetParent;
	}

	/**
	 * Handle URL hash changes
	 * @returns {void}
	 */
	handleHashChange() {
		const hasSidebarHash = window.location.hash === '#Sidebar';

		const sidebar = document.querySelector('.Sidebar-Fieldsets');

		if (!sidebar) {
			return;
		}

		sidebar.classList.toggle('Visible', hasSidebarHash);
		sidebar.inert = !hasSidebarHash && document.getElementById('Sidebar-Toggle')?.offsetParent;
	}
}
