// noinspection JSUnusedGlobalSymbols

/**
 * The CustomSelect class replaces standard HTML select elements with custom-styled dropdowns.
 *
 * It adds a filter input to allow users to search through options and keeps the keyboard navigation through
 * the options intact. The class listens for changes to the original select elements and updates the custom dropdowns accordingly.
 */
export default class CustomSelect {
	/**
	 * The event handler instance.
	 * @type {EventHandler}
	 */
	eventHandler = window.eventHandler;

	/**
	 * The CSS selector for target select elements.
	 * @type {string}
	 */
	selector = 'select';

	/**
	 * The class name for the custom select wrapper.
	 * @type {string}
	 */
	wrapperClass = 'CustomSelect';

	/**
	 * The class name for the custom select button.
	 * @type {string}
	 */
	buttonClass = 'CustomSelect-Button';

	/**
	 * The class name for the custom select dropdown.
	 * @type {string}
	 */
	dropdownClass = 'CustomSelect-Dropdown';

	/**
	 * The class name for the filter input.
	 * @type {string}
	 */
	filterClass = 'CustomSelect-Filter';

	/**
	 * The class name for the options list.
	 * @type {string}
	 */
	optionsClass = 'CustomSelect-Options';

	/**
	 * The class name for individual options.
	 * @type {string}
	 */
	optionClass = 'CustomSelect-Option';

	/**
	 * The class name for optgroup container.
	 * @type {string}
	 */
	optgroupClass = 'CustomSelect-Optgroup';

	/**
	 * The class name for optgroup label.
	 * @type {string}
	 */
	optgroupLabelClass = 'CustomSelect-OptgroupLabel';

	/**
	 * The class name for active/selected option.
	 * @type {string}
	 */
	activeClass = 'Active';

	/**
	 * The class name for highlighted option.
	 * @type {string}
	 */
	highlightedClass = 'Highlighted';

	/**
	 * The class name for open dropdown.
	 * @type {string}
	 */
	openClass = 'Opened';

	/**
	 * Currently highlighted option element.
	 * @type {Map<HTMLSelectElement, HTMLElement>}
	 */
	highlightedOptionMap = new Map();

	/**
	 * Initialize the custom selects present on the page and observe for new ones.
	 */
	constructor() {
		const selectElements = document.querySelectorAll(this.selector);
		selectElements.forEach((selectElement) => {
			this.initSelectElement(selectElement);
		});

		// Observe mutations on the document for select elements and their options
		const mutationObserver = new MutationObserver(this.handleMutations.bind(this));
		mutationObserver.observe(document, {
			childList: true,
			subtree: true,
			attributes: true,
			attributeFilter: ['disabled', 'selected'],
			characterData: true
		});

		// Attach global event handlers
		this.eventHandler.add('focus', this.handleFocus.bind(this), document, true);
		this.eventHandler.add('click', this.handleClick.bind(this));
		this.eventHandler.add('keydown', this.handleKeyDown.bind(this));
		this.eventHandler.add('input', this.handleFilterInput.bind(this));
		this.eventHandler.add('change', this.handleSelectChange.bind(this));
	}

