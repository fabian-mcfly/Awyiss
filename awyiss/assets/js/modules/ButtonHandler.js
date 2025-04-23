//noinspection JSUnusedGlobalSymbols

import CustomMouseEvent from 'CustomMouseEvent';

/**
 * ButtonHandler class
 * This class is used to handle mouseenter and mouseleave events on a set of elements.
 * It also creates and manages a hover element for each of these elements.
 */
export default class ButtonHandler {
	/**
	 * The bound click event handler.
	 * @type {function}
	 */
	boundClick;
	/**
	 * The elements array containing all elements that have a hover element.
	 * @type {[]}
	 */
	elements = [];
	/**
	 * The event handler instance.
	 * @type {EventHandler}
	 */
	eventHandler = window.eventHandler;

	/**
	 * The constructor of the ButtonHandler class.
	 * It initializes the elements array and adds mouseenter and mouseleave event listeners to the document.
	 * It also creates a MutationObserver to watch for changes in the DOM.
	 * @param {Array} params - An array of parameters. Each parameter can be a string or an object with `elementSelector` and `hoverSelector` properties.
	 */
	constructor(params) {
		// Make params an instance variable
		this.params = params;

		if (!document.documentElement.classList.contains('👀') && !(window.matchMedia('(prefers-reduced-motion: reduce)')).matches) {
			// Iterate over each parameter
			params.forEach(param => {
				// Extract the elementSelector and hoverSelector from the parameter
				const [elementSelector, hoverSelector] = this.extractSelectors(param);

				// Get all elements that match the elementSelector
				const elements = document.querySelectorAll(elementSelector);
				elements.forEach(element => {
					// Add each element to the elements array and initialize its hover element
					this.addElement(element, hoverSelector);
				});

				// Add the elements to the elements array
				this.elements = this.elements.concat(elements);
			});

			const observer = window.observer;
			observer.addObserver(this.observeMutations.bind(this));
		}

		// Create the dialog elements
		this.createDialog();

		// Add click event listener to the document
		this.eventHandler.add('click', this.handleClick.bind(this), window, true);
		// Add dblclick event listener to the document
		this.eventHandler.add('dblclick', this.handleDoubleClick.bind(this), window, true);

	}

	/**
	 * This function is responsible for adding an element to the elements array and initializing its hover element.
	 *
	 * It first checks if a hoverSelector is provided.
	 * If so, it uses this selector to find the hoverParent within the element. If not, the element itself is used as the hoverParent.
	 *
	 * It then checks if the hoverParent already contains a hoverElement. If not, it creates a new hoverElement and appends it to the hoverParent.
	 *
	 * It also adds a mousemove event listener to the hoverParent.
	 * This listener calculates the relative mouse position within the hoverParent and updates the hoverElement's position accordingly.
	 *
	 * Finally, it sets the hoverElement as a property of the element and adds the element to the elements array.
	 *
	 * @param {HTMLElement} element - The element to add. This should be a DOM element.
	 * @param {string} hoverSelector - The selector for the hover element. This should be a string containing a valid CSS selector.
	 *  If not provided, the element itself will be used as the hoverParent.
	 */
	addElement(element, hoverSelector) {
		if (element.closest('.tox-listboxfield')) {
			return;
		}

		/**
		 * Find the hoverParent within the element
		 *
		 * @type {HTMLElement}
		 */
		let hoverParent = hoverSelector ? element.querySelector(hoverSelector) : element;

		// If the element is of type link or button
		// and if the title attribute is not set, set it to the text content of the element
		if ((element.tagName === 'A' || element.tagName === 'BUTTON') && !element.hasAttribute('title')) {
			let text = element.textContent.trim();

			// Replace all types of line breaks and tabs with a single space
			text = text.replace(/[\r\n\t]+/g, ' ');

			// Replace multiple spaces with a single space
			text = text.replace(/ {2,}/g, ' ');

			element.setAttribute('title', text);
		}

		/**
		 * @type {HTMLElement}
		 */
		let hoverElement = hoverParent.querySelector('.Hover');

		// If hoverElement does not exist, create it
		if (!hoverElement) {
			hoverElement = document.createElement('span');
			hoverElement.classList.add('Hover');
			hoverParent.appendChild(hoverElement);
		}

		// Add a mousemove event listener to the hoverParent
		this.eventHandler.add('mousemove', event => {
			const rect = hoverParent.getBoundingClientRect();
			const parentWidth = hoverParent.offsetWidth;
			let x = event.clientX - rect.left - parentWidth / 2; // Center the x-coordinate

			// Initialize y at its maximum value (0, since we want to go from 0 to -15)
			let y = 0;

			// Calculate distance from the cursor to the left edge
			const distToLeftEdge = event.clientX - rect.left;
			// Calculate distance from the cursor to the right edge
			const distToRightEdge = rect.right - event.clientX;

			// If within 30px of the left edge, decrease y
			if (distToLeftEdge < 30) {
				y = -5 * (1 - (distToLeftEdge / 30));
			}
			// If within 30px of the right edge, decrease y
			else if (distToRightEdge < 30) {
				y = -5 * (1 - (distToRightEdge / 30));
			}

			// Round the x and y values
			x = Math.round(x);
			y = Math.round(y);

			hoverParent.style.setProperty('--x', `${x}px`);
			hoverParent.style.setProperty('--y', `${y}px`);
		}, hoverParent);

		// Set hoverElement as a property of the element
		element.hoverElement = hoverElement;

		// Add mouseenter and mouseleave event listeners to the document
		this.eventHandler.add('mouseenter', this.onMouseEnter.bind(this), element);
		this.eventHandler.add('mouseleave', this.onMouseLeave.bind(this), element);

		// Add the element to the elements array
		this.elements.push(element);
	}

