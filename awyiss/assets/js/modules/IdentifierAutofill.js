//noinspection JSUnusedGlobalSymbols

/**
 * Class to handle autofilling of an identifier input based on a title input
 */
export default class IdentifierAutofill {
	/**
	 * The map of accented characters to their non-accented equivalents
	 * @type {{}}
	 */
	accentsMap = {
		'À': 'A', 'Á': 'A', 'Â': 'A', 'Ã': 'A', 'Ä': 'Ae', 'Å': 'A',
		'à': 'a', 'á': 'a', 'â': 'a', 'ã': 'a', 'ä': 'ae', 'å': 'a',
		'Ò': 'O', 'Ó': 'O', 'Ô': 'O', 'Õ': 'O', 'Ö': 'Oe', 'Ø': 'O',
		'ò': 'o', 'ó': 'o', 'ô': 'o', 'õ': 'o', 'ö': 'oe', 'ø': 'o',
		'È': 'E', 'É': 'E', 'Ê': 'E', 'Ë': 'E',
		'è': 'e', 'é': 'e', 'ê': 'e', 'ë': 'e',
		'Ç': 'C', 'ç': 'c',
		'Ì': 'I', 'Í': 'I', 'Î': 'I', 'Ï': 'I',
		'ì': 'i', 'í': 'i', 'î': 'i', 'ï': 'i',
		'Ù': 'U', 'Ú': 'U', 'Û': 'U', 'Ü': 'Ue',
		'ù': 'u', 'ú': 'u', 'û': 'u', 'ü': 'ue',
		'Ñ': 'N', 'ñ': 'n',
		'ß': 'ss'
	};
	/**
	 * The bound handleBlur method.
	 */
	boundHandleBlur;
	/**
	 * The event handler instance.
	 * @type {EventHandler}
	 */
	eventHandler = window.eventHandler
	/**
	 * Whether the event handler is bound.
	 * @type {boolean}
	 */
	eventHandlerBound = false;
	/**
	 * The form elements to listen for input events on.
	 * @type {NodeListOf<HTMLElementTagNameMap[string]>}
	 */
	forms;
	/**
	 * Whether the input handler is bound.
	 * @type {boolean}
	 */
	inputHandlerBound = false;
	/**
	 * The input elements to listen for input events on.
	 * @type {{}}
	 */
	inputs = [];

	constructor() {
		this.forms = document.querySelectorAll('form');

		const observer = window.observer;
		observer.addObserver(this.observeMutations.bind(this));

		if (!this.forms.length) {
			return;
		}

		this.forms.forEach(form => {
			this.initInputs(form);
		});

		this.eventHandler.add('input', this.handleInputEvent.bind(this));
		this.eventHandlerBound = true;
	}

	/**
	 * Initialize the input elements
	 * @param {HTMLElement} form - The form element
	 */
	initInputs(form) {
		const elements = Array.from(form.getElementsByTagName('input')).filter(
			element => Array.from(element.attributes).some(attr => attr.name.startsWith('data-autofill-from-') && attr.value === 'true')
		);

		elements.forEach(element => {
			// Find the exact attribute name
			const attributeName = Array.from(element.attributes).find(attr => attr.name.startsWith('data-autofill-from-')).name;

			// Extract the title from the attribute name
			const title = attributeName.substring(19);

			let input = form.querySelector(`input[name="${title}"]`);

			if (!input) {
				return;
			}

			// Check if the input is inside a translatable text field.
			// In this case, we need to get the input for the current language as the source
			const translatableTexts = input.closest('.FormInputType-TranslatableText')
			if (translatableTexts) {
				const currentLanguageInput = translatableTexts.querySelector(`.FormInput.IsCurrentLanguage input`);
				input = currentLanguageInput || input;
			}

			this.inputs.push({
				allowedChars: element.dataset.allowedCharacters || '',
				boundHandleBlur: this.handleBlur.bind(this),
				camelize: element.dataset.camelize === 'true' || element.dataset.camelize === '1',
				identifierInput: element,
				input: input,
				isLocked: element.value !== '',
				replacementChar: element.dataset.replacementChar,
			});
		});
	}

	/**
	 * Handle input event on the title and identifier fields
	 *
	 * @param {Event} event - The input event
	 */
	handleInputEvent(event) {
		const inputDataObject = this.inputs.find(input => input.input === event.target || input.identifierInput === event.target);

		if (!inputDataObject) {
			return;
		}

		const target = event.target;

		// Check if the event target is the identifier input
		if (target === inputDataObject.identifierInput) {
			// Remember the cursor position
			let cursorPosition = {
				start: target.selectionStart,
				end: target.selectionEnd,
			}

			// Sanitize the identifier input
			target.value = this.sanitizeIdentifier(target.value, inputDataObject.replacementChar, inputDataObject.allowedChars, inputDataObject.camelize, false);

			// If the user input was an accented character, the cursor position will be moved forward by the length of the replacement
			// noinspection JSUnresolvedReference
			if (this.accentsMap[event.data]) {
				// noinspection JSUnresolvedReference
				cursorPosition.start = cursorPosition.start + this.accentsMap[event.data].length - 1;
				cursorPosition.end = cursorPosition.start;
			}

			target.setSelectionRange(cursorPosition.start, cursorPosition.end);

			// Update the locked state based on whether the identifier field is empty
			inputDataObject.isLocked = target.value !== '';

			if (!inputDataObject.blurListenerAdded) {
				this.eventHandler.add('blur', inputDataObject.boundHandleBlur, target);
				inputDataObject.blurListenerAdded = true;
			}

			return;
		}

		// If the identifier field is locked or if the event target is not the title input or if it is inside a dialog
		// then do nothing
		if (inputDataObject.isLocked || target.closest('dialog')) {
			return;
		}

		// Update the identifier field
		inputDataObject.identifierInput.value = this.sanitizeIdentifier(target.value, inputDataObject.replacementChar, inputDataObject.allowedChars, inputDataObject.camelize);
	}