	/**
	 * Initialize a select element as a custom select.
	 *
	 * @param {HTMLSelectElement} selectElement
	 */
	initSelectElement(selectElement) {
		// Avoid initializing the same select element multiple times
		if (
			selectElement.dataset.customSelectInitialized ||
			selectElement.multiple ||
			selectElement.size > 1
		) {
			return;
		}
		selectElement.dataset.customSelectInitialized = 'true';

		// Create the custom select wrapper
		const wrapper = document.createElement('div');
		wrapper.classList.add(this.wrapperClass);

		// Create the button to trigger the dropdown
		const button = document.createElement('button');
		button.type = 'button';
		button.classList.add(this.buttonClass);
		button.setAttribute('aria-haspopup', 'listbox');
		button.setAttribute('aria-expanded', 'false');

		// Create the dropdown container
		const dropdown = document.createElement('div');
		dropdown.classList.add(this.dropdownClass);
		dropdown.inert = true;

		// Create the filter input
		const filterInput = document.createElement('input');
		filterInput.type = 'text';
		filterInput.classList.add(this.filterClass);
		filterInput.placeholder = selectElement.dataset.filterPlaceholder ?? 'Search...';
		filterInput.setAttribute('aria-label', 'Filter options');
		filterInput.autocomplete = 'off';

		// Create the options list container
		const optionsList = document.createElement('div');
		optionsList.classList.add(this.optionsClass);
		optionsList.setAttribute('role', 'listbox');

		// Populate options from the select element
		this.populateOptions(selectElement, optionsList);

		// Assemble the dropdown
		dropdown.appendChild(filterInput);
		dropdown.appendChild(optionsList);

		// Assemble the wrapper
		wrapper.appendChild(button);
		wrapper.appendChild(dropdown);

		// Insert the custom select wrapper before the original select element
		selectElement.insertAdjacentElement('afterend', wrapper);
		selectElement.addEventListener('blur', (event) => {
			selectElement.customSelectData.wrapper.inert = false;
			selectElement.inert = true;
		});

		// Store references for later access
		selectElement.customSelectData = {
			wrapper,
			button,
			dropdown,
			filterInput,
			optionsList,
		};
		selectElement.inert = true;

		wrapper.customSelectData = {
			selectElement,
			button,
			dropdown,
			filterInput,
			optionsList,
		}

		// Update button text with the current selection
		this.updateButtonText(selectElement);

		if (document.activeElement === selectElement) {
			button.focus();
		}
	}

	/**
	 * Populate the options list from the select element.
	 *
	 * @param {HTMLSelectElement} selectElement
	 * @param {HTMLElement} optionsList
	 */
	populateOptions(selectElement, optionsList) {
		optionsList.innerHTML = '';
		let optionIndex = 0;

		const children = Array.from(selectElement.children);

		children.forEach((child) => {
			if (child.tagName === 'OPTGROUP') {
				// Create optgroup container
				const optgroupDiv = document.createElement('div');
				optgroupDiv.classList.add(this.optgroupClass);

				// Create optgroup label
				const labelDiv = document.createElement('div');
				labelDiv.classList.add(this.optgroupLabelClass);
				labelDiv.textContent = child.label || '';
				labelDiv.title = child.label || '';
				optgroupDiv.appendChild(labelDiv);

				// Process options within the optgroup
				child.querySelectorAll('option').forEach((option) => {
					const optionDiv = this.createOptionDiv(option, optionIndex, selectElement);
					optgroupDiv.appendChild(optionDiv);
					optionIndex++;
				});

				optionsList.appendChild(optgroupDiv);
			} else if (child.tagName === 'OPTION') {
				// Direct option (not in an optgroup)
				const optionDiv = this.createOptionDiv(child, optionIndex, selectElement);
				optionsList.appendChild(optionDiv);
				optionIndex++;
			}
		});
	}

	/**
	 * Create an option div element.
	 *
	 * @param {HTMLOptionElement} option
	 * @param {number} index
	 * @param {HTMLSelectElement} selectElement
	 * @returns {HTMLElement}
	 */
	createOptionDiv(option, index, selectElement) {
		const optionDiv = document.createElement('div');
		optionDiv.classList.add(this.optionClass);
		optionDiv.textContent = option.textContent;

		if (!option.value && index === 0 && !option.textContent) {
			optionDiv.innerHTML = '<i>' + (selectElement.dataset.emptyLabel ?? '---') + '</i>';
		}

		optionDiv.setAttribute('data-value', option.value);
		optionDiv.setAttribute('data-index', index + '');
		optionDiv.setAttribute('role', 'option');
		optionDiv.title = option.textContent;

		if (option.selected) {
			optionDiv.classList.add(this.activeClass);
		}

		return optionDiv;
	}

