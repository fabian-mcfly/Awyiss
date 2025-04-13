// noinspection JSUnusedGlobalSymbols

/**
 * SEO Snippet class
 */
export default class SeoSnippet {
	/**
	 * An instance of CharCounter
	 *
	 * @property {CharCounter} charCounter
	 */
	charCounter;
	/**
	 * An instance of EventHandler
	 *
	 * @property {EventHandler} eventHandler
	 */
	eventHandler = window.eventHandler;
	/**
	 * The main element
	 *
	 * @property {HTMLElement|null} element
	 */
	element;
	/**
	 * The settings for the SEO snippet
	 *
	 * @property {object} settings
	 */
	settings = {
		title: {
			warning: 56,
			error: 70,
		},
		desc: {
			warning: 129,
			error: 160,
		},
	};
	/**
	 * The SEO element
	 *
	 * @property {HTMLElement|null} seo
	 */
	seo = null;
	/**
	 * The page title input element
	 *
	 * @property {HTMLInputElement|null} pageTitle
	 */
	pageTitle = null;
	/**
	 * The meta title input element
	 *
	 * @property {HTMLInputElement|null} metaTitle
	 */
	metaTitle = null;
	/**
	 * The meta description textarea element
	 *
	 * @property {HTMLTextAreaElement|null} metaDescription
	 */
	metaDescription = null;
	/**
	 * The SEO snippet search term input element
	 *
	 * @property {HTMLInputElement|null} seoSnippetSearchTerm
	 */
	seoSnippetSearchTerm = null;
	/**
	 * The slug element
	 *
	 * @property {HTMLElement|null} preSlug
	 */
	slug = null;
	/**
	 * The slug element
	 *
	 * @property {HTMLElement|null} preSlug
	 */
	preSlug = null;
	/**
	 * The selector for the main element
	 *
	 * @property {string} selector
	 */
	selector;
	/**
	 * The SEO snippet title element
	 *
	 * @property {HTMLElement|null} seoSnippetTitle
	 */
	seoSnippetTitle = null;
	/**
	 * The SEO snippet URL element
	 *
	 * @property {HTMLElement|null} seoSnippetUrl
	 */
	seoSnippetUrl = null;
	/**
	 * The SEO snippet description element
	 *
	 * @property {HTMLElement|null} seoSnippetDescription
	 */
	seoSnippetDescription = null;
	/**
	 * The SEO snippet search element
	 *
	 * @property {HTMLElement|null} seoSnippetSearch
	 */
	seoSnippetSearch = null;

	/**
	 * Initialize the SEO snippet
	 * This method sets up the initial state of the SEO snippet and binds the necessary event handlers.
	 *
	 * @param {string} selector - The selector for the main element
	 */
	constructor(selector) {
		this.selector = selector;

		this.initElement(document.querySelector(selector));

		const observer = window.observer;
		observer.addObserver(this.observeForNewInputs.bind(this));

		// Add event listener for input events
		this.eventHandler.add('input', this.update.bind(this), document.body);
	}

	/**
	 * Initialize the input elements
	 * @param element
	 */
	initElement(element) {
		this.element = element;

		if (!this.element) {
			return;
		}

		// Assign the input elements
		this.pageTitle = document.querySelector('input[name="title"]');
		this.metaTitle = document.querySelector('input[name="meta_title"]');
		this.metaDescription = document.querySelector('textarea[name="meta_description"]');
		this.slug = document.querySelector('input[name="slug"]');
		this.preSlug = document.querySelector('.PrePath');

		// Assign the output elements
		this.seoSnippetSearchTerm = this.element.querySelector('input.SearchTerm');
		this.seoSnippetUrl = this.element.querySelector('.SnippetUrl');
		this.seoSnippetTitle = this.element.querySelector('.SnippetMetaTitle');
		this.seoSnippetDescription = this.element.querySelector('.SnippetMetaDescription');

		// Initialize the character counter
		this.charCounter = new CharCounter('input[data-charcounter-name], textarea[data-charcounter-name]');

		// Send an input event to update the SEO snippet
		const event = new InputEvent('input', {bubbles: true, cancelable: true});

		setTimeout(function () {
			this.seoSnippetSearchTerm.dispatchEvent(event);
			window.formLeaveConfirmation.unlock();
		}.bind(this), 100);
	}

