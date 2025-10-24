// noinspection JSUnusedGlobalSymbols

import AssignableTemplateElements from 'AssignableTemplateElements';

export default class WidgetTemplatesController {
	constructor() {
		if (!document.body.classList.contains('WidgetTemplatesController')) {
			return;
		}

		if (document.body.classList.contains('AddAction') || document.body.classList.contains('EditAction')) {
			this.initForm(document.querySelector('.WidgetTemplates.Form'));
		}

	}

	/**
	 * Initialize the form
	 * @param {HTMLElement} form The form element
	 */
	initForm(form) {
		const observer = window.observer;
		observer.addObserver(this.observeMutations.bind(this));

		new AssignableTemplateElements('.WidgetElements-List', form);
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

			const selector = '.WidgetTemplates.Form';
			if (node.matches(selector)) {
				new AssignableTemplateElements('.WidgetElements-List', node);
			}

			const children = node.querySelectorAll(selector);
			children.forEach((child) => {
				new AssignableTemplateElements('.WidgetElements-List', child);
			});
		});
	}
}

/**
 * Expose the class globally
 * @global
 * @type {WidgetTemplatesController}
 */
window.WidgetTemplatesController = WidgetTemplatesController;
