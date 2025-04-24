// noinspection JSUnusedGlobalSymbols,NpmUsedModulesInstalled

import BatchTextArea from 'Form/BatchTextArea';
import SeoSnippet from 'SeoSnippet';

/**
 * Controller for the pages
 */
export default class PagesController {
	/**
	 * The batch text area instance.
	 * @type {BatchTextArea}
	 */
	batchTextArea;
	/**
	 * The event handler instance.
	 * @type {EventHandler}
	 */
	eventHandler = window.eventHandler;
	/**
	 * The SEO snippet instance.
	 * @type {SeoSnippet}
	 */
	seoSnippet;

	constructor() {
		this.initMeta();

		// Initialize the SEO snippet
		this.seoSnippet = new SeoSnippet('.SeoSnippet');

		const batchTextArea = document.getElementById('Page-Pages')
		if (batchTextArea) {
			new BatchTextArea(batchTextArea);

			const observer = window.observer;
			observer.addObserver(this.observeMutations.bind(this));
		}
	}

	/**
	 * Initialize the meta title input field
	 */
	initMeta() {
		// Find the title input field
		const titleInput = document.querySelector('input[name="title"]');
		const metaTitleInput = document.querySelector('input[name="meta_title"]');

		if (!titleInput || !metaTitleInput) {
			return;
		}

		// Add an event listener to the title input field
		this.eventHandler.add('input', () => {
			// Set the placeholder of the meta title input field to the value of the title input field
			// plus the separator and the appendix
			metaTitleInput.placeholder = titleInput.value + metaTitleInput.dataset.separator + metaTitleInput.dataset.appendix;

			// Trigger the input event on the metaTitleInput input field
			metaTitleInput.dispatchEvent(new Event('input', {bubbles: true}));
		}, titleInput);
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
			const selector = '#Page-Pages';
			if (node.nodeType === Node.ELEMENT_NODE) {
				if (node.matches(selector)) {
					new BatchTextArea(node);
				}

				const elements = node.querySelectorAll(selector);
				elements.forEach((element) => {
					new BatchTextArea(element);
				});
			}
		});
	}
}

/**
 * Expose the class globally
 * @global
 * @type {PagesController}
 */
window.PagesController = PagesController;
