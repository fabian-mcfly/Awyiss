// noinspection JSUnusedGlobalSymbols

export default class MediaElementsController {
	/**
	 * The event handler instance.
	 * @type {EventHandler}
	 */
	eventHandler = window.eventHandler;


	constructor() {
		if (!document.body.classList.contains('MediaElementsController')) {
			return;
		}

		if (document.body.classList.contains('AddAction') || document.body.classList.contains('EditAction')) {
			this.initForm(document.querySelector('.MediaElements.Form'));
		}
	}


	/**
	 * Initialize the form
	 * @param {HTMLElement} form The form element
	 * @returns {void}
	 */
	initForm(form) {
		const assignableModelsFieldset = form.querySelector('.Fieldset-AssignableModels');

		window.eventHandler.add('input', function (event) {
			if (!event.target.matches('[name$="[scope]"]')) {
				return;
			}

			const scope = event.target.value;
			const entitySelect = event.target.closest('.FormInputType-ListItem').querySelector('[name$="[foreign_key]"]');

			if (!entitySelect) {
				return;
			}

			const oldSelectedValue = entitySelect.value;

			entitySelect.disabled = !scope || !assignableModelEntities[scope]
			entitySelect.required = scope && assignableModelEntities[scope];
			entitySelect.innerHTML = '<option value=""></option>';

			if (!scope || !assignableModelEntities[scope]) {
				return;
			}

			const options = Object.entries(assignableModelEntities[scope]);
			options.forEach(([key, value]) => {
				if (typeof value === 'object' && value !== null) {
					// Create optgroup for object values
					const optgroup = document.createElement('optgroup');
					optgroup.label = key;

					// Create options for each key-value pair in the object
					Object.entries(value).forEach(([subKey, subValue]) => {
						const option = document.createElement('option');
						option.value = subKey;
						option.text = subValue;
						optgroup.appendChild(option);
					});

					entitySelect.appendChild(optgroup);
				}
				else {
					// Create option for non-object values
					const option = document.createElement('option');
					option.value = key;
					option.text = value;
					entitySelect.appendChild(option);
				}
			});

			entitySelect.value = oldSelectedValue;
		}, assignableModelsFieldset);
	}
}


/**
 * Expose the class globally
 * @global
 * @type {MediaElementsController}
 */
window.MediaElementsController = MediaElementsController;
