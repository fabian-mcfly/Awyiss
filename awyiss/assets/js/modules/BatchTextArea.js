// noinspection JSUnusedGlobalSymbols

/**
 * BatchArea class
 * Provides tab and enter key functionality for text areas.
 * Pressing the tab key inserts a tab character at the cursor position,
 * and pressing the enter key inserts a new line with the same indentation level as the previous line.
 */
export default class BatchTextArea {
	/**
	 * The DOM element to attach the event listener to.
	 * @type {HTMLTextAreaElement}
	 */
	element;
	/**
	 * The event handler instance.
	 * @type {EventHandler}
	 */
	eventHandler = window.eventHandler;

	/**
	 * The constructor initializes the element and attaches the keydown event listener to it.
	 * @param {HTMLTextAreaElement} element The DOM element to attach the event listener to.
	 * @returns {void}
	 */
	constructor(element) {
		//Assign the passed DOM element to the private field
		this.element = element;

		//Add a keydown event listener to the element using an arrow function
		window.eventHandler.add('keydown', this.handleKeyEvent.bind(this), this.element);
	}

	/**
	 * The handleKeyEvent method is called when a keydown event is triggered on the element.
	 * It checks if the pressed key is the tab key or the enter key and calls the corresponding method.
	 * @param {KeyboardEvent} event The event object passed by the event listener.
	 * @returns {void}
	 */
	handleKeyEvent = (event) => {
		const keyCode = event.code;

		//Tab key adds a tab character
		if (keyCode === 'Tab') {
			event.preventDefault();
			this.insertTab();
		}

		//Enter key adds a new line with indentation (if not combined with Ctrl or Shift)
		if (keyCode === 'Enter' && !(event.ctrlKey || event.shiftKey)) {
			event.preventDefault();
			this.insertNewLineWithIndent();
		}
	}

	/**
	 * The insertTab method inserts a tab character at the cursor position.
	 * It destructures the selection start, selection end, and value properties from the element for easier access,
	 * inserts a tab character at the cursor position, and updates the cursor position to be after the inserted tab.
	 * @returns {void}
	 */
	insertTab = () => {
		//Destructure properties from the element for easier access
		const {selectionStart: start, selectionEnd: end, value} = this.element;

		//Insert a tab character at the cursor position
		this.element.value = `${value.substring(0, start)}\t${value.substring(end)}`;
		//Update the cursor position to be after the inserted tab
		this.element.selectionStart = this.element.selectionEnd = start + 1;
	}

	/**
	 * The insertNewLineWithIndent method inserts a new line with the same indentation level as the previous line.
	 * It gets the last line before the cursor to determine the indentation level,
	 * counts the number of tab characters for indentation (nullish coalescing defaults to 0),
	 * inserts a newline and repeats the tabs for indentation,
	 * and updates the cursor position to be after the new line and indentation.
	 * @returns {void}
	 */
	insertNewLineWithIndent = () => {
		//Destructure properties from the element for easier access
		const {selectionStart: start, selectionEnd: end, value} = this.element;
		//Get the last line before the cursor to determine the indentation level
		const lastLine = value.substring(0, start).split("\n").pop();
		//Count the number of tab characters for indentation (nullish coalescing defaults to 0)
		const indentLevel = lastLine.match(/\t/g)?.length ?? 0;

		//Insert a newline and repeat the tabs for indentation
		this.element.value = `${value.substring(0, start)}\n${"\t".repeat(indentLevel)}${value.substring(end)}`;
		//Update the cursor position to be after the new line and indentation
		this.element.selectionStart = this.element.selectionEnd = start + 1 + indentLevel;
	}
}