	/**
	 * This function is responsible for removing an element from the elements array.
	 * @param {HTMLElement} element - The element to remove. This should be a DOM element.
	 */
	removeElement(element) {
		const index = this.elements.indexOf(element);
		if (index !== -1) {
			this.elements.splice(index, 1);
		}
	}

	/**
	 * This is responsible for creating a dialog box with two buttons.
	 * It creates the dialog and its child elements, sets their IDs and classes, and appends them to the dialog.
	 * It also adds click event listeners to the buttons.
	 */
	createDialog() {
		// Check if a dialog with the specified ID already exists
		this.dialog = document.getElementById('ConfirmDialog');

		if (!this.dialog) {
			// Create dialog and its child elements
			this.dialog = document.createElement('dialog');
			this.dialog.message = document.createElement('p');
			this.dialog.confirmYes = document.createElement('button');
			this.dialog.confirmNo = document.createElement('button');

			// Set IDs for later use
			this.dialog.id = 'ConfirmDialog';
			this.dialog.message.id = 'ConfirmDialog-Message';
			this.dialog.confirmYes.id = 'ConfirmDialog-Button-Yes';
			this.dialog.confirmYes.classList.add('Button');
			this.dialog.confirmNo.id = 'ConfirmDialog-Button-No';
			this.dialog.confirmNo.classList.add('Button', 'Button-Close');

			// Set button text
			this.dialog.confirmYes.textContent = 'Yes';
			this.dialog.confirmNo.textContent = 'No';

			// Append elements to dialog
			this.dialog.appendChild(this.dialog.message);
			this.dialog.appendChild(this.dialog.confirmYes);
			this.dialog.appendChild(this.dialog.confirmNo);

			// Append dialog to body
			document.body.appendChild(this.dialog);
		}
		else {
			// If the dialog already exists, get its child elements
			this.dialog.message = document.getElementById('ConfirmDialog-Message');
			this.dialog.confirmYes = document.getElementById('ConfirmDialog-Button-Yes');
			this.dialog.confirmNo = document.getElementById('ConfirmDialog-Button-No');
		}

		// Add event listeners
		this.eventHandler.add('click', () => {
			// Close the dialog
			this.dialog.close();

			// Remove the event listener from the "Yes" button
			this.eventHandler.remove('click', this.boundClick, this.dialog.confirmYes);
		}, this.dialog.confirmNo);
	}