	/**
	 * Handle blur event on the identifier field
	 * This is used to sanitize the identifier input when it loses focus,
	 * mainly to remove trailing underscores
	 *
	 * @param {Event} event - The blur event
	 */
	handleBlur(event) {
		const inputDataObject = this.inputs.find(input => input.identifierInput === event.target);

		// Sanitize the identifier input
		event.target.value = this.sanitizeIdentifier(event.target.value, inputDataObject.replacementChar, inputDataObject.allowedChars, inputDataObject.camelize);
		this.eventHandler.remove('blur', inputDataObject.boundHandleBlur, event.target);
		inputDataObject.blurListenerAdded = false;
	}

	/**
	 * Sanitize the input string to form a valid identifier
	 *
	 * @param {string} input - The input string
	 * @param {string} replacementChar - The character to replace underscores with
	 * @param {string} allowedChars - String of allowed characters
	 * @param {boolean} camelize - Whether to camelize the identifier
	 * @param {boolean} removeTrailing - Whether to remove trailing underscores
	 * @return {string} - The sanitized identifier
	 */
	sanitizeIdentifier(input, replacementChar = '_', allowedChars = '', camelize = false, removeTrailing = true) {
		// Replace accented characters and remove any characters that are not letters, numbers, underscores, or allowed characters
		let identifier = this.replaceAccents(input).replace(new RegExp(`[^a-zA-Z0-9_${allowedChars}]`, 'g'), '_');

		// Remove consecutive underscores
		identifier = identifier.replace(/_{2,}/g, '_');

		if (removeTrailing) {
			// Remove underscores from the beginning and end of the string
			identifier = identifier.replace(/^_+|_+$/g, '');
		}
		else {
			// Remove underscores from the beginning of the string
			identifier = identifier.replace(/^_+/g, '');
		}

		// Replace underscores if replacementChar is not an underscore
		if (replacementChar !== '_') {
			identifier = identifier.replace(/_/g, replacementChar);
		}

		// If camelize is true, convert characters after underscores to uppercase
		if (camelize) {
			identifier = identifier.replace(/([a-z])([A-Z])/g, (match, p1, p2) => `${p1}${replacementChar}${p2}`);
			identifier = identifier.replace(new RegExp(`\\${replacementChar}(\\w)`, 'g'), (match, p1) => p1.toUpperCase());
		}
		else {
			// Convert to lowercase if not camelizing
			identifier = identifier.toLowerCase();
		}

		return identifier;
	}

	/**
	 * Replace accented characters with non-accented equivalents

	 * @param {string} str - The string to replace characters in
	 * @return {string} - The string with replaced characters
	 */
	replaceAccents(str) {
		return str.split('').map(letter => this.accentsMap[letter] || letter).join('');
	}

	/**
	 * Handle a mutation
	 * @param {MutationRecord} mutation - The mutation record
	 */
	observeMutations(mutation) {
		if (mutation.removedNodes.length) {
			// Convert NodeList to Array
			const removedNodesArray = Array.from(mutation.removedNodes);

			// Remove any inputs that were in the removed nodes
			this.inputs = this.inputs.filter(input => !removedNodesArray.includes(input.identifierInput) && !removedNodesArray.includes(input.input));

			// Remove any inputs that were inside the removed nodes
			mutation.removedNodes.forEach(node => {
				this.inputs = this.inputs.filter(input => !node.contains(input.identifierInput) && !node.contains(input.input));

			});
		}

		mutation.addedNodes.forEach(node => {
			if (node.nodeType !== Node.ELEMENT_NODE) {
				return;
			}

			let formFound = false;

			// If the node itself is a form, initialize the inputs
			if (node.tagName === 'FORM') {
				this.initInputs(node);
				formFound = true;
			}
			// Check if the node is a child of a form
			else if (node.closest('form')) {
				this.initInputs(node);
				formFound = true;
			}
			else {
				// If any child of the node is a form, initialize the inputs
				node.querySelectorAll('form').forEach(form => {
					this.initInputs(form);
					formFound = true;
				});
			}

			if (formFound && !this.eventHandlerBound) {
				this.eventHandler.add('input', this.handleInputEvent.bind(this));
				this.eventHandlerBound = true;
			}
		});
	}
}