	/**
	 * Update the button text based on the selected option.
	 *
	 * @param {HTMLSelectElement} selectElement
	 */
	updateButtonText(selectElement) {
		if (!selectElement.customSelectData) {
			return;
		}

		const selectedOption = selectElement.querySelector('option:checked');
		const button = selectElement.customSelectData.button;
		button.textContent = selectedOption?.textContent ?? selectElement.dataset.placeholder ?? '';
	}

	/**
	 * Handle click events on custom select elements.
	 *
	 * @param {Event} event
	 */
	handleClick(event) {
		const button = event.target.closest(`.${this.buttonClass}`);
		const option = event.target.closest(`.${this.optionClass}`);

		if (!event.target.closest(`.${this.wrapperClass}`)) {
			// Find all open dropdowns and close them
			document.querySelectorAll(`.${this.wrapperClass}.${this.openClass}`).forEach((openWrapper) => {
				this.closeWrapper(openWrapper);
			});

			return;
		}

		if (button) {
			this.toggleDropdown(button);
			return;
		}

		if (option) {
			this.useOption(option);
		}
	}

	/**
	 * Close a custom select wrapper.
	 *
	 * @param {HTMLElement} openWrapper
	 */
	closeWrapper(openWrapper){
		openWrapper.classList.remove(this.openClass);
		const openButton = openWrapper.querySelector(`.${this.buttonClass}`);
		openButton.setAttribute('aria-expanded', 'false');

		openWrapper.customSelectData.selectElement.inert = true;
		openWrapper.customSelectData.dropdown.inert = true;

		// Clear highlighted option
		const selectElement = openWrapper.customSelectData.selectElement;
		if (selectElement) {
			this.highlightedOptionMap.delete(selectElement);
		}

		// Clear filter input
		const filterInput = openWrapper.querySelector(`.${this.filterClass}`);
		if (filterInput) {
			filterInput.value = '';
		}

		const optionsList = openWrapper.querySelector(`.${this.optionsClass}`);
		optionsList.querySelectorAll(`.${this.optionClass}`).forEach((option) => {
			option.classList.remove(this.highlightedClass, 'Hidden', 'Disabled');
		});

		// Also remove Hidden class from optgroups
		optionsList.querySelectorAll(`.${this.optgroupClass}`).forEach((optgroup) => {
			optgroup.classList.remove('Hidden');
		});
	}

	/**
	 * Hides the dropdown when another element is focused.
	 *
	 * @param {Event} event
	 */
	handleFocus(event) {
		if (event.target.matches && event.target.matches('dialog')) {
			return;
		}

		const wrapper = event.target.closest ? document.activeElement.closest(`.${this.wrapperClass}`) : null;

		// Close all other dropdowns
		document.querySelectorAll(`.${this.wrapperClass}.${this.openClass}`).forEach((openWrapper) => {
			if (openWrapper !== wrapper) {
				this.closeWrapper(openWrapper);
			}
		});
	}

	/**
	 * Sync disabled state from original select options to custom options.
	 *
	 * @param {HTMLSelectElement} selectElement
	 */
	syncDisabledOptions(selectElement) {
		if (!selectElement.customSelectData) {
			return;
		}

		const optionsList = selectElement.customSelectData.optionsList;

		selectElement.querySelectorAll('option').forEach((option, index) => {
			const customOption = optionsList.querySelector(`[data-index="${index}"]`);
			if (!customOption) {
				return;
			}

			customOption.classList.toggle('Disabled', option.disabled);
			if (option.disabled) {
				customOption.setAttribute('aria-disabled', 'true');
			}
			else {
				customOption.removeAttribute('aria-disabled');
			}
		});
	}