	/**
	 * This is responsible for handling click events.
	 * It checks if the clicked element has the 'data-confirm' attribute and, if so, prevents the default action and stops propagation.
	 * It then updates the message and title of the dialog and shows the dialog.
	 *
	 * @param {Event} event - The click event.
	 */
	handleClick(event) {
		// noinspection JSUnresolvedReference
		if (
			// If the clicked element doesn't have the 'data-confirm' attribute
			!event.target.hasAttribute('data-confirm') ||
			// or if the event was sent from the confirm dialog
			event.sentFromConfirmDialog ||
			// or if the event was sent from the form leave confirm dialog
			event.sentFromFormLeaveConfirmDialog
		) {
			// do nothing here
			return;
		}

		event.preventDefault();
		event.stopPropagation();

		this.dialog.message.innerHTML = event.target.dataset.confirm;
		this.dialog.dataset.title = event.target.dataset.confirmTitle ?? 'Confirm';

		// Update the text content of the buttons if they have the data-confirm-yes and data-confirm-no attributes
		if (event.target.dataset.confirmYes) {
			// Update the first text node within the button
			this.dialog.confirmYes.firstChild.nodeValue = event.target.dataset.confirmYes;
			this.dialog.confirmYes.title = event.target.dataset.confirmYes;

			// Remove all classes except 'Button'
			this.dialog.confirmYes.className = 'Button';

			// Add the yes class to the button, if it exists
			if (event.target.dataset.confirmYesClass) {
				let classes = event.target.dataset.confirmYesClass;
				if (!Array.isArray(classes)) {
					classes = classes.includes(' ') ? classes.split(' ') : [classes];
				}
				this.dialog.confirmYes.classList.add(...classes);
			}
		}

		if (event.target.dataset.confirmNo) {
			this.dialog.confirmNo.firstChild.nodeValue = event.target.dataset.confirmNo;
			this.dialog.confirmNo.title = event.target.dataset.confirmNo;

			// Remove all classes except 'Button'
			this.dialog.confirmNo.className = 'Button Button-Close';

			// Add the no class to the button, if it exists
			if (event.target.dataset.confirmNoClass) {
				let classes = event.target.dataset.confirmNoClass;
				if (!Array.isArray(classes)) {
					classes = classes.includes(' ') ? classes.split(' ') : [classes];
				}
				this.dialog.confirmNo.classList.add(...classes);
			}
		}

		this.boundClick = this.handleConfirm.bind(this, event);
		this.eventHandler.add('click', this.boundClick, this.dialog.confirmYes);

		this.target = event.target;
		this.dialog.showModal();
		this.dialog.focus();
	}

	/**
	 * This is responsible for handling confirmations.
	 * It checks if the target element is a link and, if so, navigates to its href.
	 * If the target element is not a link, it checks if it is part of a form and, if so, submits the form.
	 * After performing the action, it closes the dialog.
	 * @param {Event} originalEvent - The original click event.
	 */
	handleConfirm(originalEvent) {
		this.eventHandler.remove('click', this.boundClick, this.dialog.confirmYes);

		// In case the target element is not a link or part of a form, resend a new event
		// This way, the event can be caught by other event listeners that might be listening for it, yet we were able to get a confirmation
		const newEvent = new CustomMouseEvent('click', {sentFromConfirmDialog: true}, {
			bubbles: true,
			cancelable: true,
			view: window
		});

		// Dispatch the event on the same target as the original event
		originalEvent.target.dispatchEvent(newEvent);

		// Close the dialog
		this.dialog.close();
	}

	/**
	 * On doubleclick, trigger the edit button if it exists.
	 * @param event
	 */
	handleDoubleClick(event) {
		const target = event.target;
		const listItem = target.closest('.ListItem, tr');
		if (!listItem || target.matches('.NestedListToggle')) {
			return;
		}

		const editButton = listItem.querySelector('.Button-Edit');
		if (editButton) {
			editButton.click();
		}
	};

	/**
	 * This method is responsible for handling mouse events.
	 * It first finds the target element from the elements array.
	 * If no target element or hoverElement is found, it returns and does nothing.
	 * It then calculates the relative mouse position within the target element.
	 * It updates the hoverElement's position based on the calculated mouse position.
	 * Finally, it adjusts the hoverElement's size based on the event type and target element's size.
	 *
	 * @param {MouseEvent} event - The mouse event.
	 * @param {string} eventType - The type of the event ("enter" or "leave").
	 */
	handleMouseEvent(event, eventType) {
		// Find the target element
		let target = this.elements.find(element => element === event.target);

		// If no target element or hoverElement is found, return
		if (!target || !target.hoverElement) {
			return;
		}

		// If the target is disabled, return
		if (target.disabled) {
			return;
		}

		// Calculate the relative mouse position
		let parentOffset = target.getBoundingClientRect(),
			relX = event.clientX - parentOffset.left,
			relY = event.clientY - parentOffset.top;

		// Update the hoverElement's position
		target.hoverElement.style.top = relY + 'px';
		target.hoverElement.style.left = relX + 'px';

		// Adjust the hoverElement's size based on the event type
		if (eventType === 'enter') {
			target.hoverElement.style.width = target.offsetWidth * 2.75 + 'px';
			target.hoverElement.style.height = target.offsetHeight * 3.5 + 'px';
		}
		else if (eventType === 'leave') {
			target.hoverElement.style.width = '0';
			target.hoverElement.style.height = '0';

			target.hoverElement.parentElement.style.removeProperty('--x');
			target.hoverElement.parentElement.style.removeProperty('--y');
		}
	}

