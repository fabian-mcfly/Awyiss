// noinspection JSUnusedGlobalSymbols

import AssignableTemplateElements from 'AssignableTemplateElements';

export default class WidgetTemplatesController {
	constructor() {
		if (!document.body.classList.contains('WidgetTemplatesController')) {
			return;
		}

		if (document.body.classList.contains('AddAction') || document.body.classList.contains('EditAction')) {
			this.initForm();
		}

	}

	initForm() {
		new AssignableTemplateElements('.WidgetElements-List');
	}
}

/**
 * Expose the class globally
 * @global
 * @type {WidgetTemplatesController}
 */
window.WidgetTemplatesController = WidgetTemplatesController;
