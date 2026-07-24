//noinspection JSUnusedGlobalSymbols

export default class PlaceholderHelper {
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
	 * The selector for the placeholder elements.
	 * @type {string}
	 */
	selector = 'input[placeholder], textarea[placeholder]';

	constructor() {
		const elements = document.querySelectorAll(this.selector);
		elements.forEach((element) => {
			this.initElement(element);
		});

		this.observer.addObserver(this.observeMutations.bind(this));
	}

	/**
	 * Handle the focus and blur events for the element.
	 * @param {HTMLInputElement|HTMLTextAreaElement} element
	 */
	initElement(element) {
		// Bind focus and blur events to the element
		this.eventHandler.add('focus', this.handleElement.bind(this), element);
		this.eventHandler.add('blur', this.handleElement.bind(this), element);
		this.eventHandler.add('keyup', this.handleElement.bind(this), element);
		this.eventHandler.add('input', this.handleElement.bind(this), element);
	}

	/**
	 * Handle the events for the apply button.
	 */
	handleButton(event) {
		const button = event.target;
		const input = button.input;

		if (event.type === 'blur') {
			button.hideTimeout = setTimeout(() => {
				// If the button is blurred, hide the button
				button.classList.remove('Visible');
				button.inert = true;
			}, 100);

			return;
		}

		if (event.type === 'focus') {
			clearTimeout(button.hideTimeout);

			button.classList.add('Visible');
			button.inert = false;
			return;
		}

		if (input.value.length > 0) {
			return;
		}

		// If the input is empty, set the value to the placeholder
		input.value = input.placeholder;
		input.dispatchEvent(new Event('input', {bubbles: true}));
		input.focus();

		button.classList.remove('Visible');
		button.inert = true;
	}

	/**
	 * Handle the focus and blur events for the element.
	 */
	handleElement(event) {
		const input = event.target;
		let applyButton = input.applyButton;

		if (
			!input.placeholder ||
			input.closest('.jodit-ui-input__wrapper') ||
			input.closest('.tox-dialog')
		) {
			return;
		}

		if (!applyButton) {
			applyButton = document.createElement('button');
			applyButton.type = 'button';
			applyButton.classList.add('Button', 'Button-Save', 'Button-ApplyPlaceholder');
			applyButton.input = input;
			input.parentNode.insertBefore(applyButton, input.nextSibling);

			this.eventHandler.add('click', this.handleButton.bind(this), applyButton);
			this.eventHandler.add('focus', this.handleButton.bind(this), applyButton);
			this.eventHandler.add('blur', this.handleButton.bind(this), applyButton);

			input.applyButton = applyButton;
		}

		const value = input.value;

		if (event.type === 'keyup' || event.type === 'input') {
			if (value.length > 0) {
				// Set a timeout to hide the button
				applyButton.hideTimeout = setTimeout(() => {
					applyButton.classList.remove('Visible');
					applyButton.inert = true;
				}, 100);
			}
			else {
				clearTimeout(applyButton.hideTimeout);

				// If the input is focused, show the button
				applyButton.classList.add('Visible');
				applyButton.inert = false;
			}
		}

		if (value.length > 0) {
			// If the input has a value, there is nothing to do
			return;
		}

		if (event.type === 'blur') {
			// Set a timeout to hide the button
			applyButton.hideTimeout = setTimeout(() => {
				applyButton.classList.remove('Visible');
				applyButton.inert = true;
			}, 100);
		}

		if (event.type === 'focus') {
			clearTimeout(applyButton.hideTimeout);

			// If the input is focused, show the button
			applyButton.classList.add('Visible');
			applyButton.inert = false;

			const dummy = document.createElement('span');
			document.body.appendChild(dummy);
			dummy.innerText = input.placeholder;

			// If the input is a textarea, use the first line width
			if (input.nodeName.toLowerCase() === 'textarea') {
				const lines = input.placeholder.split("\n");
				dummy.innerText = lines[0];
			}

			// Calculate the width of the placeholder text
			let placeholderWidth = dummy.getBoundingClientRect().width + 20;
			dummy.remove();

			if (placeholderWidth + 60 > input.getBoundingClientRect().width) {
				placeholderWidth = -50;
			}

			applyButton.style.setProperty('--offsetLeft', `${placeholderWidth}px`);
		}
	}

	/**
	 * Observe mutations in the DOM and initialize the batch text area for new elements.
	 *
	 * @param {MutationRecord} mutation - The mutation to observe.
	 */
	observeMutations(mutation) {
		if (!mutation.addedNodes.length > 0) {
			return;
		}

		mutation.addedNodes.forEach((node) => {
			if (node.nodeType !== Node.ELEMENT_NODE) {
				return;
			}

			if (node.matches(this.selector)) {
				this.initElement(node);
			}

			const elements = node.querySelectorAll(this.selector);
			elements.forEach((element) => {
				this.initElement(element);
			});
		});
	}
}