	/**
	 * Determine the optimal positioning for the dropdown.
	 *
	 * @param {HTMLElement} wrapper
	 * @param {HTMLElement} button
	 * @returns {boolean} Returns true if native picker should be used, false otherwise
	 */
	determineDropdownPosition(wrapper, button) {
		const closestScrollingElement = this.getClosestScrollingElement(wrapper);
		const maxHeight = Math.min(320, Math.max(100, Math.min(window.visualViewport.height - 220))) + 80;
		const minSmallHeight = 250;

		const buttonRect = button.getBoundingClientRect();
		const scrollingRect = closestScrollingElement.getBoundingClientRect();
		const isDocumentScroller = closestScrollingElement === (document.scrollingElement || document.documentElement);

		// Calculate total available space in the scrolling container
		const totalHeight = isDocumentScroller
			? document.documentElement.scrollHeight
			: closestScrollingElement.scrollHeight;

		// Button's absolute position within the scrollable content area
		let buttonTopInContainer, buttonBottomInContainer;

		if (isDocumentScroller) {
			// For document scrolling, buttonRect.top is relative to viewport, add current scroll position
			buttonTopInContainer = buttonRect.top + window.scrollY;
			buttonBottomInContainer = buttonTopInContainer + buttonRect.height;
		} else {
			// For container scrolling, convert from viewport position to container content position
			buttonTopInContainer = buttonRect.top - scrollingRect.top + closestScrollingElement.scrollTop;
			buttonBottomInContainer = buttonTopInContainer + buttonRect.height;
		}

		// Total space available in the container (scroll-independent)
		const spaceBelow = totalHeight - buttonBottomInContainer;
		const spaceAbove = buttonTopInContainer;

		const dropdown = wrapper.customSelectData.dropdown;
		let fitsBelow = false;
		let fitsAbove = false;

		// Try below with full height
		if (spaceBelow >= maxHeight) {
			fitsBelow = true;
		}

		// Try above with full height
		if (spaceAbove >= maxHeight) {
			if (!fitsBelow) {
				dropdown.classList.add('Above');
			}

			fitsAbove = true;
		}

		if (!fitsBelow && !fitsAbove) {
			// Try below with small height
			if (spaceBelow >= minSmallHeight) {
				dropdown.classList.add('Small');
				fitsBelow = true;
			}

			// Try above with small height
			if (spaceAbove >= minSmallHeight) {
				dropdown.classList.add('Above', 'Small');
				fitsAbove = true;
			}
		}

		if (fitsBelow && fitsAbove) {
			// Compute visible space as the intersection between the scrolling container and the visual viewport
			const viewportTop = window.visualViewport ? window.visualViewport.offsetTop : 0;
			const viewportBottom = window.visualViewport ? (window.visualViewport.offsetTop + window.visualViewport.height) : window.innerHeight;

			const visibleRectTop = Math.max(scrollingRect.top, viewportTop);
			const visibleRectBottom = Math.min(scrollingRect.bottom, viewportBottom);

			const visibleSpaceBelow = visibleRectBottom - buttonRect.bottom;
			const visibleSpaceAbove = buttonRect.top - visibleRectTop;

			// Prefer the side with more visible room
			if (visibleSpaceAbove > visibleSpaceBelow) {
				dropdown.classList.add('Above');
			}
			else {
				dropdown.classList.remove('Above');
			}
		}

		// Fallback to native picker
		return !fitsBelow && !fitsAbove;
	}

	/**
	 * Toggle the dropdown visibility.
	 *
	 * @param {HTMLElement} button
	 */
	toggleDropdown(button) {
		const wrapper = button.closest(`.${this.wrapperClass}`);
		const isOpen = wrapper.classList.contains(this.openClass);

		// Close all other dropdowns
		document.querySelectorAll(`.${this.wrapperClass}.${this.openClass}`).forEach((openWrapper) => {
			if (openWrapper !== wrapper || isOpen) {
				this.closeWrapper(openWrapper)
			}
		});

		wrapper.customSelectData.dropdown.inert = isOpen;

		if (isOpen) {
			return;
		}

		wrapper.customSelectData.dropdown.classList.remove('Above', 'Small');

		// Determine positioning and whether to use native picker
		const useNativePicker = this.determineDropdownPosition(wrapper, button);

		if (useNativePicker) {
			wrapper.customSelectData.selectElement.inert = false;
			wrapper.customSelectData.selectElement.focus();
			wrapper.customSelectData.selectElement.showPicker();
			wrapper.inert = true;
			return;
		}

		// Open the dropdown
		wrapper.classList.add(this.openClass);
		button.setAttribute('aria-expanded', 'true');

		// Clear and focus the filter input
		const filterInput = wrapper.querySelector(`.${this.filterClass}`);
		filterInput.value = '';
		filterInput.focus();

		// Sync disabled options before opening
		const selectElement = wrapper.customSelectData.selectElement;
		this.syncDisabledOptions(selectElement);

		// Set highlighted option to the active one
		const activeItem = wrapper.customSelectData.optionsList.querySelector(`.${this.activeClass}`);
		this.highlightedOptionMap.set(selectElement, activeItem);

		if (activeItem && !selectElement.dataset.scrolledIntoView) {
			activeItem.scrollIntoView({behavior: 'smooth', block: 'nearest'});
			selectElement.dataset.scrolledIntoView = 'true';
		}
	}

