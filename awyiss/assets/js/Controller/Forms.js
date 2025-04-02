export default class FormsController {
	/**
	 * The event handler instance.
	 * @type {EventHandler}
	 */
	eventHandler = window.eventHandler;

	constructor() {
		if (document.body.classList.contains('AddAction') || document.body.classList.contains('EditAction')) {
			this.initForm();
		}
	}

	/**
	 * Initialize the logic for the form.
	 *
	 * @returns {void}
	 */
	initForm() {
		const conditionalRecipients = document.querySelector('.FormInputName-FormConditionalRecipients');
		if (conditionalRecipients) {
			window.eventHandler.add('change', this.handleConditionalRecipientTypeChange, conditionalRecipients);
		}
	}

	/**
	 * Handle the change event of the conditional recipient type select.
	 *
	 * @param {Event} event - The event object.
	 * @returns {void}
	 */
	handleConditionalRecipientTypeChange(event) {
		if (!event.target.matches('select[name^="form_conditional_recipients"][name$="[type]"]')) {
			return;
		}

		const row = event.target.closest('.FormInputType-ListItem');
		const fieldSelect = row.querySelector('select[name^="form_conditional_recipients"][name$="[field]"]');

		// Remove all options
		fieldSelect.innerHTML = '';

		if (event.target.value === 'element_identifier') {
			const template = document.getElementById('FormElementOptions');
			const options = template.content.querySelectorAll('select > *');
			options.forEach(option => {
				fieldSelect.appendChild(option.cloneNode(true));
			});
		}
		else if (event.target.value === 'current_page') {
			const template = document.getElementById('CurrentPageOptions');
			const options = template.content.querySelectorAll('select > *');
			options.forEach(option => {
				fieldSelect.appendChild(option.cloneNode(true));
			});
		}

		fieldSelect.disabled = event.target.value === '';
		fieldSelect.required = event.target.value !== '';
	}
}

/**
 * Expose the class globally
 * @global
 * @type {FormsController}
 */
window.FormsController = FormsController;