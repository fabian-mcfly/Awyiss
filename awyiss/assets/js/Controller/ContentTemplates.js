// noinspection JSUnusedGlobalSymbols

import AssignableTemplateElements from 'AssignableTemplateElements';

export default class ContentTemplatesController {
	/**
	 * The observer instance.
	 * @type {Observer}
	 */
	observer = window.observer;

	constructor() {
		if (!document.body.classList.contains('ContentTemplatesController')) {
			return;
		}

		if (document.body.classList.contains('AddAction') || document.body.classList.contains('EditAction')) {
			this.initForm(document.querySelector('.ContentTemplates.Form'));
		}
	}

	/**
	 * Initialize the form
	 * @param {HTMLElement} form The form element
	 */
	initForm(form) {
		this.observer.addObserver(this.observeMutations.bind(this), form);

		new AssignableTemplateElements('.ContentElements-List', form);
	}

	/**
	 * Observe mutations in the DOM and initialize the form if necessary.
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

			const selector = '.Fieldset-ContentElements';
			if (node.matches(selector)) {
				new AssignableTemplateElements('.ContentElements-List', node);
			}

			const children = node.querySelectorAll(selector);
			children.forEach((child) => {
				new AssignableTemplateElements('.ContentElements-List', child);
			});
		});
	}
}

/**
 * Expose the class globally
 * @global
 * @type {ContentTemplatesController}
 */
window.ContentTemplatesController = ContentTemplatesController;
