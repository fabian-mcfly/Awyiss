//noinspection JSUnusedGlobalSymbols

/**
 * TitleAttributeSetter class.
 * This class is used to set the title attribute of HTML elements.
 * The title attribute is set to the trimmed text content of each element.
 */
export default class TitleSetter {
	/**
	 * @type {string} selector - The selector of the elements to set the title attribute for.
	 */
	selector = '.DataItem, .Button';

	/**
	 * @param {string} selector - The selector of the elements to set the title attribute for.
	 */
	constructor(selector) {
		if (selector) {
			this.selector = selector;
		}

		const elements = document.querySelectorAll(this.selector);
		elements.forEach((element) => {
			this.initElement(element);
		});

		const observer = window.observer;
		observer.addObserver(this.observeMutations.bind(this));
	}

	/**
	 * Initialize the title attribute for the given element.
	 *
	 * @param {HTMLElement} element - The element to initialize.
	 */
	initElement(element) {
		if (element.title) {
			// If the element already has a title, skip it
			return;
		}

		let text = element.textContent.trim();

		// Replace all types of line breaks and tabs with a single space
		text = text.replace(/[\r\n\t]+/g, ' ');

		// Replace multiple spaces with a single space
		text = text.replace(/ {2,}/g, ' ');

		element.setAttribute('title', text);
	}

	/*
	 * Mutation Observer
	 *
	 * @param {MutationRecord} mutation - The mutation to observe.
	 */
	observeMutations(mutation) {
		const addedNodes = mutation.addedNodes || [];
		addedNodes.forEach((node) => {
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
		})
	}
}
