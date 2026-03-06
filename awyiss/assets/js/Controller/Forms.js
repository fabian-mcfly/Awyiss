export default class FormsController {
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

	constructor() {
		if (!document.body.classList.contains('FormsController')) {
			return;
		}

		if (document.body.classList.contains('AddAction') || document.body.classList.contains('EditAction')) {
			this.initForm(document.querySelector('.Forms.Form'));
		}
	}

	/**
	 * Initialize the logic for the form.
	 * @param {HTMLElement} form The form element
	 * @returns {void}
	 */
	initForm(form) {
		const conditionalRecipients = form.querySelector('.FormInputName-FormConditionalRecipients');
		if (conditionalRecipients) {
			window.eventHandler.add('change', this.handleConditionalRecipientTypeChange.bind(this, form), conditionalRecipients);
		}

		this.observer.addObserver(this.observeMutations.bind(this), form);
	}

	/**
	 * Handle the change event of the conditional recipient type select.
	 *
	 * @param {HTMLElement} form - The form element.
	 * @param {Event} event - The event object.
	 * @returns {void}
	 */
	handleConditionalRecipientTypeChange(form, event) {
		if (!event.target.matches('select[name^="conditionalRecipients"][name$="[type]"]')) {
			return;
		}

		const row = event.target.closest('.FormInputType-ListItem');
		const fieldSelect = row.querySelector('select[name^="conditionalRecipients"][name$="[field]"]');

		// Remove all options
		fieldSelect.innerHTML = '';

		if (event.target.value === 'elementIdentifier') {
			const template = form.querySelector('#FormElementOptions');
			const options = template.content.querySelectorAll('select > *');
			options.forEach(option => {
				fieldSelect.appendChild(option.cloneNode(true));
			});
		}
		else if (event.target.value === 'currentPage') {
			const template = form.querySelector('#CurrentPageOptions');
			const options = template.content.querySelectorAll('select > *');
			options.forEach(option => {
				fieldSelect.appendChild(option.cloneNode(true));
			});
		}

		fieldSelect.disabled = event.target.value === '';
		fieldSelect.required = event.target.value !== '';
	}

	/**
	 * Observe mutations.
	 * @param {MutationRecord} mutation
	 */
	observeMutations(mutation) {
		mutation.addedNodes.forEach(node => {
			if (!(node instanceof HTMLElement)) {
				return;
			}

			if (node.matches('.FormInputName-FormConditionalRecipients')) {
				window.eventHandler.add('change', this.handleConditionalRecipientTypeChange.bind(this, form), conditionalRecipients);
			}

			const conditionalRecipients = node.querySelector('.FormInputName-FormConditionalRecipients');
			if (conditionalRecipients) {
				window.eventHandler.add('change', this.handleConditionalRecipientTypeChange.bind(this, form), conditionalRecipients);
			}
		})
	}
}

/**
 * Expose the class globally
 * @global
 * @type {FormsController}
 */
window.FormsController = FormsController;