	/**
	 * Select an option.
	 *
	 * @param {HTMLElement} optionDiv
	 */
	useOption(optionDiv) {
		const wrapper = optionDiv.closest(`.${this.wrapperClass}`);
		const selectElement = wrapper.customSelectData.selectElement;

		if (!selectElement) {
			return;
		}

		// Update the original select element
		selectElement.value = optionDiv.getAttribute('data-value');

		// Update UI
		this.updateButtonText(selectElement);
		this.updateActiveOption(selectElement);

		// Close the dropdown
		wrapper.classList.remove(this.openClass);
		const button = wrapper.querySelector(`.${this.buttonClass}`);
		button.setAttribute('aria-expanded', 'false');
		button.focus();

		// Trigger change and input events
		selectElement.dispatchEvent(new Event('input', { bubbles: true, target: selectElement }));
		selectElement.dispatchEvent(new Event('change', { bubbles: true, target: selectElement }));

		this.closeWrapper(wrapper);
	}

	/**
	 * Update the active option styling.
	 *
	 * @param {HTMLSelectElement} selectElement
	 */
	updateActiveOption(selectElement) {
		if (!selectElement.customSelectData) {
			return;
		}

		const optionsList = selectElement.customSelectData.optionsList;

		// Remove active class from all options
		optionsList.querySelectorAll(`.${this.optionClass}`).forEach((option) => {
			option.classList.remove(this.activeClass);
		});

		// Add active class to the selected option
		const selectedValue = selectElement.value;
		const selectedOption = optionsList.querySelector(`[data-value="${selectedValue}"]`);
		if (selectedOption) {
			selectedOption.classList.add(this.activeClass);
		}
	}

	/**
	 * Get all visible and enabled option elements in order.
	 *
	 * @param {HTMLElement} optionsList
	 * @returns {HTMLElement[]}
	 */
	getAvailableOptions(optionsList) {
		return Array.from(optionsList.querySelectorAll(`.${this.optionClass}:not(.Hidden):not(.Disabled)`));
	}

	/**
	 * Get the next visible and enabled option element.
	 *
	 * @param {HTMLElement} currentOption
	 * @param {HTMLElement} optionsList
	 * @returns {HTMLElement|null}
	 */
	getNextOption(currentOption, optionsList) {
		const availableOptions = this.getAvailableOptions(optionsList);
		const currentIndex = availableOptions.indexOf(currentOption);

		if (currentIndex === -1 || currentIndex === availableOptions.length - 1) {
			return null;
		}

		return availableOptions[currentIndex + 1];
	}

	/**
	 * Get the previous visible and enabled option element.
	 *
	 * @param {HTMLElement} currentOption
	 * @param {HTMLElement} optionsList
	 * @returns {HTMLElement|null}
	 */
	getPreviousOption(currentOption, optionsList) {
		const availableOptions = this.getAvailableOptions(optionsList);
		const currentIndex = availableOptions.indexOf(currentOption);

		if (currentIndex <= 0) {
			return null;
		}

		return availableOptions[currentIndex - 1];
	}

