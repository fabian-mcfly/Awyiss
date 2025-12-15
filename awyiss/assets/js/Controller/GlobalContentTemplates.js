// noinspection JSUnusedGlobalSymbols

import AssignableTemplateElements from 'AssignableTemplateElements';

export default class GlobalContentTemplatesController {
	/**
	 * The observer instance.
	 * @type {Observer}
	 */
	observer = window.observer;


	constructor() {
		if (!document.body.classList.contains('GlobalContentTemplatesController')) {
			return;
		}

		if (document.body.classList.contains('AddAction') || document.body.classList.contains('EditAction')) {
			this.initForm(document.querySelector('.GlobalContentTemplates.Form'));
		}

	}

	/**
	 * Initialize the form
	 * @param {HTMLElement} form The form element
	 */
	initForm(form) {
		this.observer.addObserver(this.observeMutations.bind(this), form);

		new AssignableTemplateElements('.GlobalContentElements-List', form);
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

			const selector = '.GlobalContentTemplates.Form';
			if (node.matches(selector)) {
				new AssignableTemplateElements('.GlobalContentElements-List', node);
			}

			const children = node.querySelectorAll(selector);
			children.forEach((child) => {
				new AssignableTemplateElements('.GlobalContentElements-List', child);
			});
		});
	}
}

/**
 * Expose the class globally
 * @global
 * @type {GlobalContentTemplatesController}
 */
window.GlobalContentTemplatesController = GlobalContentTemplatesController;