	/**
	 * This method is responsible for observing mutations in the DOM.
	 * It iterates over each mutation and, if nodes were added, iterates over each added node.
	 * If the node is an element, it checks if it matches the elementSelector and, if so, adds it to the elements array and initializes its hover element.
	 * It also checks the children of the node.
	 * @param {MutationRecord} mutation - The mutation to observe.
	 */
	observeMutations(mutation) {
		// If nodes were added
		if (mutation.addedNodes.length > 0) {
			// Iterate over each added node
			mutation.addedNodes.forEach(node => {
				if (node.nodeType !== Node.ELEMENT_NODE) {
					return;
				}

				// Iterate over each parameter
				this.params.map(this.extractSelectors).forEach(([elementSelector, hoverSelector]) => {
					// If the node matches the elementSelector, add it to the elements array and initialize its hover element
					if (node.matches(elementSelector)) {
						this.addElement(node, hoverSelector);
					}

					// Check the children of the node that match the elementSelector
					node.querySelectorAll(elementSelector).forEach(childElement => {
						// Add the child element to the elements array and initialize its hover element
						this.addElement(childElement, hoverSelector);
					});
				});
			});
		}

		// If nodes were removed
		if (mutation.removedNodes.length > 0) {
			// Iterate over each removed node
			mutation.removedNodes.forEach(node => {
				if (node.nodeType !== Node.ELEMENT_NODE) {
					return;
				}

				// Iterate over each parameter
				this.params.map(this.extractSelectors).forEach(([elementSelector]) => {
					// If the node matches the elementSelector, remove it from the elements array
					if (node.matches(elementSelector)) {
						this.removeElement(node);
					}

					// Check the children of the node that match the elementSelector
					node.querySelectorAll(elementSelector).forEach(childElement => {
						// Remove the child element from the elements array
						this.removeElement(childElement);
					});
				});
			});
		}
	}

	/**
	 * This method is responsible for handling the mouseenter event.
	 * It first finds the target element from the elements array.
	 * If no target element or hoverElement is found, it returns and does nothing.
	 * It then calculates the relative mouse position within the target element.
	 * It updates the hoverElement's position based on the calculated mouse position.
	 * Finally, it increases the hoverElement's size based on the target element's size.
	 *
	 * @param {MouseEvent} event - The mouseenter event.
	 */
	onMouseEnter(event) {
		this.handleMouseEvent(event, 'enter');
	}

	/**
	 * This method is responsible for handling the mouseleave event.
	 * It first finds the target element from the elements array.
	 * If no target element or hoverElement is found, it returns and does nothing.
	 * It then calculates the relative mouse position within the target element.
	 * It updates the hoverElement's position based on the calculated mouse position.
	 * Finally, it resets the hoverElement's size to 0.
	 *
	 * @param {MouseEvent} event - The mouseleave event.
	 */
	onMouseLeave(event) {
		this.handleMouseEvent(event, 'leave');
	}

	/**
	 * This function extracts the elementSelector and hoverSelector from a parameter.
	 * @param {string|object} param - The parameter to extract from. It can be a string or an object with `elementSelector` and `hoverSelector` properties.
	 * @returns {Array} An array containing the elementSelector and hoverSelector.
	 */
	extractSelectors(param) {
		let elementSelector, hoverSelector;

		// If the parameter is a string, treat it as the elementSelector
		if (typeof param === 'string') {
			elementSelector = param;
			hoverSelector = null;
		}
		// If the parameter is an object, extract the elementSelector and hoverSelector properties
		else if (typeof param === 'object') {
			elementSelector = param.elementSelector;
			hoverSelector = param.hoverSelector;
		}

		return [elementSelector, hoverSelector];
	}
}