	/**
	 * Handle keyboard navigation.
	 *
	 * @param {KeyboardEvent} event
	 */
	handleKeyDown(event) {
		const filterInput = event.target.closest(`.${this.filterClass}`);
		const wrapper = event.target.closest(`.${this.wrapperClass}`);
		const selectElement = wrapper?.customSelectData?.selectElement;

		if (!selectElement) {
			return;
		}

		// Handle Escape key first, regardless of highlighted option
		if (event.key === 'Escape') {
			event.preventDefault();
			this.closeWrapper(wrapper);
			const button = wrapper.querySelector(`.${this.buttonClass}`);
			button.focus();
			return;
		}

		const optionsList = wrapper.querySelector(`.${this.optionsClass}`);
		let currentOption = this.highlightedOptionMap.get(selectElement);

		// If no option is highlighted and the key event did not originate from the filter input, highlight option before or after the active one
		if (!currentOption && !filterInput && event.target.matches(`.${this.buttonClass}`)) {
			currentOption = optionsList.querySelector(`.${this.activeClass}`);
		}

		// If no option is highlighted, start with the first visible one
		if (!currentOption) {
			currentOption = optionsList.querySelector(`.${this.optionClass}:not(.Hidden, .Disabled)`);
			if (currentOption) {
				this.highlightedOptionMap.set(selectElement, currentOption);
				currentOption.classList.add(this.highlightedClass);
			}
			return;
		}

		switch (event.key) {
			case 'ArrowDown': {
				event.preventDefault();
				let nextOption = this.getNextOption(currentOption, optionsList);

				if (nextOption) {
					currentOption.classList.remove(this.highlightedClass);
					nextOption.classList.add(this.highlightedClass);
					this.highlightedOptionMap.set(selectElement, nextOption);
					nextOption.scrollIntoView({ block: 'nearest' });

					// If the arrow down did not originate from the filter input but the button, use the new highlighted option
					if (!filterInput && event.target.matches(`.${this.buttonClass}`)) {
						this.useOption(nextOption);
					}
				}

				break;
			}

			case 'ArrowUp': {
				event.preventDefault();
				let prevOption = this.getPreviousOption(currentOption, optionsList);

				if (prevOption) {
					currentOption.classList.remove(this.highlightedClass);
					prevOption.classList.add(this.highlightedClass);
					this.highlightedOptionMap.set(selectElement, prevOption);
					prevOption.scrollIntoView({ block: 'nearest' });

					// If the arrow down did not originate from the filter input but the button, use the new highlighted option
					if (!filterInput && event.target.matches(`.${this.buttonClass}`)) {
						this.useOption(prevOption);
					}
				}

				break;
			}

			case 'Enter': {
				event.preventDefault();
				this.useOption(currentOption);
				break;
			}
		}
	}

	/**
	 * Handle filter input changes.
	 *
	 * @param {Event} event
	 */
	handleFilterInput(event) {
		const filterInput = event.target.closest(`.${this.filterClass}`);
		if (!filterInput) {
			return;
		}

		const wrapper = filterInput.closest(`.${this.wrapperClass}`);
		const optionsList = wrapper.querySelector(`.${this.optionsClass}`);
		const filterValue = filterInput.value.toLowerCase();
		const selectElement = wrapper.customSelectData.selectElement;

		// Filter options
		optionsList.querySelectorAll(`.${this.optionClass}`).forEach((option) => {
			const optionText = option.textContent.toLowerCase();
			const isMatching = optionText.includes(filterValue);

			option.classList.toggle('Hidden', !isMatching);
		});

		// Hide optgroups that have no visible options
		optionsList.querySelectorAll(`.${this.optgroupClass}`).forEach((optgroup) => {
			const visibleOptions = optgroup.querySelectorAll(`.${this.optionClass}:not(.Hidden)`);
			optgroup.classList.toggle('Hidden', visibleOptions.length === 0);
		});

		// Clear highlighted option and remove highlight styling
		if (selectElement) {
			this.highlightedOptionMap.delete(selectElement);
			optionsList.querySelectorAll(`.${this.optionClass}`).forEach((option) => {
				option.classList.remove(this.highlightedClass);
			});
		}

		optionsList.scrollTo(0, 0);
	}