	/**
	 * Update the SEO snippet
	 * This method updates the SEO snippet based on the current state of the inputs.
	 * @param {Event} event - The event object
	 */
	update(event) {
		// If the target isn't the meta title input, the meta description input or the slug input, return
		const targets = [this.metaTitle, this.metaDescription, this.slug, this.seoSnippetSearchTerm];

		if (!targets.includes(event.target)) {
			return;
		}

		// Define constants for the current state of the inputs
		const searchTerm = this.seoSnippetSearchTerm.value;
		const pageTitle = this.pageTitle.value;
		const metaTitle = this.metaTitle.value;
		let description = this.metaDescription.value;
		const preSlug = this.preSlug?.textContent ? ' ... ›' : '';

		// Set the title if it's not already set
		const title = metaTitle || this.metaTitle.placeholder;

		// Add warning and stop classes based on the length of the title
		this.metaTitle.classList.toggle('SeoLengthWarning', title.length >= this.settings.title.warning);
		this.metaTitle.classList.toggle('SeoLengthError', title.length >= this.settings.title.error);

		// Add warning and stop classes based on the length of the description
		this.metaDescription.classList.toggle('SeoLengthWarning', description.length >= this.settings.desc.warning);
		this.metaDescription.classList.toggle('SeoLengthError', description.length >= this.settings.desc.error);

		// Set the description if it's not already set
		description = description || this.seoSnippetDescription.dataset.default;

		if (searchTerm) {
			description = this.replace(description, searchTerm);
		}

		const url = `${this.seoSnippetUrl.dataset.baseUrl} ›${preSlug} ${pageTitle || this.slug.value}`;

		// Update the placeholders and innerHTML of the SEO snippet elements
		this.seoSnippetTitle.innerHTML = title;
		this.seoSnippetUrl.innerHTML = url;
		this.seoSnippetDescription.innerHTML = description;
	}

	/**
	 * Replace the search term in the given string with an emphasized version
	 * @param {string} haystack - The string to search in
	 * @param {string} needle - The search term to replace
	 * @returns {string} The updated string
	 */
	replace(haystack, needle) {
		// Create a copy of the haystack
		let haystackCopy = haystack;

		// Split the needle into words and replace each word in the haystack with an emphasized version
		needle.split(' ').forEach((word) => {
			if (word.length < 3) {
				return;
			}

			haystackCopy = haystackCopy.replace(new RegExp(word, 'gi'), (matched) => `<strong>${matched}</strong>`);
		});

		// Return the updated haystack
		return haystackCopy;
	}

	observeForNewInputs(mutation) {
		if (!mutation.addedNodes.length) {
			return;
		}

		mutation.addedNodes.forEach((node) => {
			if (node.nodeType !== Node.ELEMENT_NODE) {
				return;
			}

			if (node.matches(this.selector)) {
				this.initElement(node);
			}

			if (node.querySelector(this.selector)) {
				this.initElement(node.querySelector(this.selector));
			}
		});
	}
}


/**
 * Character counter object
 * This object contains methods for initializing and updating a character counter for the inputs.
 */
export class CharCounter {
	/**
	 * An instance of EventHandler
	 *
	 * @property {EventHandler} eventHandler
	 */
	eventHandler = window.eventHandler;
	/**
	 * The selector for the input elements
	 *
	 * @property {string} selector
	 */
	selector;

	/**
	 * Initialize the character counter
	 * This method sets up the initial state of the character counter and binds the necessary event handlers.
	 *
	 * @param {string} inputSelector - The selector for the input elements
	 */
	constructor(inputSelector) {
		this.selector = inputSelector;

		// Add event listeners for input events on elements with the data-charcounter-i attribute
		const elements = document.querySelectorAll(inputSelector);

		elements.forEach((element) => {
			this.eventHandler.add('input', this.update.bind(this), element);
			element.charCounter = document.querySelector('span[data-charcounter-name="' + element.dataset.charcounterName + '"]');
		});
	}

	/**
	 * Update the character counter
	 * This method updates the character counter based on the current state of the inputs.
	 * @param {Event} event - The event object
	 */
	update(event) {
		// Define the target of the event
		const target = event.target;

		// Update the text content of the target's charcounter target with the length of the target's value
		if (target.charCounter) {
			target.charCounter.textContent = target.value.length;
		}
	}
}