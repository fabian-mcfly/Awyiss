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
	 * The observer instance.
	 * @type {Observer}
	 */
	observer = window.observer;
	/**
	 * The SEO snippet instance.
	 * @type {SeoSnippet}
	 */
	seoSnippet;

	constructor() {
		if (
			document.body.classList.contains('AddAction') ||
			document.body.classList.contains('AddBatchAction') ||
			document.body.classList.contains('EditAction')
		) {
			const form = document.querySelector('.Pages.Form')

			this.initMeta(form);

			// Initialize the SEO snippet
			this.seoSnippet = new SeoSnippet('.SeoSnippet');

			const batchTextArea = form.querySelector('.FormInputName-Pages > textarea')
			if (batchTextArea) {
				new BatchTextArea(batchTextArea);
			}

			this.observer.addObserver(this.observeMutations.bind(this), form);
		}
	}

	/**
	 * Initialize the meta title input field
	 *
	 * @param {HTMLElement} form The form element
	 */
	initMeta(form) {
		// Find the title input field
		const titleInput = form.querySelector('input[name="title"]');
		const metaTitleInput = form.querySelector('input[name="meta_title"]');

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