	/**
	 * Handle changes to the original select element.
	 *
	 * @param {Event} event
	 */
	handleSelectChange(event) {
		const selectElement = event.target;

		if (selectElement.tagName !== 'SELECT' || !selectElement.customSelectData) {
			return;
		}

		this.updateButtonText(selectElement);
		this.updateActiveOption(selectElement);
	}

	/**
	 * Get the closest scrolling ancestor element.
	 *
	 * @param {HTMLElement} wrapper
	 * @returns {Element|HTMLElement|HTMLElement}
	 */
	getClosestScrollingElement(wrapper) {
		let parent = wrapper.parentElement;
		while (parent) {
			const overflowY = window.getComputedStyle(parent).overflowY;
			if (overflowY === 'auto' || overflowY === 'scroll') {
				return parent;
			}
			parent = parent.parentElement;
		}

		return document.scrollingElement || document.documentElement;
	}

	/*
	 * Mutation Observer
	 *
	 * @param {MutationRecord} mutation - The mutation to observe.
	 */
	handleMutations(mutations) {
		const affectedSelects = new Set();

		mutations.forEach((mutation) => {
			const addedNodes = mutation.addedNodes;
			addedNodes.forEach((node) => {
				if (node.nodeType !== Node.ELEMENT_NODE) {
					return;
				}

				if (node.matches(this.selector)) {
					this.initSelectElement(node);
				}

				const selectElements = node.querySelectorAll(this.selector);
				selectElements.forEach((selectElement) => {
					this.initSelectElement(selectElement);
				});
			});

			// Check for changes to options
			if (mutation.type === 'childList' && mutation.target.matches && mutation.target.matches(this.selector)) {
				affectedSelects.add(mutation.target);
			}
			// Check for changes to optgroups
			else if (mutation.type === 'childList' && mutation.target.nodeName === 'OPTGROUP' && mutation.target.parentNode.matches && mutation.target.parentNode.matches(this.selector)) {
				affectedSelects.add(mutation.target.parentNode);
			}
			else if (mutation.type === 'attributes' && mutation.target.nodeName === 'OPTION' && mutation.target.parentNode.matches && mutation.target.parentNode.matches(this.selector)) {
				affectedSelects.add(mutation.target.parentNode);
			}
			// Handle options within optgroups
			else if (mutation.type === 'attributes' && mutation.target.nodeName === 'OPTION' && mutation.target.parentNode.nodeName === 'OPTGROUP' && mutation.target.parentNode.parentNode.matches && mutation.target.parentNode.parentNode.matches(this.selector)) {
				affectedSelects.add(mutation.target.parentNode.parentNode);
			}
			else if (mutation.type === 'characterData' && mutation.target.parentNode && mutation.target.parentNode.nodeName === 'OPTION' && mutation.target.parentNode.parentNode.matches && mutation.target.parentNode.parentNode.matches(this.selector)) {
				affectedSelects.add(mutation.target.parentNode.parentNode);
			}
			// Handle character data changes within options inside optgroups
			else if (mutation.type === 'characterData' && mutation.target.parentNode && mutation.target.parentNode.nodeName === 'OPTION' && mutation.target.parentNode.parentNode.nodeName === 'OPTGROUP' && mutation.target.parentNode.parentNode.parentNode.matches && mutation.target.parentNode.parentNode.parentNode.matches(this.selector)) {
				affectedSelects.add(mutation.target.parentNode.parentNode.parentNode);
			}
		});

		// Update affected selects
		affectedSelects.forEach((selectElement) => {
			if (selectElement.customSelectData) {
				this.populateOptions(selectElement, selectElement.customSelectData.optionsList);
				this.updateActiveOption(selectElement);
				this.updateButtonText(selectElement);
				this.syncDisabledOptions(selectElement);
			}
		});
	}
}
