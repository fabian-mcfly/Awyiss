// noinspection JSUnusedGlobalSymbols

import AssignableTemplateElements from 'AssignableTemplateElements';

export default class ContentTemplatesController {
	constructor() {
		if (document.body.classList.contains('AddAction') || document.body.classList.contains('EditAction')) {
			this.initForm();
		}

	}

	initForm() {
		new AssignableTemplateElements('.ContentElements-List');
	}
}

/**
 * Expose the class globally
 * @global
 * @type {ContentTemplatesController}
 */
window.ContentTemplatesController = ContentTemplatesController;
